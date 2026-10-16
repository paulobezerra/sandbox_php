<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Response\JsonResponse;
use App\Infrastructure\Http\JsonObject;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

trait ContentControllerSupport
{
    private function adminFailure(ServerRequestInterface $request, ResponseInterface $response): ?ResponseInterface
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id)) {
            return JsonResponse::error($response, 'Autenticação necessária.', 401);
        }
        $user = $this->users->findById($id);
        if ($user === null) {
            return JsonResponse::error($response, 'Autenticação necessária.', 401);
        }
        if ($user->role !== 'ADMIN') {
            return JsonResponse::error($response, 'Acesso restrito a administradores.', 403);
        }
        return null;
    }

    /** @return array<string,mixed> */
    private function body(ServerRequestInterface $request): array
    {
        return JsonObject::fromRequest($request);
    }

    /** @return array{int,int} */
    private function pageQuery(ServerRequestInterface $request): array
    {
        $query = $request->getQueryParams();
        $page = filter_var($query['page'] ?? '1', FILTER_VALIDATE_INT);
        $perPage = filter_var($query['perPage'] ?? '20', FILTER_VALIDATE_INT);
        return [$page === false ? 0 : $page, $perPage === false ? 0 : $perPage];
    }
}
