<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $purpose,
        public string $code,
        public int $minutes = 30,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PANELCO III SmartDocs — Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.profile-code');
    }
}
