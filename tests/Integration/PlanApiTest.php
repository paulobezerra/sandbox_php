<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;

class PlanApiTest extends TestCase
{
    public function testListPlansReturns200WithArray(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('GET', '/api/plans');
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
    }

    public function testCreatePlanValidationFailsWhenFieldsAreMissing(): void
    {
        $app = $this->getApp();
        $request = $this->createJsonRequest('POST', '/api/plans', [
            'name' => '',
            'price' => 'invalido',
        ]);
        $response = $app->handle($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        $this->assertSame(422, $body['error']['status']);
        $this->assertArrayHasKey('name', $body['error']['details']);
        $this->assertArrayHasKey('price', $body['error']['details']);
    }

    public function testMalformedJsonReturnsBadRequestInsteadOfValidationError(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('POST', '/api/plans', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream('{invalid json'));

        $response = $app->handle($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testFullCrudLifecycleForPlan(): void
    {
        $app = $this->getApp();

        // 1. Create
        $createRequest = $this->createJsonRequest('POST', '/api/plans', [
            'name' => 'Plano Teste Integração',
            'price' => 49.90,
            'description' => 'Descrição do plano de teste',
        ]);
        self::assertSame('Plano Teste Integração', json_decode((string) $createRequest->getBody(), true)['name'] ?? null);
        $createResponse = $app->handle($createRequest);
        $this->assertSame(201, $createResponse->getStatusCode());

        $created = json_decode((string) $createResponse->getBody(), true);
        $this->assertIsArray($created);
        $this->assertArrayHasKey('id', $created);
        $id = (int) $created['id'];
        $this->assertSame('Plano Teste Integração', $created['name']);
        $this->assertEquals(49.90, (float) $created['price']);

        // 2. Get / Show
        $showRequest = $this->createRequest('GET', "/api/plans/{$id}");
        $showResponse = $app->handle($showRequest);
        $this->assertSame(200, $showResponse->getStatusCode());
        $shown = json_decode((string) $showResponse->getBody(), true);
        $this->assertSame($id, (int) $shown['id']);
        $this->assertSame('Plano Teste Integração', $shown['name']);

        // 3. Update
        $updateRequest = $this->createJsonRequest('PUT', "/api/plans/{$id}", [
            'name' => 'Plano Teste Atualizado',
            'price' => 59.90,
            'description' => 'Nova descrição',
        ]);
        $updateResponse = $app->handle($updateRequest);
        $this->assertSame(200, $updateResponse->getStatusCode());
        $updated = json_decode((string) $updateResponse->getBody(), true);
        $this->assertSame('Plano Teste Atualizado', $updated['name']);
        $this->assertEquals(59.90, (float) $updated['price']);

        // 4. Delete
        $deleteRequest = $this->createRequest('DELETE', "/api/plans/{$id}");
        $deleteResponse = $app->handle($deleteRequest);
        $this->assertSame(200, $deleteResponse->getStatusCode());

        // 5. Show after delete (should be 404)
        $showAgainResponse = $app->handle($this->createRequest('GET', "/api/plans/{$id}"));
        $this->assertSame(404, $showAgainResponse->getStatusCode());
    }

    public function testGetNonExistentPlanReturns404(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('GET', '/api/plans/999999');
        $response = $app->handle($request);

        $this->assertSame(404, $response->getStatusCode());
    }
}
