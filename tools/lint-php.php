<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$directories = ['src', 'tests', 'database', 'tools', 'public'];
$files = [$root . '/phinx.php', $root . '/.php-cs-fixer.dist.php'];

foreach ($directories as $directory) {
    $path = $root . '/' . $directory;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'phtml'], true)) {
            continue;
        }

        if (str_contains($file->getPathname(), '/public/assets/vendor/')) {
            continue;
        }

        $files[] = $file->getPathname();
    }
}

sort($files);

foreach ($files as $file) {
    passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file), $exitCode);

    if ($exitCode !== 0) {
        exit($exitCode);
    }
}
