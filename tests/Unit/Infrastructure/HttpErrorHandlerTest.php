<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\Http\Error\HttpErrorHandler;
use App\Infrastructure\Http\JsonRequestException;
use App\Infrastructure\I18n\Localization;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use RuntimeException;

final class HttpErrorHandlerTest extends TestCase
{
    public function testApiErrorsUseLocalizedJsonAndRetainExistingVaryValues(): void
    {
        $handler = new HttpErrorHandler($this->responseFactory('Origin'), Localization::load(['en', 'pt-BR'], 'en'));
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/missing')
            ->withHeader('Accept-Language', 'pt-BR')
            ->withHeader('Vary', 'Origin');
        $response = $handler($request, new HttpNotFoundException($request), false, false, false);
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('pt-BR', $response->getHeaderLine('Content-Language'));
        self::assertSame('Origin, Accept-Language', $response->getHeaderLine('Vary'));
        self::assertSame(404, $payload['error']['status']);
    }

    public function testJsonAcceptHeaderAndJsonRequestErrorsUseApiFormat(): void
    {
        $handler = new HttpErrorHandler(new ResponseFactory(), Localization::load(['en', 'pt-BR'], 'en'));
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/page')
            ->withHeader('Accept', 'application/json');
        $invalid = $handler($request, new JsonRequestException($request, 'invalid_json_object'), false, false, false);
        $malformed = $handler($request, new JsonRequestException($request, 'malformed_json'), false, false, false);

        self::assertSame('application/json', $invalid->getHeaderLine('Content-Type'));
        self::assertNotSame((string) $invalid->getBody(), (string) $malformed->getBody());
    }

    public function testHtmlErrorsEscapeLocalizedMessagesAndSetLanguageHeaders(): void
    {
        $handler = new HttpErrorHandler(new ResponseFactory(), Localization::load(['en', 'pt-BR'], 'en'));
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/missing')->withHeader('Accept-Language', 'pt-BR');
        $response = $handler($request, new HttpNotFoundException($request), false, false, false);

        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('pt-BR', $response->getHeaderLine('Content-Language'));
        self::assertStringContainsString('<html lang="pt-BR">', (string) $response->getBody());
        self::assertStringContainsString('Erro 404', (string) $response->getBody());
    }

    public function testStatusMappingCoversBadRequestMethodNotAllowedAndGenericFailure(): void
    {
        $handler = new HttpErrorHandler(new ResponseFactory(), Localization::load(['en'], 'en'));
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/x');

        self::assertSame(400, $handler($request, new HttpBadRequestException($request), false, false, false)->getStatusCode());
        self::assertSame(405, $handler($request, (new HttpMethodNotAllowedException($request))->setAllowedMethods(['GET']), false, false, false)->getStatusCode());
        self::assertSame(500, $handler($request, new RuntimeException('secret'), false, false, false)->getStatusCode());
    }

    public function testWithoutLocalizationUsesEnglishFallbackAndKeepsExistingLanguageVaryCaseInsensitive(): void
    {
        $handler = new HttpErrorHandler($this->responseFactory('Accept-Encoding, accept-language'));
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/x')
            ->withHeader('Accept-Language', 'pt-BR')
            ->withHeader('Vary', 'Accept-Encoding, accept-language');
        $response = $handler($request, new RuntimeException('secret'), false, false, false);

        self::assertSame('en', $response->getHeaderLine('Content-Language'));
        self::assertSame('Accept-Encoding, accept-language', $response->getHeaderLine('Vary'));
        self::assertStringContainsString('An internal server error occurred.', (string) $response->getBody());
    }

    public function testFallbackMessagesCoverKnownHttpStatusesAndJsonPayloadFailures(): void
    {
        $handler = new HttpErrorHandler(new ResponseFactory());
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/x');

        foreach ([
            [new HttpBadRequestException($request), 'The request could not be understood.'],
            [new HttpNotFoundException($request), 'The requested resource or page was not found.'],
            [(new HttpMethodNotAllowedException($request))->setAllowedMethods(['GET']), 'This request method is not allowed.'],
        ] as [$exception, $message]) {
            self::assertStringContainsString($message, (string) $handler($request, $exception, false, false, false)->getBody());
        }

        self::assertStringContainsString('JSON request is invalid', (string) $handler(
            $request,
            new JsonRequestException($request, 'malformed_json'),
            false,
            false,
            false
        )->getBody());
        self::assertStringContainsString('JSON request is invalid', (string) $handler(
            $request,
            new JsonRequestException($request, 'invalid_json_object'),
            false,
            false,
            false
        )->getBody());
    }

    public function testErrorLoggingCanIncludeOrOmitDetails(): void
    {
        $handler = new HttpErrorHandler(new ResponseFactory());
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/missing');
        self::assertSame(500, $handler($request, new RuntimeException('failure'), false, true, false)->getStatusCode());
        self::assertSame(500, $handler($request, new RuntimeException('failure'), false, true, true)->getStatusCode());
    }

    private function responseFactory(string $vary): ResponseFactoryInterface
    {
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturnCallback(
            static fn (int $status): \Psr\Http\Message\ResponseInterface => (new \Slim\Psr7\Response($status))->withHeader('Vary', $vary)
        );
        return $factory;
    }
}
