<?php
declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\View\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class MarkdownRendererTest extends TestCase
{
    public function testRawHtmlIsEscapedAndUnsafeLinksAreNotRendered(): void
    {
        $html = (new MarkdownRenderer())->render("<script>alert(1)</script>\n\n[link](javascript:alert(1))");
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('href="javascript:', $html);
    }

    public function testEventHandlersAndDataUrlsRemainInert(): void
    {
        $html = (new MarkdownRenderer())->render("<img src=x onerror=alert(1)>\n\n[link](data:text/html;base64,PHNjcmlwdD4=)");

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        self::assertStringNotContainsString('href="data:', $html);
    }
}
