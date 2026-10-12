<?php

use App\Jobs\CreateInquiryInCas;
use App\Jobs\SendInquiryMails;
use App\Rules\NoForeignLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

function contactFormDataWithMessage(string $nachricht, array $overrides = []): array
{
    return array_merge([
        'name' => 'Dr. Max Mustermann',
        'email' => 'max@example.com',
        'plz' => '44269',
        'nachricht' => $nachricht,
        'datenschutz' => 'ja',
    ], $overrides);
}

test('the contact form rejects foreign links with a visible error instead of failing silently', function (string $nachricht) {
    Bus::fake();

    $this->post(route('kontakt.store'), contactFormDataWithMessage($nachricht))
        ->assertRedirect()
        ->assertSessionHasErrors(['nachricht' => NoForeignLinks::message(['dormed.de'])]);

    Bus::assertNotDispatched(CreateInquiryInCas::class);
    Bus::assertNotDispatched(SendInquiryMails::class);
})->with([
    'https link' => 'Gute Angebote unter https://seo-agentur.ru/angebot',
    'www link' => 'Besuchen Sie www.billig-backlinks.com',
    'bare domain' => 'Mehr Infos auf seo-agentur.com',
    'lookalike domain' => 'Siehe evil-dormed.de',
]);

test('the contact form rejects foreign links in name and praxis as well', function (string $field) {
    Bus::fake();

    $this->post(route('kontakt.store'), contactFormDataWithMessage('Hallo', [$field => 'https://spam.example.com']))
        ->assertSessionHasErrors($field);

    Bus::assertNothingDispatched();
})->with(['name', 'praxis']);

test('the contact form accepts links to whitelisted domains and plain text without links', function (string $nachricht) {
    Bus::fake();

    $this->post(route('kontakt.store'), contactFormDataWithMessage($nachricht))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('danke'));

    Bus::assertDispatched(SendInquiryMails::class);
})->with([
    'dormed link' => 'Ich interessiere mich für https://www.dormed.de/ultraschallgeraete/handheld.',
    'bare dormed domain' => 'Gesehen auf dormed.de',
    'subdomain' => 'Wie auf shop.dormed.de beschrieben',
    'e-mail address' => 'Antwort bitte an info@praxis-mustermann.de',
    'abbreviations' => 'z.B. Dr.med. Müller, ca. 30.000 € am 12.10.2026',
]);

test('additional domains can be whitelisted via config', function () {
    Bus::fake();
    config(['contact.allowed_link_domains' => ['dormed.de', 'mindray.com']]);

    $this->post(route('kontakt.store'), contactFormDataWithMessage('Wie auf https://www.mindray.com/de beschrieben'))
        ->assertSessionHasNoErrors();

    Bus::assertDispatched(SendInquiryMails::class);
});

test('the contact page ships the same link pattern and whitelist to the frontend', function () {
    $this->withoutVite();

    $config = json_decode(
        html_entity_decode(str($this->get(route('kontakt'))->assertOk()->getContent())->match('/data-form-guard="([^"]*)"/')->toString()),
        true,
    );

    expect($config['linkPattern'])->toBe(NoForeignLinks::PATTERN)
        ->and($config['allowedDomains'])->toBe(['dormed.de'])
        ->and($config['linkFields'])->toBe(['name', 'praxis', 'nachricht']);
});
