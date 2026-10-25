<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $senderName) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SMTP Test Email',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test-smtp',
            with: [
                'senderName' => $this->senderName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
