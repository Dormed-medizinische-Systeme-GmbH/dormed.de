<?php

namespace App;

use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Local copy of one CAS event record for /veranstaltungen. Named
 * CalendarEvent rather than Event to stay clear of Laravel's Event facade.
 * Kept flat like UsedDevice. No image: the CAS API can't deliver one
 * properly, so the page shows events without pictures.
 *
 * @property-read string $typeLabel
 * @property-read string $typeShort
 * @property-read string $dateRangeLabel
 * @property-read string $monthLabel
 * @property-read string $locationLabel
 * @property-read string|null $statusLabel
 * @property-read string $registrationLink
 * @property-read string $schemaStatus
 * @property-read bool $isPast
 * @property-read Carbon $leadsUntil
 * @property-read bool $acceptsLeads
 * @property-read bool $hasConfirmedDate
 */
#[Fillable([
    'cas_id', 'etag', 'cas_updated_at', 'synced_at', 'slug', 'type', 'name',
    'starts_on', 'ends_on', 'status', 'location', 'description', 'registration_url',
])]
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    public const TYPES = [
        'fachtagung' => 'Fachtagung',
        'messe' => 'Messe',
    ];

    /**
     * CAS status -> schema.org eventStatus (the values Google's event rich
     * results understand). Unknown CAS values fall back to "scheduled".
     */
    public const STATUSES = [
        'scheduled' => 'https://schema.org/EventScheduled',
        'postponed' => 'https://schema.org/EventPostponed',
        'cancelled' => 'https://schema.org/EventCancelled',
        'moved_online' => 'https://schema.org/EventMovedOnline',
    ];

    /**
     * Short CSS modifier per type, as used by the existing timeline styles
     * (ver-tl__ev-accent--ft, ver-tl__ev-badge--me, ...).
     */
    public const TYPE_SHORTS = [
        'fachtagung' => 'ft',
        'messe' => 'me',
    ];

    /**
     * Labels shown next to the date when an event isn't simply scheduled.
     */
    public const STATUS_LABELS = [
        'postponed' => 'Verschoben, neuer Termin folgt',
        'cancelled' => 'Abgesagt',
        'moved_online' => 'Online',
    ];

    /**
     * German month names, independent of the app locale.
     */
    public const MONTHS = [
        1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
    ];

    /**
     * The QR lead form stays open this many months after the event's last
     * day, then closes on its own - no per-event date to maintain.
     */
    public const LEAD_WINDOW_MONTHS = 3;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'cas_updated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::TYPES[$this->type] ?? ucfirst($this->type));
    }


    /**
     * Past once the last day (or the only day) is over - moves the event
     * into the "Vergangene Veranstaltungen" list.
     *
     * @return Attribute<bool, never>
     */
    protected function isPast(): Attribute
    {
        return Attribute::get(fn (): bool => ($this->ends_on ?? $this->starts_on)->endOfDay()->isPast());
    }

    /**
     * Only upcoming events are shown; past ones stay in the table until CAS
     * drops them, so the lead form keeps working after the event. A
     * postponed event keeps its original date until a new one is set, so it
     * stays listed even once that date has passed.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereRaw('coalesce(ends_on, starts_on) >= ?', [now()->toDateString()])
            ->orWhere('status', 'postponed'))
            ->orderBy('starts_on');
    }

    /**
     * False for postponed and cancelled events: their stored date is not
     * going to happen, so nothing counts down to it.
     *
     * @return Attribute<bool, never>
     */
    protected function hasConfirmedDate(): Attribute
    {
        return Attribute::get(fn (): bool => ! in_array($this->status, ['postponed', 'cancelled'], true));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function schemaStatus(): Attribute
    {
        return Attribute::get(fn (): string => self::STATUSES[$this->status] ?? self::STATUSES['scheduled']);
    }

    /**
     * @return Attribute<Carbon, never>
     */
    protected function leadsUntil(): Attribute
    {
        return Attribute::get(fn (): Carbon => ($this->ends_on ?? $this->starts_on)->copy()->addMonthsNoOverflow(self::LEAD_WINDOW_MONTHS)->endOfDay());
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function acceptsLeads(): Attribute
    {
        return Attribute::get(fn (): bool => $this->status !== 'cancelled' && now()->lte($this->leadsUntil));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function typeShort(): Attribute
    {
        return Attribute::get(fn (): string => self::TYPE_SHORTS[$this->type] ?? 'st');
    }

    /**
     * "09. Mai 2026", "18.–19. April 2026", "30. April – 2. Mai 2026",
     * "29. Dezember 2026 – 2. Januar 2027".
     *
     * @return Attribute<string, never>
     */
    protected function dateRangeLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $start = $this->starts_on;
            $end = $this->ends_on;

            if ($end === null || $end->isSameDay($start)) {
                return $start->format('d').'. '.self::MONTHS[$start->month].' '.$start->year;
            }

            if ($start->year !== $end->year) {
                return $start->format('j').'. '.self::MONTHS[$start->month].' '.$start->year.' – '.$end->format('j').'. '.self::MONTHS[$end->month].' '.$end->year;
            }

            if ($start->month !== $end->month) {
                return $start->format('j').'. '.self::MONTHS[$start->month].' – '.$end->format('j').'. '.self::MONTHS[$end->month].' '.$end->year;
            }

            return $start->format('j').'.–'.$end->format('j').'. '.self::MONTHS[$start->month].' '.$start->year;
        });
    }

    /**
     * Timeline group heading: "April 2026".
     *
     * @return Attribute<string, never>
     */
    protected function monthLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::MONTHS[$this->starts_on->month].' '.$this->starts_on->year);
    }

    /**
     * Short location for the meta line and the countdown: the free-text
     * location up to the first comma ("Messe Düsseldorf, Düsseldorfer
     * Messegelände" -> "Messe Düsseldorf"). The expanded card shows the
     * full text.
     *
     * @return Attribute<string, never>
     */
    protected function locationLabel(): Attribute
    {
        return Attribute::get(fn (): string => trim(str($this->location)->before(',')->toString()));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => self::STATUS_LABELS[$this->status] ?? null);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function registrationLink(): Attribute
    {
        return Attribute::get(fn (): string => $this->registration_url ?: '/kontakt');
    }

    /**
     * schema.org Event for the page's JSON-LD block.
     *
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return array_filter([
            '@type' => 'Event',
            'name' => $this->name,
            'startDate' => $this->starts_on->toDateString(),
            'endDate' => ($this->ends_on ?? $this->starts_on)->toDateString(),
            'eventStatus' => $this->schemaStatus,
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            // CAS only has free text for the location, so the address is
            // passed as text (schema.org allows Text for Place.address).
            'location' => [
                '@type' => 'Place',
                'name' => $this->locationLabel,
                'address' => $this->location,
            ],
            'organizer' => ['@id' => 'https://dormed.de/#organization'],
            'description' => $this->description,
            'url' => 'https://dormed.de/veranstaltungen',
        ], fn (mixed $value): bool => $value !== null);
    }
}
