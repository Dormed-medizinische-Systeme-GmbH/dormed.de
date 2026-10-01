<?php

use App\UsedDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('the used devices page lists the stored devices with filter attributes and sends no CAS request', function () {
    $this->withoutVite();
    Http::fake();

    UsedDevice::factory()->create([
        'name' => 'DC-70 EXP X-INSIGHT',
        'manufacturer' => 'Mindray Co. Ltd.',
        'description' => 'Farbdopplersystem',
        'year' => 2021,
        'probes' => ['Convex-Sonde SC6-1E'],
        'images' => ['gebraucht/ABC/doc1.jpg'],
    ]);
    UsedDevice::factory()->create(['name' => 'M6', 'description' => 'portables Farbdopplersystem', 'year' => null]);

    $response = $this->get('/ultraschallgeraete/gebraucht2');

    $response->assertOk();
    $response->assertSeeText(['DC-70 EXP X-INSIGHT', 'M6', 'Convex-Sonde SC6-1E', 'Baujahr auf Anfrage']);
    $response->assertSee('data-brand="mindray"', false);
    $response->assertSee('data-system="farbdoppler"', false);
    $response->assertSee('data-year="ab-2020"', false);
    $response->assertSee('data-year="unbekannt"', false);
    $response->assertSee('src="/storage/gebraucht/ABC/doc1.jpg"', false);
    $response->assertSee('/assets/img/platzhalter-geraet.svg', false);
    $response->assertSee('2 Geräte');
    Http::assertNothingSent();
});

test('the used devices page keeps all other sections of the current used devices page', function () {
    $this->withoutVite();

    $this->get('/ultraschallgeraete/gebraucht2')->assertOk()->assertSeeText([
        'Gebrauchte Ultraschallgeräte',
        'So bereiten wir',
        'Warum ein Gebrauchtgerät von Dormed?',
        'Was suchen Sie',
        'Ihre Fragen, unsere Antworten',
    ])->assertSee('"@type": "FAQPage"', false);
});

test('the used devices page shows the empty state without devices', function () {
    $this->withoutVite();

    $this->get('/ultraschallgeraete/gebraucht2')->assertOk()->assertSeeText('Keine Geräte gefunden');
});

test('the used devices page is not indexed while it is being built', function () {
    $this->withoutVite();

    $this->get('/ultraschallgeraete/gebraucht2')->assertSee('noindex', false);
});

test('devices are grouped into year bands and systems', function () {
    expect(UsedDevice::factory()->make(['year' => 2021])->yearBand)->toBe('ab-2020')
        ->and(UsedDevice::factory()->make(['year' => 2016])->yearBand)->toBe('2015-2019')
        ->and(UsedDevice::factory()->make(['year' => 2009])->yearBand)->toBe('vor-2015')
        ->and(UsedDevice::factory()->make(['year' => null])->yearBand)->toBe('unbekannt')
        ->and(UsedDevice::factory()->make(['description' => 'Schwarz-/Weiß-System'])->system)->toBe('schwarz-weiss')
        ->and(UsedDevice::factory()->make(['manufacturer' => 'Esaote S.p.A.'])->brandLabel)->toBe('Esaote');
});
