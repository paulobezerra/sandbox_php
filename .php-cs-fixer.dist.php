<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(['src', 'tests', 'database', 'tools', 'public'])
    ->append(['phinx.php', '.php-cs-fixer.dist.php'])
    ->exclude(['assets/vendor'])
    ->ignoreDotFiles(false)
    ->name('/\.(php|phtml)$/');

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
