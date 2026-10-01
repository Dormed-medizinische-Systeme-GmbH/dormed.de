<?php

namespace App\Services\UsedDevices;

use App\Services\Cas\CasClient;
use App\Services\Cas\CasRequestFailedException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Online-available used devices from the CAS "GEBRAUCHTGERAETE" view, plus
 * their first dossier image, for /gebraucht2.
 *
 * @phpstan-type UsedDevice array{
 *     id: string,
 *     name: string,
 *     brand: string,
 *     brandLabel: string,
 *     system: string,
 *     systemLabel: string,
 *     description: string,
 *     year: int|null,
 *     yearBand: string,
 *     probes: list<string>,
 * }
 */
class UsedDeviceCatalog
{
    private const DATA_OBJECT_TYPE = 'GEBRAUCHTGERAETE';

    private const CACHE_KEY = 'used-devices';

    private const LAST_GOOD_CACHE_KEY = 'used-devices:last-good';

    private const CACHE_SECONDS = 600;

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public const YEAR_BANDS = [
        'ab-2020' => 'Baujahr ab 2020',
        '2015-2019' => 'Baujahr 2015–2019',
        'vor-2015' => 'Baujahr vor 2015',
        'unbekannt' => 'Baujahr auf Anfrage',
    ];

    public const SYSTEMS = [
        'farbdoppler' => 'Farbdopplersystem',
        'schwarz-weiss' => 'Schwarz-/Weiß-System',
        'sonstige' => 'Sonstige Systeme',
    ];

    public function __construct(private readonly CasClient $cas) {}

    /**
     * Cached for a few minutes. If CAS is unreachable the last successful
     * result is served instead, so a CAS reboot does not empty the page.
     *
     * @return Collection<int, UsedDevice>
     */
    public function all(): Collection
    {
        $devices = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, function (): array {
            try {
                $devices = $this->fetch()->all();
            } catch (CasRequestFailedException $exception) {
                Log::channel('api')->warning('Used devices could not be loaded from CAS.', ['message' => $exception->getMessage()]);

                return Cache::get(self::LAST_GOOD_CACHE_KEY, []);
            }

            Cache::forever(self::LAST_GOOD_CACHE_KEY, $devices);

            return $devices;
        });

        return collect($devices);
    }

    public function find(string $id): ?array
    {
        return $this->all()->firstWhere('id', $id);
    }

    /**
     * Number of devices per value of the given attribute, for the filter counters.
     *
     * @param  Collection<int, UsedDevice>  $devices
     * @return Collection<string, int>
     */
    public function countBy(Collection $devices, string $attribute): Collection
    {
        return $devices->countBy($attribute);
    }

    /**
     * Local path of the device's first image, downloaded from the CAS dossier
     * on first use. Null if the device is unknown or has no image.
     */
    public function imagePath(string $id): ?string
    {
        if ($this->find($id) === null) {
            return null;
        }

        // A failed lookup returns null, which Cache::remember does not store,
        // so it is retried on the next request; "no image" is cached as [].
        $image = Cache::remember("used-device-image:{$id}", 3600, function () use ($id): ?array {
            try {
                return $this->firstImage($id) ?? [];
            } catch (CasRequestFailedException $exception) {
                Log::channel('api')->warning('Used device dossier could not be loaded from CAS.', ['id' => $id, 'message' => $exception->getMessage()]);

                return null;
            }
        });

        if ($image === null || $image === []) {
            return null;
        }

        $path = "used-devices/{$id}-{$image['guid']}.{$image['extension']}";
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            try {
                $disk->put($path, $this->cas->documentFile($image['guid']));
            } catch (CasRequestFailedException $exception) {
                Log::channel('api')->warning('Used device image could not be downloaded from CAS.', ['id' => $id, 'message' => $exception->getMessage()]);

                return null;
            }
        }

        return $disk->path($path);
    }

    /**
     * @return Collection<int, UsedDevice>
     *
     * @throws CasRequestFailedException
     */
    private function fetch(): Collection
    {
        $records = $this->cas->listView(self::DATA_OBJECT_TYPE, (string) config('services.cas_genesis_world.used_devices_view_id'));

        return collect($records)
            ->filter(fn (array $record): bool => ($record['fields']['ONLINE'] ?? false) === true)
            ->map(fn (array $record): array => $this->map($record))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $record
     * @return UsedDevice
     */
    private function map(array $record): array
    {
        $fields = $record['fields'];
        $brandLabel = $this->brandLabel((string) ($fields['GG_SYSTEM_HERSTELLER'] ?? ''));
        $year = ctype_digit((string) ($fields['GG_SYSTEM_BAUJAHR'] ?? '')) ? (int) $fields['GG_SYSTEM_BAUJAHR'] : null;
        $description = (string) ($fields['GG_SYSTEM_BEZ'] ?? '');
        $system = $this->system($description);

        return [
            'id' => (string) $record['id'],
            'name' => (string) ($fields['GG_SYSTEM_ARTIKEL'] ?? 'Ultraschallgerät'),
            'brand' => str($brandLabel)->slug()->toString() ?: 'sonstige',
            'brandLabel' => $brandLabel ?: 'Sonstige',
            'system' => $system,
            'systemLabel' => self::SYSTEMS[$system],
            'description' => $description,
            'year' => $year,
            'yearBand' => $this->yearBand($year),
            'probes' => $this->probes($fields),
        ];
    }

    /**
     * "Mindray Co. Ltd." -> "Mindray": the manufacturer's first word is
     * enough for the filter and keeps legal suffixes out of the UI.
     */
    private function brandLabel(string $manufacturer): string
    {
        return trim(str($manufacturer)->before(' ')->toString());
    }

    private function system(string $description): string
    {
        $description = mb_strtolower($description);

        return match (true) {
            str_contains($description, 'farb') => 'farbdoppler',
            str_contains($description, 'schwarz') => 'schwarz-weiss',
            default => 'sonstige',
        };
    }

    private function yearBand(?int $year): string
    {
        return match (true) {
            $year === null => 'unbekannt',
            $year >= 2020 => 'ab-2020',
            $year >= 2015 => '2015-2019',
            default => 'vor-2015',
        };
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
     * @return array{guid: string, extension: string}|null
     *
     * @throws CasRequestFailedException
     */
    private function firstImage(string $id): ?array
    {
        $image = collect($this->cas->dossier(self::DATA_OBJECT_TYPE, $id))
            ->map(fn (array $entry): array => $entry['fields'] ?? [])
            ->filter(fn (array $fields): bool => filled($fields['GGUID2'] ?? null)
                && in_array(mb_strtolower((string) ($fields['DOCEXT'] ?? '')), self::IMAGE_EXTENSIONS, true))
            ->sortBy(fn (array $fields): string => (string) ($fields['SORTDATE'] ?? ''))
            ->first();

        if ($image === null) {
            return null;
        }

        return [
            'guid' => preg_replace('/[^A-Za-z0-9]/', '', (string) $image['GGUID2']),
            'extension' => mb_strtolower((string) $image['DOCEXT']),
        ];
    }
}
