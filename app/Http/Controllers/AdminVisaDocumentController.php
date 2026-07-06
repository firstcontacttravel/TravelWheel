<?php

namespace App\Http\Controllers;

use App\Models\VisaAdditionalDocumentRequest;
use App\Models\VisaApplicationDocument;
use App\Models\VisaIssuedDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVisaDocumentController extends Controller
{
    public function application(VisaApplicationDocument $document): StreamedResponse
    {
        $this->authorizeStaff();

        return $this->view($document->disk, $document->path, $document->original_name);
    }

    public function requested(VisaAdditionalDocumentRequest $documentRequest): StreamedResponse
    {
        $this->authorizeStaff();
        abort_unless($documentRequest->path, 404);

        return $this->view($documentRequest->disk ?: 'local', $documentRequest->path, $documentRequest->original_name ?: 'document');
    }

    public function issued(VisaIssuedDocument $document): StreamedResponse
    {
        $this->authorizeStaff();

        return $this->view($document->disk, $document->path, $document->original_name);
    }

    private function authorizeStaff(): void
    {
        $user = auth()->user();
        abort_unless($user?->canViewVisaOperations(), 403);
    }

    private function view(string $disk, string $path, string $name): StreamedResponse
    {
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, $name, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
