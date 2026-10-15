<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$sourceDirectory = $projectRoot . '/vendor/swagger-api/swagger-ui/dist';
$targetDirectory = $projectRoot . '/public/assets/vendor/swagger-ui';
$requiredFiles = [
    'favicon-16x16.png',
    'favicon-32x32.png',
    'index.css',
    'index.html',
    'oauth2-redirect.html',
    'swagger-initializer.js',
    'swagger-ui-bundle.js',
    'swagger-ui-standalone-preset.js',
    'swagger-ui.css',
];
$legalFiles = ['LICENSE', 'NOTICE'];

if (!is_dir($sourceDirectory)) {
    fwrite(STDERR, "Swagger UI não está instalado. Execute composer install primeiro.\n");
    exit(1);
}

if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
    fwrite(STDERR, "Não foi possível criar o diretório de assets Swagger UI.\n");
    exit(1);
}

$uiPage = $projectRoot . '/docs/swagger-ui.html';

foreach ($requiredFiles as $file) {
    $contents = file_get_contents($sourceDirectory . '/' . $file);
    if ($contents === false) {
        fwrite(STDERR, sprintf("Arquivo Swagger UI ausente: %s\n", $file));
        exit(1);
    }

    if ($file === 'swagger-initializer.js') {
        $contents = str_replace(
            'https://petstore.swagger.io/v2/swagger.json',
            '/docs/openapi.json',
            $contents
        );
    } elseif ($file === 'index.html') {
        $contents = str_replace('<title>Swagger UI</title>', '<title>API Documentation</title>', $contents);
        foreach ([
            './swagger-ui.css' => '/assets/vendor/swagger-ui/swagger-ui.css',
            'index.css' => '/assets/vendor/swagger-ui/index.css',
            './favicon-16x16.png' => '/assets/vendor/swagger-ui/favicon-16x16.png',
            './favicon-32x32.png' => '/assets/vendor/swagger-ui/favicon-32x32.png',
            './swagger-ui-bundle.js' => '/assets/vendor/swagger-ui/swagger-ui-bundle.js',
            './swagger-ui-standalone-preset.js' => '/assets/vendor/swagger-ui/swagger-ui-standalone-preset.js',
            './swagger-initializer.js' => '/assets/vendor/swagger-ui/swagger-initializer.js',
        ] as $search => $replacement) {
            $contents = str_replace($search, $replacement, $contents);
        }
    }

    $destination = $file === 'index.html' ? $uiPage : $targetDirectory . '/' . $file;
    if (file_put_contents($destination, $contents) === false) {
        fwrite(STDERR, sprintf("Não foi possível publicar o asset Swagger UI: %s\n", $file));
        exit(1);
    }
}

if (is_file($targetDirectory . '/index.html')) {
    unlink($targetDirectory . '/index.html');
}

foreach ($legalFiles as $file) {
    $contents = file_get_contents($projectRoot . '/vendor/swagger-api/swagger-ui/' . $file);
    if ($contents === false || file_put_contents($targetDirectory . '/' . $file, $contents) === false) {
        fwrite(STDERR, sprintf("Não foi possível publicar o arquivo legal Swagger UI: %s\n", $file));
        exit(1);
    }
}
