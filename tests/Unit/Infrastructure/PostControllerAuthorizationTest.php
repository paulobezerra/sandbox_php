<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Application\Post\PostService;
use App\Domain\Post\PostRepository;
use App\Domain\User\{User, UserRepository};
use App\Infrastructure\Http\Controller\PostController;
use App\Infrastructure\View\MarkdownRenderer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class PostControllerAuthorizationTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['user_id']);
        parent::tearDown();
    }

    public function testAnonymousUserCannotListAuthoredPosts(): void
    {
        unset($_SESSION['user_id']);
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::never())->method('findById');
        $controller = new PostController(new PostService($this->createMock(PostRepository::class)), $users, new MarkdownRenderer());

        $response = $controller->myList((new ServerRequestFactory())->createServerRequest('GET', '/api/me/posts'), new Response());

        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString('Autenticação necessária', (string) $response->getBody());
    }

    public function testAnyAuthenticatedUserCanListTheirPostsWithoutAnAdminRole(): void
    {
        $_SESSION['user_id'] = 123;
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findById')->with(123)->willReturn(
            new User(123, 'Regular User', 'user@example.test', 'hash', new DateTimeImmutable())
        );
        $repository = $this->createMock(PostRepository::class);
        $repository->expects(self::once())->method('listByAuthor')->with(123, 20, 0)->willReturn([]);
        $repository->expects(self::once())->method('countByAuthor')->with(123)->willReturn(0);
        $controller = new PostController(new PostService($repository), $users, new MarkdownRenderer());

        $response = $controller->myList((new ServerRequestFactory())->createServerRequest('GET', '/api/me/posts'), new Response());

        self::assertSame(200, $response->getStatusCode());
    }
}
