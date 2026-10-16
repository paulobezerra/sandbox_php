<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\I18n\LocaleNegotiator;
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
}
