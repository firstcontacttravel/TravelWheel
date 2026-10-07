<?php

namespace App\Http\Controllers;

use App\Jobs\CloseLinearIssueForEscalation;
use App\Models\Escalation;
use App\Models\User;
use App\Workflow\EscalationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Linear tells us when an escalation's issue is completed, cancelled or
 * commented on.
 *
 * Every request is checked against the signing secret Linear gave when the
 * webhook was created, and must be recent; anything else is refused. Each
 * delivery is applied once, however many times Linear retries it. Issues
 * that are not escalations, and changes that do not close one, are
 * acknowledged and ignored.
 */
class LinearWebhookController extends Controller
{
    /** Linear's own advice: refuse anything signed more than a minute ago. */
    private const MAX_AGE_SECONDS = 60;

    public function __invoke(Request $request, EscalationService $escalations): JsonResponse
    {
        $secret = (string) config('services.linear.webhook_secret');
        if ($secret === '') {
            abort(404);
        }

        $signature = (string) $request->header('Linear-Signature');
        if ($signature === '' || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
            abort(401, 'Bad signature.');
        }

        $payload = $request->json()->all();
        $sentAt = (int) ($payload['webhookTimestamp'] ?? 0);
        if (abs(now()->getTimestampMs() - $sentAt) > self::MAX_AGE_SECONDS * 1000) {
            abort(401, 'Stale delivery.');
        }

        $type = (string) ($payload['type'] ?? '');
        $action = (string) ($payload['action'] ?? '');
        $delivery = (string) ($request->header('Linear-Delivery') ?: ($payload['webhookId'] ?? '').':'.$sentAt);

        try {
            DB::table('linear_webhook_receipts')->insert([
                'delivery_id' => $delivery,
                'type' => substr($type, 0, 40),
                'action' => substr($action, 0, 20),
                'created_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['status' => 'duplicate']);
        }

        $data = (array) ($payload['data'] ?? []);
        $actor = (array) ($payload['actor'] ?? []);

        $handled = match (true) {
            $type === 'Issue' && $action === 'update' => $this->issueUpdated($data, $actor, $escalations),
            $type === 'Comment' && $action === 'create' => $this->commentCreated($data, $actor, $escalations),
            default => false,
        };

        return response()->json(['status' => $handled ? 'applied' : 'ignored']);
    }

    private function issueUpdated(array $data, array $actor, EscalationService $escalations): bool
    {
        $stateType = $data['state']['type'] ?? null;
        if (! in_array($stateType, ['completed', 'canceled'], true)) {
            return false;
        }

        $escalation = $this->escalation($data['id'] ?? null);
        if (! $escalation?->isActive()) {
            return false;
        }

        $escalations->closeFromLinear($escalation, $stateType, $this->staff($actor), $this->name($actor));

        return true;
    }

    private function commentCreated(array $data, array $actor, EscalationService $escalations): bool
    {
        $body = (string) ($data['body'] ?? '');

        // Our own comment, posted when the escalation closed in the admin.
        if (str_contains($body, CloseLinearIssueForEscalation::SIGNATURE)) {
            return false;
        }

        $escalation = $this->escalation($data['issueId'] ?? ($data['issue']['id'] ?? null));
        if (! $escalation) {
            return false;
        }

        $author = ($data['user'] ?? null) ?: $actor;
        $escalations->commentFromLinear($escalation, $this->staff($author), $this->name($author), $body);

        return true;
    }

    private function escalation(?string $issueId): ?Escalation
    {
        return $issueId ? Escalation::query()->with('workItem')->where('linear_issue_id', $issueId)->first() : null;
    }

    /** The member of staff with the same email, when Linear sends one. */
    private function staff(array $person): ?User
    {
        $email = $person['email'] ?? null;

        return $email ? User::query()->where('email', $email)->first() : null;
    }

    private function name(array $person): string
    {
        return (string) ($person['name'] ?? 'someone in Linear');
    }
}
