<?php

use App\Jobs\CreateInquiryInCas;
use App\Jobs\SendInquiryMails;
use App\Mail\ContactFormCompanyMail;
use App\Mail\ContactFormCustomerMail;
use App\Services\Cas\CasClient;
use App\Services\Cas\CasRequestFailedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.cas_genesis_world.host' => 'https://cas.example.test',
        'services.cas_genesis_world.username' => 'testuser',
        'services.cas_genesis_world.password' => 'secret',
        'services.cas_genesis_world.product_key' => 'test-product-key',
        'services.decision_model.url' => 'https://decision.example.test',
        'services.decision_model.key' => 'test-decision-key',
    ]);

    File::ensureDirectoryExists(storage_path('logs'));
    File::put(storage_path('logs/mail.log'), '');
    File::put(storage_path('logs/api.log'), '');
});

afterEach(function () {
    File::delete(storage_path('logs/mail.log'));
    File::delete(storage_path('logs/api.log'));
});

function validContactFormData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Dr. Max Mustermann',
        'email' => 'max@example.com',
        'telefon' => '0231123456',
        'plz' => '44269',
        'nachricht' => 'Ich interessiere mich für ein Gerät.',
        'praxis' => 'Praxis Mustermann',
        'fachgebiet' => 'Allgemeinmedizin / Hausarzt',
        'rueckruf' => 'ja',
        'rueckruf_datum' => now()->addDays(3)->format('Y-m-d'),
        'datenschutz' => 'ja',
    ], $overrides);
}

function inquiryJob(array $overrides = []): CreateInquiryInCas
{
    $data = array_merge([
        'name' => 'Dr. Max Mustermann',
        'email' => 'max@example.com',
        'telefon' => null,
        'plz' => '44269',
        'nachricht' => null,
        'praxis' => null,
        'fachgebiet' => null,
        'wantsCallback' => false,
        'rueckrufDatum' => null,
    ], $overrides);

    return new CreateInquiryInCas(...$data);
}

function mailJob(array $overrides = []): SendInquiryMails
{
    $data = array_merge([
        'name' => 'Dr. Max Mustermann',
        'email' => 'max@example.com',
        'telefon' => null,
        'plz' => '44269',
        'nachricht' => null,
        'praxis' => null,
        'fachgebiet' => null,
        'wantsCallback' => false,
        'rueckrufDatum' => null,
    ], $overrides);

    return new SendInquiryMails(...$data);
}

test('contact form requires name, email, plz and datenschutz consent', function () {
    Bus::fake();

    $response = $this->post(route('kontakt.store'), []);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['name', 'email', 'plz', 'datenschutz']);
    Bus::assertNothingDispatched();
});

test('the contact form is rate limited to 3 submissions per minute', function () {
    Bus::fake();

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('kontakt.store'), validContactFormData())->assertRedirect(route('danke'));
    }

    $this->post(route('kontakt.store'), validContactFormData())->assertStatus(429);
});

test('valid submission dispatches CreateInquiryInCas and SendInquiryMails independently and redirects to the danke page', function () {
    Bus::fake();

    $response = $this->post(route('kontakt.store'), validContactFormData());

    $response->assertRedirect(route('danke'));

    Bus::assertDispatched(CreateInquiryInCas::class, function (CreateInquiryInCas $job) {
        return $job->name === 'Dr. Max Mustermann'
            && $job->email === 'max@example.com'
            && $job->wantsCallback === true;
    });

    Bus::assertDispatched(SendInquiryMails::class, function (SendInquiryMails $job) {
        return $job->name === 'Dr. Max Mustermann'
            && $job->email === 'max@example.com'
            && $job->wantsCallback === true;
    });
});

test('CreateInquiryInCas creates the CAS record', function () {
    Http::fake([
        '*/v7.0/type/Inquiries*' => Http::response(['GGUID' => 'guid-123'], 200),
    ]);

    inquiryJob()->handle(app(CasClient::class));

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v7.0/type/Inquiries')
            && $request['fields']['Name'] === 'Dr. Max Mustermann';
    });
});

test('CreateInquiryInCas does not fail when no GUID can be extracted from a successful response', function () {
    Http::fake([
        '*/v7.0/type/Inquiries*' => Http::response(['unexpected' => 'shape'], 200),
    ]);

    // The request itself succeeded - an unparsable GUID is only a logging
    // concern now that nothing downstream needs it, so handle() must not
    // throw or otherwise mark the job as failed.
    expect(fn () => inquiryJob()->handle(app(CasClient::class)))->not->toThrow(Throwable::class);
});

test('CreateInquiryInCas still reaches CAS when the configured host has no scheme', function () {
    config(['services.cas_genesis_world.host' => 'cas.example.test/genesisrest.svc']);

    Http::fake([
        'https://cas.example.test/genesisrest.svc/v7.0/type/Inquiries*' => Http::response(['GGUID' => 'guid-123'], 200),
    ]);

    inquiryJob()->handle(app(CasClient::class));

    Http::assertSent(fn ($request) => $request->url() === 'https://cas.example.test/genesisrest.svc/v7.0/type/Inquiries?tag-as-recently-used=false');
});

test('CreateInquiryInCas throws on a failed CAS request so the queue retries it', function () {
    Http::fake([
        '*/v7.0/type/Inquiries*' => Http::response('Service Unavailable', 503),
    ]);

    expect(fn () => inquiryJob()->handle(app(CasClient::class)))
        ->toThrow(CasRequestFailedException::class);
});

test('SendInquiryMails sends both mails and logs the submission', function () {
    Mail::fake();

    mailJob([
        'telefon' => '0231123456',
        'nachricht' => 'Testnachricht',
        'praxis' => 'Praxis Mustermann',
        'fachgebiet' => 'Allgemeinmedizin / Hausarzt',
        'wantsCallback' => true,
        'rueckrufDatum' => '2026-08-01',
    ])->handle();

    Mail::assertSent(ContactFormCompanyMail::class, function ($mail) {
        return $mail->hasTo((string) config('mail.from.address'))
            && $mail->hasReplyTo('max@example.com');
    });
    Mail::assertSent(ContactFormCustomerMail::class, function ($mail) {
        return $mail->hasTo('max@example.com')
            && $mail->name === 'Dr. Max Mustermann';
    });

    $logContent = file_get_contents(storage_path('logs/mail.log'));
    expect($logContent)
        ->toContain('Anfrage von Dr. Max Mustermann')
        ->toContain('Mailversand an max@example.com ✓');
});

test('SendInquiryMails failed() hook logs a failure line', function () {
    mailJob()->failed(new RuntimeException('SMTP down'));

    $logContent = file_get_contents(storage_path('logs/mail.log'));
    expect($logContent)->toContain('Mailversand an max@example.com ✗');
});

test('SendInquiryMails sends only the message text to the decision model and passes the spam score to the company mail', function () {
    Mail::fake();
    Http::fake([
        'https://decision.example.test/v1/systemone' => Http::response([
            'answers' => ['istSpam' => ['type' => 'noul', 'noul' => 0.83, 'confidence' => 0.9]],
        ]),
    ]);

    mailJob([
        'telefon' => '0231123456',
        'nachricht' => 'Testnachricht',
        'praxis' => 'Praxis Mustermann',
    ])->handle();

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-decision-key')
            && $request->data() === [
                'state' => 'Testnachricht',
                'questions' => [
                    'istSpam' => [
                        'type' => 'noul',
                        'instructions' => 'Dormed ist ein deutsches Unternehmen für Medizintechnik und verkauft ausschließlich innerhalb Deutschlands. Über dieses Kontaktformular gehen normalerweise echte Anfragen von Kliniken und Praxen zu Produkten, Wartung oder Service ein. Ist die folgende Nachricht für uns Spam — dazu zählt auch unaufgeforderte Kaltakquise wie Vertriebs-, Marketing- oder SEO-Angebote fremder Anbieter, nicht nur klassischer Werbe- oder Betrugsspam?',
                    ],
                ],
            ];
    });
    Mail::assertSent(ContactFormCompanyMail::class, fn ($mail) => $mail->spamScore === 0.83);
});

test('SendInquiryMails skips the spam score request when the message is empty', function () {
    Mail::fake();
    Http::fake();

    mailJob(['nachricht' => null])->handle();

    Http::assertNothingSent();
    Mail::assertSent(ContactFormCompanyMail::class, fn ($mail) => $mail->spamScore === null);
});

test('SendInquiryMails still sends both mails without a spam score when the decision model fails', function (Closure $fakeResponse) {
    Mail::fake();
    Http::fake(['https://decision.example.test/*' => $fakeResponse]);

    mailJob(['nachricht' => 'Testnachricht'])->handle();

    Mail::assertSent(ContactFormCompanyMail::class, fn ($mail) => $mail->spamScore === null);
    Mail::assertSent(ContactFormCustomerMail::class);
})->with([
    'http error' => fn () => fn () => Http::response('Internal Server Error', 500),
    'connection error' => fn () => fn () => Http::failedConnection(),
    'malformed json' => fn () => fn () => Http::response('not json', 200),
    'missing score' => fn () => fn () => Http::response(['answers' => []], 200),
    'score out of range' => fn () => fn () => Http::response(['answers' => ['istSpam' => ['noul' => 1.5]]], 200),
]);

test('company mail shows the spam score banner with the matching colour', function (float $score, string $percent, string $color, string $background) {
    $mail = new ContactFormCompanyMail(
        name: 'Dr. Max Mustermann',
        email: 'max@example.com',
        telefon: null,
        plz: '44269',
        nachricht: 'Testnachricht',
        praxis: null,
        fachgebiet: null,
        wantsCallback: false,
        rueckrufDatum: null,
        spamScore: $score,
    );

    $mail->assertSeeInHtml("Spam-Score: {$percent} %", false)
        ->assertSeeInHtml("color:{$color};", false)
        ->assertSeeInHtml("background-color:{$background};", false);
})->with([
    'red' => [0.83, '83', '#B91C1C', '#FEE2E2'],
    'red threshold' => [0.7, '70', '#B91C1C', '#FEE2E2'],
    'orange' => [0.4, '40', '#B45309', '#FEF3C7'],
    'green' => [0.12, '12', '#047857', '#ECFDF5'],
]);

test('company mail omits the spam score banner without a score', function () {
    $mail = new ContactFormCompanyMail(
        name: 'Dr. Max Mustermann',
        email: 'max@example.com',
        telefon: null,
        plz: '44269',
        nachricht: null,
        praxis: null,
        fachgebiet: null,
        wantsCallback: false,
        rueckrufDatum: null,
    );

    $mail->assertDontSeeInHtml('Spam-Score', false);
});
