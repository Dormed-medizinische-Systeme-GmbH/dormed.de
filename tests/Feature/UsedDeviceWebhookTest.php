<?php

use App\Jobs\SyncUsedDevice;
use App\Services\Cas\CasRequestFailedException;
use App\UsedDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const WEBHOOK_URL = '/webhooks/cas/gebrauchtgeraete';
const DC70 = '04B56DB4FCE34AF8B58B09CBA4D294CB';
const M6 = '719A024717A0476B8C13C2B971F38254';
const NEW_ID = 'AA036DEDDA63498CB2EE7C77D9E111E0';

beforeEach(function () {
    config()->set('services.cas_genesis_world.webhook_bearer', 'secret-token');
    config()->set('services.cas_genesis_world.host', 'cas.test');
});

/**
 * Single-record response of GET /type/GEBRAUCHTGERAETE/{guid}: no list, no ETAG in the body.
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function casRecord(string $id, array $fields = []): array
{
    return [
        'objectType' => 'GEBRAUCHTGERAETE',
        'id' => $id,
        'fields' => $fields + ['ONLINE' => true, 'GG_SYSTEM_ARTIKEL' => 'Modell '.$id, 'GG_SYSTEM_HERSTELLER' => 'Mindray Co. Ltd.', 'GG_SYSTEM_BEZ' => 'Farbdopplersystem'],
    ];
}

test('webhook logs go to their own file', function () {
    expect(config('logging.channels.webhook.path'))->toEndWith('storage/logs/webhook.api.log');
});

describe('webhook endpoint', function () {
    test('it queues a sync for a valid request', function () {
        Queue::fake();

        $this->withToken('secret-token')
            ->postJson(WEBHOOK_URL, ['gguid' => strtolower(DC70), 'action' => 'geändert'])
            ->assertStatus(202);

        Queue::assertPushed(SyncUsedDevice::class, fn (SyncUsedDevice $job) => $job->guid === DC70);
    });

    test('it rejects missing and wrong tokens', function () {
        Queue::fake();

        $this->postJson(WEBHOOK_URL, ['gguid' => DC70])->assertUnauthorized();
        $this->withToken('wrong')->postJson(WEBHOOK_URL, ['gguid' => DC70])->assertUnauthorized();

        Queue::assertNothingPushed();
    });

    test('it rejects everything while no token is configured', function () {
        Queue::fake();
        config()->set('services.cas_genesis_world.webhook_bearer', '');

        $this->withToken('')->postJson(WEBHOOK_URL, ['gguid' => DC70])->assertStatus(503);

        Queue::assertNothingPushed();
    });

    test('it validates the guid and answers with json even without an Accept header', function () {
        Queue::fake();

        $this->withToken('secret-token')->post(WEBHOOK_URL, ['gguid' => 'nope'], ['Accept' => '*/*'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('gguid');

        Queue::assertNothingPushed();
    });

    test('it only accepts POST', function () {
        $this->withToken('secret-token')->get(WEBHOOK_URL)->assertStatus(405);
    });
});

describe('sync job', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    test('it stores a device fetched directly by its guid, without any list or view', function () {
        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70 => Http::response(casRecord(DC70, [
                'GG_SYSTEM_ARTIKEL' => 'DC-70 EXP X-INSIGHT',
                'GG_SYSTEM_BAUJAHR' => '2021',
                'GG_SONDE1_ARTIKEL' => 'SC6-1E', 'GG_SONDE1_BEZ' => 'Convex-Sonde',
                'GG_SONDE3_ARTIKEL' => 'Sonde 3 Artikel', 'GG_SONDE3_BEZ' => 'Sonde 3 Bezeichnung',
                'DORMEDGGPREIS' => 6900.0,
            ]), 200, ['ETag' => '"abc123"']),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70.'/dossier/full' => Http::response([
                ['fields' => ['GGUID2' => 'DOCB', 'DOCEXT' => 'PNG', 'SORTDATE' => '2026-02-01T00:00:00Z']],
                ['fields' => ['GGUID2' => 'DOCA', 'DOCEXT' => 'JPG', 'SORTDATE' => '2026-01-01T00:00:00Z']],
                ['fields' => ['GGUID2' => 'DOCP', 'DOCEXT' => 'PDF', 'SORTDATE' => '2026-01-01T00:00:00Z']],
            ]),
            'cas.test/v7.0/type/document/DOCA/file' => Http::response('a-bytes'),
            'cas.test/v7.0/type/document/DOCB/file' => Http::response('b-bytes'),
        ]);

        SyncUsedDevice::dispatchSync(DC70);

        $device = UsedDevice::where('cas_id', DC70)->sole();
        expect($device->name)->toBe('DC-70 EXP X-INSIGHT')
            ->and($device->etag)->toBe('abc123')
            ->and($device->year)->toBe(2021)
            ->and($device->probes)->toBe(['Convex-Sonde SC6-1E'])
            ->and($device->images)->toBe(['gebraucht/'.DC70.'/DOCA.jpg', 'gebraucht/'.DC70.'/DOCB.png'])
            ->and($device->getAttributes())->not->toHaveKey('price');
        Storage::disk('public')->assertExists('gebraucht/'.DC70.'/DOCA.jpg');
        Storage::disk('public')->assertMissing('gebraucht/'.DC70.'/DOCP.pdf');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/view/'));
    });

    test('it stores a brand new device that was never in any list', function () {
        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.NEW_ID => Http::response(casRecord(NEW_ID)),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.NEW_ID.'/dossier/full' => Http::response([]),
        ]);

        SyncUsedDevice::dispatchSync(NEW_ID);

        expect(UsedDevice::where('cas_id', NEW_ID)->exists())->toBeTrue();
    });

    test('it reads the year from every format CAS uses', function (string $input, ?int $year) {
        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70 => Http::response(casRecord(DC70, ['GG_SYSTEM_BAUJAHR' => $input])),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70.'/dossier/full' => Http::response([]),
        ]);

        SyncUsedDevice::dispatchSync(DC70);

        expect(UsedDevice::sole()->year)->toBe($year);
    })->with([
        ['2021', 2021],
        ['2021-01-13', 2021],
        ['07/2009', 2009],
        ['unbekannt', null],
        ['', null],
    ]);

    test('it updates an existing device and drops images that left the dossier', function () {
        Storage::disk('public')->put('gebraucht/'.DC70.'/OLD.jpg', 'old');
        UsedDevice::factory()->create(['cas_id' => DC70, 'name' => 'Alt', 'images' => ['gebraucht/'.DC70.'/OLD.jpg']]);

        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70 => Http::response(casRecord(DC70, ['GG_SYSTEM_ARTIKEL' => 'Neu'])),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70.'/dossier/full' => Http::response([
                ['fields' => ['GGUID2' => 'NEW', 'DOCEXT' => 'JPG', 'SORTDATE' => '2026-01-01T00:00:00Z']],
            ]),
            'cas.test/v7.0/type/document/NEW/file' => Http::response('new'),
        ]);

        SyncUsedDevice::dispatchSync(DC70);

        expect(UsedDevice::where('cas_id', DC70)->sole())
            ->name->toBe('Neu')
            ->images->toBe(['gebraucht/'.DC70.'/NEW.jpg']);
        Storage::disk('public')->assertMissing('gebraucht/'.DC70.'/OLD.jpg');
        Storage::disk('public')->assertExists('gebraucht/'.DC70.'/NEW.jpg');
    });

    test('it removes a device that CAS reports as deleted (404)', function () {
        Storage::disk('public')->put('gebraucht/'.DC70.'/OLD.jpg', 'old');
        UsedDevice::factory()->create(['cas_id' => DC70]);
        UsedDevice::factory()->create(['cas_id' => M6]);

        Http::fake(['cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70 => Http::response('', 404)]);

        SyncUsedDevice::dispatchSync(DC70);

        expect(UsedDevice::pluck('cas_id')->all())->toBe([M6]);
        Storage::disk('public')->assertMissing('gebraucht/'.DC70.'/OLD.jpg');
    });

    test('it removes a device that is explicitly set offline', function () {
        UsedDevice::factory()->create(['cas_id' => DC70]);
        Http::fake(['cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70 => Http::response(casRecord(DC70, ['ONLINE' => false]))]);

        SyncUsedDevice::dispatchSync(DC70);

        expect(UsedDevice::count())->toBe(0);
    });

    test('it keeps a device whose record has no ONLINE field', function () {
        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70 => Http::response(['id' => DC70, 'fields' => ['GG_SYSTEM_ARTIKEL' => 'X']]),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70.'/dossier/full' => Http::response([]),
        ]);

        SyncUsedDevice::dispatchSync(DC70);

        expect(UsedDevice::count())->toBe(1);
    });

    test('it throws on other CAS errors so the queue retries, and never deletes', function (int $status) {
        UsedDevice::factory()->create(['cas_id' => DC70]);
        Http::fake(['cas.test/*' => Http::response('', $status)]);

        expect(fn () => SyncUsedDevice::dispatchSync(DC70))->toThrow(CasRequestFailedException::class);
        expect(UsedDevice::count())->toBe(1);
    })->with([500, 503, 401]);
});

describe('used-devices:sync command', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    test('it imports the given guids', function () {
        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70 => Http::response(casRecord(DC70)),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.M6 => Http::response(casRecord(M6)),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/*/dossier/full' => Http::response([]),
        ]);

        $this->artisan('used-devices:sync', ['gguid' => [strtolower(DC70), M6]])
            ->expectsOutput('2 Gebrauchtgeräte synchronisiert.')
            ->assertSuccessful();

        expect(UsedDevice::pluck('cas_id')->sort()->values()->all())->toBe([DC70, M6]);
    });

    test('it re-syncs all known devices and removes deleted ones when no guid is given', function () {
        UsedDevice::factory()->create(['cas_id' => DC70, 'name' => 'Alt']);
        UsedDevice::factory()->create(['cas_id' => M6]);

        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.DC70 => Http::response(casRecord(DC70, ['GG_SYSTEM_ARTIKEL' => 'Neu'])),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/'.M6 => Http::response('', 404),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/*/dossier/full' => Http::response([]),
        ]);

        $this->artisan('used-devices:sync')->assertSuccessful();

        expect(UsedDevice::pluck('name', 'cas_id')->all())->toBe([DC70 => 'Neu']);
    });

    test('it fails without touching local data when CAS is unreachable', function () {
        UsedDevice::factory()->create(['cas_id' => DC70]);
        Http::fake(['cas.test/*' => Http::response('', 503)]);

        $this->artisan('used-devices:sync')->assertFailed();

        expect(UsedDevice::count())->toBe(1);
    });
});
