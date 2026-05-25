<?php

declare(strict_types=1);

namespace App\Services\Moderation;

/**
 * Singleton-style accessor for the three auto-moderation rule families used at
 * publish time. Centralises the matching logic so callers stay declarative:
 *
 *   $service->containsBannedWords($title . ' ' . $description);
 *   $service->containsPhone($description);
 *   $service->containsExternalLink($description);
 *
 * Configuration lives in config/moderation.php so a non-code admin can tune
 * the seed lists without a deploy. The service is registered as a singleton
 * in App\Providers\AppServiceProvider so the precompiled rule arrays survive
 * across requests in a worker process.
 */
class ModerationRulesService
{
    /**
     * Lowercase, punctuation-stripped banned-word list. Computed once in the
     * constructor so the publish hot-path stays allocation-light.
     *
     * @var list<string>
     */
    private array $normalisedBannedWords;

    /** @var list<string> */
    private array $allowedDomains;

    private string $phoneRegex;

    private string $externalLinkRegex;

    public function __construct()
    {
        /** @var list<string> $banned */
        $banned = (array) config('moderation.banned_words', []);
        $this->normalisedBannedWords = array_values(array_unique(array_map(
            fn (string $word): string => $this->normalise($word),
            array_filter($banned, 'is_string'),
        )));

        /** @var list<string> $allowed */
        $allowed = (array) config('moderation.allowed_domains', []);
        $this->allowedDomains = array_values(array_map(
            static fn (string $domain): string => strtolower(trim($domain)),
            array_filter($allowed, 'is_string'),
        ));

        $this->phoneRegex = (string) config(
            'moderation.phone_regex',
            '/(?:\+?974|00974)[\s\-]?\d{4}[\s\-]?\d{4}/u',
        );

        $this->externalLinkRegex = (string) config(
            'moderation.external_link_regex',
            '/(?:https?:\/\/|\bwww\.)[^\s,]+/iu',
        );
    }

    /**
     * Match banned words in $text. Returns the list of distinct words that
     * fired (original config-file form) so the caller can log them.
     *
     * Matching strategy:
     *   - lower-case both sides
     *   - strip non-letter/non-digit punctuation between words to defeat
     *     simple obfuscation ("b.i.t.c.o.i.n" → "bitcoin")
     *   - substring match on word boundaries when the term is whitespace-free,
     *     plain `str_contains` otherwise (multi-word phrases).
     *
     * @return list<string>
     */
    public function containsBannedWords(string $text): array
    {
        if ($this->normalisedBannedWords === []) {
            return [];
        }

        $haystack = $this->normalise($text);

        if ($haystack === '') {
            return [];
        }

        $hits = [];
        foreach ($this->normalisedBannedWords as $needle) {
            if ($needle === '') {
                continue;
            }

            if (str_contains($haystack, $needle)) {
                $hits[] = $needle;
            }
        }

        return array_values(array_unique($hits));
    }

    /**
     * True when $text contains a phone-number-like sequence per the configured
     * regex. We intentionally don't return the matched number — the goal is to
     * flag-and-block, not to harvest contacts.
     */
    public function containsPhone(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        return preg_match($this->phoneRegex, $text) === 1;
    }

    /**
     * Return the list of external URLs found in $text whose host is NOT in
     * the allowed-domains list. The host extraction handles bare `www.`
     * prefixes (sellers often drop the protocol).
     *
     * @return list<string>
     */
    public function containsExternalLink(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $matches = [];
        $count = preg_match_all($this->externalLinkRegex, $text, $matches);

        if ($count === false || $count === 0) {
            return [];
        }

        /** @var list<string> $urls */
        $urls = array_values(array_unique($matches[0]));

        $external = [];
        foreach ($urls as $url) {
            $host = $this->extractHost($url);
            if ($host === '') {
                continue;
            }

            if (! in_array($host, $this->allowedDomains, true)) {
                $external[] = $url;
            }
        }

        return $external;
    }

    /**
     * Normalise text for word matching. Lower-cases, strips punctuation and
     * collapses whitespace so obfuscations like "b!i!t!c!o!i!n" still hit.
     */
    private function normalise(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');

        // Replace anything that isn't a letter/digit/whitespace with a space.
        // Use the Unicode-aware property classes so Arabic characters survive.
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $lower) ?? $lower;

        // Collapse repeat whitespace.
        $collapsed = preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned;

        return trim($collapsed);
    }

    /**
     * Extract the host portion from a URL-ish string. Handles bare `www.`
     * prefixes (no scheme) since sellers often paste those.
     */
    private function extractHost(string $url): string
    {
        $candidate = $url;

        if (! str_contains($candidate, '://')) {
            // parse_url won't recognise a host without a scheme; prepend one.
            $candidate = 'http://' . ltrim($candidate, '/');
        }

        $parts = parse_url($candidate);
        if (! is_array($parts) || ! isset($parts['host']) || ! is_string($parts['host'])) {
            return '';
        }

        return strtolower($parts['host']);
    }
}
