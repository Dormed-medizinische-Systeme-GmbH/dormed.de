<?php

namespace App;

use Database\Factories\UsedDeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Local copy of one online-available CAS "GEBRAUCHTGERAETE" record. Kept
 * flat on purpose; images live on the public disk under gebraucht/{cas_id}/
 * and are referenced here by relative path.
 *
 * @property-read string $brand
 * @property-read string $brandLabel
 * @property-read string $system
 * @property-read string $systemLabel
 * @property-read string $yearBand
 * @property-read string|null $imageUrl
 */
#[Fillable(['cas_id', 'etag', 'name', 'manufacturer', 'description', 'year', 'probes', 'images', 'cas_updated_at', 'synced_at'])]
class UsedDevice extends Model
{
    /** @use HasFactory<UsedDeviceFactory> */
    use HasFactory;

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'probes' => 'array',
            'images' => 'array',
            'cas_updated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * "Mindray Co. Ltd." -> "Mindray": the manufacturer's first word is
     * enough for the filter and keeps legal suffixes out of the UI.
     *
     * @return Attribute<string, never>
     */
    protected function brandLabel(): Attribute
    {
        return Attribute::get(fn (): string => trim(str((string) $this->manufacturer)->before(' ')->toString()) ?: 'Sonstige');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function brand(): Attribute
    {
        return Attribute::get(fn (): string => str($this->brandLabel)->slug()->toString());
    }

    /**
     * @return Attribute<string, never>
     */
    protected function system(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            str_contains(mb_strtolower((string) $this->description), 'farb') => 'farbdoppler',
            str_contains(mb_strtolower((string) $this->description), 'schwarz') => 'schwarz-weiss',
            default => 'sonstige',
        });
    }

    /**
     * @return Attribute<string, never>
     */
    protected function systemLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::SYSTEMS[$this->system]);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function yearBand(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->year === null => 'unbekannt',
            $this->year >= 2020 => 'ab-2020',
            $this->year >= 2015 => '2015-2019',
            default => 'vor-2015',
        });
    }

    /**
     * Web path of the first image (served straight from public/storage).
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => isset($this->images[0]) ? '/storage/'.$this->images[0] : null);
    }
}
