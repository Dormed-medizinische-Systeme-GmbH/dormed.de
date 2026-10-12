<?php

namespace App;

use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Local copy of one CAS event record for /veranstaltungen. Named
 * CalendarEvent rather than Event to stay clear of Laravel's Event facade.
 * Kept flat like UsedDevice; the image lives on the public disk under
 * events/{cas_id}/ and is referenced by relative path.
 *
 * @property-read string $typeLabel
 * @property-read string $displayTitle
 * @property-read bool $isPast
 * @property-read string|null $imageUrl
 */
#[Fillable([
    'cas_id', 'etag', 'cas_updated_at', 'synced_at', 'slug', 'type', 'name', 'title',
    'starts_on', 'ends_on', 'venue', 'city', 'location_note', 'description',
    'registration_url', 'image', 'image_alt', 'leads_until',
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'leads_until' => 'date',
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
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? '/storage/'.$this->image : null);
    }
}
