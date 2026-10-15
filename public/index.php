<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../src/boot.php';

$app->run();
