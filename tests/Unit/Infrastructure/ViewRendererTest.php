<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\View\ViewRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Slim\Psr7\Response;

final class ViewRendererTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/views-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testRenderEscapesDataAndSetsHtmlContentType(): void
    {
        file_put_contents($this->directory . '/page.phtml', '<p><?= $e($value) ?></p>');
        $response = (new ViewRenderer($this->directory))->render(new Response(), 'page.phtml', ['value' => '<script>']);

        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('<p>&lt;script&gt;</p>', (string) $response->getBody());
        self::assertSame('&quot;&amp;', (new ViewRenderer())->escape('"&'));
    }

    public function testMissingTemplateFailsWithItsPath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing.phtml');
        (new ViewRenderer($this->directory))->render(new Response(), 'missing.phtml');
    }

    public function testTemplateFailureStillClosesItsOutputBuffer(): void
    {
        file_put_contents($this->directory . '/failure.phtml', '<?php echo "partial"; throw new RuntimeException("template failed");');
        $bufferLevel = ob_get_level();
        try {
            (new ViewRenderer($this->directory))->render(new Response(), 'failure.phtml');
            self::fail('The template exception should be rethrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('template failed', $exception->getMessage());
        }
        self::assertSame($bufferLevel, ob_get_level());
    }
}
