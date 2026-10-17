<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Infrastructure\Http\Controller\AppController;
use App\Infrastructure\Http\Controller\DocumentationController;
use DI\Container;
use Jgut\Slim\Routing\RouteCollector;
use Slim\App as SlimApp;

class Routes
{
    /** @param SlimApp<Container> $app */
    public static function register(
        SlimApp $app,
        AppController $appController,
        DocumentationController $documentationController,
        bool $openApiEnabled
    ): void {
        $routeCollector = $app->getRouteCollector();
        if (!$routeCollector instanceof RouteCollector) {
            throw new \RuntimeException('The attribute-based route collector is not configured.');
        }
        $routeCollector->registerRoutes();

        if ($openApiEnabled) {
            $app->get('/docs', [$documentationController, 'index']);
            $app->get('/docs/openapi.json', [$documentationController, 'openApi']);
        }

        $app->get('/[{path:(?!api(?:/|$)).*}]', [$appController, 'index']);
    }
}
