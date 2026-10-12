<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects links in free-text fields unless they point to an allowed domain
 * (config/contact.php). Around a third of contact-form spam carries links,
 * so this is enforced server-side; kontakt.blade.php runs the same pattern
 * client-side to mark the field before submitting.
 */
class NoForeignLinks implements ValidationRule
{
    /**
     * Shared with the frontend (new RegExp(PATTERN, 'gi')), so it must stay
     * valid in both PCRE and JavaScript. Matches "http(s)://..." and
     * "www...." as well as bare domains with a common TLD ("example.com").
     * The lookbehind keeps e-mail addresses ("info@praxis.de") and
     * abbreviations like "z.B." from being treated as links.
     */
    public const PATTERN = '(?:https?:\/\/|www\.)[^\s<>"\']+|(?<![@\w.-])(?:[a-z0-9-]+\.)+(?:com|net|org|de|eu|info|biz|io|co|ru|xyz|top|online|site|shop|app|me)\b(?:\/[^\s<>"\']*)?';

    /**
     * @param  array<int, string>|null  $allowedDomains
     */
    public function __construct(private ?array $allowedDomains = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $this->firstForeignLink($value) === null) {
            return;
        }

        $fail(self::message($this->allowedDomains()));
    }

    /**
     * @param  array<int, string>  $allowedDomains
     */
    public static function message(array $allowedDomains): string
    {
        return 'Links sind aus Spamschutz-Gründen nicht erlaubt (ausgenommen '.implode(', ', $allowedDomains).'). Bitte entfernen Sie den Link.';
    }

    public function firstForeignLink(string $text): ?string
    {
        preg_match_all('~'.self::PATTERN.'~iu', $text, $matches);

        foreach ($matches[0] as $link) {
            if (! $this->isAllowedHost($this->hostOf($link))) {
                return $link;
            }
        }

        return null;
    }

    private function hostOf(string $link): string
    {
        $withoutScheme = preg_replace('~^https?://~i', '', $link);
        $host = preg_split('~[/?#:]~', $withoutScheme, 2)[0];

        return rtrim(mb_strtolower($host), '.,;!?)');
    }

    private function isAllowedHost(string $host): bool
    {
        foreach ($this->allowedDomains() as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function allowedDomains(): array
    {
        return array_map(
            fn (string $domain): string => mb_strtolower($domain),
            $this->allowedDomains ?? config('contact.allowed_link_domains', []),
        );
    }
}
