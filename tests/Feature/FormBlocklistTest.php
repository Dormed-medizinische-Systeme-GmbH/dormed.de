<?php

use App\Jobs\SendInquiryMails;
use App\Support\FormBlocklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'events' => [
            'testmesse-2026' => [
                'name' => 'Testmesse 2026',
                'location' => 'Messe Dortmund',
                'accepts_leads_until' => now()->addDays(10)->format('Y-m-d'),
            ],
        ],
        'contact.blocklist' => [
            'name' => ['Robertloult'],
            'email' => ['filip.b@hotmail.de', 'spam.person@gmail.com'],
            'telefon' => ['+49 231 999999'],
            'praxis' => ['Spam Praxis'],
        ],
    ]);
});

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function blocklistContactData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Dr. Max Mustermann',
        'email' => 'max@example.com',
        'telefon' => '0231123456',
        'plz' => '44269',
        'nachricht' => 'Ich interessiere mich für ein Gerät.',
        'praxis' => 'Praxis Mustermann',
        'datenschutz' => 'ja',
    ], $overrides);
}

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function blocklistLeadData(array $overrides = []): array
{
    return array_merge([
        'praxis_name' => 'Praxis Dr. Mustermann',
        'email' => 'praxis@example.com',
        'telefon' => '0231123456',
        'datenschutz' => 'ja',
    ], $overrides);
}

test('a blocked value in the contact form redirects to danke without dispatching anything', function (string $field, string $value) {
    Bus::fake();

    $this->post(route('kontakt.store'), blocklistContactData([$field => $value]))
        ->assertRedirect(route('danke'))
        ->assertSessionHasNoErrors();

    Bus::assertNothingDispatched();
})->with([
    'name' => ['name', 'Robertloult'],
    'name in other case with spaces' => ['name', '  rOBERTLOULT '],
    'email' => ['email', 'filip.b@hotmail.de'],
    'email in other case' => ['email', 'Filip.B@Hotmail.DE'],
    'email with plus tag' => ['email', 'filip.b+shop@hotmail.de'],
    'gmail with other dots' => ['email', 'spamperson+x@googlemail.com'],
    'telefon in other format' => ['telefon', '0049 231 999999'],
    'praxis' => ['praxis', 'spam praxis'],
]);

test('a blocked value in the lead form redirects to its danke page without dispatching anything', function (string $field, string $value) {
    Bus::fake();

    $this->post(route('veranstaltungen.lead.store', 'testmesse-2026'), blocklistLeadData([$field => $value]))
        ->assertRedirect(route('veranstaltungen.lead.danke', 'testmesse-2026'));

    Bus::assertNothingDispatched();
})->with([
    'praxis_name matches name list' => ['praxis_name', 'Robertloult'],
    'praxis_name matches praxis list' => ['praxis_name', 'Spam Praxis'],
    'email' => ['email', 'filip.b@hotmail.de'],
    'telefon' => ['telefon', '+49231999999'],
]);

test('values that only contain a blocked entry as a part are not blocked', function () {
    Bus::fake();

    $this->post(route('kontakt.store'), blocklistContactData(['name' => 'Robertloult Mustermann']))
        ->assertRedirect(route('danke'));

    Bus::assertDispatched(SendInquiryMails::class);
});

test('a non blocked contact form submission still goes through', function () {
    Bus::fake();

    $this->post(route('kontakt.store'), blocklistContactData())
        ->assertRedirect(route('danke'));

    Bus::assertDispatched(SendInquiryMails::class);
});

test('a form without a configured redirect goes back silently on a hit', function () {
    config(['contact.blocklist_redirects' => []]);
    Bus::fake();

    $this->from('/kontakt')
        ->post(route('kontakt.store'), blocklistContactData(['name' => 'Robertloult']))
        ->assertRedirect('/kontakt')
        ->assertSessionHasNoErrors();

    Bus::assertNothingDispatched();
});

test('the e-mail is brought into one canonical format', function (string $input, string $expected) {
    expect(FormBlocklist::normalizeEmail($input))->toBe($expected);
})->with([
    ['  Filip.B@Hotmail.DE ', 'filip.b@hotmail.de'],
    ['filip.b+newsletter@hotmail.de', 'filip.b@hotmail.de'],
    ['Max.Mustermann+x@Gmail.com', 'maxmustermann@gmail.com'],
    ['max.mustermann@googlemail.com', 'maxmustermann@gmail.com'],
    ["fi\u{200B}lip.b@hotmail.de", 'filip.b@hotmail.de'],
    ['kein-at-zeichen', 'kein-at-zeichen'],
]);

test('phone numbers are compared by digits with the german prefix folded', function (string $input, string $expected) {
    expect(FormBlocklist::normalizePhone($input))->toBe($expected);
})->with([
    ['0231 123456', '0231123456'],
    ['+49 231 123456', '0231123456'],
    ['0049-231-123456', '0231123456'],
    ['(0231) 12 34 56', '0231123456'],
]);

test('empty values never match', function () {
    expect(FormBlocklist::matches('name', '   ', ['']))->toBeFalse()
        ->and(FormBlocklist::findMatch(['name' => ''], ['name' => ['']]))->toBeNull();
});
