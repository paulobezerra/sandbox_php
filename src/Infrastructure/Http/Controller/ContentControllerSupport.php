<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Http\JsonObject;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

trait ContentControllerSupport
{
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
