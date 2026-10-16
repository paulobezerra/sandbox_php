<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Application\Auth\AuthService;
use App\Application\Page\PageService;
use App\Application\Post\PostService;
use App\Domain\Page\PageRepository;
use App\Domain\Post\PostRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\Database\Repository\MysqlPageRepository;
use App\Infrastructure\Database\Repository\MysqlPostRepository;
use App\Infrastructure\Database\Repository\MysqlUserRepository;
use App\Infrastructure\View\MarkdownRenderer;
use DI\Container;
use PDO;
use Psr\Container\ContainerInterface;
use App\Infrastructure\Http\Middleware\SessionMiddleware;
use App\Infrastructure\Http\ClientIpResolver;
use Slim\App as SlimApp;

final class CmsModule implements BootstrapModule
{
    /** @return array<class-string, mixed> */
    public function definitions(PDO $pdo): array
    {
        return [
            UserRepository::class => \DI\autowire(MysqlUserRepository::class),
            PostRepository::class => \DI\autowire(MysqlPostRepository::class),
            PageRepository::class => \DI\autowire(MysqlPageRepository::class),
            AuthService::class => \DI\autowire(),
            ClientIpResolver::class => \DI\factory(static fn (ContainerInterface $container): ClientIpResolver => new ClientIpResolver(
                $container->get(Config::class)->trustedProxies
            )),
            PostService::class => \DI\autowire(),
            PageService::class => \DI\factory(static fn (ContainerInterface $container): PageService => new PageService(
                $container->get(PageRepository::class),
                ProjectModules::reservedPageSlugs()
            )),
            MarkdownRenderer::class => \DI\autowire(),
        ];
    }

    /** @return list<string> */
    public function controllerFiles(): array
    {
        $directory = dirname(__DIR__) . '/Infrastructure/Http/Controller/';
        return array_map(
            static fn (string $name): string => $directory . $name,
            ['AuthController.php', 'MarkdownPreviewController.php', 'PageController.php', 'PostController.php']
        );
    }

    /** @param SlimApp<Container> $app */
    public function addMiddlewares(SlimApp $app, ContainerInterface $container, Config $config): void
    {
        $app->add(new SessionMiddleware($config, $container->get(UserRepository::class)));
    }

}
