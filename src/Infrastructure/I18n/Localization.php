<?php

declare(strict_types=1);

namespace App\Infrastructure\I18n;

use RuntimeException;

final readonly class Localization
{
    /** @param array<string, array<string, mixed>> $catalogues */
    private function __construct(
        private array $catalogues,
        public LocaleNegotiator $negotiator,
        public string $fallbackLocale
    ) {
    }

    /** @param non-empty-list<string> $locales */
    public static function load(array $locales, string $fallback, ?string $directory = null): self
    {
        $directory ??= dirname(__DIR__, 3) . '/public/assets/i18n';
        $catalogues = [];
        foreach ($locales as $locale) {
            $path = $directory . '/' . $locale . '.json';
            $contents = is_file($path) ? file_get_contents($path) : false;
            $catalogue = is_string($contents) ? json_decode($contents, true) : null;
            if (!is_array($catalogue) || json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(sprintf('The translation catalogue for "%s" is missing or invalid.', $locale));
            }
            if (array_filter($catalogue, static fn (mixed $value): bool => !is_string($value)) !== []) {
                throw new RuntimeException(sprintf('The translation catalogue for "%s" must contain only text values.', $locale));
            }
            $catalogues[$locale] = $catalogue;
        }

        $baselineKeys = self::keys($catalogues[$fallback]);
        foreach ($catalogues as $locale => $catalogue) {
            if (self::keys($catalogue) !== $baselineKeys) {
                throw new RuntimeException(sprintf('The translation catalogue for "%s" has incomplete keys.', $locale));
            }
        }

        return new self($catalogues, new LocaleNegotiator($locales, $fallback), $fallback);
    }

    public function translate(string $locale, string $key): string
    {
        $value = $this->catalogues[$locale][$key] ?? $this->catalogues[$this->fallbackLocale][$key] ?? null;
        if (!is_string($value)) {
            throw new RuntimeException(sprintf('Translation key "%s" is missing.', $key));
        }
        return $value;
    }

    /** @return list<string> */
    /** @param array<string, mixed> $catalogue
     * @return list<string>
     */
    private static function keys(array $catalogue): array
    {
        $keys = array_map('strval', array_keys($catalogue));
        sort($keys);
        return $keys;
    }
}
