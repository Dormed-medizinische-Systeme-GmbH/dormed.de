<?php

namespace App\Services\UsedDevices;

use App\Services\Cas\CasClient;
use App\Services\Cas\CasRequestFailedException;
use App\UsedDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Mirrors the CAS "GEBRAUCHTGERAETE" view (online devices only) into the
 * local used_devices table and the first-party image store. The view is the
 * single source of truth: a device that is not in it - deleted, set offline
 * or no longer matching the view - is removed locally.
 */
class UsedDeviceSynchronizer
{
    private const DATA_OBJECT_TYPE = 'GEBRAUCHTGERAETE';

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    private const IMAGE_DIRECTORY = 'gebraucht';

    public function __construct(private readonly CasClient $cas) {}

    /**
     * @throws CasRequestFailedException
     */
    public function sync(string $guid): void
    {
        $record = $this->onlineRecords()->get($guid);

        if ($record === null) {
            $this->remove($guid);

            return;
        }

        $this->store($guid, $record);
    }

    /**
     * Full reconcile. Returns the number of devices now stored.
     *
     * @throws CasRequestFailedException
     */
    public function syncAll(): int
    {
        $records = $this->onlineRecords();

        foreach ($records as $guid => $record) {
            $this->store($guid, $record);
        }

        UsedDevice::query()
            ->whereNotIn('cas_id', $records->keys())
            ->pluck('cas_id')
            ->each(fn (string $guid) => $this->remove($guid));

        return $records->count();
    }

    /**
     * @return Collection<string, array<string, mixed>> online records keyed by upper-case GUID
     *
     * @throws CasRequestFailedException
     */
    private function onlineRecords(): Collection
    {
        $records = $this->cas->listView(self::DATA_OBJECT_TYPE, (string) config('services.cas_genesis_world.used_devices_view_id'));

        return collect($records)
            ->filter(fn (array $record): bool => ($record['fields']['ONLINE'] ?? false) === true)
            ->keyBy(fn (array $record): string => strtoupper((string) $record['id']));
    }

    /**
     * Images first, row second: a failed download throws before the row is
     * touched, so the retry starts from the same state.
     *
     * @param  array<string, mixed>  $record
     *
     * @throws CasRequestFailedException
     */
    private function store(string $guid, array $record): void
    {
        $fields = $record['fields'];
        $year = (string) ($fields['GG_SYSTEM_BAUJAHR'] ?? '');

        $images = $this->syncImages($guid);

        $device = UsedDevice::updateOrCreate(['cas_id' => $guid], [
            'etag' => $record['ETAG'] ?? null,
            'name' => (string) ($fields['GG_SYSTEM_ARTIKEL'] ?? 'Ultraschallgerät'),
            'manufacturer' => $fields['GG_SYSTEM_HERSTELLER'] ?? null,
            'description' => $fields['GG_SYSTEM_BEZ'] ?? null,
            'year' => ctype_digit($year) ? (int) $year : null,
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

    private function remove(string $guid): void
    {
        $deleted = UsedDevice::where('cas_id', $guid)->delete();
        Storage::disk('public')->deleteDirectory(self::IMAGE_DIRECTORY."/{$guid}");

        Log::channel('webhook')->info('Used device not online in CAS, removed locally.', ['gguid' => $guid, 'hadRecord' => $deleted > 0]);
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
