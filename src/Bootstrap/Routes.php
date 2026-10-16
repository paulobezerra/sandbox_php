<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Infrastructure\Http\Controller\AppController;
use App\Infrastructure\Http\Controller\DocumentationController;
use DI\Container;
use Jgut\Slim\Routing\RouteCollector;
use Slim\App as SlimApp;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

class Routes
{
    /**
     * @param SlimApp<Container> $app
     * @param list<string> $controllerFiles
     */
    public static function register(
        SlimApp $app,
        AppController $appController,
        DocumentationController $documentationController,
        bool $openApiEnabled,
        array $controllerFiles
    ): void {
        $routeCollector = $app->getRouteCollector();
        if (!$routeCollector instanceof RouteCollector) {
            throw new \RuntimeException('O coletor de rotas com atributos não foi configurado.');
        }
        $routeSourceFingerprint = hash(
            'sha256',
            implode('', array_map(static fn (string $file): string => $file . ':' . hash_file('sha256', $file), $controllerFiles))
        );
        $routeCollector->setCache(new Psr16Cache(new FilesystemAdapter(
            'sandbox_php_routes',
            0,
            sys_get_temp_dir() . '/sandbox_php_cache'
        )));
        $routeCollector->setCachePrefix($routeSourceFingerprint . ':');
        $routeCollector->registerRoutes();

        if ($openApiEnabled) {
            $app->get('/docs', [$documentationController, 'index']);
            $app->get('/docs/openapi.json', [$documentationController, 'openApi']);
        }

        $app->get('/[{path:(?!api(?:/|$)).*}]', [$appController, 'index']);
    }
}
