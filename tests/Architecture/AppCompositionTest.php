<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use App\Bootstrap\App;
use PHPUnit\Framework\TestCase;

final class AppCompositionTest extends TestCase
{
    public function testDefaultCompositionKeepsPostRoutesAndDropsRemovedRoutes(): void
    {
        $routes = [];
        foreach (App::create()->getRouteCollector()->getRoutes() as $route) {
            foreach ($route->getMethods() as $method) {
                $routes[] = $method . ' ' . $route->getPattern();
            }
        }

        self::assertContains('GET /api/posts', $routes);
        self::assertSame(1, count(array_filter($routes, static fn (string $route): bool => str_starts_with($route, 'GET /api/posts/'))));
        self::assertNotContains('GET /api/health', $routes);
        self::assertNotContains('GET /api/plans', $routes);
        self::assertNotContains('GET /api/pages', $routes);
        self::assertNotContains('GET /:slug', $routes);
    }

    public function testVueRouterKeepsBlogSlugWithoutPublishedPageSlugFallback(): void
    {
        $router = (string) file_get_contents(dirname(__DIR__, 2) . '/frontend/src/spa/router.js');

        self::assertStringContainsString("path: '/blog/:slug'", $router);
        self::assertStringNotContainsString("path: '/:slug'", $router);
        self::assertStringNotContainsString("from './pages/PublishedPage.vue'", $router);
    }
}
