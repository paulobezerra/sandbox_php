<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Infrastructure\Database\ConnectionFactory;
use App\Application\Auth\AuthService;
use App\Application\Post\PostService;
use App\Infrastructure\Http\Controller\AppController;
use App\Infrastructure\Http\Controller\DocumentationController;
use App\Infrastructure\Http\Error\HttpErrorHandler;
use App\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;
use App\Infrastructure\Http\Middleware\CsrfMiddleware;
use App\Infrastructure\Http\Middleware\SessionMiddleware;
use App\Domain\Post\PostRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\Database\Repository\MysqlPostRepository;
use App\Infrastructure\Database\Repository\MysqlUserRepository;
use App\Infrastructure\View\MarkdownRenderer;
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
     * @param list<string> $anonymousMutationCsrfExemptions
     * @return SlimApp<Container>
     */
    public static function create(
        ?PDO $pdo = null,
        ?Config $config = null,
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
            UserRepository::class => \DI\autowire(MysqlUserRepository::class),
            PostRepository::class => \DI\autowire(MysqlPostRepository::class),
            AuthService::class => \DI\autowire(),
            PostService::class => \DI\autowire(),
            MarkdownRenderer::class => \DI\autowire(),
        ];
        $controllerFiles = array_map(static fn (string $name): string => dirname(__DIR__) . '/Infrastructure/Http/Controller/' . $name, [
            'AuthController.php', 'MarkdownPreviewController.php', 'PostController.php',
        ]);
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
        $app->add(new SessionMiddleware($config));

        // 3. Register routes
        Routes::register($app, $appController, new DocumentationController($config), $config->openApiEnabled);

        return $app;
    }
}
