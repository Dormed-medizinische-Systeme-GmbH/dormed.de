<?php

use App\CalendarEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an event stores everything the events page renders', function () {
    $event = CalendarEvent::factory()->messe()->create([
        'slug' => 'medica-2026',
        'name' => 'MEDICA 2026',
        'starts_on' => '2026-11-16',
        'ends_on' => '2026-11-19',
    ])->fresh();

    expect($event->typeLabel)->toBe('Messe')
        ->and($event->name)->toBe('MEDICA 2026')
        ->and($event->location)->toBe('Messe Düsseldorf, Düsseldorfer Messegelände')
        ->and($event->locationLabel)->toBe('Messe Düsseldorf')
        ->and($event->starts_on->toDateString())->toBe('2026-11-16')
        ->and($event->ends_on->toDateString())->toBe('2026-11-19')
        ->and($event->status)->toBe('scheduled');
});

test('the short location is the free text up to the first comma', function (string $location, string $expected) {
    expect(CalendarEvent::factory()->make(['location' => $location])->locationLabel)->toBe($expected);
})->with([
    ['Messe Düsseldorf, Düsseldorfer Messegelände', 'Messe Düsseldorf'],
    ['Travemünde', 'Travemünde'],
]);

test('an event is past once its last day is over', function () {
    $this->travelTo('2026-11-19 23:00');

    $medica = CalendarEvent::factory()->make(['starts_on' => '2026-11-16', 'ends_on' => '2026-11-19']);
    $oneDay = CalendarEvent::factory()->make(['starts_on' => '2026-11-18', 'ends_on' => null]);

    expect($medica->isPast)->toBeFalse()
        ->and($oneDay->isPast)->toBeTrue()
        ->and(CalendarEvent::factory()->past()->make()->isPast)->toBeTrue();
});

test('cas id and slug are unique', function (string $column) {
    CalendarEvent::factory()->create([$column => 'DOPPELT']);

    expect(fn () => CalendarEvent::factory()->create([$column => 'DOPPELT']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
})->with(['cas_id', 'slug']);

test('the lead form stays open for three months after the last day', function () {
    $medica = CalendarEvent::factory()->make(['starts_on' => '2026-11-16', 'ends_on' => '2026-11-19']);

    expect($medica->leadsUntil->toDateTimeString())->toBe('2027-02-19 23:59:59');

    $this->travelTo('2027-02-19 23:00');
    expect($medica->acceptsLeads)->toBeTrue();

    $this->travelTo('2027-02-20 00:01');
    expect($medica->acceptsLeads)->toBeFalse();
});

test('a cancelled event accepts no leads', function () {
    $this->travelTo('2026-11-01');

    expect(CalendarEvent::factory()->make(['starts_on' => '2026-11-16', 'status' => 'cancelled'])->acceptsLeads)->toBeFalse();
});

test('the cas status maps to the schema.org event status', function (string $status, string $expected) {
    expect(CalendarEvent::factory()->make(['status' => $status])->schemaStatus)->toBe($expected);
})->with([
    ['scheduled', 'https://schema.org/EventScheduled'],
    ['rescheduled', 'https://schema.org/EventRescheduled'],
    ['postponed', 'https://schema.org/EventPostponed'],
    ['cancelled', 'https://schema.org/EventCancelled'],
    ['moved_online', 'https://schema.org/EventMovedOnline'],
    ['unbekannt', 'https://schema.org/EventScheduled'],
]);

test('only upcoming events are listed, ordered by start date', function () {
    $this->travelTo('2026-11-18');

    CalendarEvent::factory()->create(['name' => 'Vorbei', 'starts_on' => '2026-11-10', 'ends_on' => '2026-11-11']);
    CalendarEvent::factory()->create(['name' => 'Später', 'starts_on' => '2027-03-01', 'ends_on' => null]);
    CalendarEvent::factory()->create(['name' => 'Läuft noch', 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-19']);
    CalendarEvent::factory()->create(['name' => 'Heute', 'starts_on' => '2026-11-18', 'ends_on' => null]);

    expect(CalendarEvent::upcoming()->pluck('name')->all())->toBe(['Läuft noch', 'Heute', 'Später']);
});
