<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Bootstrap\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class DocumentationController
{
    public function __construct(private readonly ?Config $config = null)
    {
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $filePath = dirname(__DIR__, 4) . '/docs/swagger-ui.html';

        return $this->writeFile($response, $filePath, 'text/html; charset=utf-8');
    }

    public function openApi(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $filePath = dirname(__DIR__, 4) . '/docs/openapi.json';

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new RuntimeException('The API documentation file is unavailable.');
        }
        if ($this->config !== null) {
            $document = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            $document['components']['securitySchemes']['cookieAuth']['name'] = $this->config->sessionCookie;
            $contents = json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }
        $response->getBody()->write($contents);
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function writeFile(
        ResponseInterface $response,
        string $filePath,
        string $contentType
    ): ResponseInterface {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new RuntimeException('The API documentation file is unavailable.');
        }

        $response->getBody()->write($contents);

        return $response->withHeader('Content-Type', $contentType);
    }
}
