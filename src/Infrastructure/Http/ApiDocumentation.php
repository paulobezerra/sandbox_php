<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.1.0',
    info: new OA\Info(
        version: '1.0.0',
        description: 'API HTTP do skeleton. A autenticação usa sessão PHP em cookie HttpOnly, SameSite=Lax e Secure conforme APP_URL. Toda operação mutável exige X-CSRF-Token obtido em GET /api/auth/csrf ou /api/auth/me, salvo exceção anônima exata declarada na composição da aplicação; sessões autenticadas nunca podem usar essas exceções. Códigos de recuperação são gerados após reautenticação e exibidos uma única vez.',
        title: 'Sandbox PHP API'
    ),
    servers: [new OA\Server(url: '/')],
    components: new OA\Components(securitySchemes: [new OA\SecurityScheme(
        securityScheme: 'cookieAuth',
        type: 'apiKey',
        in: 'cookie',
        name: 'sandbox_session',
        description: 'Sessão PHP; operações mutáveis exigem X-CSRF-Token obtido em /api/auth/csrf ou /api/auth/me.'
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
            new OA\Property(property: 'html', type: 'string', description: 'HTML de Markdown escapado, presente na API pública'),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'), new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
        ], type: 'object'),
        new OA\Schema(schema: 'Page', required: ['id','title','slug','content','status','createdAt','updatedAt'], properties: [
            new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'slug', type: 'string'), new OA\Property(property: 'content', type: 'string', description: 'Markdown source'),
            new OA\Property(property: 'status', type: 'string', enum: ['DRAFT','PUBLISHED']),
            new OA\Property(property: 'publishedAt', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'html', type: 'string', description: 'HTML de Markdown escapado, presente na API pública'),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'), new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
        ], type: 'object'),
        new OA\Schema(schema: 'Pagination', required: ['total','page','perPage'], properties: [
            new OA\Property(property: 'total', type: 'integer'), new OA\Property(property: 'page', type: 'integer'), new OA\Property(property: 'perPage', type: 'integer'),
        ], type: 'object'),
        new OA\Schema(schema: 'PostCollection', allOf: [new OA\Schema(ref: '#/components/schemas/Pagination')], properties: [new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Post'))], type: 'object'),
        new OA\Schema(schema: 'PageCollection', allOf: [new OA\Schema(ref: '#/components/schemas/Pagination')], properties: [new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Page'))], type: 'object'),
        new OA\Schema(schema: 'PostResult', required: ['post'], properties: [new OA\Property(property: 'post', ref: '#/components/schemas/Post')], type: 'object'),
        new OA\Schema(schema: 'PageResult', required: ['page'], properties: [new OA\Property(property: 'page', ref: '#/components/schemas/Page')], type: 'object'),
    ])
)]
final class ApiDocumentation
{
}
