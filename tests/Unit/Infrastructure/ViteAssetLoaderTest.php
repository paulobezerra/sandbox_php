<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Bootstrap\Config;
use App\Infrastructure\Http\Controller\AppController;
use App\Infrastructure\I18n\Localization;
use App\Infrastructure\View\ViewRenderer;
use App\Infrastructure\View\ViteAssetLoader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ViteAssetLoaderTest extends TestCase
{
    private string $publicDirectory;

    protected function setUp(): void
    {
        $this->publicDirectory = sys_get_temp_dir() . '/vite-assets-' . bin2hex(random_bytes(6));
        mkdir($this->publicDirectory . '/assets/build/.vite', 0777, true);
    }

    protected function tearDown(): void
    {
        $manifest = $this->publicDirectory . '/assets/build/.vite/manifest.json';
        if (is_file($manifest)) {
            unlink($manifest);
        }
        rmdir($this->publicDirectory . '/assets/build/.vite');
        rmdir($this->publicDirectory . '/assets/build');
        rmdir($this->publicDirectory . '/assets');
        rmdir($this->publicDirectory);
    }

    public function testDevelopmentUsesOnlyTheConfiguredViteServerAndEntry(): void
    {
        $loader = new ViteAssetLoader($this->publicDirectory, 'http://localhost:5173');

        $tags = $loader->renderTags(true, 'assets/js/app.js');

        self::assertStringContainsString('http://localhost:5173/@vite/client', $tags);
        self::assertStringContainsString('http://localhost:5173/assets/js/app.js', $tags);
        self::assertStringNotContainsString('/assets/build/', $tags);

    }

    public function testPhpTemplateSelectsDevelopmentAssetsFromApplicationEnvironment(): void
    {
        $config = Config::fromArray([
            'APP_ENV' => 'development',
            'APP_URL' => 'http://localhost:8080',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app_user',
        ]);
        $controller = new AppController(
            new ViewRenderer(),
            Localization::load(['en', 'pt-BR'], 'en'),
            $config,
            new ViteAssetLoader($this->publicDirectory, 'http://localhost:5173')
        );
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')->withAttribute('locale', 'en');

        $body = (string) $controller->index($request, new Response())->getBody();

        self::assertStringContainsString('http://localhost:5173/@vite/client', $body);
        self::assertStringContainsString('http://localhost:5173/src/spa/main.js', $body);

        $fallbackRequest = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $fallbackBody = $controller->index($fallbackRequest, new Response())->getBody();
        self::assertStringContainsString('<html lang="en">', (string) $fallbackBody);
    }

    public function testPhpTemplateSelectsManifestAssetsOutsideDevelopment(): void
    {
        file_put_contents(
            $this->publicDirectory . '/assets/build/.vite/manifest.json',
            json_encode(['src/spa/main.js' => [
                'file' => 'assets/main-a1b2.js',
                'css' => ['assets/main-c3d4.css'],
            ]], JSON_THROW_ON_ERROR)
        );
        $config = Config::fromArray([
            'APP_ENV' => 'production',
            'APP_URL' => 'https://example.test',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app_user',
        ]);
        $controller = new AppController(
            new ViewRenderer(),
            Localization::load(['en', 'pt-BR'], 'en'),
            $config,
            new ViteAssetLoader($this->publicDirectory, 'http://localhost:5173')
        );
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')->withAttribute('locale', 'en');

        $body = (string) $controller->index($request, new Response())->getBody();

        self::assertStringContainsString('/assets/build/assets/main-c3d4.css', $body);
        self::assertStringContainsString('/assets/build/assets/main-a1b2.js', $body);
        self::assertStringNotContainsString('localhost:5173', $body);
    }

    public function testProductionResolvesHashedAssetsFromManifest(): void
    {
        file_put_contents(
            $this->publicDirectory . '/assets/build/.vite/manifest.json',
            json_encode([
                'src/main.js' => [
                    'file' => 'assets/main-a1b2.js',
                    'css' => ['assets/main-c3d4.css'],
                ],
            ], JSON_THROW_ON_ERROR)
        );
        $loader = new ViteAssetLoader($this->publicDirectory, 'http://localhost:5173');

        $tags = $loader->renderTags(false, 'src/main.js');

        self::assertStringContainsString('<link rel="stylesheet" href="/assets/build/assets/main-c3d4.css">', $tags);
        self::assertStringContainsString('<script type="module" src="/assets/build/assets/main-a1b2.js"></script>', $tags);
        self::assertStringNotContainsString('localhost:5173', $tags);
    }

    public function testProductionFailsClearlyWhenManifestOrEntryIsMissing(): void
    {
        $loader = new ViteAssetLoader($this->publicDirectory, 'http://localhost:5173');

        try {
            $loader->renderTags(false, 'src/main.js');
            self::fail('Expected the missing Vite manifest to fail.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Vite manifest', $exception->getMessage());
        }

        file_put_contents($this->publicDirectory . '/assets/build/.vite/manifest.json', '{}');
        try {
            $loader->renderTags(false, 'src/main.js');
            self::fail('Expected a missing Vite entry to fail.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('src/main.js', $exception->getMessage());
        }
    }

    public function testDevelopmentServerUrlRejectsNonLocalOrigins(): void
    {
        $this->expectException(RuntimeException::class);
        new ViteAssetLoader($this->publicDirectory, 'https://attacker.example:5173');
    }

    #[DataProvider('invalidDevelopmentOrigins')]
    public function testDevelopmentServerRejectsUnsafeOrigins(string $url): void
    {
        $this->expectException(RuntimeException::class);
        new ViteAssetLoader($this->publicDirectory, $url);
    }

    public static function invalidDevelopmentOrigins(): iterable
    {
        yield 'wrong port' => ['http://localhost:8080'];
        yield 'credentials' => ['http://user:pass@localhost:5173'];
        yield 'query' => ['http://localhost:5173?x=1'];
        yield 'fragment' => ['http://localhost:5173#x'];
        yield 'path' => ['http://localhost:5173/vite'];
    }

    public function testDevelopmentRejectsUnsafeEntryPaths(): void
    {
        $loader = new ViteAssetLoader($this->publicDirectory, 'http://localhost:5173');

        foreach (['', '/absolute.js', '../secret.js', 'bad name.js'] as $entry) {
            try {
                $loader->renderTags(true, $entry);
                self::fail('Unsafe entry must be rejected.');
            } catch (RuntimeException $exception) {
                self::assertSame('Vite entry must be a safe relative path.', $exception->getMessage());
            }
        }
    }

    public function testProductionRejectsInvalidManifestDataAndAssetPaths(): void
    {
        $loader = new ViteAssetLoader($this->publicDirectory, 'http://localhost:5173');
        $manifestPath = $this->publicDirectory . '/assets/build/.vite/manifest.json';
        file_put_contents($manifestPath, '{broken');
        try {
            $loader->renderTags(false, 'src/main.js');
            self::fail('Malformed JSON must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('invalid JSON', $exception->getMessage());
        }

        foreach ([
            ['src/main.js' => []],
            ['src/main.js' => ['file' => 5]],
            ['src/main.js' => ['file' => '']],
            ['src/main.js' => ['file' => '/absolute.js']],
            ['src/main.js' => ['file' => 'bad name.js']],
            ['src/main.js' => ['file' => 'assets/main.js', 'css' => [5]]],
            ['src/main.js' => ['file' => '../secret.js']],
            ['src/main.js' => ['file' => 'assets\\secret.js']],
        ] as $manifest) {
            file_put_contents($manifestPath, json_encode($manifest, JSON_THROW_ON_ERROR));
            try {
                $loader->renderTags(false, 'src/main.js');
                self::fail('Invalid manifest data must be rejected.');
            } catch (RuntimeException) {
                self::assertTrue(true);
            }
        }
    }
}
