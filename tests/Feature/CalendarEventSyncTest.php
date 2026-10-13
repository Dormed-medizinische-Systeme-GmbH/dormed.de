<?php

use App\CalendarEvent;
use App\Jobs\SendEventLeadMails;
use App\Jobs\SyncCalendarEvent;
use App\Services\CalendarEvents\CalendarEventSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

const MEDICA_GUID = '395FC312CD9C431596DFDACE630D70F3';

beforeEach(function () {
    config()->set('services.cas_genesis_world.webhook_bearer', 'secret-token');
    config()->set('services.cas_genesis_world.host', 'cas.test');
});

/**
 * GET /type/APPOINTMENT/{guid} as delivered by CAS (trimmed to the fields we read).
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function casAppointment(array $fields = []): array
{
    return [
        'objectType' => 'APPOINTMENT',
        'id' => MEDICA_GUID,
        'fields' => $fields + [
            'GGUID' => MEDICA_GUID,
            'GWSTYPE' => 'Veranstaltung',
            'KEYWORD' => 'Medica 2026',
            'CATEGORY' => 'Messe',
            'WHOLEDAY' => true,
            'START_DT' => '2026-11-16T00:00:00.000Z',
            'END_DT' => '2026-11-20T00:00:00.000Z',
            'GISDESCRIPTION' => 'Messe Düsseldorf, Düsseldorfer Messegelände',
            'NOTES2' => '----- Di, 25.11.2025 06:55 (A.Komp) -----',
            'UPDATETIMESTAMP' => '2026-10-12T09:45:04.903Z',
        ],
    ];
}

function fakeCasAppointment(array $fields = [], int $status = 200): void
{
    Http::fake([
        'cas.test/v7.0/type/APPOINTMENT/'.MEDICA_GUID => $status === 200
            ? Http::response(casAppointment($fields), 200, ['ETag' => '"etag-1"'])
            : Http::response([], $status),
    ]);
}

function syncMedica(): void
{
    app(CalendarEventSynchronizer::class)->sync(MEDICA_GUID);
}

describe('webhook endpoint', function () {
    test('it queues a sync for a valid request', function () {
        Queue::fake();

        $this->withToken('secret-token')
            ->postJson('/webhooks/cas/veranstaltungen', ['gguid' => '{'.strtolower(MEDICA_GUID).'}', 'action' => 'changed'])
            ->assertStatus(202);

        Queue::assertPushed(SyncCalendarEvent::class, fn (SyncCalendarEvent $job) => $job->guid === MEDICA_GUID);
    });

    test('it rejects wrong tokens and invalid guids', function () {
        Queue::fake();

        $this->withToken('wrong')->postJson('/webhooks/cas/veranstaltungen', ['gguid' => MEDICA_GUID])->assertUnauthorized();
        $this->withToken('secret-token')->postJson('/webhooks/cas/veranstaltungen', ['gguid' => 'kaputt'])->assertUnprocessable();

        Queue::assertNothingPushed();
    });
});

describe('synchronizer', function () {
    test('it imports a published appointment', function () {
        fakeCasAppointment();

        syncMedica();

        $event = CalendarEvent::sole();

        expect($event->cas_id)->toBe(MEDICA_GUID)
            ->and($event->etag)->toBe('etag-1')
            ->and($event->slug)->toBe('medica-2026')
            ->and($event->type)->toBe('messe')
            ->and($event->name)->toBe('Medica 2026')
            ->and($event->location)->toBe('Messe Düsseldorf, Düsseldorfer Messegelände')
            ->and($event->status)->toBe('scheduled')
            ->and($event->description)->toBeNull()
            ->and($event->registration_url)->toBeNull()
            ->and($event->cas_updated_at->toIso8601String())->toBe('2026-10-12T09:45:04+00:00');
    });

    test('a whole-day appointment ends the day before END_DT', function () {
        fakeCasAppointment();

        syncMedica();

        expect(CalendarEvent::sole()->starts_on->toDateString())->toBe('2026-11-16')
            ->and(CalendarEvent::sole()->ends_on->toDateString())->toBe('2026-11-19');
    });

    test('dates are derived correctly', function (string $start, ?string $end, bool $wholeDay, string $startsOn, ?string $endsOn) {
        expect(CalendarEventSynchronizer::dates($start, $end, $wholeDay))->toBe([$startsOn, $endsOn]);
    })->with([
        'single whole day' => ['2026-05-09T00:00:00.000Z', '2026-05-10T00:00:00.000Z', true, '2026-05-09', null],
        'timed, same day' => ['2026-04-18T07:00:00.000Z', '2026-04-18T16:00:00.000Z', false, '2026-04-18', null],
        'timed over two days' => ['2026-04-24T07:00:00.000Z', '2026-04-25T15:00:00.000Z', false, '2026-04-24', '2026-04-25'],
        'timed late evening in Berlin' => ['2026-04-17T22:30:00.000Z', null, false, '2026-04-18', null],
    ]);

    test('internal notes are never imported', function () {
        fakeCasAppointment();

        syncMedica();

        expect(CalendarEvent::sole()->toArray())->not->toContain('----- Di, 25.11.2025 06:55 (A.Komp) -----');
    });

    test('an update keeps the slug printed on QR codes', function () {
        // Http::fake() stubs don't override each other, so both answers go in one sequence.
        Http::fake([
            'cas.test/v7.0/type/APPOINTMENT/'.MEDICA_GUID => Http::sequence()
                ->push(casAppointment(), 200, ['ETag' => '"etag-1"'])
                ->push(casAppointment(['KEYWORD' => 'MEDICA 2026 Düsseldorf']), 200, ['ETag' => '"etag-2"']),
        ]);

        syncMedica();
        syncMedica();

        expect(CalendarEvent::sole()->name)->toBe('MEDICA 2026 Düsseldorf')
            ->and(CalendarEvent::sole()->slug)->toBe('medica-2026');
    });

    test('a taken slug gets a suffix', function () {
        CalendarEvent::factory()->create(['slug' => 'medica-2026']);
        fakeCasAppointment();

        syncMedica();

        expect(CalendarEvent::where('cas_id', MEDICA_GUID)->sole()->slug)->toBe('medica-2026-2');
    });

    test('the CAS category sets the event type', function () {
        fakeCasAppointment(['CATEGORY' => 'Fachtagung']);

        syncMedica();

        expect(CalendarEvent::sole()->type)->toBe('fachtagung');
    });

    test('appointments outside the published categories are removed', function () {
        CalendarEvent::factory()->create(['cas_id' => MEDICA_GUID]);
        fakeCasAppointment(['CATEGORY' => 'Geburtstag']);

        syncMedica();

        expect(CalendarEvent::count())->toBe(0);
    });

    test('appointments that are not of type Veranstaltung are removed', function (?string $gwtype) {
        CalendarEvent::factory()->create(['cas_id' => MEDICA_GUID]);
        fakeCasAppointment(['GWSTYPE' => $gwtype]);

        syncMedica();

        expect(CalendarEvent::count())->toBe(0);
    })->with(['Besprechung', null]);

    test('appointments deleted in CAS are removed', function () {
        CalendarEvent::factory()->create(['cas_id' => MEDICA_GUID]);
        fakeCasAppointment(status: 404);

        syncMedica();

        expect(CalendarEvent::count())->toBe(0);
    });

    test('a failing CAS request keeps local data and throws for the retry', function () {
        CalendarEvent::factory()->create(['cas_id' => MEDICA_GUID]);
        fakeCasAppointment(status: 500);

        expect(fn () => syncMedica())->toThrow(App\Services\Cas\CasRequestFailedException::class)
            ->and(CalendarEvent::count())->toBe(1);
    });

    test('description, registration link and status come from the configured fields', function () {
        config()->set('calendar_events.fields', ['description' => 'WEB_TEXT', 'registration_url' => 'WEB_URL', 'status' => 'WEB_STATUS']);
        config()->set('calendar_events.statuses', ['Abgesagt' => 'cancelled']);
        fakeCasAppointment(['WEB_TEXT' => ' Weltgrößte Medizinmesse. ', 'WEB_URL' => 'https://medica.de', 'WEB_STATUS' => 'Abgesagt']);

        syncMedica();

        expect(CalendarEvent::sole()->only(['description', 'registration_url', 'status']))->toBe([
            'description' => 'Weltgrößte Medizinmesse.',
            'registration_url' => 'https://medica.de',
            'status' => 'cancelled',
        ]);
    });

    test('the CAS status values map to the page status', function (string $casValue, string $status) {
        config()->set('calendar_events.fields.status', 'WEB_STATUS');
        fakeCasAppointment(['WEB_STATUS' => $casValue]);

        syncMedica();

        expect(CalendarEvent::sole()->status)->toBe($status);
    })->with([
        ['Geplant', 'scheduled'],
        ['Verschoben', 'postponed'],
        ['Abgesagt', 'cancelled'],
        ['Online', 'moved_online'],
        ['', 'scheduled'],
    ]);

    test('only http links are used for the registration button', function () {
        config()->set('calendar_events.fields.registration_url', 'WEB_URL');
        fakeCasAppointment(['WEB_URL' => 'javascript:alert(1)']);

        syncMedica();

        expect(CalendarEvent::sole()->registration_url)->toBeNull();
    });

    test('the queued job runs the synchronizer', function () {
        fakeCasAppointment();

        (new SyncCalendarEvent(MEDICA_GUID))->handle(app(CalendarEventSynchronizer::class));

        expect(CalendarEvent::count())->toBe(1);
    });
});

test('the sync command re-syncs all known events', function () {
    CalendarEvent::factory()->create(['cas_id' => MEDICA_GUID]);
    fakeCasAppointment();

    $this->artisan('calendar-events:sync')->expectsOutput('1 Veranstaltungen synchronisiert.')->assertSuccessful();

    expect(CalendarEvent::sole()->name)->toBe('Medica 2026');
});

test('the lead form uses the synced event and its three-month window', function () {
    Bus::fake();
    fakeCasAppointment();
    syncMedica();

    $this->travelTo('2027-02-19 12:00');
    $this->get('/veranstaltungen/medica-2026/kontakt')->assertOk()->assertSee('Medica 2026 · Messe Düsseldorf')->assertSee('name="praxis_name"', false);

    $this->post('/veranstaltungen/medica-2026/kontakt', ['praxis_name' => 'Praxis Test', 'email' => 'test@example.com', 'datenschutz' => 'ja'])
        ->assertRedirect(route('veranstaltungen.lead.danke', 'medica-2026'));
    Bus::assertDispatched(SendEventLeadMails::class, fn (SendEventLeadMails $job) => $job->eventName === 'Medica 2026');

    $this->travelTo('2027-02-20 12:00');
    $this->get('/veranstaltungen/medica-2026/kontakt')->assertOk()->assertDontSee('name="praxis_name"', false);
});

test('appointments without keyword or start date are removed', function () {
    CalendarEvent::factory()->create(['cas_id' => MEDICA_GUID]);
    fakeCasAppointment(['KEYWORD' => '']);

    syncMedica();

    expect(CalendarEvent::count())->toBe(0);
});

test('the sync command imports given guids and reports CAS failures', function () {
    fakeCasAppointment();

    $this->artisan('calendar-events:sync', ['gguid' => [strtolower(MEDICA_GUID)]])
        ->expectsOutput('1 Veranstaltungen synchronisiert.')
        ->assertSuccessful();

    expect(CalendarEvent::sole()->cas_id)->toBe(MEDICA_GUID);
});

test('the sync command fails when CAS is unreachable', function () {
    fakeCasAppointment(status: 500);

    $this->artisan('calendar-events:sync', ['gguid' => [MEDICA_GUID]])->assertFailed();
});

test('the sync job is unique per event and retries through the CAS reboot window', function () {
    $this->travelTo('2026-10-12 02:00');
    $job = new SyncCalendarEvent(MEDICA_GUID);

    expect($job->uniqueId())->toBe(MEDICA_GUID)
        ->and($job->backoff())->toBe([60, 120, 300, 600])
        ->and($job->retryUntil()->format('Y-m-d H:i'))->toBe('2026-10-12 05:00');
});

test('a permanently failed sync job is logged', function () {
    Illuminate\Support\Facades\Log::shouldReceive('channel')->with('webhook')->andReturnSelf();
    Illuminate\Support\Facades\Log::shouldReceive('error')->once()->with('SyncCalendarEvent permanently failed.', ['gguid' => MEDICA_GUID, 'message' => 'CAS down']);

    (new SyncCalendarEvent(MEDICA_GUID))->failed(new RuntimeException('CAS down'));
});
