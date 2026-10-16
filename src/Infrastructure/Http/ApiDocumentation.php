<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.1.0',
    info: new OA\Info(
        version: '1.0.0',
        description: 'Skeleton HTTP API. Authentication uses a PHP session with an HttpOnly, SameSite=Lax cookie that is Secure according to APP_URL. Every mutating operation requires an X-CSRF-Token obtained from GET /api/auth/csrf or /api/auth/me, except for an exact anonymous exception declared in the application composition; authenticated sessions can never use these exceptions.',
        title: 'Sandbox PHP API'
    ),
    servers: [new OA\Server(url: '/')],
    components: new OA\Components(securitySchemes: [new OA\SecurityScheme(
        securityScheme: 'cookieAuth',
        type: 'apiKey',
        in: 'cookie',
        name: 'sandbox_session',
        description: 'PHP session; mutating operations require an X-CSRF-Token obtained from /api/auth/csrf or /api/auth/me.'
    )], schemas: [
        new OA\Schema(
            schema: 'Error',
            required: ['error'],
            properties: [
                new OA\Property(
                    property: 'error',
                    required: ['status', 'message'],
                    properties: [
                        new OA\Property(property: 'status', type: 'integer'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'details',
                            type: 'object',
                            additionalProperties: true
                        )
                    ],
                    type: 'object'
                )
            ],
            type: 'object'
        ),
        new OA\Schema(schema: 'Post', required: ['id','title','slug','content','status','publishedAt','createdAt'], properties: [
            new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'slug', type: 'string'), new OA\Property(property: 'content', type: 'string', description: 'Markdown source'),
            new OA\Property(property: 'excerpt', type: 'string', nullable: true), new OA\Property(property: 'status', type: 'string', enum: ['DRAFT','PUBLISHED']),
            new OA\Property(property: 'authorId', type: 'integer'), new OA\Property(property: 'publishedAt', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'html', type: 'string', description: 'Escaped Markdown HTML, included in the public API'),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'), new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
        ], type: 'object'),
        new OA\Schema(schema: 'Pagination', required: ['total','page','perPage'], properties: [
            new OA\Property(property: 'total', type: 'integer'), new OA\Property(property: 'page', type: 'integer'), new OA\Property(property: 'perPage', type: 'integer'),
        ], type: 'object'),
        new OA\Schema(schema: 'PostCollection', allOf: [new OA\Schema(ref: '#/components/schemas/Pagination')], properties: [new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Post'))], type: 'object'),
        new OA\Schema(schema: 'PostResult', required: ['post'], properties: [new OA\Property(property: 'post', ref: '#/components/schemas/Post')], type: 'object'),
    ])
)]
final class ApiDocumentation
{
}
