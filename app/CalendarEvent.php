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
 * Kept flat like UsedDevice; the image lives on the public disk under
 * events/{cas_id}/ and is referenced by relative path.
 *
 * @property-read string $typeLabel
 * @property-read string $displayTitle
 * @property-read string $schemaStatus
 * @property-read bool $isPast
 * @property-read Carbon $leadsUntil
 * @property-read bool $acceptsLeads
 * @property-read string|null $imageUrl
 */
#[Fillable([
    'cas_id', 'etag', 'cas_updated_at', 'synced_at', 'slug', 'type', 'name', 'title',
    'starts_on', 'ends_on', 'status', 'venue', 'city', 'location_note', 'description',
    'registration_url', 'image', 'image_alt',
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
        'rescheduled' => 'https://schema.org/EventRescheduled',
        'postponed' => 'https://schema.org/EventPostponed',
        'cancelled' => 'https://schema.org/EventCancelled',
        'moved_online' => 'https://schema.org/EventMovedOnline',
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
     * @return Attribute<string, never>
     */
    protected function displayTitle(): Attribute
    {
        return Attribute::get(fn (): string => $this->title ?: $this->name);
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
     * drops them, so the lead form keeps working after the event.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->whereRaw('coalesce(ends_on, starts_on) >= ?', [now()->toDateString()])
            ->orderBy('starts_on');
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
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? '/storage/'.$this->image : null);
    }
}
