<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SharedHostingInstallerTest extends TestCase
{
    #[DataProvider('emptyInstallerPathProvider')]
    public function testRejectsEmptyDotenvPathBeforeUsingFallbackOrStartingDeployment(string $variableName): void
    {
        $directory = sys_get_temp_dir() . '/sandbox-installer-' . bin2hex(random_bytes(8));
        $toolsDirectory = $directory . '/tools';
        $binDirectory = $directory . '/bin';
        $logPath = $directory . '/commands.log';

        self::assertTrue(mkdir($directory));
        self::assertTrue(mkdir($toolsDirectory));
        self::assertTrue(mkdir($binDirectory));

        $installer = file_get_contents(dirname(__DIR__, 2) . '/tools/deploy-shared-hosting.sh');
        self::assertNotFalse($installer);
        self::assertNotFalse(file_put_contents($toolsDirectory . '/deploy-shared-hosting.sh', $installer));
        self::assertTrue(chmod($toolsDirectory . '/deploy-shared-hosting.sh', 0755));

        $fallbackPhp = <<<'SH'
            #!/usr/bin/env bash
            printf 'fallback:PHP_BIN %s\n' "$*" >> "$INSTALLER_TEST_LOG"
            if [[ "${1:-}" == '-r' ]]; then
                exit 0
            fi
            printf 'Composer version 2.10.3 2026-10-17 00:00:00\n'
            SH;
        $fallbackGit = <<<'SH'
            #!/usr/bin/env bash
            printf 'fallback:GIT_BIN %s\n' "$*" >> "$INSTALLER_TEST_LOG"
            exit 1
            SH;
        $fallbackComposer = <<<'PHP'
            <?php
            file_put_contents((string) getenv('INSTALLER_TEST_LOG'), "fallback:COMPOSER_BIN\n", FILE_APPEND);
            echo "Composer version 2.10.3 2026-10-17 00:00:00\n";
            PHP;

        self::assertNotFalse(file_put_contents($binDirectory . '/php', $fallbackPhp));
        self::assertNotFalse(file_put_contents($binDirectory . '/git', $fallbackGit));
        self::assertNotFalse(file_put_contents($directory . '/composer.phar', $fallbackComposer));
        self::assertTrue(chmod($binDirectory . '/php', 0755));
        self::assertTrue(chmod($binDirectory . '/git', 0755));
        self::assertNotFalse(file_put_contents($directory . '/.env', $variableName . "=\n"));

        $environment = getenv();
        self::assertIsArray($environment);
        $environment['PATH'] = $binDirectory . ':' . ($environment['PATH'] ?? '/usr/bin:/bin');
        $environment['INSTALLER_TEST_LOG'] = $logPath;
        $environment['PHP_BIN'] = PHP_BINARY;
        $environment['GIT_BIN'] = '/usr/bin/git';
        $environment['COMPOSER_BIN'] = $directory . '/composer.phar';
        unset($environment[$variableName]);

        try {
            $process = proc_open(
                [$toolsDirectory . '/deploy-shared-hosting.sh'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                '/tmp',
                $environment,
            );
            self::assertIsResource($process);

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $commands = is_file($logPath) ? (string) file_get_contents($logPath) : '';

            self::assertNotSame(0, $exitCode);
            self::assertSame('', $stdout);
            self::assertStringContainsString($variableName . ' is configured but empty.', $stderr);
            self::assertStringNotContainsString('fallback:' . $variableName, $commands);
            self::assertDoesNotMatchRegularExpression('/\b(pull|install|migrate)\b/', $commands);
        } finally {
            foreach ([
                $logPath,
                $directory . '/.env',
                $directory . '/composer.phar',
                $binDirectory . '/php',
                $binDirectory . '/git',
                $toolsDirectory . '/deploy-shared-hosting.sh',
            ] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            rmdir($binDirectory);
            rmdir($toolsDirectory);
            rmdir($directory);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function emptyInstallerPathProvider(): iterable
    {
        yield 'PHP_BIN' => ['PHP_BIN'];
        yield 'GIT_BIN' => ['GIT_BIN'];
        yield 'COMPOSER_BIN' => ['COMPOSER_BIN'];
    }
}
