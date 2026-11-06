<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OperationalHealthAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, int>  $issues
     */
    public function __construct(public array $issues) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'SmartDocs operational issues require attention');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.operational-health-alert');
    }
}
