<?php

namespace App\Http\Controllers;

use App\CalendarEvent;
use App\Http\Requests\EventLeadRequest;
use App\Jobs\SendEventLeadMails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Lead form for events (trade fairs etc.), reachable only via QR code.
 * Deliberately without rate limiting: many visitors share one IP on fair
 * WiFi or mobile carrier NAT, and staff may enter several leads in a row
 * on the same device. Every response carries X-Robots-Tag: noindex.
 */
class EventLeadController extends Controller
{
    public function show(string $event): Response
    {
        $eventDetails = $this->resolveEvent($event);

        return $this->noindex(response()->view('veranstaltungen.lead', [
            'slug' => $event,
            'event' => $eventDetails,
            'isAcceptingLeads' => $this->isAcceptingLeads($eventDetails),
        ]));
    }

    public function store(EventLeadRequest $request, string $event): RedirectResponse
    {
        $eventDetails = $this->resolveEvent($event);

        if (! $this->isAcceptingLeads($eventDetails)) {
            return redirect()->route('veranstaltungen.lead.show', $event);
        }

        if ($request->isHoneypotTriggered()) {
            Log::channel('single')->info('Lead-Formular: Honeypot ausgelöst, Anfrage verworfen.', ['ip' => $request->ip(), 'event' => $event]);

            return redirect()->route('veranstaltungen.lead.danke', $event);
        }

        SendEventLeadMails::dispatch(
            eventName: $eventDetails['name'],
            praxisName: $request->validated('praxis_name'),
            strasse: $request->validated('strasse'),
            plz: $request->validated('plz'),
            ort: $request->validated('ort'),
            email: $request->validated('email'),
            telefon: $request->validated('telefon'),
            interesse: $request->validated('interesse'),
            budget: $request->validated('budget'),
            nachricht: $request->validated('nachricht'),
        );

        return redirect()->route('veranstaltungen.lead.danke', $event);
    }

    public function danke(string $event): Response
    {
        return $this->noindex(response()->view('veranstaltungen.lead-danke', [
            'slug' => $event,
            'event' => $this->resolveEvent($event),
        ]));
    }

    /**
     * Events synced from CAS (calendar_events) come first; config/events.php
     * stays as fallback for QR codes printed before the sync went live.
     *
     * @return array{name: string, location: string, accepts_leads: bool}
     */
    private function resolveEvent(string $slug): array
    {
        $calendarEvent = CalendarEvent::firstWhere('slug', $slug);

        if ($calendarEvent !== null) {
            return [
                'name' => $calendarEvent->name,
                'location' => $calendarEvent->locationLabel,
                'accepts_leads' => $calendarEvent->acceptsLeads,
            ];
        }

        $eventDetails = config("events.{$slug}");

        abort_unless(is_array($eventDetails), 404);

        return [
            'name' => $eventDetails['name'],
            'location' => $eventDetails['location'],
            'accepts_leads' => now()->lte(Carbon::parse($eventDetails['accepts_leads_until'])->endOfDay()),
        ];
    }

    /**
     * @param  array{accepts_leads: bool}  $eventDetails
     */
    private function isAcceptingLeads(array $eventDetails): bool
    {
        return $eventDetails['accepts_leads'];
    }

    private function noindex(Response $response): Response
    {
        return $response->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
