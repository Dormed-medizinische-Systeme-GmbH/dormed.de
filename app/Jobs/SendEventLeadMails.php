<?php

namespace App\Jobs;

use App\Mail\EventLeadCompanyMail;
use App\Mail\EventLeadCustomerMail;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the mails for an event lead and records the outcome in mail.log.
 * Mirrors SendInquiryMails (same retry trade-off), but without spam score
 * and without CAS. The customer confirmation only goes out when the lead
 * left an e-mail address - a phone number alone is enough for the form.
 */
class SendEventLeadMails implements ShouldQueue
{
    use Queueable;

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

    public function retryUntil(): DateTime
    {
        return now()->addHours(3);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 120, 300, 600];
    }

    public function handle(): void
    {
        Mail::send(new EventLeadCompanyMail(
            eventName: $this->eventName,
            praxisName: $this->praxisName,
            strasse: $this->strasse,
            plz: $this->plz,
            ort: $this->ort,
            email: $this->email,
            telefon: $this->telefon,
            interesse: $this->interesse,
            budget: $this->budget,
            nachricht: $this->nachricht,
        ));

        if (filled($this->email)) {
            Mail::send(new EventLeadCustomerMail(
                eventName: $this->eventName,
                praxisName: $this->praxisName,
                email: $this->email,
            ));
        }

        $this->logSubmission(mailSucceeded: true);
    }

    public function failed(Throwable $exception): void
    {
        $this->logSubmission(mailSucceeded: false);

        Log::error('SendEventLeadMails permanently failed.', [
            'event' => $this->eventName,
            'name' => $this->praxisName,
            'email' => $this->email,
            'exception' => $exception->getMessage(),
        ]);
    }

    private function logSubmission(bool $mailSucceeded): void
    {
        $timestamp = now()->format('d.m.Y H:i:s');
        $status = $mailSucceeded ? '✓' : '✗';
        $recipient = $this->email ?: 'keine E-Mail angegeben';

        Log::channel('mail')->info(
            "[{$timestamp}] Lead ({$this->eventName}) von {$this->praxisName} | Mailversand an {$recipient} {$status}"
        );
    }
}
