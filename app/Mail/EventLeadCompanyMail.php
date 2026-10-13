<?php

namespace App\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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

    /**
     * The lead as a PDF for archiving or forwarding. A failed PDF render
     * must never swallow the lead itself, so the mail goes out without the
     * attachment and the error is logged instead.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        try {
            $receivedAt = now();
            $pdf = Pdf::loadView('mail.event-lead.pdf', [...$this->viewData(), 'receivedAt' => $receivedAt])
                ->setPaper('a4')
                ->output();
        } catch (Throwable $exception) {
            Log::error('EventLeadCompanyMail: PDF attachment could not be created.', [
                'event' => $this->eventName,
                'name' => $this->praxisName,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }

        $filename = 'Lead_'.Str::slug($this->eventName).'_'.Str::slug($this->praxisName).'_'.$receivedAt->format('Y-m-d').'.pdf';

        return [
            Attachment::fromData(fn () => $pdf, $filename)->withMime('application/pdf'),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function viewData(): array
    {
        return [
            'eventName' => $this->eventName,
            'praxisName' => $this->praxisName,
            'strasse' => $this->strasse,
            'plz' => $this->plz,
            'ort' => $this->ort,
            'email' => $this->email,
            'telefon' => $this->telefon,
            'interesse' => $this->interesse,
            'budget' => $this->budget,
            'nachricht' => $this->nachricht,
        ];
    }
}
