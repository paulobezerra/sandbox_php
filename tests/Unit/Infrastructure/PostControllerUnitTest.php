<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Application\Post\PostService;
use App\Domain\Post\Post;
use App\Domain\Post\PostRepository;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Controller\PostController;
use App\Infrastructure\I18n\Localization;
use App\Infrastructure\View\MarkdownRenderer;
use App\Tests\TestCase;
use DateTimeImmutable;
use PDOException;
use Slim\Psr7\Response;

final class PostControllerUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['user_id']);
        parent::tearDown();
    }

    public function testAllPostEndpointsReturnSuccessfulResponsesForAuthorizedUser(): void
    {
        $_SESSION['user_id'] = 8;
        $post = $this->post();
        $repository = $this->createMock(PostRepository::class);
        $repository->method('listPublished')->willReturn([$post]);
        $repository->method('countPublished')->willReturn(1);
        $repository->method('findPublishedBySlug')->willReturn($post);
        $repository->method('listByAuthor')->willReturn([$post]);
        $repository->method('countByAuthor')->willReturn(1);
        $repository->method('findByAuthor')->willReturn($post);
        $repository->method('create')->willReturn($post);
        $repository->method('update')->willReturn($post);
        $controller = $this->controller($repository);

        self::assertSame(200, $controller->publicList($this->createRequest('GET', '/api/posts'), new Response())->getStatusCode());
        self::assertSame(200, $controller->publicShow($this->createRequest('GET', '/api/posts/example'), new Response(), ['slug' => 'example'])->getStatusCode());
        self::assertSame(200, $controller->myList($this->createRequest('GET', '/api/me/posts'), new Response())->getStatusCode());
        self::assertSame(201, $controller->create($this->createJsonRequest('POST', '/api/me/posts', ['title' => 'New', 'content' => 'Body']), new Response())->getStatusCode());
        self::assertSame(200, $controller->show($this->createRequest('GET', '/api/me/posts/1'), new Response(), ['id' => '1'])->getStatusCode());
        self::assertSame(200, $controller->update($this->createJsonRequest('PUT', '/api/me/posts/1', ['title' => 'Updated', 'content' => 'Body']), new Response(), ['id' => '1'])->getStatusCode());
        self::assertSame(204, $controller->delete($this->createRequest('DELETE', '/api/me/posts/1'), new Response(), ['id' => '1'])->getStatusCode());
        self::assertSame(200, $controller->publish($this->createRequest('POST', '/api/me/posts/1/publish'), new Response(), ['id' => '1'])->getStatusCode());
        self::assertSame(200, $controller->unpublish($this->createRequest('POST', '/api/me/posts/1/unpublish'), new Response(), ['id' => '1'])->getStatusCode());
    }

    public function testPostEndpointsTranslateDomainAndValidationFailures(): void
    {
        $_SESSION['user_id'] = 8;
        $repository = $this->createMock(PostRepository::class);
        $repository->method('findPublishedBySlug')->willReturn(null);
        $repository->method('findByAuthor')->willReturn(null);
        $repository->method('listPublished')->willReturn([]);
        $repository->method('listByAuthor')->willReturn([]);
        $repository->method('countPublished')->willReturn(0);
        $repository->method('countByAuthor')->willReturn(0);
        $controller = $this->controller($repository);

        self::assertSame(404, $controller->publicShow($this->createRequest('GET', '/api/posts/missing'), new Response(), ['slug' => 'missing'])->getStatusCode());
        self::assertSame(404, $controller->show($this->createRequest('GET', '/api/me/posts/99'), new Response(), ['id' => '99'])->getStatusCode());
        self::assertSame(404, $controller->update($this->createJsonRequest('PUT', '/api/me/posts/99', ['title' => 'Updated', 'content' => 'Body']), new Response(), ['id' => '99'])->getStatusCode());
        self::assertSame(404, $controller->delete($this->createRequest('DELETE', '/api/me/posts/99'), new Response(), ['id' => '99'])->getStatusCode());
        self::assertSame(404, $controller->publish($this->createRequest('POST', '/api/me/posts/99/publish'), new Response(), ['id' => '99'])->getStatusCode());
        self::assertSame(404, $controller->unpublish($this->createRequest('POST', '/api/me/posts/99/unpublish'), new Response(), ['id' => '99'])->getStatusCode());
        self::assertSame(422, $controller->create($this->createJsonRequest('POST', '/api/me/posts', ['title' => '', 'content' => 'Body']), new Response())->getStatusCode());
        self::assertSame(422, $controller->publicList($this->createRequest('GET', '/api/posts?page=invalid'), new Response())->getStatusCode());
        self::assertSame(422, $controller->myList($this->createRequest('GET', '/api/me/posts?perPage=101'), new Response())->getStatusCode());
        self::assertSame(422, $controller->myList($this->createRequest('GET', '/api/me/posts?perPage=invalid'), new Response())->getStatusCode());
        self::assertSame(422, $controller->myList($this->createRequest('GET', '/api/me/posts?perPage=invalid'), new Response())->getStatusCode());
    }

    public function testCreateMapsUniqueConstraintAndRethrowsOtherDatabaseFailures(): void
    {
        $_SESSION['user_id'] = 8;
        $request = $this->createJsonRequest('POST', '/api/me/posts', ['title' => 'New', 'content' => 'Body']);
        $unique = $this->createMock(PostRepository::class);
        $unique->method('create')->willThrowException(new PDOException('duplicate', 23000));
        self::assertSame(409, $this->controller($unique)->create($request, new Response())->getStatusCode());

        $other = $this->createMock(PostRepository::class);
        $other->method('create')->willThrowException(new PDOException('database unavailable', 42000));
        $this->expectException(PDOException::class);
        $this->controller($other)->create($request, new Response());
    }

    public function testUpdateMapsUniqueConstraintAndRethrowsOtherDatabaseFailures(): void
    {
        $_SESSION['user_id'] = 8;
        $request = $this->createJsonRequest('PUT', '/api/me/posts/1', ['title' => 'Changed', 'content' => 'Body']);
        $post = $this->post();
        $unique = $this->createMock(PostRepository::class);
        $unique->method('findByAuthor')->willReturn($post);
        $unique->method('update')->willThrowException(new PDOException('duplicate', 23000));
        self::assertSame(409, $this->controller($unique)->update($request, new Response(), ['id' => '1'])->getStatusCode());

        $other = $this->createMock(PostRepository::class);
        $other->method('findByAuthor')->willReturn($post);
        $other->method('update')->willThrowException(new PDOException('database unavailable', 42000));
        $this->expectException(PDOException::class);
        $this->controller($other)->update($request, new Response(), ['id' => '1']);
    }

    public function testEveryPrivateEndpointRejectsAnonymousAndStaleSessions(): void
    {
        $repository = $this->createMock(PostRepository::class);
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::never())->method('findById');
        $controller = new PostController(
            new PostService($repository),
            $users,
            new MarkdownRenderer(),
            Localization::load(['en', 'pt-BR'], 'en')
        );
        unset($_SESSION['user_id']);

        $responses = [
            $controller->myList($this->createRequest('GET', '/api/me/posts'), new Response()),
            $controller->create($this->createJsonRequest('POST', '/api/me/posts', []), new Response()),
            $controller->show($this->createRequest('GET', '/api/me/posts/1'), new Response(), ['id' => '1']),
            $controller->update($this->createJsonRequest('PUT', '/api/me/posts/1', []), new Response(), ['id' => '1']),
            $controller->delete($this->createRequest('DELETE', '/api/me/posts/1'), new Response(), ['id' => '1']),
            $controller->publish($this->createRequest('POST', '/api/me/posts/1/publish'), new Response(), ['id' => '1']),
            $controller->unpublish($this->createRequest('POST', '/api/me/posts/1/unpublish'), new Response(), ['id' => '1']),
        ];
        foreach ($responses as $response) {
            self::assertSame(401, $response->getStatusCode());
        }

        $_SESSION['user_id'] = '8';
        self::assertSame(401, $controller->myList($this->createRequest('GET', '/api/me/posts'), new Response())->getStatusCode());
        unset($_SESSION['user_id']);
        $_SESSION['user_id'] = 999;
        $missingUser = $this->createMock(UserRepository::class);
        $missingUser->expects(self::once())->method('findById')->with(999)->willReturn(null);
        $stale = new PostController(new PostService($repository), $missingUser, new MarkdownRenderer(), Localization::load(['en', 'pt-BR'], 'en'));
        self::assertSame(401, $stale->myList($this->createRequest('GET', '/api/me/posts'), new Response())->getStatusCode());
    }

    private function controller(PostRepository $repository): PostController
    {
        $users = $this->createMock(UserRepository::class);
        $users->method('findById')->willReturn(new User(8, 'Author', 'author@example.test', 'hash', new DateTimeImmutable()));

        return new PostController(
            new PostService($repository),
            $users,
            new MarkdownRenderer(),
            Localization::load(['en', 'pt-BR'], 'en')
        );
    }

    private function post(): Post
    {
        $now = new DateTimeImmutable('2026-01-01T00:00:00Z');
        return new Post(1, 'Example', 'example', '# Body', null, 'DRAFT', 8, null, $now, $now);
    }
}
