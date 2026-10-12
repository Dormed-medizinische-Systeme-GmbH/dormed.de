<?php

namespace App\Rules;

use App\Support\FormGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Only allows characters from the Unicode scripts in
 * config('contact.allowed_scripts') - by default Latin plus the shared
 * "Common"/"Inherited" characters (digits, punctuation, €, emoji, accents).
 * Rejects spam written in Cyrillic, Georgian, CJK etc. as well as Latin
 * words disguised with lookalike letters (a Cyrillic "а" inside "Angebot").
 */
class AllowedScriptsOnly implements ValidationRule
{
    /**
     * @param  array<int, string>|null  $allowedScripts
     */
    public function __construct(private ?array $allowedScripts = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $disallowed = self::disallowedCharacters($value, $this->allowedScripts);

        if ($disallowed === []) {
            return;
        }

        FormGuard::logRejection('schrift', $attribute);

        $fail(self::message($disallowed));
    }

    /**
     * Distinct disallowed characters in order of appearance.
     *
     * @param  array<int, string>|null  $allowedScripts
     * @return array<int, string>
     */
    public static function disallowedCharacters(string $text, ?array $allowedScripts = null): array
    {
        preg_match_all('~'.self::phpPattern($allowedScripts).'~u', $text, $matches);

        return array_values(array_unique($matches[0]));
    }

    /**
     * Names up to three offending characters, so a real customer can find
     * the stray one (e.g. a pasted Cyrillic "а") instead of guessing.
     *
     * @param  array<int, string>  $disallowed
     */
    public static function message(array $disallowed): string
    {
        $examples = $disallowed === []
            ? ''
            : ' (gefunden: '.implode(' ', array_map(fn (string $char): string => '„'.$char.'“', array_slice($disallowed, 0, 3))).')';

        return 'Wir verstehen Deutsch und Englisch – bitte schreiben Sie Ihre Nachricht in lateinischer Schrift'.$examples.'.';
    }

    /**
     * @param  array<int, string>|null  $allowedScripts
     */
    public static function phpPattern(?array $allowedScripts = null): string
    {
        $classes = array_map(fn (string $script): string => '\p{'.$script.'}', self::scripts($allowedScripts));

        return '[^'.implode('', $classes).']';
    }

    /**
     * Same character class in JavaScript syntax (needs the "u" flag).
     *
     * @param  array<int, string>|null  $allowedScripts
     */
    public static function javascriptPattern(?array $allowedScripts = null): string
    {
        $classes = array_map(fn (string $script): string => '\p{Script='.$script.'}', self::scripts($allowedScripts));

        return '[^'.implode('', $classes).']';
    }

    /**
     * @param  array<int, string>|null  $allowedScripts
     * @return array<int, string>
     */
    private static function scripts(?array $allowedScripts): array
    {
        return array_values(array_filter(
            $allowedScripts ?? config('contact.allowed_scripts', []),
            fn (string $script): bool => preg_match('/^[A-Za-z_]+$/', $script) === 1,
        ));
    }
}
