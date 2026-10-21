<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Domain\User\{User, UserRepository};
use App\Infrastructure\Http\Controller\MarkdownPreviewController;
use App\Infrastructure\I18n\Localization;
use App\Infrastructure\View\MarkdownRenderer;
use App\Tests\TestCase;
use DateTimeImmutable;
use Slim\Psr7\Response;

final class MarkdownPreviewControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['user_id']);
        parent::tearDown();
    }

    public function testPreviewRequiresAnExistingUser(): void
    {
        $_SESSION = ['user_id' => 9];
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findById')->with(9)->willReturn(null);
        $response = $this->controller($users)->render(
            $this->createJsonRequest('POST', '/api/me/markdown-preview')->withParsedBody(['content' => '# Preview']),
            new Response()
        );

        self::assertSame(401, $response->getStatusCode());
    }

    public function testAuthorizedPreviewEscapesRawHtml(): void
    {
        $_SESSION = ['user_id' => 9];
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findById')->with(9)->willReturn(new User(
            9,
            'Author',
            'author@example.test',
            'hash',
            new DateTimeImmutable()
        ));
        $response = $this->controller($users)->render(
            $this->createJsonRequest('POST', '/api/me/markdown-preview')->withParsedBody([
                'content' => '<script>alert(1)</script>',
            ]),
            new Response()
        );
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('&lt;script&gt;', $body['html']);
        self::assertStringNotContainsString('<script>', $body['html']);
    }

    public function testAuthorizedPreviewRejectsMissingWrongTypeAndOversizedContent(): void
    {
        $_SESSION = ['user_id' => 9];
        $users = $this->createMock(UserRepository::class);
        $users->method('findById')->willReturn(new User(9, 'Author', 'author@example.test', 'hash', new DateTimeImmutable()));
        $controller = $this->controller($users);

        foreach ([[], ['content' => 5], ['content' => str_repeat('x', 60001)]] as $body) {
            $response = $controller->render(
                $this->createJsonRequest('POST', '/api/me/markdown-preview')->withParsedBody($body),
                new Response()
            );
            self::assertSame(422, $response->getStatusCode());
        }
    }

    private function controller(UserRepository $users): MarkdownPreviewController
    {
        return new MarkdownPreviewController($users, new MarkdownRenderer(), Localization::load(['en', 'pt-BR'], 'en'));
    }
}
