<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\I18n\LocaleNegotiator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LocaleNegotiatorTest extends TestCase
{
    private LocaleNegotiator $negotiator;

    protected function setUp(): void
    {
        $this->negotiator = new LocaleNegotiator(['en', 'pt-BR'], 'en');
    }

    public function testUsesFallbackForMissingUnsupportedAndWildcardPreferences(): void
    {
        self::assertSame('en', $this->negotiator->negotiate(''));
        self::assertSame('en', $this->negotiator->negotiate('fr-FR'));
        self::assertSame('en', $this->negotiator->negotiate('*'));
    }

    public function testMatchesRegionalTagsAndHighestQualityWithHeaderOrderAsTieBreaker(): void
    {
        self::assertSame('pt-BR', $this->negotiator->negotiate('pt-PT'));
        self::assertSame('en', $this->negotiator->negotiate('en-US'));
        self::assertSame('pt-BR', $this->negotiator->negotiate('en;q=0.7, pt;q=0.9'));
        self::assertSame('pt-BR', $this->negotiator->negotiate('pt;q=0.8, en;q=0.8'));
    }

    public function testExplicitZeroQualityDoesNotMatchAnExcludedLanguage(): void
    {
        self::assertSame('pt-BR', $this->negotiator->negotiate('en;q=0, pt;q=0.5'));
        self::assertSame('en', $this->negotiator->negotiate('en;q=0, fr;q=1'));
    }

    public function testIgnoresMalformedQualityValues(): void
    {
        self::assertSame('en', $this->negotiator->negotiate('pt;q=1.0000'));
        self::assertSame('en', $this->negotiator->negotiate('pt;q=2'));
    }

    public function testIgnoresMalformedTagsAndZeroQualityUntilAUsablePreference(): void
    {
        self::assertSame('pt-BR', $this->negotiator->negotiate('bad_tag, pt;q=0, pt-BR;q=0.25'));
        self::assertSame('en', $this->negotiator->negotiate('*, en;q=0'));
        self::assertSame('pt-BR', $this->negotiator->negotiate('PT-br;q=1'));
    }

    public function testRejectsEmptyDuplicateMalformedAndUnsupportedLocaleConfiguration(): void
    {
        foreach ([
            [[], 'en', 'unique locale identifiers'],
            [['en', 'EN'], 'en', 'unique locale identifiers'],
            [['english_US'], 'english_US', 'malformed locale identifier'],
            [['en'], 'fr', 'FALLBACK_LOCALE'],
            [['en', 7], 'en', 'malformed locale identifier'],
        ] as [$locales, $fallback, $expected]) {
            try {
                LocaleNegotiator::validateConfiguration($locales, $fallback);
                self::fail('Invalid locale configuration should be rejected.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString($expected, $exception->getMessage());
            }
        }
    }

    public function testMalformedPreferencesAreSkippedAndQualityTiesKeepHeaderOrder(): void
    {
        self::assertSame('pt-BR', $this->negotiator->negotiate('bad_tag;q=1, pt;q=0.7, en;q=0.7'));
        self::assertSame('en', $this->negotiator->negotiate('fr;q=0, *;q=1'));
        self::assertSame('pt-BR', $this->negotiator->negotiate('PT-br;q=0.5'));
    }
}
