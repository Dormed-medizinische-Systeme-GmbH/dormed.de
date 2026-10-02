<?php

namespace App\Jobs;

use App\Mail\ContactFormCompanyMail;
use App\Mail\ContactFormCustomerMail;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends both contact-form mails and records the outcome in mail.log.
 * Dispatched independently of CreateInquiryInCas - has no knowledge of CAS
 * at all, so nothing here waits on (or reports back to) the CRM record.
 *
 * Any mail failure lets the job retry as a whole (see retryUntil()) rather
 * than treating a single failed send as final - the accepted trade-off is
 * that a retry may re-send the company mail a second time if it already
 * went out but the customer mail then failed; a duplicate internal
 * notification is a much smaller problem than a lost inquiry.
 */
class SendInquiryMails implements ShouldQueue
{
    use Queueable;

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
        Mail::send(new ContactFormCompanyMail(
            name: $this->name,
            email: $this->email,
            telefon: $this->telefon,
            plz: $this->plz,
            nachricht: $this->nachricht,
            praxis: $this->praxis,
            fachgebiet: $this->fachgebiet,
            wantsCallback: $this->wantsCallback,
            rueckrufDatum: $this->rueckrufDatum,
            spamScore: $this->spamScore(),
        ));

        Mail::send(new ContactFormCustomerMail(name: $this->name, email: $this->email));

        $this->logSubmission(mailSucceeded: true);
    }

    /**
     * Interim spam score (0-1) for the company mail, until the contact form
     * moves to the new monolith and its SDK. Only the free-text message is
     * sent - never name, e-mail or other personal fields. Any failure
     * (timeout, HTTP error, unexpected payload) yields null so the mails
     * still go out unchanged.
     */
    private function spamScore(): ?float
    {
        if (blank($this->nachricht)) {
            return null;
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('services.decision_model.url'), '/'))
                ->withToken((string) config('services.decision_model.key'))
                ->acceptJson()
                ->asJson()
                ->timeout(8)
                ->post('/v1/systemone', [
                    'state' => $this->nachricht,
                    'questions' => [
                        'istSpam' => [
                            'type' => 'noul',
                            'instructions' => 'Dormed ist ein deutsches Unternehmen für Medizintechnik und verkauft ausschließlich innerhalb Deutschlands. Über dieses Kontaktformular gehen normalerweise echte Anfragen von Kliniken und Praxen zu Produkten, Wartung oder Service ein. Ist die folgende Nachricht für uns Spam — dazu zählt auch unaufgeforderte Kaltakquise wie Vertriebs-, Marketing- oder SEO-Angebote fremder Anbieter, nicht nur klassischer Werbe- oder Betrugsspam?',
                        ],
                    ],
                ]);

            $score = $response->successful() ? $response->json('answers.istSpam.noul') : null;
        } catch (Throwable $exception) {
            Log::warning('Spam score request failed.', ['exception' => $exception->getMessage()]);

            return null;
        }

        if (! is_numeric($score) || $score < 0 || $score > 1) {
            // The error body usually says why (e.g. "invalid API key"); the
            // effective URL shows whether a redirect changed the host.
            Log::warning('Spam score unavailable.', [
                'status' => $response->status(),
                'url' => (string) $response->effectiveUri(),
                'body' => Str::limit($response->body(), 300),
            ]);

            return null;
        }

        return (float) $score;
    }

    public function failed(Throwable $exception): void
    {
        $this->logSubmission(mailSucceeded: false);

        Log::error('SendInquiryMails permanently failed.', [
            'name' => $this->name,
            'email' => $this->email,
            'exception' => $exception->getMessage(),
        ]);
    }

    private function logSubmission(bool $mailSucceeded): void
    {
        $timestamp = now()->format('d.m.Y H:i:s');
        $status = $mailSucceeded ? '✓' : '✗';

        Log::channel('mail')->info(
            "[{$timestamp}] Anfrage von {$this->name} | Mailversand an {$this->email} {$status}"
        );
    }
}
