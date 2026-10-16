<?php

declare(strict_types=1);

namespace App\Infrastructure\I18n;

use InvalidArgumentException;

final readonly class LocaleNegotiator
{
    /** @param non-empty-list<string> $supportedLocales */
    public function __construct(
        private array $supportedLocales,
        private string $fallbackLocale
    ) {
    }

    /** @param list<mixed> $locales */
    public static function validateConfiguration(array $locales, string $fallback): void
    {
        $normalizedLocales = array_map(
            static fn (mixed $locale): ?string => is_string($locale) ? strtolower($locale) : null,
            $locales
        );
        if ($locales === [] || count($locales) !== count(array_unique($normalizedLocales))) {
            throw new InvalidArgumentException('SUPPORTED_LOCALES must contain unique locale identifiers.');
        }
        foreach ($locales as $locale) {
            if (!is_string($locale) || preg_match('/^[a-zA-Z]{2,3}(?:-[a-zA-Z]{2})?$/', $locale) !== 1) {
                throw new InvalidArgumentException('SUPPORTED_LOCALES contains a malformed locale identifier.');
            }
        }
        if (!in_array($fallback, $locales, true)) {
            throw new InvalidArgumentException('FALLBACK_LOCALE must be included in SUPPORTED_LOCALES.');
        }
    }

    public function negotiate(string $header): string
    {
        $preferences = [];
        foreach (explode(',', $header) as $index => $part) {
            $segments = array_map('trim', explode(';', $part));
            $tag = $segments[0];
            if ($tag === '*' || preg_match('/^[a-zA-Z]{1,8}(?:-[a-zA-Z0-9]{1,8})*$/', $tag) !== 1) {
                continue;
            }
            $quality = 1.0;
            $invalidQuality = false;
            foreach (array_slice($segments, 1) as $parameter) {
                if (str_starts_with(strtolower($parameter), 'q=')) {
                    if (preg_match('/^q=(0(?:\.\d{0,3})?|1(?:\.0{0,3})?)$/i', $parameter, $match) !== 1) {
                        $invalidQuality = true;
                        break;
                    }
                    $quality = (float) $match[1];
                }
            }
            if ($invalidQuality) {
                continue;
            }
            $preferences[] = ['tag' => strtolower($tag), 'quality' => $quality, 'index' => $index];
        }

        usort($preferences, static fn (array $a, array $b): int => $b['quality'] <=> $a['quality'] ?: $a['index'] <=> $b['index']);
        foreach ($preferences as $preference) {
            if ($preference['quality'] <= 0) {
                continue;
            }
            foreach ($this->supportedLocales as $locale) {
                $canonical = strtolower($locale);
                $primary = explode('-', $canonical)[0];
                if ($preference['tag'] === $canonical || explode('-', $preference['tag'])[0] === $primary) {
                    return $locale;
                }
            }
        }

        return $this->fallbackLocale;
    }
}
