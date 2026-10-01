<?php

use App\Services\UsedDevices\UsedDeviceCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const DC70_ID = '04B56DB4FCE34AF8B58B09CBA4D294CB';
const M6_ID = '719A024717A0476B8C13C2B971F38254';

/**
 * @return list<array<string, mixed>>
 */
function casUsedDeviceRecords(): array
{
    return [
        [
            'id' => DC70_ID,
            'fields' => [
                'DORMEDGGPREIS' => 6900.0,
                'DORMEDGGSTEUER' => 'netto',
                'GG_SONDE1_ARTIKEL' => 'SC6-1E',
                'GG_SONDE1_BEZ' => 'Convex-Sonde',
                'GG_SONDE2_ARTIKEL' => 'L12-3E',
                'GG_SONDE2_BEZ' => 'Linear-Sonde',
                'GG_SONDE3_ARTIKEL' => 'Sonde 3 Artikel',
                'GG_SONDE3_BEZ' => 'Sonde 3 Bezeichnung',
                'GG_SYSTEM_ARTIKEL' => 'DC-70 EXP X-INSIGHT',
                'GG_SYSTEM_BAUJAHR' => '2021',
                'GG_SYSTEM_BEZ' => 'Farbdopplersystem',
                'GG_SYSTEM_HERSTELLER' => 'Mindray Co. Ltd.',
                'ONLINE' => true,
            ],
        ],
        [
            'id' => M6_ID,
            'fields' => [
                'GG_SYSTEM_ARTIKEL' => 'M6',
                'GG_SYSTEM_BEZ' => 'portables Farbdopplersystem',
                'GG_SYSTEM_HERSTELLER' => 'Mindray Co. Ltd.',
                'ONLINE' => true,
            ],
        ],
        [
            'id' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            'fields' => [
                'GG_SYSTEM_ARTIKEL' => 'Offline-Gerät',
                'GG_SYSTEM_BEZ' => 'Farbdopplersystem',
                'GG_SYSTEM_HERSTELLER' => 'Esaote S.p.A.',
                'ONLINE' => false,
            ],
        ],
    ];
}

beforeEach(function () {
    Cache::flush();
    Storage::fake('local');
    config()->set('services.cas_genesis_world.host', 'cas.test');

    Http::fake([
        'cas.test/v7.0/type/GEBRAUCHTGERAETE/view/*' => Http::response(casUsedDeviceRecords()),
        'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70_ID.'/dossier/full' => Http::response([
            ['fields' => ['GGUID2' => 'DOC2', 'DOCEXT' => 'PDF', 'SORTDATE' => '2026-01-01T00:00:00Z']],
            ['fields' => ['GGUID2' => 'DOC1', 'DOCEXT' => 'JPG', 'SORTDATE' => '2026-01-02T00:00:00Z']],
        ]),
        'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.M6_ID.'/dossier/full' => Http::response([]),
        'cas.test/v7.0/type/document/DOC1/file' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);
});

test('the used devices page lists the online devices from CAS with filter attributes', function () {
    $this->withoutVite();

    $response = $this->get('/gebraucht2');

    $response->assertOk();
    $response->assertSeeText(['DC-70 EXP X-INSIGHT', 'M6', 'Convex-Sonde SC6-1E', '6.900 €']);
    $response->assertDontSeeText('Offline-Gerät');
    $response->assertDontSeeText('Sonde 3');
    $response->assertSee('data-brand="mindray"', false);
    $response->assertSee('data-system="farbdoppler"', false);
    $response->assertSee('data-year="ab-2020"', false);
    $response->assertSee('data-year="unbekannt"', false);
    $response->assertSee('2 Geräte');
});

test('the used devices page is not indexed while it is being built', function () {
    $this->withoutVite();

    $this->get('/gebraucht2')->assertSee('noindex', false);
});

test('the last successful device list is served while CAS is unreachable', function () {
    $this->withoutVite();
    $this->get('/gebraucht2')->assertSeeText('M6');

    Cache::forget('used-devices');
    Http::fake(['cas.test/*' => Http::response('', 503)]);

    $this->get('/gebraucht2')->assertOk()->assertSeeText('M6');
});

test('the page still renders when CAS is unreachable and nothing is cached', function () {
    $this->withoutVite();
    Http::fake(['cas.test/*' => Http::response('', 503)]);

    $this->get('/gebraucht2')->assertOk()->assertSeeText('Keine Geräte gefunden');
});

test('a device image is fetched from the CAS dossier and served', function () {
    $response = $this->get(route('gebraucht2.bild', DC70_ID));

    $response->assertOk();
    expect(file_get_contents($response->baseResponse->getFile()->getPathname()))->toBe('jpeg-bytes');
});

test('a device without a dossier image or an unknown device returns 404', function () {
    $this->get(route('gebraucht2.bild', M6_ID))->assertNotFound();
    $this->get(route('gebraucht2.bild', 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB'))->assertNotFound();
});

test('devices are grouped into year bands', function () {
    $bands = app(UsedDeviceCatalog::class)->all()->pluck('yearBand', 'name');

    expect($bands['DC-70 EXP X-INSIGHT'])->toBe('ab-2020')
        ->and($bands['M6'])->toBe('unbekannt');
});
