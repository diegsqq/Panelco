<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentApprovalNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public string $reviewerName,
        public bool $approved,
        public ?string $reviewNote,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->approved ? 'Document approved: ' : 'Document rejected: ')
                .($this->document->title ?? $this->document->original_filename),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.document-approval');
    }
}
