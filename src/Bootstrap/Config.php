<?php

declare(strict_types=1);

namespace App\Bootstrap;

use InvalidArgumentException;
use Dotenv\Dotenv;
use Throwable;
use App\Infrastructure\I18n\LocaleNegotiator;
use App\Infrastructure\I18n\Localization;

final readonly class Config
{
    private function __construct(
        public string $appEnv,
        public bool $appDebug,
        public bool $openApiEnabled,
        public string $databaseHost,
        public int $databasePort,
        public string $databaseName,
        public string $databaseUser,
        public string $databasePassword,
        public string $databaseCharset,
        public string $appUrl,
        public string $sessionCookie,
        /** @var non-empty-list<string> */
        public array $supportedLocales,
        public string $fallbackLocale,
    ) {
    }

    /**
     * @param array<string, string>|null $processEnvironment Explicit source values, primarily for isolated tests.
     */
    public static function fromEnvironment(?array $processEnvironment = null, ?string $environmentFile = null): self
    {
        $names = [
            'APP_ENV',
            'APP_DEBUG',
            'OPENAPI_ENABLED',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'DB_CHARSET',
            'APP_URL', 'SESSION_COOKIE', 'SUPPORTED_LOCALES', 'FALLBACK_LOCALE',
        ];

        if ($processEnvironment === null) {
            $processEnvironment = [];
            foreach ($names as $name) {
                $value = getenv($name);
                if ($value !== false) {
                    $processEnvironment[$name] = $value;
                }
            }
        }

        $environmentFile ??= dirname(__DIR__, 2) . '/.env';
        $fileValues = [];
        if (file_exists($environmentFile)) {
            if (!is_file($environmentFile) || !is_readable($environmentFile)) {
                throw new InvalidArgumentException('Unable to read configuration file ' . basename($environmentFile) . '.');
            }

            $contents = @file_get_contents($environmentFile);
            if ($contents === false) {
                throw new InvalidArgumentException('Unable to read configuration file ' . basename($environmentFile) . '.');
            }

            try {
                $fileValues = Dotenv::parse($contents);
            } catch (Throwable) {
                throw new InvalidArgumentException('Unable to parse configuration file ' . basename($environmentFile) . '.');
            }
        }

        $recognizedFileValues = array_intersect_key($fileValues, array_flip($names));
        $recognizedProcessValues = array_intersect_key($processEnvironment, array_flip($names));

        return self::fromArray(array_replace($recognizedFileValues, $recognizedProcessValues));
    }

    /** @param array<string, string> $values */
    public static function fromArray(array $values): self
    {
        $appEnv = $values['APP_ENV'] ?? 'production';
        if (!in_array($appEnv, ['development', 'production', 'testing'], true)) {
            throw new InvalidArgumentException('APP_ENV must be development, production, or testing.');
        }

        $debugValue = strtolower(trim($values['APP_DEBUG'] ?? 'false'));
        $appDebug = filter_var($debugValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($appDebug === null) {
            throw new InvalidArgumentException('APP_DEBUG must be a valid boolean value.');
        }

        $openApiDefault = in_array($appEnv, ['development', 'testing'], true) ? 'true' : 'false';
        $openApiValue = strtolower(trim($values['OPENAPI_ENABLED'] ?? $openApiDefault));
        $openApiEnabled = filter_var($openApiValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($openApiEnabled === null) {
            throw new InvalidArgumentException('OPENAPI_ENABLED must be a valid boolean value.');
        }

        $databaseName = trim($values['DB_DATABASE'] ?? '');
        if ($databaseName === '') {
            throw new InvalidArgumentException('DB_DATABASE is required.');
        }

        $databaseUser = trim($values['DB_USERNAME'] ?? '');
        if ($databaseUser === '') {
            throw new InvalidArgumentException('DB_USERNAME is required.');
        }

        $portValue = $values['DB_PORT'] ?? '3306';
        $databasePort = filter_var($portValue, FILTER_VALIDATE_INT);
        if ($databasePort === false || $databasePort < 1 || $databasePort > 65535) {
            throw new InvalidArgumentException('DB_PORT must be a number between 1 and 65535.');
        }

        $databaseCharset = $values['DB_CHARSET'] ?? 'utf8mb4';
        if (preg_match('/^[a-zA-Z0-9_]+$/', $databaseCharset) !== 1) {
            throw new InvalidArgumentException('DB_CHARSET contains invalid characters.');
        }

        $appUrl = rtrim($values['APP_URL'] ?? 'http://localhost:8080', '/');
        $urlParts = parse_url($appUrl);
        if (filter_var($appUrl, FILTER_VALIDATE_URL) === false || !is_array($urlParts)
            || !in_array($urlParts['scheme'] ?? '', ['http', 'https'], true)
            || preg_match('/[\r\n]/', $appUrl) === 1) {
            throw new InvalidArgumentException('APP_URL must be a valid HTTP or HTTPS URL.');
        }
        if ($appEnv === 'production' && $urlParts['scheme'] !== 'https') {
            throw new InvalidArgumentException('APP_URL must use HTTPS in production.');
        }
        $sessionCookie = $values['SESSION_COOKIE'] ?? 'sandbox_session';
        if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $sessionCookie) !== 1) {
            throw new InvalidArgumentException('SESSION_COOKIE contains invalid characters.');
        }
        $supportedLocales = array_map('trim', explode(',', $values['SUPPORTED_LOCALES'] ?? 'en,pt-BR'));
        $fallbackLocale = trim($values['FALLBACK_LOCALE'] ?? 'en');
        LocaleNegotiator::validateConfiguration($supportedLocales, $fallbackLocale);
        Localization::load($supportedLocales, $fallbackLocale);
        return new self(
            $appEnv,
            $appDebug,
            $openApiEnabled,
            $values['DB_HOST'] ?? '127.0.0.1',
            $databasePort,
            $databaseName,
            $databaseUser,
            $values['DB_PASSWORD'] ?? '',
            $databaseCharset,
            $appUrl,
            $sessionCookie,
            $supportedLocales,
            $fallbackLocale
        );
    }
}
