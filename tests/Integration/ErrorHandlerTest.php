<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Infrastructure\Http\Error\HttpErrorHandler;
use App\Infrastructure\Http\JsonRequestException;
use App\Tests\TestCase;
use Exception;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;

class ErrorHandlerTest extends TestCase
{
    public function testUnhandledExceptionInApiHidesDetailsInProduction(): void
    {
        $responseFactory = new ResponseFactory();
        $handler = new HttpErrorHandler($responseFactory);

        $request = $this->createRequest('GET', '/api/qualquer-coisa');
        $exception = new Exception('Internal confidential error with secret credentials');

        $response = $handler($request, $exception, displayErrorDetails: false, logErrors: false, logErrorDetails: false);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        $this->assertSame(500, $body['error']['status']);
        $this->assertSame('An internal server error occurred.', $body['error']['message']);
        $this->assertSame('en', $response->getHeaderLine('Content-Language'));
        $this->assertSame('Accept-Language', $response->getHeaderLine('Vary'));
        $this->assertArrayNotHasKey('details', $body['error']);
    }

    public function testUnhandledExceptionInApiKeepsStableMessageInDevelopment(): void
    {
        $responseFactory = new ResponseFactory();
        $handler = new HttpErrorHandler($responseFactory);

        $request = $this->createRequest('GET', '/api/qualquer-coisa');
        $exception = new Exception('Detailed internal error for debugging');

        $response = $handler($request, $exception, displayErrorDetails: true, logErrors: false, logErrorDetails: false);

        $this->assertSame(500, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('An internal server error occurred.', $body['error']['message']);
        $this->assertArrayNotHasKey('details', $body['error']);
    }

    public function testErrorLogHasRequestContextWithoutExceptionMessage(): void
    {
        $responseFactory = new ResponseFactory();
        $handler = new HttpErrorHandler($responseFactory);
        $request = $this->createRequest('GET', '/api/private-resource');
        $exception = new Exception('credential=must-not-be-logged');
        $logFile = sys_get_temp_dir() . '/sandbox_php_error_' . bin2hex(random_bytes(8)) . '.log';
        $previousErrorLog = ini_get('error_log');

        try {
            ini_set('error_log', $logFile);
            $handler($request, $exception, false, true, false);
            $log = file_get_contents($logFile);

            self::assertIsString($log);
            self::assertStringContainsString('GET /api/private-resource -> 500', $log);
            self::assertStringNotContainsString('credential=must-not-be-logged', $log);
        } finally {
            if ($previousErrorLog !== false) {
                ini_set('error_log', $previousErrorLog);
            }
            if (is_file($logFile)) {
                unlink($logFile);
            }
        }
    }

    public function testUnhandledExceptionInWebReturnsGenericHtmlInProduction(): void
    {
        $responseFactory = new ResponseFactory();
        $handler = new HttpErrorHandler($responseFactory);

        $request = $this->createRequest('GET', '/pagina-qualquer', ['HTTP_ACCEPT' => 'text/html']);
        $exception = new Exception('Internal error in web rendering');

        $response = $handler($request, $exception, displayErrorDetails: false, logErrors: false, logErrorDetails: false);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $this->assertStringContainsString('Error 500', $body);
        $this->assertStringContainsString('An internal server error occurred.', $body);
        $this->assertStringNotContainsString('Internal error in web rendering', $body);
    }

    public function testErrorUsesNegotiatedPortugueseWithoutExposingInternalDetails(): void
    {
        $localization = \App\Infrastructure\I18n\Localization::load(['en', 'pt-BR'], 'en');
        $handler = new HttpErrorHandler(new ResponseFactory(), $localization);
        $request = $this->createRequest('GET', '/api/private-resource', ['Accept-Language' => 'pt-PT']);
        $response = $handler($request, new Exception('secret'), false, false, false);
        $payload = json_decode((string) $response->getBody(), true);

        self::assertSame('pt-BR', $response->getHeaderLine('Content-Language'));
        self::assertSame('Ocorreu um erro interno no servidor.', $payload['error']['message']);
        self::assertStringNotContainsString('secret', (string) $response->getBody());
    }

    public function testHtmlErrorDeclaresNegotiatedPortugueseLanguage(): void
    {
        $localization = \App\Infrastructure\I18n\Localization::load(['en', 'pt-BR'], 'en');
        $handler = new HttpErrorHandler(new ResponseFactory(), $localization);
        $request = $this->createRequest('GET', '/missing-page', ['Accept' => 'text/html', 'Accept-Language' => 'pt-BR']);
        $response = $handler($request, new Exception('internal detail'), false, false, false);
        $body = (string) $response->getBody();

        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('pt-BR', $response->getHeaderLine('Content-Language'));
        self::assertStringContainsString('<html lang="pt-BR">', $body);
        self::assertStringContainsString('Ocorreu um erro interno no servidor.', $body);
        self::assertStringNotContainsString('internal detail', $body);
    }

    public function testMalformedJsonUsesStableReasonAndLocalizedMessage(): void
    {
        $localization = \App\Infrastructure\I18n\Localization::load(['en', 'pt-BR'], 'en');
        $handler = new HttpErrorHandler(new ResponseFactory(), $localization);

        foreach ([
            ['en', 'The request body contains malformed JSON.'],
            ['pt-BR', 'O corpo da solicitação contém JSON malformado.'],
        ] as [$locale, $message]) {
            $request = $this->createRequest('POST', '/api/auth/register', ['Accept-Language' => $locale]);
            $response = $handler($request, new JsonRequestException($request, 'malformed_json'), false, false, false);
            $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame(400, $response->getStatusCode());
            self::assertSame($message, $payload['error']['message']);
        }
    }
}
