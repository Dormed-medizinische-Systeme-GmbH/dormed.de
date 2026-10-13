<?php

use App\Jobs\SendEventLeadMails;
use App\Mail\EventLeadCompanyMail;
use App\Mail\EventLeadCustomerMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'events' => [
            'testmesse-2026' => [
                'name' => 'Testmesse 2026',
                'location' => 'Messe Dortmund',
                'accepts_leads_until' => now()->addDays(10)->format('Y-m-d'),
            ],
            'vergangene-messe' => [
                'name' => 'Vergangene Messe',
                'location' => 'Messe Hamburg',
                'accepts_leads_until' => now()->subDay()->format('Y-m-d'),
            ],
        ],
    ]);

    File::ensureDirectoryExists(storage_path('logs'));
    File::put(storage_path('logs/mail.log'), '');
});

afterEach(function () {
    File::delete(storage_path('logs/mail.log'));
});

function leadFormData(array $overrides = []): array
{
    return array_merge([
        'praxis_name' => 'Praxis Dr. Mustermann',
        'strasse' => 'Musterstraße 1',
        'plz' => '44269',
        'ort' => 'Dortmund',
        'email' => 'praxis@example.com',
        'telefon' => '0231123456',
        'interesse' => 'Mindray TE 7',
        'budget' => 'ca. 30.000 €',
        'nachricht' => 'Bitte nach der Messe anrufen.',
        'datenschutz' => 'ja',
    ], $overrides);
}

function eventLeadMailJob(array $overrides = []): SendEventLeadMails
{
    return new SendEventLeadMails(...array_merge([
        'eventName' => 'Testmesse 2026',
        'praxisName' => 'Praxis Dr. Mustermann',
        'strasse' => null,
        'plz' => null,
        'ort' => null,
        'email' => 'praxis@example.com',
        'telefon' => null,
        'interesse' => null,
        'budget' => null,
        'nachricht' => null,
    ], $overrides));
}

test('the lead form is shown for a configured event and is not indexable', function () {
    $this->get('/veranstaltungen/testmesse-2026/kontakt')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('Testmesse 2026')
        ->assertSee('name="praxis_name"', false);
});

test('an unknown event returns 404', function () {
    $this->get('/veranstaltungen/unbekannt/kontakt')->assertNotFound();
    $this->post('/veranstaltungen/unbekannt/kontakt', leadFormData())->assertNotFound();
});

test('a past event shows a notice instead of the form and rejects submissions', function () {
    Bus::fake();

    $this->get('/veranstaltungen/vergangene-messe/kontakt')
        ->assertOk()
        ->assertSee('nicht mehr aktiv')
        ->assertDontSee('name="praxis_name"', false);

    $this->post('/veranstaltungen/vergangene-messe/kontakt', leadFormData())
        ->assertRedirect(route('veranstaltungen.lead.show', 'vergangene-messe'));

    Bus::assertNothingDispatched();
});

test('the lead form requires praxis name and datenschutz consent', function () {
    Bus::fake();

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), [])
        ->assertSessionHasErrors(['praxis_name', 'datenschutz']);

    Bus::assertNothingDispatched();
});

test('the lead form requires either an e-mail or a phone number', function () {
    Bus::fake();

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData(['email' => null, 'telefon' => null]))
        ->assertSessionHasErrors(['email', 'telefon']);

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData(['email' => null]))
        ->assertRedirect(route('veranstaltungen.lead.danke', 'testmesse-2026'));

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData(['telefon' => null]))
        ->assertRedirect(route('veranstaltungen.lead.danke', 'testmesse-2026'));

    Bus::assertDispatchedTimes(SendEventLeadMails::class, 2);
});

test('a valid lead dispatches the mail job with the event taken from the url', function () {
    Bus::fake();

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData(['event' => 'Gefälscht']))
        ->assertRedirect(route('veranstaltungen.lead.danke', 'testmesse-2026'));

    Bus::assertDispatched(SendEventLeadMails::class, fn (SendEventLeadMails $job) => $job->eventName === 'Testmesse 2026'
        && $job->praxisName === 'Praxis Dr. Mustermann'
        && $job->budget === 'ca. 30.000 €'
        && $job->nachricht === 'Bitte nach der Messe anrufen.');
});

test('the lead form offers trade-in, purchase date, financing and services', function () {
    $this->get(route('veranstaltungen.lead.show', 'testmesse-2026'))
        ->assertOk()
        ->assertSee('Ankauf / Entsorgung Ihres Altgeräts')
        ->assertSee('name="altgeraet_system"', false)
        ->assertSee('name="anschaffungsdatum" type="date"', false)
        ->assertSee('name="finanzierung" value="full_service"', false)
        ->assertSee('name="dienstleistungen[]" value="netzwerkanbindung"', false)
        ->assertSee('Ausstattung / Bemerkungen')
        ->assertDontSee('>Nachricht<', false);
});

test('the new lead fields reach the mail job as readable values', function () {
    Bus::fake();

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData([
        'altgeraet' => 'ja',
        'altgeraet_system' => 'GE Voluson E8',
        'anschaffungsdatum' => '2027-03-01',
        'finanzierung' => 'full_service',
        'dienstleistungen' => ['wartung', 'garantieverlaengerung'],
    ]))->assertRedirect(route('veranstaltungen.lead.danke', 'testmesse-2026'));

    Bus::assertDispatched(SendEventLeadMails::class, fn (SendEventLeadMails $job) => $job->altgeraetAnkauf === true
        && $job->altgeraetSystem === 'GE Voluson E8'
        && $job->anschaffungsdatum === '01.03.2027'
        && $job->finanzierung === 'Full-Service'
        && $job->dienstleistungen === ['Wartung', 'Garantieverlängerung']);
});

test('the new lead fields are optional and the old system is dropped without the trade-in box', function () {
    Bus::fake();

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData(['altgeraet_system' => 'GE Voluson E8']))
        ->assertRedirect(route('veranstaltungen.lead.danke', 'testmesse-2026'));

    Bus::assertDispatched(SendEventLeadMails::class, fn (SendEventLeadMails $job) => $job->altgeraetAnkauf === false
        && $job->altgeraetSystem === null
        && $job->anschaffungsdatum === null
        && $job->finanzierung === null
        && $job->dienstleistungen === []);
});

test('unknown financing, services or dates are rejected', function (string $field, mixed $value) {
    Bus::fake();

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData([$field => $value]))
        ->assertSessionHasErrors(is_array($value) ? $field.'.0' : $field);

    Bus::assertNothingDispatched();
})->with([
    'financing' => ['finanzierung', 'ratenkauf'],
    'service' => ['dienstleistungen', ['schulung']],
    'date' => ['anschaffungsdatum', '01.03.2027'],
]);

test('the lead form has no rate limit so staff can enter several leads in a row', function () {
    Bus::fake();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData())
            ->assertRedirect(route('veranstaltungen.lead.danke', 'testmesse-2026'));
    }

    Bus::assertDispatchedTimes(SendEventLeadMails::class, 5);
});

test('a filled honeypot field silently redirects without sending mails', function () {
    Bus::fake();

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), leadFormData(['website' => 'https://spam.example']))
        ->assertRedirect(route('veranstaltungen.lead.danke', 'testmesse-2026'));

    Bus::assertNothingDispatched();
});

test('the thank-you page is not indexable', function () {
    $this->get(route('veranstaltungen.lead.danke', 'testmesse-2026'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('Testmesse 2026');
});

test('the mail job sends the company mail with the event in the subject and a customer confirmation', function () {
    Mail::fake();

    eventLeadMailJob()->handle();

    Mail::assertSent(EventLeadCompanyMail::class, function (EventLeadCompanyMail $mail) {
        return $mail->hasTo(config('mail.from.address'))
            && $mail->hasSubject('Neuer Lead Testmesse 2026: Praxis Dr. Mustermann')
            && $mail->hasReplyTo('praxis@example.com');
    });
    Mail::assertSent(EventLeadCustomerMail::class, fn (EventLeadCustomerMail $mail) => $mail->hasTo('praxis@example.com'));

    expect(file_get_contents(storage_path('logs/mail.log')))
        ->toContain('Lead (Testmesse 2026) von Praxis Dr. Mustermann | Mailversand an praxis@example.com ✓');
});

test('the mail job skips the customer confirmation when only a phone number was given', function () {
    Mail::fake();

    eventLeadMailJob(['email' => null, 'telefon' => '0231123456'])->handle();

    Mail::assertSent(EventLeadCompanyMail::class);
    Mail::assertNotSent(EventLeadCustomerMail::class);
});

test('the company mail renders all lead fields', function () {
    $mail = new EventLeadCompanyMail(
        eventName: 'Testmesse 2026',
        praxisName: 'Praxis Dr. Mustermann',
        strasse: 'Musterstraße 1',
        plz: '44269',
        ort: 'Dortmund',
        email: 'praxis@example.com',
        telefon: '0231123456',
        interesse: 'Mindray TE 7',
        budget: 'ca. 30.000 €',
        nachricht: 'Bitte nach der Messe anrufen.',
    );

    $mail->assertSeeInHtml('Testmesse 2026')
        ->assertSeeInHtml('Musterstraße 1')
        ->assertSeeInHtml('44269 Dortmund')
        ->assertSeeInHtml('Mindray TE 7')
        ->assertSeeInHtml('ca. 30.000 €')
        ->assertSeeInHtml('Bitte nach der Messe anrufen.');
});

test('the company mail renders trade-in, purchase date, financing and services', function () {
    $mail = new EventLeadCompanyMail(
        eventName: 'Testmesse 2026',
        praxisName: 'Praxis Dr. Mustermann',
        strasse: null,
        plz: null,
        ort: null,
        email: 'praxis@example.com',
        telefon: null,
        interesse: null,
        budget: null,
        nachricht: 'Mit Linearsonde',
        altgeraetAnkauf: true,
        altgeraetSystem: 'GE Voluson E8',
        anschaffungsdatum: '01.03.2027',
        finanzierung: 'Leasing',
        dienstleistungen: ['Wartung', 'Netzwerkanbindung'],
    );

    $mail->assertSeeInHtml('Ankauf / Entsorgung gewünscht: GE Voluson E8')
        ->assertSeeInHtml('01.03.2027')
        ->assertSeeInHtml('Leasing')
        ->assertSeeInHtml('Wartung, Netzwerkanbindung')
        ->assertSeeInHtml('Ausstattung / Bemerkungen')
        ->assertDontSeeInHtml('>Nachricht<', false);
});

test('the pdf attachment is a valid pdf with the mail filename', function () {
    $mail = new EventLeadCompanyMail(
        eventName: 'Testmesse 2026',
        praxisName: 'Praxis Dr. Müller & Söhne',
        strasse: null,
        plz: null,
        ort: null,
        email: 'praxis@example.com',
        telefon: null,
        interesse: null,
        budget: 'ca. 30.000 €',
        nachricht: "Zeile 1\nZeile 2",
    );

    $attachments = $mail->attachments();

    expect($attachments)->toHaveCount(1);

    $attachment = $attachments[0];
    $filename = null;
    $data = null;
    $attachment->attachWith(
        fn () => null,
        function ($callback) use (&$data, &$filename, $attachment) {
            $data = $callback();
            $filename = $attachment->as;
        },
    );

    expect($filename)->toBe('Lead_testmesse-2026_praxis-dr-muller-sohne_'.now()->format('Y-m-d').'.pdf')
        ->and($attachment->mime)->toBe('application/pdf')
        ->and($data)->toStartWith('%PDF');
});

test('the company mail is still sent without attachment when the pdf cannot be rendered', function () {
    Mail::fake();
    Pdf::shouldReceive('loadView')->andThrow(new RuntimeException('render failed'));
    Log::spy();

    $mail = new EventLeadCompanyMail(
        eventName: 'Testmesse 2026',
        praxisName: 'Praxis Dr. Mustermann',
        strasse: null,
        plz: null,
        ort: null,
        email: null,
        telefon: '0231123456',
        interesse: null,
        budget: null,
        nachricht: null,
    );

    expect($mail->attachments())->toBe([]);
    Log::shouldHaveReceived('error')->once();
});

test('the customer confirmation has no attachment', function () {
    $mail = new EventLeadCustomerMail(eventName: 'Testmesse 2026', praxisName: 'Praxis', email: 'praxis@example.com');

    expect($mail->attachments)->toBe([])
        ->and($mail->rawAttachments)->toBe([])
        ->and($mail->diskAttachments)->toBe([]);
});
