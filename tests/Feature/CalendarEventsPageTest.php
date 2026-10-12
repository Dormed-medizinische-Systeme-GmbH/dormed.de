<?php

use App\CalendarEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo('2026-10-12 10:00');
});

test('the events work copy lists upcoming events from the database grouped by month', function () {
    CalendarEvent::factory()->messe()->create([
        'name' => 'MEDICA 2026',
        'starts_on' => '2026-11-16',
        'ends_on' => '2026-11-19',
        'description' => 'Weltgrößte Medizinmesse.',
        'registration_url' => 'https://example.com/anmeldung',
    ]);
    CalendarEvent::factory()->create([
        'name' => 'Pränatales Symposium',
        'starts_on' => '2026-10-24',
        'ends_on' => null,
        'location' => 'Hamburg, Hamburger Team vor Ort',
    ]);

    $this->get(route('veranstaltungen2'))
        ->assertOk()
        ->assertSeeInOrder(['Oktober 2026', 'Pränatales Symposium', 'November 2026', 'MEDICA 2026'])
        ->assertSee('24. Oktober 2026 · Hamburg')
        ->assertSee('16.–19. November 2026 · Messe Düsseldorf')
        ->assertSee('Hamburg, Hamburger Team vor Ort')
        ->assertSee('data-date="2026-11-16"', false)
        ->assertSee('href="https://example.com/anmeldung"', false)
        ->assertSee('ver-tl__ev-badge--me', false);
});

test('past events are not shown and there is no past-events section', function () {
    CalendarEvent::factory()->create(['name' => 'Schon vorbei', 'starts_on' => '2026-09-01', 'ends_on' => '2026-09-02']);

    $this->get(route('veranstaltungen2'))
        ->assertOk()
        ->assertDontSee('Schon vorbei')
        ->assertDontSee('Vergangene Veranstaltungen')
        ->assertSee('Aktuell sind keine Veranstaltungen geplant');
});

test('event cards come without images', function () {
    CalendarEvent::factory()->create(['starts_on' => '2026-11-01']);

    $this->get(route('veranstaltungen2'))->assertOk()->assertDontSee('ver-tl__ev-img"', false);
});

test('a cancelled event is marked, offers no registration and is left out of the countdown', function () {
    CalendarEvent::factory()->create(['name' => 'Abgesagte Tagung', 'starts_on' => '2026-11-01', 'status' => 'cancelled']);

    $response = $this->get(route('veranstaltungen2'))->assertOk()->assertSee('· Abgesagt');

    expect($response->viewData('countdownEvents'))->toBeEmpty()
        ->and($response->getContent())->not->toContain('Zur Anmeldung<svg');
});

test('the countdown and the event JSON-LD are generated from the database', function () {
    CalendarEvent::factory()->messe()->create(['name' => 'MEDICA 2026', 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-19', 'status' => 'postponed']);

    $response = $this->get(route('veranstaltungen2'))->assertOk();

    expect($response->viewData('countdownEvents')->all())->toBe([
        ['name' => 'MEDICA 2026', 'location' => 'Messe Düsseldorf', 'date' => '2026-11-16T09:00:00'],
    ]);

    $schema = $response->viewData('eventSchema');

    expect($schema['@context'])->toBe('https://schema.org')
        ->and($schema['@graph'][0])->toMatchArray([
            '@type' => 'Event',
            'name' => 'MEDICA 2026',
            'startDate' => '2026-11-16',
            'endDate' => '2026-11-19',
            'eventStatus' => 'https://schema.org/EventPostponed',
        ])
        ->and($schema['@graph'][0]['location'])->toBe([
            '@type' => 'Place',
            'name' => 'Messe Düsseldorf',
            'address' => 'Messe Düsseldorf, Düsseldorfer Messegelände',
        ]);

    $response->assertSee('"@type": "Event"', false);
});

test('the work copy is not indexed while it is being built', function () {
    $this->get(route('veranstaltungen2'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('date ranges are written out in German', function (?string $endsOn, string $expected) {
    expect(CalendarEvent::factory()->make(['starts_on' => '2026-04-30', 'ends_on' => $endsOn])->dateRangeLabel)->toBe($expected);
})->with([
    'single day' => [null, '30. April 2026'],
    'same month' => ['2026-04-30', '30. April 2026'],
    'across months' => ['2026-05-02', '30. April – 2. Mai 2026'],
    'across years' => ['2027-01-02', '30. April 2026 – 2. Januar 2027'],
]);

test('a range within one month uses the short form', function () {
    expect(CalendarEvent::factory()->make(['starts_on' => '2026-04-18', 'ends_on' => '2026-04-19'])->dateRangeLabel)->toBe('18.–19. April 2026');
});
