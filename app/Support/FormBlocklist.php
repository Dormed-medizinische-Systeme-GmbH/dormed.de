<?php

namespace App\Support;

/**
 * Matches form input against config('contact.blocklist'). Pure functions
 * (no config access) so the canonical formats are easy to test; the
 * middleware passes the entries in.
 *
 * Every field is compared as a whole value, never as a substring.
 */
class FormBlocklist
{
    /**
     * Request field => blocklists it is checked against. "praxis_name" is
     * labelled "Praxis / Name" in the lead form, so it is checked against both.
     *
     * @var array<string, array<int, string>>
     */
    public const FIELDS = [
        'name' => ['name'],
        'praxis' => ['praxis'],
        'praxis_name' => ['praxis', 'name'],
        'email' => ['email'],
        'telefon' => ['telefon'],
    ];

    public static function normalizeText(string $value): string
    {
        $value = FormGuard::stripInvisible($value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return mb_strtolower(trim($value));
    }

    /**
     * Canonical e-mail: lower case, no invisible characters, no "+tag", and
     * for Gmail no dots in the local part (googlemail.com = gmail.com).
     */
    public static function normalizeEmail(string $value): string
    {
        $value = self::normalizeText($value);
        $value = str_replace(' ', '', $value);

        if (! str_contains($value, '@')) {
            return $value;
        }

        [$local, $domain] = explode('@', $value, 2);
        $local = explode('+', $local, 2)[0];

        if ($domain === 'googlemail.com') {
            $domain = 'gmail.com';
        }

        if ($domain === 'gmail.com') {
            $local = str_replace('.', '', $local);
        }

        return $local.'@'.$domain;
    }

    /**
     * Canonical phone number: digits only, +49 / 0049 / 49 become a leading 0.
     */
    public static function normalizePhone(string $value): string
    {
        $digits = (string) preg_replace('/\D+/', '', FormGuard::stripInvisible($value));

        if (str_starts_with($digits, '0049')) {
            return '0'.substr($digits, 4);
        }

        if (str_starts_with($digits, '49')) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }

    public static function normalize(string $list, string $value): string
    {
        return match ($list) {
            'email' => self::normalizeEmail($value),
            'telefon' => self::normalizePhone($value),
            default => self::normalizeText($value),
        };
    }

    /**
     * @param  array<int, string>  $entries
     */
    public static function matches(string $list, string $value, array $entries): bool
    {
        $needle = self::normalize($list, $value);

        if ($needle === '') {
            return false;
        }

        foreach ($entries as $entry) {
            if (self::normalize($list, $entry) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * First hit for the given request input, or null.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, array<int, string>>  $blocklist
     * @return array{field: string, list: string}|null
     */
    public static function findMatch(array $input, array $blocklist): ?array
    {
        foreach (self::FIELDS as $field => $lists) {
            $value = $input[$field] ?? null;

            if (! is_string($value)) {
                continue;
            }

            foreach ($lists as $list) {
                if (self::matches($list, $value, $blocklist[$list] ?? [])) {
                    return ['field' => $field, 'list' => $list];
                }
            }
        }

        return null;
    }
}
