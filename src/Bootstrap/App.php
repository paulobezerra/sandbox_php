<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Http\Controller\AppController;
use App\Infrastructure\Http\Controller\DocumentationController;
use App\Infrastructure\Http\Error\HttpErrorHandler;
use App\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;
use App\Infrastructure\Http\Middleware\CsrfMiddleware;
use App\Infrastructure\View\ViewRenderer;
use DI\Container;
use DI\ContainerBuilder;
use Jgut\Slim\Routing\AppFactory as RoutingAppFactory;
use Jgut\Slim\Routing\Configuration as RoutingConfiguration;
use PDO;
use Slim\App as SlimApp;

class App
{
    /**
     * @param list<mixed>|null $modules
     * @param list<string> $anonymousMutationCsrfExemptions
     * @return SlimApp<Container>
     */
    public static function create(
        ?PDO $pdo = null,
        ?Config $config = null,
        ?array $modules = null,
        array $anonymousMutationCsrfExemptions = []
    ): SlimApp {
        $config ??= Config::fromEnvironment();
        $db = $pdo ?? ConnectionFactory::create($config);
        $viewRenderer = new ViewRenderer();
        $appController = new AppController($viewRenderer);

        $containerBuilder = new ContainerBuilder();
        $definitions = [
            PDO::class => $db,
            Config::class => $config,
        ];
        $controllerFiles = [
            dirname(__DIR__) . '/Infrastructure/Http/Controller/HealthController.php',
        ];
        $modules ??= ProjectModules::enabled();
        foreach ($modules as $module) {
            if (!$module instanceof BootstrapModule) {
                throw new \InvalidArgumentException('Módulos devem implementar BootstrapModule.');
            }
            $definitions += $module->definitions($db);
            $controllerFiles = [...$controllerFiles, ...$module->controllerFiles()];
        }
        $containerBuilder->addDefinitions($definitions);
        $container = $containerBuilder->build();

        RoutingAppFactory::setRouteCollectorConfiguration(new RoutingConfiguration([
            'sources' => $controllerFiles,
        ]));
        $app = RoutingAppFactory::create(container: $container);

        // Route resolution runs before CSRF so unknown API paths keep their 404 contract.
        $app->addBodyParsingMiddleware();
        $app->add(new CsrfMiddleware($anonymousMutationCsrfExemptions));
        $app->addRoutingMiddleware();

        $displayErrorDetails = $config->appEnv === 'development' || $config->appDebug;
        $logErrors = true;
        $logErrorDetails = $displayErrorDetails;

        $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);
        $errorHandler = new HttpErrorHandler($app->getResponseFactory());
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $app->add(new SecurityHeadersMiddleware());
        foreach ($modules as $module) {
            $module->addMiddlewares($app, $container, $config);
        }

        // 3. Register routes
        Routes::register($app, $appController, new DocumentationController($config), $config->openApiEnabled, $controllerFiles);

        return $app;
    }
}
