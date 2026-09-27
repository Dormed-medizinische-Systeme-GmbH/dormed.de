<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormCompanyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public ?string $telefon,
        public string $plz,
        public ?string $nachricht,
        public ?string $praxis,
        public ?string $fachgebiet,
        public bool $wantsCallback,
        public ?string $rueckrufDatum,
        public ?float $spamScore = null,
    ) {}

    /**
     * Recipient is the company's own mailbox (MAIL_FROM_ADDRESS sends to
     * itself, see AGENTS.md). Reply-To is the customer's own address, so a
     * staff member can answer directly from their inbox without looking the
     * address up separately.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address((string) config('mail.from.address'), (string) config('mail.from.name'))],
            subject: 'Neue Anfrage über das Kontaktformular',
            replyTo: [new Address($this->email, $this->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-form.company',
        );
    }
}
