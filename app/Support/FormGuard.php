<?php

namespace App\Support;

use App\Rules\AllowedScriptsOnly;
use App\Rules\NoForeignLinks;
use Illuminate\Support\Facades\Log;

/**
 * Shared spam protection for the contact form and the event lead form.
 * The rules themselves live in NoForeignLinks / AllowedScriptsOnly; this
 * class strips invisible characters before validation, hands the same
 * patterns to the frontend (resources/js/form-guard.js) and records
 * rejections in spam.log.
 */
class FormGuard
{
    /**
     * Unicode "Format" characters: zero-width space/joiner, soft hyphen,
     * BOM, bidi overrides etc. Invisible in the form, but used to slip
     * past keyword and link filters.
     */
    public const INVISIBLE_PATTERN = '\p{Cf}';

    public static function stripInvisible(string $text): string
    {
        return (string) preg_replace('~'.self::INVISIBLE_PATTERN.'~u', '', $text);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, string>  $fields
     * @return array<string, string>
     */
    public static function sanitize(array $input, array $fields): array
    {
        $sanitized = [];

        foreach ($fields as $field) {
            if (is_string($input[$field] ?? null)) {
                $sanitized[$field] = self::stripInvisible($input[$field]);
            }
        }

        return $sanitized;
    }

    /**
     * Configuration for resources/js/form-guard.js, rendered into the
     * form's data-form-guard attribute.
     *
     * @param  array<int, string>  $linkFields
     * @param  array<int, string>  $scriptFields
     * @return array<string, mixed>
     */
    public static function frontendConfig(array $linkFields, array $scriptFields): array
    {
        $allowedDomains = config('contact.allowed_link_domains', []);

        return [
            'linkPattern' => NoForeignLinks::PATTERN,
            'allowedDomains' => $allowedDomains,
            'linkMessage' => NoForeignLinks::message($allowedDomains),
            'linkFields' => $linkFields,
            'disallowedScriptPattern' => AllowedScriptsOnly::javascriptPattern(),
            'scriptFields' => $scriptFields,
            // Ohne Schlusspunkt - das Frontend haengt wie das Backend " (gefunden: „x“)." an.
            'scriptMessageBase' => rtrim(AllowedScriptsOnly::message([]), '.'),
            'invisiblePattern' => self::INVISIBLE_PATTERN,
        ];
    }

    /**
     * @param  array<string, string>  $details
     */
    public static function logRejection(string $reason, string $field, array $details = []): void
    {
        Log::channel('spam')->info('Formular-Eingabe abgelehnt', [
            'grund' => $reason,
            'formular' => request()->route()?->getName(),
            'feld' => $field,
            ...$details,
        ]);
    }
}
