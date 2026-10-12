<?php

use App\Jobs\SendEventLeadMails;
use App\Jobs\SendInquiryMails;
use App\Rules\AllowedScriptsOnly;
use App\Support\FormGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;

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
    ]);

    File::ensureDirectoryExists(storage_path('logs'));
    File::put(storage_path('logs/spam.log'), '');
});

afterEach(function () {
    File::delete(storage_path('logs/spam.log'));
});

/**
 * Posts the given message through either form and returns the response.
 */
function submitMessage(string $form, string $nachricht, array $overrides = []): Illuminate\Testing\TestResponse
{
    return match ($form) {
        'kontakt' => test()->post(route('kontakt.store'), array_merge([
            'name' => 'Dr. Max Mustermann',
            'email' => 'max@example.com',
            'plz' => '44269',
            'nachricht' => $nachricht,
            'datenschutz' => 'ja',
        ], $overrides)),
        'lead' => test()->post(route('veranstaltungen.lead.store', 'testmesse-2026'), array_merge([
            'praxis_name' => 'Praxis Dr. Mustermann',
            'email' => 'praxis@example.com',
            'nachricht' => $nachricht,
            'datenschutz' => 'ja',
        ], $overrides)),
    };
}

dataset('forms', ['kontakt', 'lead']);

test('messages in non-Latin scripts are rejected with a visible error naming the characters', function (string $form, string $nachricht, string $firstCharacter) {
    Bus::fake();

    submitMessage($form, $nachricht)->assertSessionHasErrors('nachricht');

    expect(session('errors')->first('nachricht'))
        ->toContain('lateinischer Schrift')
        ->toContain('„'.$firstCharacter.'“');

    Bus::assertNothingDispatched();
})->with('forms')->with([
    'georgian' => ['Hi, მინდოდა ვიცოდე თქვენი ფასი', 'მ'],
    'cyrillic' => ['Здравствуйте, какая цена?', 'З'],
    'cyrillic lookalike inside a latin word' => ['Ihr Sonderаngebot', 'а'],
    'chinese' => ['你好，价格是多少', '你'],
    'arabic' => ['مرحبا', 'م'],
]);

test('latin text with umlauts, foreign latin names, symbols and emoji is accepted', function (string $form) {
    Bus::fake();

    submitMessage($form, 'Grüße von Łukasz Şahin & Zoë Café – 30.000 € netto, ½ Jahr, 5 µm 😀 „Danke“')
        ->assertSessionHasNoErrors();

    Bus::assertDispatched($form === 'kontakt' ? SendInquiryMails::class : SendEventLeadMails::class);
})->with('forms');

test('only the message is checked for the script, not the name', function () {
    Bus::fake();

    submitMessage('kontakt', 'Guten Tag', ['name' => 'Dr. Иван Петров'])->assertSessionHasNoErrors();

    Bus::assertDispatched(SendInquiryMails::class);
});

test('invisible characters are stripped before validation and never reach the mail', function () {
    Bus::fake();

    submitMessage('kontakt', "Bitte\u{200B} um\u{00AD} Rückruf\u{FEFF}", ['name' => "Dr. Max\u{200D} Mustermann"])
        ->assertSessionHasNoErrors();

    Bus::assertDispatched(SendInquiryMails::class, fn (SendInquiryMails $job) => $job->nachricht === 'Bitte um Rückruf'
        && $job->name === 'Dr. Max Mustermann');
});

test('invisible characters cannot hide a foreign link', function (string $form) {
    Bus::fake();

    submitMessage($form, "Mehr auf seo\u{200B}-agentur.com")->assertSessionHasErrors('nachricht');

    Bus::assertNothingDispatched();
})->with('forms');

test('the lead form blocks foreign links in all its free-text fields like the contact form', function (string $field) {
    Bus::fake();

    submitMessage('lead', 'Hallo', [$field => 'siehe https://spam.example.com'])->assertSessionHasErrors($field);

    Bus::assertNothingDispatched();
})->with(['praxis_name', 'strasse', 'ort', 'interesse', 'budget', 'nachricht']);

test('rejections are logged with reason, form and field but without the submitted content', function () {
    Bus::fake();

    submitMessage('kontakt', 'Hi, მინდოდა ვიცოდე');
    submitMessage('lead', 'Angebot auf https://seo-agentur.ru/x');

    $log = File::get(storage_path('logs/spam.log'));

    expect($log)
        ->toContain('"grund":"schrift"')
        ->toContain('"formular":"kontakt.store"')
        ->toContain('"grund":"link"')
        ->toContain('"formular":"veranstaltungen.lead.store"')
        ->toContain('"domain":"seo-agentur.ru"')
        ->not->toContain('მინდოდა')
        ->not->toContain('Angebot');
});

test('additional scripts can be allowed via config', function () {
    Bus::fake();
    config(['contact.allowed_scripts' => ['Latin', 'Common', 'Inherited', 'Greek']]);

    submitMessage('kontakt', 'Frage zu β-Blockern')->assertSessionHasNoErrors();

    Bus::assertDispatched(SendInquiryMails::class);
});

test('both forms ship the same guard configuration to the frontend', function (string $routeName, array $parameters, array $linkFields) {
    $this->withoutVite();

    $html = $this->get(route($routeName, $parameters))->assertOk()->getContent();
    $config = json_decode(html_entity_decode(str($html)->match('/data-form-guard="([^"]*)"/')->toString()), true);

    expect($config['disallowedScriptPattern'])->toBe(AllowedScriptsOnly::javascriptPattern())
        ->and($config['scriptFields'])->toBe(['nachricht'])
        ->and($config['linkFields'])->toBe($linkFields)
        ->and($config['invisiblePattern'])->toBe(FormGuard::INVISIBLE_PATTERN);
})->with([
    'kontakt' => ['kontakt', [], ['name', 'praxis', 'nachricht']],
    'lead' => ['veranstaltungen.lead.show', ['testmesse-2026'], ['praxis_name', 'strasse', 'ort', 'interesse', 'budget', 'nachricht']],
]);
