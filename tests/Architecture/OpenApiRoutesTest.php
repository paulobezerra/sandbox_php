<?php
declare(strict_types=1);

namespace App\Tests\Architecture;

use App\Bootstrap\App;
use App\Tests\TestCase;
use JsonException;

class OpenApiRoutesTest extends TestCase
{
    /** @throws JsonException */
    public function testGeneratedOpenApiPathsMatchRegisteredApiRoutes(): void
    {
        $openApiFile = dirname(__DIR__, 2) . '/docs/openapi.json';
        self::assertFileExists($openApiFile, 'Generate the OpenAPI document before running tests.');

        $document = json_decode(
            (string) file_get_contents($openApiFile),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        $documentedRoutes = [];
        foreach ($document['paths'] as $path => $operations) {
            foreach (array_keys($operations) as $method) {
                self::assertNotEmpty($operations[$method]['responses'] ?? [], "$method $path must document responses.");
                if (str_starts_with($path, '/api/admin/')) {
                    self::assertContains(['cookieAuth' => []], $operations[$method]['security'] ?? [], "$method $path must document cookie authentication.");
                }
                if (in_array(strtolower($method), ['post', 'put', 'patch'], true)
                    && !str_ends_with($path, '/publish') && !str_ends_with($path, '/unpublish')
                    && $path !== '/api/auth/logout') {
                    self::assertNotEmpty($operations[$method]['requestBody'] ?? [], "$method $path must document its request body.");
                }
                $documentedRoutes[] = strtoupper($method) . ' ' . $path;
            }
        }

        $registeredRoutes = [];
        foreach (App::create()->getRouteCollector()->getRoutes() as $route) {
            $path = preg_replace('/\{([^}:]+):[^}]+\}/', '{$1}', $route->getPattern());
            if (!str_starts_with((string) $path, '/api/')) {
                continue;
            }

            foreach ($route->getMethods() as $method) {
                $registeredRoutes[] = $method . ' ' . $path;
            }
        }

        sort($documentedRoutes);
        sort($registeredRoutes);

        self::assertSame(
            array_values(array_unique($registeredRoutes)),
            $registeredRoutes,
            'Registered API routes must not contain duplicate method/path pairs.'
        );
        self::assertSame($registeredRoutes, $documentedRoutes);
    }
}
