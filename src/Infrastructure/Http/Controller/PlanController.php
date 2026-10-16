<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Plan\PlanData;
use App\Application\Plan\PlanService;
use App\Domain\Plan\PlanNotFoundException;
use App\Domain\Plan\PlanValidationException;
use App\Infrastructure\Http\Response\JsonResponse;
use Jgut\Slim\Routing\Mapping\Attribute\Group;
use Jgut\Slim\Routing\Mapping\Attribute\Route;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

#[Group(pattern: 'api/plans')]
class PlanController
{
    public function __construct(
        private readonly PlanService $planService
    ) {
    }

    #[Route(methods: ['GET'])]
    #[OA\Get(
        path: '/api/plans',
        tags: ['Plans'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de planos',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Plan')
                )
            )
        ]
    )]
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $plans = $this->planService->listPlans();
        return JsonResponse::success($response, $plans);
    }

    /**
     * @param array<string, string> $args
     */
    #[Route(methods: ['GET'], pattern: '{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Get(
        path: '/api/plans/{id}',
        tags: ['Plans'],
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        )],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Plano encontrado',
                content: new OA\JsonContent(ref: '#/components/schemas/Plan')
            ),
            new OA\Response(
                response: 404,
                description: 'Plano não encontrado',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $id = (int) ($args['id'] ?? 0);

        try {
            $plan = $this->planService->getPlan($id);
            return JsonResponse::success($response, $plan->toArray());
        } catch (PlanNotFoundException) {
            return JsonResponse::error($response, 'Plano não encontrado', 404);
        }
    }

    #[Route(methods: ['POST'])]
    #[OA\Post(
        path: '/api/plans',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PlanInput')
        ),
        tags: ['Plans'],
        responses: [
            new OA\Response(response: 400, description: 'JSON malformado'),
            new OA\Response(
                response: 201,
                description: 'Plano criado',
                content: new OA\JsonContent(ref: '#/components/schemas/Plan')
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = $this->extractData($request);
        $errors = $this->validateHttpInput($data);

        if (!empty($errors)) {
            return JsonResponse::validationError($response, $errors);
        }

        try {
            $planData = PlanData::fromArray($data);
            $created = $this->planService->createPlan($planData);
            return JsonResponse::success($response, $created->toArray(), 201);
        } catch (PlanValidationException $e) {
            return JsonResponse::validationError($response, $e->getErrors());
        }
    }

    /**
     * @param array<string, string> $args
     */
    #[Route(methods: ['PUT'], pattern: '{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Put(
        path: '/api/plans/{id}',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PlanInput')
        ),
        tags: ['Plans'],
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        )],
        responses: [
            new OA\Response(response: 400, description: 'JSON malformado'),
            new OA\Response(
                response: 200,
                description: 'Plano atualizado',
                content: new OA\JsonContent(ref: '#/components/schemas/Plan')
            ),
            new OA\Response(
                response: 404,
                description: 'Plano não encontrado',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $id = (int) ($args['id'] ?? 0);
        $data = $this->extractData($request);
        $errors = $this->validateHttpInput($data);

        if (!empty($errors)) {
            return JsonResponse::validationError($response, $errors);
        }

        try {
            $planData = PlanData::fromArray($data);
            $updated = $this->planService->updatePlan($id, $planData);
            return JsonResponse::success($response, $updated->toArray());
        } catch (PlanNotFoundException) {
            return JsonResponse::error($response, 'Plano não encontrado', 404);
        } catch (PlanValidationException $e) {
            return JsonResponse::validationError($response, $e->getErrors());
        }
    }

    /**
     * @param array<string, string> $args
     */
    #[Route(methods: ['DELETE'], pattern: '{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Delete(
        path: '/api/plans/{id}',
        tags: ['Plans'],
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        )],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Plano excluído',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Plano não encontrado',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function delete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $id = (int) ($args['id'] ?? 0);

        try {
            $this->planService->deletePlan($id);
            return JsonResponse::success($response, ['message' => 'Plano excluído com sucesso']);
        } catch (PlanNotFoundException) {
            return JsonResponse::error($response, 'Plano não encontrado', 404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractData(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            return $parsed;
        }
        if ($parsed !== null) {
            throw new HttpBadRequestException($request, 'O corpo deve ser um objeto JSON.');
        }

        $body = (string) $request->getBody();
        if (trim($body) !== '') {
            try {
                $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new HttpBadRequestException($request, 'O corpo contém JSON malformado.');
            }

            if (!is_array($decoded)) {
                throw new HttpBadRequestException($request, 'O corpo deve ser um objeto JSON.');
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateHttpInput(array $data): array
    {
        $errors = [];

        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        if ($name === '') {
            $errors['name'] = 'O nome do plano é obrigatório.';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'O nome deve ter entre 2 e 100 caracteres.';
        }

        if (!isset($data['price']) || $data['price'] === '') {
            $errors['price'] = 'O preço é obrigatório.';
        } elseif (!is_numeric($data['price']) || (float) $data['price'] < 0) {
            $errors['price'] = 'O preço deve ser um valor numérico positivo.';
        }

        return $errors;
    }
}
