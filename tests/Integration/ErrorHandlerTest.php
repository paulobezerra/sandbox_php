<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Infrastructure\Http\Error\HttpErrorHandler;
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
        $exception = new Exception('Erro interno confidencial com credenciais secretas');

        $response = $handler($request, $exception, displayErrorDetails: false, logErrors: false, logErrorDetails: false);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        $this->assertSame(500, $body['error']['status']);
        $this->assertSame('Ocorreu um erro interno no servidor.', $body['error']['message']);
        $this->assertArrayNotHasKey('details', $body['error']);
    }

    public function testUnhandledExceptionInApiKeepsStableMessageInDevelopment(): void
    {
        $responseFactory = new ResponseFactory();
        $handler = new HttpErrorHandler($responseFactory);

        $request = $this->createRequest('GET', '/api/qualquer-coisa');
        $exception = new Exception('Erro interno detalhado para debug');

        $response = $handler($request, $exception, displayErrorDetails: true, logErrors: false, logErrorDetails: false);

        $this->assertSame(500, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Ocorreu um erro interno no servidor.', $body['error']['message']);
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
        $exception = new Exception('Erro interno no render web');

        $response = $handler($request, $exception, displayErrorDetails: false, logErrors: false, logErrorDetails: false);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $this->assertStringContainsString('Erro 500', $body);
        $this->assertStringContainsString('Ocorreu um erro interno no servidor.', $body);
        $this->assertStringNotContainsString('Erro interno no render web', $body);
    }
}
