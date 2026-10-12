<?php

namespace App\Http\Controllers;

use App\CalendarEvent;
use Illuminate\Contracts\View\View;

class CalendarEventController extends Controller
{
    /**
     * Work copy of /veranstaltungen fed from the calendar_events table
     * (CAS sync) instead of hard-coded markup. Only upcoming and running
     * events, no images.
     */
    public function index(): View
    {
        $events = CalendarEvent::query()->upcoming()->get();

        return view('veranstaltungen2', [
            'eventsByMonth' => $events->groupBy(fn (CalendarEvent $event): string => $event->monthLabel),
            // Hero countdown and ticker (same shape as the old hard-coded EVENTS array)
            'countdownEvents' => $events
                ->reject(fn (CalendarEvent $event): bool => $event->status === 'cancelled')
                ->map(fn (CalendarEvent $event): array => [
                    'name' => $event->name,
                    'location' => $event->locationLabel,
                    'date' => $event->starts_on->toDateString().'T09:00:00',
                ])
                ->values(),
            'eventSchema' => [
                '@context' => 'https://schema.org',
                '@graph' => $events->map(fn (CalendarEvent $event): array => $event->toSchema())->values(),
            ],
        ]);
    }
}
