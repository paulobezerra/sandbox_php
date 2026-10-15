<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class DocumentationController
{
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

        return $this->writeFile($response, $filePath, 'application/json');
    }

    private function writeFile(
        ResponseInterface $response,
        string $filePath,
        string $contentType
    ): ResponseInterface {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new RuntimeException('O arquivo de documentação da API não está disponível.');
        }

        $response->getBody()->write($contents);

        return $response->withHeader('Content-Type', $contentType);
    }
}
