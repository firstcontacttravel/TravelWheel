<?php

namespace App\Services\Linear;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The few Linear GraphQL calls the workflow needs. Team, label, state and
 * user ids are looked up by name and cached, so nothing in config holds a
 * Linear UUID that would go stale if a team were recreated.
 */
class LinearClient
{
    private const ENDPOINT = 'https://api.linear.app/graphql';

    private const CACHE_SECONDS = 3600;

    public function isConfigured(): bool
    {
        return filled(config('services.linear.api_key'));
    }

    /**
     * @return array{id: string, identifier: string, url: string}
     */
    public function createIssue(string $teamKey, string $title, string $description, int $priority, array $labelIds = [], ?string $assigneeId = null): array
    {
        $team = $this->team($teamKey);

        $input = array_filter([
            'teamId' => $team['id'],
            // Straight into the team's first "unstarted" state (Todo). The
            // default is Backlog, where an escalation waits unseen.
            'stateId' => collect($team['states'])->firstWhere('type', 'unstarted')['id'] ?? null,
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'labelIds' => $labelIds ?: null,
            'assigneeId' => $assigneeId,
        ], fn ($value) => $value !== null);

        $data = $this->query(
            'mutation($input: IssueCreateInput!) { issueCreate(input: $input) { success issue { id identifier url } } }',
            ['input' => $input],
        );

        return $data['issueCreate']['issue'] ?? throw new RuntimeException('Linear did not return the new issue.');
    }

    /** Moves the issue to the team's first state of this type: completed or canceled. */
    public function moveIssueTo(string $issueId, string $teamKey, string $stateType): void
    {
        $state = collect($this->team($teamKey)['states'])->firstWhere('type', $stateType)
            ?? throw new RuntimeException("Linear team {$teamKey} has no {$stateType} state.");

        $this->query(
            'mutation($id: String!, $stateId: String!) { issueUpdate(id: $id, input: { stateId: $stateId }) { success } }',
            ['id' => $issueId, 'stateId' => $state['id']],
        );
    }

    public function comment(string $issueId, string $body): void
    {
        $this->query(
            'mutation($input: CommentCreateInput!) { commentCreate(input: $input) { success } }',
            ['input' => ['issueId' => $issueId, 'body' => $body]],
        );
    }

    public function archive(string $issueId): void
    {
        $this->query('mutation($id: String!) { issueArchive(id: $id) { success } }', ['id' => $issueId]);
    }

    /** A workspace label by name, created the first time it is needed. */
    public function labelId(string $name): string
    {
        return Cache::remember('linear.label.'.md5(strtolower($name)), self::CACHE_SECONDS, function () use ($name): string {
            $found = $this->query(
                'query($name: String!) { issueLabels(filter: { name: { eqIgnoreCase: $name } }) { nodes { id team { id } } } }',
                ['name' => $name],
            )['issueLabels']['nodes'] ?? [];

            $workspaceLabel = collect($found)->first(fn (array $label) => $label['team'] === null);
            if ($workspaceLabel) {
                return $workspaceLabel['id'];
            }

            return $this->query(
                'mutation($input: IssueLabelCreateInput!) { issueLabelCreate(input: $input) { success issueLabel { id } } }',
                ['input' => ['name' => $name]],
            )['issueLabelCreate']['issueLabel']['id'] ?? throw new RuntimeException("Linear did not create the label {$name}.");
        });
    }

    /** The Linear user with this email, if they have a seat. */
    public function userIdByEmail(string $email): ?string
    {
        return Cache::remember('linear.user.'.md5(strtolower($email)), self::CACHE_SECONDS, fn (): string => (string) ($this->query(
            'query($email: String!) { users(filter: { email: { eqIgnoreCase: $email } }) { nodes { id } } }',
            ['email' => $email],
        )['users']['nodes'][0]['id'] ?? '')) ?: null;
    }

    /**
     * Active (not archived) issues across the workspace, counted up to 250.
     *
     * @return array{count: int, more: bool}
     */
    public function activeIssueCount(): array
    {
        $data = $this->query('{ issues(first: 250) { nodes { id } pageInfo { hasNextPage } } }');

        return [
            'count' => count($data['issues']['nodes'] ?? []),
            'more' => (bool) ($data['issues']['pageInfo']['hasNextPage'] ?? false),
        ];
    }

    /** @return array{name: string, organization: string} */
    public function whoAmI(): array
    {
        $data = $this->query('{ viewer { name } organization { name } }');

        return ['name' => (string) $data['viewer']['name'], 'organization' => (string) $data['organization']['name']];
    }

    /** @return array{id: string, name: string, states: list<array{id: string, type: string}>} */
    public function team(string $key): array
    {
        return Cache::remember('linear.team.'.strtolower($key), self::CACHE_SECONDS, function () use ($key): array {
            $team = $this->query(
                'query($key: String!) { teams(filter: { key: { eq: $key } }) { nodes { id name states { nodes { id type position } } } } }',
                ['key' => $key],
            )['teams']['nodes'][0] ?? throw new RuntimeException("No Linear team with key {$key}.");

            return [
                'id' => $team['id'],
                'name' => $team['name'],
                'states' => collect($team['states']['nodes'])->sortBy('position')->map(fn (array $state) => ['id' => $state['id'], 'type' => $state['type']])->values()->all(),
            ];
        });
    }

    public function query(string $query, array $variables = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('LINEAR_API_KEY is not set.');
        }

        $response = Http::withHeaders(['Authorization' => (string) config('services.linear.api_key')])
            ->acceptJson()
            ->timeout(20)
            ->post(self::ENDPOINT, ['query' => $query, 'variables' => (object) $variables]);

        $errors = $response->json('errors');
        if ($response->failed() || $errors) {
            $message = $errors[0]['message'] ?? "HTTP {$response->status()}";

            throw new RuntimeException("Linear: {$message}");
        }

        return (array) $response->json('data');
    }
}
