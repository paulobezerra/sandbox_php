<?php
declare(strict_types=1);

namespace App\Bootstrap;

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Http\Controller\AppController;
use App\Infrastructure\Http\Controller\DocumentationController;
use App\Infrastructure\Http\Error\HttpErrorHandler;
use App\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;
use App\Infrastructure\Http\Middleware\SessionMiddleware;
use App\Infrastructure\Http\Middleware\CsrfMiddleware;
use App\Application\Auth\AuthService;
use App\Domain\User\UserRepository;
use App\Infrastructure\Database\Repository\MysqlUserRepository;
use App\Domain\Post\PostRepository;
use App\Domain\Page\PageRepository;
use App\Infrastructure\Database\Repository\MysqlPostRepository;
use App\Infrastructure\Database\Repository\MysqlPageRepository;
use App\Infrastructure\View\ViewRenderer;
use App\Infrastructure\View\MarkdownRenderer;
use App\Application\Post\PostService;
use App\Application\Page\PageService;
use DI\ContainerBuilder;
use Jgut\Slim\Routing\AppFactory as RoutingAppFactory;
use Jgut\Slim\Routing\Configuration as RoutingConfiguration;
use PDO;
use Slim\App as SlimApp;

class App
{
    public static function create(
        ?PDO $pdo = null,
        ?Config $config = null,
        bool $includeLegacyPlans = false
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
            PageRepository::class => \DI\autowire(MysqlPageRepository::class),
            AuthService::class => \DI\autowire(),
            PostService::class => \DI\autowire(),
            PageService::class => \DI\autowire(),
            MarkdownRenderer::class => \DI\autowire(),
        ];
        if ($includeLegacyPlans) {
            $definitions += LegacyPlanModule::definitions($db);
        }
        $containerBuilder->addDefinitions($definitions);
        $container = $containerBuilder->build();

        $controllerFiles = Routes::controllerFiles($includeLegacyPlans);
        RoutingAppFactory::setRouteCollectorConfiguration(new RoutingConfiguration([
            'sources' => $controllerFiles,
        ]));
        $app = RoutingAppFactory::create(container: $container);

        // 2. Middlewares
        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();

        $displayErrorDetails = $config->appEnv === 'development' || $config->appDebug;
        $logErrors = true;
        $logErrorDetails = $displayErrorDetails;

        $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);
        $errorHandler = new HttpErrorHandler($app->getResponseFactory());
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $app->add(new CsrfMiddleware());
        $app->add(new SecurityHeadersMiddleware());
        $app->add(new SessionMiddleware($config, $container->get(UserRepository::class)));

        // 3. Register routes
        Routes::register($app, $appController, new DocumentationController(), $config->openApiEnabled);

        return $app;
    }
}
