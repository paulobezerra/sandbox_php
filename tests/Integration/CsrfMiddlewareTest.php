<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Infrastructure\Http\Middleware\CsrfMiddleware;
use App\Tests\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{RequestHandlerInterface};
use Slim\Psr7\Response;

final class CsrfMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['user_id'], $_SESSION['csrf']);
        parent::tearDown();
    }

    public function testAuthenticatedMutationsRequireCsrfOutsideKnownPrefixes(): void
    {
        $_SESSION['user_id'] = 44;
        foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'PROPFIND'] as $method) {
            $called = (object) ['value' => false];
            $handler = new class($called) implements RequestHandlerInterface {
                public function __construct(private object $called) {}
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    $this->called->value = true;
                    return new Response();
                }
            };
            $result = (new CsrfMiddleware())->process($this->createRequest($method, '/api/extensions/mutate'), $handler);
            self::assertSame(419, $result->getStatusCode(), $method);
            self::assertFalse($called->value, $method);
        }
    }

    public function testAnonymousExtensionMutationDoesNotAssumeCookieAuthentication(): void
    {
        unset($_SESSION['user_id']);
        $result = (new CsrfMiddleware())->process(
            $this->createRequest('POST', '/api/extensions/public-hook'),
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface { return new Response(204); }
            }
        );
        self::assertSame(204, $result->getStatusCode());
    }
}
