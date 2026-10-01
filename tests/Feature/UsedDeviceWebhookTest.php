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

beforeEach(function () {
    config()->set('services.cas_genesis_world.webhook_bearer', 'secret-token');
    config()->set('services.cas_genesis_world.host', 'cas.test');
});

function casRecord(string $id, array $fields = []): array
{
    return [
        'id' => $id,
        'ETAG' => 'etag-'.$id,
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

    test('it stores a new online device with probes and images', function () {
        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/view/*' => Http::response([
                casRecord(DC70, [
                    'GG_SYSTEM_ARTIKEL' => 'DC-70 EXP X-INSIGHT',
                    'GG_SYSTEM_BAUJAHR' => '2021',
                    'GG_SONDE1_ARTIKEL' => 'SC6-1E', 'GG_SONDE1_BEZ' => 'Convex-Sonde',
                    'GG_SONDE3_ARTIKEL' => 'Sonde 3 Artikel', 'GG_SONDE3_BEZ' => 'Sonde 3 Bezeichnung',
                    'DORMEDGGPREIS' => 6900.0,
                ]),
                casRecord(M6),
            ]),
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
            ->and($device->year)->toBe(2021)
            ->and($device->probes)->toBe(['Convex-Sonde SC6-1E'])
            ->and($device->images)->toBe(['gebraucht/'.DC70.'/DOCA.jpg', 'gebraucht/'.DC70.'/DOCB.png'])
            ->and(UsedDevice::count())->toBe(1);
        Storage::disk('public')->assertExists('gebraucht/'.DC70.'/DOCA.jpg');
        Storage::disk('public')->assertMissing('gebraucht/'.DC70.'/DOCP.pdf');
        expect($device->getAttributes())->not->toHaveKey('price');
    });

    test('it updates an existing device and drops images that left the dossier', function () {
        Storage::disk('public')->put('gebraucht/'.DC70.'/OLD.jpg', 'old');
        UsedDevice::factory()->create(['cas_id' => DC70, 'name' => 'Alt', 'images' => ['gebraucht/'.DC70.'/OLD.jpg']]);

        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/view/*' => Http::response([casRecord(DC70, ['GG_SYSTEM_ARTIKEL' => 'Neu'])]),
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

    test('it removes a device that is no longer in the online view', function () {
        Storage::disk('public')->put('gebraucht/'.DC70.'/OLD.jpg', 'old');
        UsedDevice::factory()->create(['cas_id' => DC70]);
        UsedDevice::factory()->create(['cas_id' => M6]);

        Http::fake(['cas.test/v7.0/type/GEBRAUCHTGERAETE/view/*' => Http::response([
            casRecord(DC70, ['ONLINE' => false]),
            casRecord(M6),
        ])]);

        SyncUsedDevice::dispatchSync(DC70);

        expect(UsedDevice::pluck('cas_id')->all())->toBe([M6]);
        Storage::disk('public')->assertMissing('gebraucht/'.DC70.'/OLD.jpg');
    });

    test('it throws when CAS is unreachable so the queue retries and keeps local data', function () {
        UsedDevice::factory()->create(['cas_id' => DC70]);
        Http::fake(['cas.test/*' => Http::response('', 503)]);

        expect(fn () => SyncUsedDevice::dispatchSync(DC70))->toThrow(CasRequestFailedException::class);
        expect(UsedDevice::count())->toBe(1);
    });
});

describe('used-devices:sync command', function () {
    test('it imports online devices and removes stale ones', function () {
        Storage::fake('public');
        UsedDevice::factory()->create(['cas_id' => 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF']);

        Http::fake([
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/view/*' => Http::response([casRecord(DC70), casRecord(M6)]),
            'cas.test/v7.0/type/GEBRAUCHTGERAETE/*/dossier/full' => Http::response([]),
        ]);

        $this->artisan('used-devices:sync')->expectsOutput('2 Gebrauchtgeräte synchronisiert.')->assertSuccessful();

        expect(UsedDevice::pluck('cas_id')->sort()->values()->all())->toBe([DC70, M6]);
    });

    test('it fails without touching local data when CAS is unreachable', function () {
        UsedDevice::factory()->create();
        Http::fake(['cas.test/*' => Http::response('', 503)]);

        $this->artisan('used-devices:sync')->assertFailed();

        expect(UsedDevice::count())->toBe(1);
    });
});
