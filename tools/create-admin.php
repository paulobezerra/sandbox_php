<?php

declare(strict_types=1);

use App\Bootstrap\Config;
use App\Domain\User\UserRepository;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\MysqlUserRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli' || $argc !== 3) {
    fwrite(STDERR, "Uso: php tools/create-admin.php \"Nome\" email@example.com\n");
    exit(2);
}
$name = trim($argv[1]);
$email = mb_strtolower(trim($argv[2]));
if ($name === '' || mb_strlen($name) > 120 || filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 254) {
    fwrite(STDERR, "Nome ou e-mail inválido.\n");
    exit(2);
}

$password = promptPassword('Senha (mínimo 12 caracteres): ');
$confirmation = promptPassword('Confirme a senha: ');
if ($password !== $confirmation || strlen($password) < 12 || strlen($password) > 4096) {
    fwrite(STDERR, "As senhas não coincidem ou não atendem ao tamanho mínimo.\n");
    exit(2);
}
try {
    $pdo = ConnectionFactory::create(Config::fromEnvironment());
    $repository = new MysqlUserRepository($pdo);
    if ($repository->findByEmail($email) !== null) {
        fwrite(STDERR, "Já existe uma conta com esse e-mail.\n");
        exit(1);
    }
    $admin = $repository->createAdmin($name, $email, password_hash($password, PASSWORD_DEFAULT));
    fwrite(STDOUT, sprintf("Administrador %s criado (id %d).\n", $admin->email, $admin->id));
} catch (Throwable $exception) {
    fwrite(STDERR, "Não foi possível criar o administrador. Verifique a configuração e tente novamente.\n");
    exit(1);
}

function promptPassword(string $prompt): string
{
    fwrite(STDERR, $prompt);
    $isTty = function_exists('posix_isatty') && posix_isatty(STDIN);
    if ($isTty) {
        system('stty -echo');
    }
    try {
        $value = fgets(STDIN);
    } finally {
        if ($isTty) {
            system('stty echo');
            fwrite(STDERR, "\n");
        }
    }
    return trim($value === false ? '' : $value);
}
