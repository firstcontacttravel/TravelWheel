<?php

namespace App\Mail;

use App\Models\VisaApplication;
use App\Models\VisaApplicationDocument;
use App\Models\VisaAdditionalDocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class VisaApplicationVendorMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VisaApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Visa application {$this->application->reference}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.visa-application-vendor');
    }

    public function attachments(): array
    {
        $documents = $this->application->documents()->get()
            ->map(fn (VisaApplicationDocument $document): ?Attachment => $this->attachment($document->disk, $document->path, $document->original_name, $document->mime_type, 'application'));

        $additional = $this->application->additionalDocumentRequests()
            ->whereNotNull('path')
            ->get()
            ->map(fn (VisaAdditionalDocumentRequest $document): ?Attachment => $this->attachment($document->disk, $document->path, $document->original_name, $document->mime_type, 'additional'));

        return $documents->concat($additional)->filter()->values()->all();
    }

    private function attachment(?string $disk, ?string $path, ?string $name, ?string $mime, string $prefix): ?Attachment
    {
        $disk ??= 'local';

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $attachment = Attachment::fromStorageDisk($disk, $path)
            ->as($prefix.'-'.basename($name ?: $path));

        return $mime ? $attachment->withMime($mime) : $attachment;
    }
}
