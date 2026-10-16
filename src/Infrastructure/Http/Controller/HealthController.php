<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Database\DatabaseHealthProbe;
use App\Infrastructure\Http\Response\JsonResponse;
use Jgut\Slim\Routing\Mapping\Attribute\Group;
use Jgut\Slim\Routing\Mapping\Attribute\Route;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Group(pattern: 'api')]
class HealthController
{
    public function __construct(
        private readonly DatabaseHealthProbe $databaseHealthProbe
    ) {
    }

    #[Route(methods: ['GET'], pattern: 'health')]
    #[OA\Get(
        path: '/api/health',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado da aplicação e conexão com o banco',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'database', type: 'string', example: 'connected')
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function check(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $this->databaseHealthProbe->check();

        return JsonResponse::success($response, [
            'status' => 'ok',
            'database' => 'connected',
        ]);
    }
}
