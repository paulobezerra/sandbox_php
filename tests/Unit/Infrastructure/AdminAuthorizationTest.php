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

final class AdminAuthorizationTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['user_id']);
        parent::tearDown();
    }

    public function testRegularUserCannotListAdministrativePosts(): void
    {
        $_SESSION['user_id'] = 123;
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findById')->with(123)->willReturn(
            new User(123, 'Regular User', 'user@example.test', 'hash', 'USER', new DateTimeImmutable())
        );
        $controller = new PostController(new PostService($this->createMock(PostRepository::class)), $users, new MarkdownRenderer());
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/admin/posts');
        $response = $controller->adminList($request, new Response());
        self::assertSame(403, $response->getStatusCode());
    }
}
