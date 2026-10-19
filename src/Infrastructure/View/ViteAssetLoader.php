<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use JsonException;
use RuntimeException;

final readonly class ViteAssetLoader
{
    private const string BUILD_DIRECTORY = 'assets/build';

    public function __construct(
        private string $publicDirectory,
        private string $devServerUrl
    ) {
        $parts = parse_url($this->devServerUrl);
        if (!is_array($parts)
            || ($parts['scheme'] ?? null) !== 'http'
            || !in_array($parts['host'] ?? null, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
            || ($parts['port'] ?? null) !== 5173
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || (isset($parts['path']) && $parts['path'] !== '')) {
            throw new RuntimeException('Vite development server must use the local origin http://localhost:5173.');
        }
    }

    public function renderTags(bool $development, string $entry): string
    {
        if ($development) {
            $entryPath = ltrim($this->validateEntry($entry), '/');
            $server = rtrim($this->devServerUrl, '/');

            return sprintf(
                '<script type="module" src="%s/@vite/client"></script>' . "\n" . '<script type="module" src="%s/%s"></script>',
                htmlspecialchars($server, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($server, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($entryPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        $manifestPath = rtrim($this->publicDirectory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . self::BUILD_DIRECTORY . DIRECTORY_SEPARATOR . '.vite/manifest.json';
        if (!is_file($manifestPath) || !is_readable($manifestPath)) {
            throw new RuntimeException(sprintf(
                'Vite manifest is missing at %s. Run `npm --prefix frontend run build` before serving production.',
                $manifestPath
            ));
        }

        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('Vite manifest is invalid JSON at ' . $manifestPath . '.');
        }

        if (!is_array($manifest) || !isset($manifest[$entry]) || !is_array($manifest[$entry])) {
            throw new RuntimeException(sprintf('Vite manifest entry "%s" is missing.', $entry));
        }

        $entryData = $manifest[$entry];
        $file = $entryData['file'] ?? null;
        if (!is_string($file)) {
            throw new RuntimeException(sprintf('Vite manifest entry "%s" has no output file.', $entry));
        }

        $tags = [];
        foreach (($entryData['css'] ?? []) as $cssFile) {
            if (!is_string($cssFile)) {
                throw new RuntimeException(sprintf('Vite manifest entry "%s" has an invalid CSS file.', $entry));
            }
            $tags[] = sprintf('<link rel="stylesheet" href="%s">', $this->assetUrl($cssFile));
        }
        $tags[] = sprintf('<script type="module" src="%s"></script>', $this->assetUrl($file));

        return implode("\n", $tags);
    }

    private function validateEntry(string $entry): string
    {
        if ($entry === '' || str_starts_with($entry, '/') || str_contains($entry, '..') || preg_match('/^[A-Za-z0-9_.\/-]+$/', $entry) !== 1) {
            throw new RuntimeException('Vite entry must be a safe relative path.');
        }

        return $entry;
    }

    private function assetUrl(string $file): string
    {
        if ($file === '' || str_starts_with($file, '/') || str_contains($file, '..') || str_contains($file, '\\')
            || preg_match('/^[A-Za-z0-9_.\/-]+$/', $file) !== 1) {
            throw new RuntimeException('Vite manifest contains an unsafe asset path.');
        }

        return '/' . self::BUILD_DIRECTORY . '/' . htmlspecialchars($file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
