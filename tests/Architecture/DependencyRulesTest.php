<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class DependencyRulesTest extends TestCase
{
    public function testDomainDoesNotDependOnInfrastructureOrHttpLibraries(): void
    {
        $this->assertLayerDoesNotReference(
            layer: 'Domain',
            forbiddenPattern: '/\b(?:App\\\\Infrastructure\\\\|Slim\\\\|Psr\\\\Http\\\\|PDO\b)/'
        );
    }

    public function testApplicationDoesNotDependOnInfrastructureOrHttpLibraries(): void
    {
        $this->assertLayerDoesNotReference(
            layer: 'Application',
            forbiddenPattern: '/\b(?:App\\\\Infrastructure\\\\|Slim\\\\|Psr\\\\Http\\\\|PDO\b)/'
        );
    }

    private function assertLayerDoesNotReference(string $layer, string $forbiddenPattern): void
    {
        $root = dirname(__DIR__, 2) . '/src/' . $layer;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        $phpFiles = new RegexIterator($files, '/\.php$/');

        foreach ($phpFiles as $file) {
            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);
            self::assertDoesNotMatchRegularExpression(
                $forbiddenPattern,
                $source,
                sprintf('%s must not depend on HTTP or infrastructure (%s).', $layer, $file->getFilename())
            );
        }
    }
}
