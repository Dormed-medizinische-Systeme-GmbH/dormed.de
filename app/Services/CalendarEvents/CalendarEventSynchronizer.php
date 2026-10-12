<?php

namespace App\Services\CalendarEvents;

use App\CalendarEvent;
use App\Services\Cas\CasClient;
use App\Services\Cas\CasRequestFailedException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors single CAS APPOINTMENT records into the calendar_events table,
 * same contract as UsedDeviceSynchronizer: the record is fetched by GUID;
 * found and published -> stored, deleted in CAS (404) or not (or no
 * longer) in a published category -> removed locally. Any other CAS
 * failure throws and leaves local data untouched, so the queue retries.
 * Field mapping and the published categories live in
 * config/calendar_events.php.
 */
class CalendarEventSynchronizer
{
    private const TIMEZONE = 'Europe/Berlin';

    public function __construct(private readonly CasClient $cas) {}

    /**
     * @throws CasRequestFailedException
     */
    public function sync(string $guid): void
    {
        $result = $this->cas->getDataObject((string) config('calendar_events.data_object_type'), $guid);

        if ($result === null) {
            $this->remove($guid, 'deleted in CAS');

            return;
        }

        $fields = $result['record']['fields'];
        $type = config('calendar_events.categories')[$fields['CATEGORY'] ?? ''] ?? null;

        if ($type === null) {
            $this->remove($guid, 'category not published: '.($fields['CATEGORY'] ?? '-'));

            return;
        }

        if (blank($fields['KEYWORD'] ?? null) || blank($fields['START_DT'] ?? null)) {
            $this->remove($guid, 'keyword or start date missing');

            return;
        }

        $this->store($guid, $type, $fields, $result['etag']);
    }

    /**
     * Re-sync every event known locally. Returns how many were processed.
     *
     * @throws CasRequestFailedException
     */
    public function syncKnown(): int
    {
        $guids = CalendarEvent::query()->pluck('cas_id');

        $guids->each(fn (string $guid) => $this->sync($guid));

        return $guids->count();
    }

    /**
     * Whole-day appointments run from START_DT up to, but not including,
     * END_DT (MEDICA 16.-19.11. arrives as START 16.11. 00:00, END 20.11.
     * 00:00), so the last day is the day before END_DT.
     *
     * @return array{0: string, 1: string|null}
     */
    public static function dates(string $start, ?string $end, bool $wholeDay): array
    {
        $startsOn = Carbon::parse($start)->setTimezone(self::TIMEZONE);
        $endsOn = filled($end) ? Carbon::parse($end)->setTimezone(self::TIMEZONE) : null;

        if ($wholeDay) {
            // Whole-day values are midnight UTC; the UTC date is the day meant.
            $startsOn = Carbon::parse($start)->utc();
            $endsOn = $endsOn !== null ? Carbon::parse($end)->utc()->subDay() : null;
        }

        if ($endsOn !== null && $endsOn->toDateString() <= $startsOn->toDateString()) {
            $endsOn = null;
        }

        return [$startsOn->toDateString(), $endsOn?->toDateString()];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function store(string $guid, string $type, array $fields, ?string $etag): void
    {
        [$startsOn, $endsOn] = self::dates((string) $fields['START_DT'], $fields['END_DT'] ?? null, (bool) ($fields['WHOLEDAY'] ?? false));

        $event = CalendarEvent::firstOrNew(['cas_id' => $guid]);

        // The slug is printed on QR codes, so it is set once and never follows later renames.
        if (! $event->exists) {
            $event->slug = $this->uniqueSlug((string) $fields['KEYWORD']);
        }

        $event->fill([
            'etag' => $etag,
            'type' => $type,
            'name' => trim((string) $fields['KEYWORD']),
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'status' => $this->status($fields),
            'location' => trim((string) ($fields['GISDESCRIPTION'] ?? '')),
            'description' => $this->optionalField($fields, 'description'),
            'registration_url' => $this->registrationUrl($fields),
            'cas_updated_at' => $fields['UPDATETIMESTAMP'] ?? null,
            'synced_at' => now(),
        ])->save();

        Log::channel('webhook')->info('Event synced.', [
            'gguid' => $guid,
            'name' => $event->name,
            'slug' => $event->slug,
            'created' => $event->wasRecentlyCreated,
        ]);
    }

    private function remove(string $guid, string $reason): void
    {
        $deleted = CalendarEvent::where('cas_id', $guid)->delete();

        Log::channel('webhook')->info("Event removed locally ({$reason}).", ['gguid' => $guid, 'hadRecord' => $deleted > 0]);
    }

    private function uniqueSlug(string $keyword): string
    {
        $base = str($keyword)->slug()->toString() ?: 'veranstaltung';
        $slug = $base;

        for ($suffix = 2; CalendarEvent::where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function status(array $fields): string
    {
        $value = $this->optionalField($fields, 'status');

        return config('calendar_events.statuses')[$value] ?? 'scheduled';
    }

    /**
     * Only http(s) links end up behind the "Zur Anmeldung" button.
     *
     * @param  array<string, mixed>  $fields
     */
    private function registrationUrl(array $fields): ?string
    {
        $url = $this->optionalField($fields, 'registration_url');

        return $url !== null && preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function optionalField(array $fields, string $key): ?string
    {
        $casField = config("calendar_events.fields.{$key}");

        if ($casField === null) {
            return null;
        }

        $value = trim((string) ($fields[$casField] ?? ''));

        return $value !== '' ? $value : null;
    }
}
