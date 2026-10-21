<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Bootstrap\Config;
use App\Infrastructure\Http\Controller\DocumentationController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;
use Slim\Psr7\Factory\ServerRequestFactory;

final class DocumentationControllerTest extends TestCase
{
    public function testServesSwaggerAndOpenApiDocumentsWithOptionalCookieConfiguration(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/docs');
        $swagger = (new DocumentationController())->index($request, new Response());
        self::assertSame('text/html; charset=utf-8', $swagger->getHeaderLine('Content-Type'));
        self::assertNotSame('', (string) $swagger->getBody());

        $openApi = (new DocumentationController())->openApi($request, new Response());
        self::assertSame('application/json', $openApi->getHeaderLine('Content-Type'));
        self::assertIsArray(json_decode((string) $openApi->getBody(), true));

        $config = Config::fromArray(['APP_ENV' => 'testing', 'DB_DATABASE' => 'app', 'DB_USERNAME' => 'user', 'SESSION_COOKIE' => 'custom_session']);
        $configured = (new DocumentationController($config))->openApi($request, new Response());
        $document = json_decode((string) $configured->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('custom_session', $document['components']['securitySchemes']['cookieAuth']['name']);
    }
}
