<?php

use App\CalendarEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an event stores everything the events page renders', function () {
    $event = CalendarEvent::factory()->messe()->create([
        'slug' => 'medica-2026',
        'name' => 'MEDICA 2026',
        'title' => 'MEDICA 2026 — Weltgrößte Medizinmesse',
        'starts_on' => '2026-11-16',
        'ends_on' => '2026-11-19',
        'image' => 'events/ABC/medica.png',
        'image_alt' => 'MEDICA 2026 — Weltgrößte Medizinmesse Düsseldorf',
        'leads_until' => '2026-12-31',
    ])->fresh();

    expect($event->typeLabel)->toBe('Messe')
        ->and($event->displayTitle)->toBe('MEDICA 2026 — Weltgrößte Medizinmesse')
        ->and($event->venue)->toBe('Messe Düsseldorf')
        ->and($event->starts_on->toDateString())->toBe('2026-11-16')
        ->and($event->ends_on->toDateString())->toBe('2026-11-19')
        ->and($event->leads_until->toDateString())->toBe('2026-12-31')
        ->and($event->imageUrl)->toBe('/storage/events/ABC/medica.png');
});

test('the display title falls back to the name', function () {
    expect(CalendarEvent::factory()->make(['name' => 'MEDICA 2026', 'title' => null])->displayTitle)->toBe('MEDICA 2026');
});

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
