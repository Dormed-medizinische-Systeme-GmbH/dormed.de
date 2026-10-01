<?php

namespace App\Services\UsedDevices;

use App\Services\Cas\CasClient;
use App\Services\Cas\CasRequestFailedException;
use App\UsedDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Mirrors single CAS "GEBRAUCHTGERAETE" records into the local used_devices
 * table and the public image store. The record is fetched directly by its
 * GUID (no list/view involved): found -> stored, deleted in CAS (404) or
 * explicitly ONLINE=false -> removed locally. Any other CAS failure throws
 * and leaves local data untouched, so the queue retries.
 */
class UsedDeviceSynchronizer
{
    private const DATA_OBJECT_TYPE = 'GEBRAUCHTGERAETE';

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    private const IMAGE_DIRECTORY = 'gebraucht';

    public function __construct(private readonly CasClient $cas) {}

    /**
     * CAS may send the GUID in any case and with braces or dashes.
     */
    public static function normalizeGuid(string $guid): string
    {
        return strtoupper(str_replace(['{', '}', '-'], '', trim($guid)));
    }

    /**
     * @throws CasRequestFailedException
     */
    public function sync(string $guid): void
    {
        $result = $this->cas->getDataObject(self::DATA_OBJECT_TYPE, $guid);

        if ($result === null || ($result['record']['fields']['ONLINE'] ?? true) === false) {
            $this->remove($guid, $result === null ? 'deleted in CAS' : 'set offline in CAS');

            return;
        }

        $this->store($guid, $result['record'], $result['etag']);
    }

    /**
     * Re-sync every device known locally. Returns how many were processed.
     *
     * @throws CasRequestFailedException
     */
    public function syncKnown(): int
    {
        $guids = UsedDevice::query()->pluck('cas_id');

        $guids->each(fn (string $guid) => $this->sync($guid));

        return $guids->count();
    }

    /**
     * Images first, row second: a failed download throws before the row is
     * touched, so the retry starts from the same state.
     *
     * @param  array<string, mixed>  $record
     *
     * @throws CasRequestFailedException
     */
    private function store(string $guid, array $record, ?string $etag): void
    {
        $fields = $record['fields'];
        $images = $this->syncImages($guid);

        $device = UsedDevice::updateOrCreate(['cas_id' => $guid], [
            'etag' => $etag,
            'name' => (string) ($fields['GG_SYSTEM_ARTIKEL'] ?? 'Ultraschallgerät'),
            'manufacturer' => $fields['GG_SYSTEM_HERSTELLER'] ?? null,
            'description' => $fields['GG_SYSTEM_BEZ'] ?? null,
            'year' => $this->year((string) ($fields['GG_SYSTEM_BAUJAHR'] ?? '')),
            'probes' => $this->probes($fields),
            'images' => $images,
            'cas_updated_at' => $fields['UPDATETIMESTAMP'] ?? null,
            'synced_at' => now(),
        ]);

        Log::channel('webhook')->info('Used device synced.', [
            'gguid' => $guid,
            'name' => $device->name,
            'images' => count($images),
            'created' => $device->wasRecentlyCreated,
        ]);
    }

    private function remove(string $guid, string $reason): void
    {
        $deleted = UsedDevice::where('cas_id', $guid)->delete();
        Storage::disk('public')->deleteDirectory(self::IMAGE_DIRECTORY."/{$guid}");

        Log::channel('webhook')->info("Used device removed locally ({$reason}).", ['gguid' => $guid, 'hadRecord' => $deleted > 0]);
    }

    /**
     * CAS stores the year free-form ("2021", "2021-01-13", "07/2009"), so
     * the four-digit year is picked out of whatever was typed.
     */
    private function year(string $value): ?int
    {
        return preg_match('/\b(19|20)\d{2}\b/', $value, $match) === 1 ? (int) $match[0] : null;
    }

    /**
     * Probe slots 1-4 ("Convex-Sonde SC6-1E"). CAS fills unused slots with
     * the placeholder texts "Sonde N ...", which are skipped.
     *
     * @param  array<string, mixed>  $fields
     * @return list<string>
     */
    private function probes(array $fields): array
    {
        $probes = [];

        foreach (range(1, 4) as $slot) {
            $description = trim((string) ($fields["GG_SONDE{$slot}_BEZ"] ?? ''));
            $article = trim((string) ($fields["GG_SONDE{$slot}_ARTIKEL"] ?? ''));

            if ($description === '' || str_starts_with($description, "Sonde {$slot} ")) {
                continue;
            }

            $probes[] = trim("{$description} {$article}");
        }

        return $probes;
    }

    /**
     * Downloads the dossier images that are not on disk yet, deletes files
     * that are no longer in the dossier, and returns the relative paths in
     * capture order. The dossier is checked on every sync because adding a
     * document may not change the record's ETag.
     *
     * @return list<string>
     *
     * @throws CasRequestFailedException
     */
    private function syncImages(string $guid): array
    {
        $disk = Storage::disk('public');

        $paths = collect($this->cas->dossier(self::DATA_OBJECT_TYPE, $guid))
            ->map(fn (array $entry): array => $entry['fields'] ?? [])
            ->filter(fn (array $fields): bool => filled($fields['GGUID2'] ?? null)
                && in_array(mb_strtolower((string) ($fields['DOCEXT'] ?? '')), self::IMAGE_EXTENSIONS, true))
            ->sortBy(fn (array $fields): string => (string) ($fields['SORTDATE'] ?? ''))
            ->map(function (array $fields) use ($guid, $disk): string {
                $documentGuid = preg_replace('/[^A-Za-z0-9]/', '', (string) $fields['GGUID2']);
                $path = self::IMAGE_DIRECTORY."/{$guid}/{$documentGuid}.".mb_strtolower((string) $fields['DOCEXT']);

                if (! $disk->exists($path)) {
                    $disk->put($path, $this->cas->documentFile($documentGuid));
                }

                return $path;
            })
            ->values();

        foreach ($disk->files(self::IMAGE_DIRECTORY."/{$guid}") as $file) {
            if (! $paths->contains($file)) {
                $disk->delete($file);
            }
        }

        return $paths->all();
    }
}
