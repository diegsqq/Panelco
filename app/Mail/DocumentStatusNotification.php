<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the uploader when document processing finishes. */
class DocumentStatusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public bool $failed,
    ) {
    }

    public function envelope(): Envelope
    {
        $title = $this->document->title ?? $this->document->original_filename;

        return new Envelope(
            subject: ($this->failed ? 'Processing failed: ' : 'Ready: ') . $title,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.document-status');
    }
}
