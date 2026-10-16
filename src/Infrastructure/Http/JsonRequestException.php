<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Slim\Exception\HttpBadRequestException;
use Psr\Http\Message\ServerRequestInterface;

final class JsonRequestException extends HttpBadRequestException
{
    public function __construct(ServerRequestInterface $request, public readonly string $reason)
    {
        parent::__construct($request, $reason);
    }
}
