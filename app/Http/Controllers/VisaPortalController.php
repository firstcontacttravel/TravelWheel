<?php

namespace App\Http\Controllers;

use App\Models\VisaAdditionalDocumentRequest;
use App\Models\VisaApplication;
use App\Models\VisaApplicationDocument;
use App\Models\VisaIssuedDocument;
use App\Models\VisaNotificationEvent;
use App\Models\VisaPayment;
use App\Services\VisaCommunicationService;
use App\Services\VisaPortalAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisaPortalController extends Controller
{
    public function entry(): View
    {
        return view('visa.portal-access');
    }

    public function requestCode(Request $request, VisaPortalAccessService $access): RedirectResponse
    {
        $validated = $request->validate(['reference' => ['required', 'string'], 'email' => ['required', 'email']]);
        $application = VisaApplication::query()->where('reference', strtoupper($validated['reference']))->first();
        if (! $application) {
            return back()->withErrors(['reference' => 'The application reference and email address do not match.'])->withInput();
        }
        $access->requestCode($application, $validated['email'], $request->ip());
        session()->put('visa_portal_pending_reference', $application->reference);

        return redirect()->route('visa.portal.verify.form')->with('status', 'We sent a six-digit code to your application email.');
    }

    public function verifyForm(): View
    {
        abort_unless(session('visa_portal_pending_reference'), 403);

        return view('visa.portal-verify', ['reference' => session('visa_portal_pending_reference')]);
    }

    public function verify(Request $request, VisaPortalAccessService $access): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $application = VisaApplication::query()->where('reference', session('visa_portal_pending_reference'))->firstOrFail();
        if (! $access->verify($application, $validated['code'])) {
            return back()->withErrors(['code' => 'That code is invalid or expired.']);
        }
        session()->forget('visa_portal_pending_reference');

        return redirect()->route('visa.portal.show', $application);
    }

    public function show(VisaApplication $application, VisaPortalAccessService $access): View
    {
        abort_unless($access->authorize($application), 403);
        $application->load(['product', 'processingOption', 'travelers', 'statusHistory' => fn ($q) => $q->latest(), 'payments' => fn ($q) => $q->latest(), 'quotes.items', 'additionalDocumentRequests.traveler', 'additionalDocumentRequests.requirement', 'issuedDocuments', 'notificationEvents' => fn ($q) => $q->latest()]);

        return view('visa.portal', compact('application'));
    }

    public function upload(Request $request, VisaApplication $application, VisaAdditionalDocumentRequest $documentRequest, VisaPortalAccessService $access): RedirectResponse
    {
        abort_unless($access->authorize($application) && $documentRequest->visa_application_id === $application->id && in_array($documentRequest->status, ['open', 'replacement_requested'], true), 403);
        $max = $documentRequest->requirement?->maximum_file_size_kb ?: 5120;
        $validated = $request->validate(['document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$max]]);
        $file = $validated['document'];
        $name = $file->getClientOriginalName();
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $size = $file->getSize();
        $path = $file->store("visa-applications/{$application->reference}/additional-documents", 'local');
        $documentRequest->update(['disk' => 'local', 'path' => $path, 'original_name' => $name, 'mime_type' => $mime, 'size' => $size, 'status' => 'submitted', 'submitted_at' => now()]);

        return back()->with('status', 'Document uploaded securely.');
    }

    public function downloadDocument(VisaApplication $application, VisaApplicationDocument $document, VisaPortalAccessService $access): StreamedResponse
    {
        abort_unless($access->authorize($application) && $document->visa_application_id === $application->id && in_array($document->status, ['approved', 'issued'], true), 403);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function downloadIssuedDocument(VisaApplication $application, VisaIssuedDocument $document, VisaPortalAccessService $access): StreamedResponse
    {
        abort_unless($access->authorize($application) && $document->visa_application_id === $application->id && $document->superseded_at === null, 403);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function receipt(VisaApplication $application, VisaPayment $payment, VisaPortalAccessService $access): View
    {
        abort_unless($access->authorize($application) && $payment->visa_application_id === $application->id && $payment->status === 'paid', 403);

        return view('visa.receipt', ['application' => $application->load('product'), 'payment' => $payment->load('quote.items')]);
    }

    public function resend(VisaApplication $application, VisaNotificationEvent $notification, VisaPortalAccessService $access, VisaCommunicationService $communications): RedirectResponse
    {
        abort_unless($access->authorize($application) && $notification->visa_application_id === $application->id, 403);
        $communications->resend($notification);

        return back()->with('status', 'Notification queued for delivery.');
    }
}
