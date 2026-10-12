<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventLeadCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $eventName,
        public string $praxisName,
        public string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->email],
            subject: "Ihr Besuch bei SONORING DORMED – {$this->eventName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-lead.customer',
        );
    }
}
