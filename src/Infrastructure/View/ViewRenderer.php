<?php
declare(strict_types=1);

namespace App\Infrastructure\View;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ViewRenderer
{
    private string $templatePath;

    public function __construct(?string $templatePath = null)
    {
        $this->templatePath = $templatePath ?? dirname(__DIR__, 2) . '/template';
    }

    /**
     * Renderiza um template .phtml e escreve o resultado no body da Response.
     *
     * @param array<string, mixed> $data Dados passados ao template.
     */
    public function render(
        ResponseInterface $response,
        string $template,
        array $data = []
    ): ResponseInterface {
        $filePath = rtrim($this->templatePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($template, DIRECTORY_SEPARATOR);

        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('Template "%s" não encontrado em "%s"', $template, $filePath));
        }

        $escape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $e = $escape;

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            require $filePath;
        } finally {
            $content = (string) ob_get_clean();
        }

        $response->getBody()->write($content);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Helper de escape avulso para valores dinâmicos.
     */
    public function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
