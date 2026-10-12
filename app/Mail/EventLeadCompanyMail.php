<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventLeadCompanyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $eventName,
        public string $praxisName,
        public ?string $strasse,
        public ?string $plz,
        public ?string $ort,
        public ?string $email,
        public ?string $telefon,
        public ?string $interesse,
        public ?string $budget,
        public ?string $nachricht,
    ) {}

    /**
     * Same recipient as the contact form (MAIL_FROM_ADDRESS sends to
     * itself); routing to the right people happens via mailbox rules on
     * the subject. Reply-To is only set when the lead left an e-mail.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address((string) config('mail.from.address'), (string) config('mail.from.name'))],
            subject: "Neuer Lead {$this->eventName}: {$this->praxisName}",
            replyTo: filled($this->email) ? [new Address($this->email, $this->praxisName)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-lead.company',
        );
    }
}
