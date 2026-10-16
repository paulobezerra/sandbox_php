<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Page\PageService;
use App\Domain\ContentNotFound;
use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Response\JsonResponse;
use App\Infrastructure\View\MarkdownRenderer;
use DomainException;
use Jgut\Slim\Routing\Mapping\Attribute\Group;
use Jgut\Slim\Routing\Mapping\Attribute\Route;
use OpenApi\Attributes as OA;
use PDOException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

#[Group(pattern: 'api')]
final readonly class PageController
{
    use ContentControllerSupport;
    public function __construct(private PageService $pages, private UserRepository $users, private MarkdownRenderer $markdown)
    {
    }

    #[Route(methods: ['GET'], pattern: 'pages')]
    #[OA\Get(path: '/api/pages', tags: ['Pages'], responses: [new OA\Response(response: 200, description: 'Páginas publicadas paginadas', content: new OA\JsonContent(ref: '#/components/schemas/PageCollection'))])]
    public function publicList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $page = $this->pages->listPublished(...$this->pageQuery($request));
            return JsonResponse::success($response, ['items' => array_map(fn ($item) => $item->toArray(true), $page['items']),'total' => $page['total'],'page' => $page['page'],'perPage' => $page['perPage']]);
        } catch (DomainException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['GET'], pattern: 'pages/{slug}', placeholders: ['slug' => '[a-z0-9-]+'])]
    #[OA\Get(path: '/api/pages/{slug}', tags: ['Pages'], parameters: [new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Página publicada', content: new OA\JsonContent(ref: '#/components/schemas/PageResult')),new OA\Response(response: 404, description:'Não encontrada')])]
    public function publicShow(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $item = $this->pages->findPublished($args['slug']);
            $data = $item->toArray(true);
            $data['html'] = $this->markdown->render($item->content);
            return JsonResponse::success($response, ['page' => $data]);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        }
    }

    #[Route(methods: ['GET'], pattern: 'admin/pages')]
    #[OA\Get(path: '/api/admin/pages', tags: ['Admin Pages'], security: [['cookieAuth' => []]], responses: [new OA\Response(response: 200, description: 'Páginas administrativas paginadas', content: new OA\JsonContent(ref: '#/components/schemas/PageCollection')),new OA\Response(response:401, description:'Não autenticado'),new OA\Response(response:403, description:'Apenas ADMIN')])]
    public function adminList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            $page = $this->pages->listAdmin(...$this->pageQuery($request));
            return JsonResponse::success($response, ['items' => array_map(fn ($item) => $item->toArray(), $page['items']),'total' => $page['total'],'page' => $page['page'],'perPage' => $page['perPage']]);
        } catch (DomainException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        }
    }

    #[Route(methods: ['POST'], pattern: 'admin/pages')]
    #[OA\Post(path:'/api/admin/pages', tags:['Admin Pages'], security:[['cookieAuth' => []]], requestBody:new OA\RequestBody(required:true, content:new OA\JsonContent(type:'object', required:['title','content'], properties:[new OA\Property(property:'title', type:'string', maxLength:200),new OA\Property(property:'slug', type:'string'),new OA\Property(property:'content', type:'string')])), responses:[new OA\Response(response:201, description:'Rascunho criado'),new OA\Response(response:422, description:'Validação')])]
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            return JsonResponse::success($response, ['page' => $this->pages->create($this->body($request))->toArray()], 201);
        } catch (DomainException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                return JsonResponse::error($response, 'Esse slug já está em uso.', 409);
            } throw $e;
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods:['GET'], pattern:'admin/pages/{id}', placeholders:['id' => 'numeric'])]
    #[OA\Get(path:'/api/admin/pages/{id}', tags:['Admin Pages'], security:[['cookieAuth' => []]], parameters:[new OA\Parameter(name:'id', in:'path', required:true, schema:new OA\Schema(type:'integer'))], responses:[new OA\Response(response:200, description:'Página', content:new OA\JsonContent(ref:'#/components/schemas/PageResult')),new OA\Response(response:404, description:'Não encontrada')])]
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            return JsonResponse::success($response, ['page' => $this->pages->find((int)$args['id'])->toArray()]);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods:['PUT'], pattern:'admin/pages/{id}', placeholders:['id' => 'numeric'])]
    #[OA\Put(path:'/api/admin/pages/{id}', tags:['Admin Pages'], security:[['cookieAuth' => []]], parameters:[new OA\Parameter(name:'id', in:'path', required:true, schema:new OA\Schema(type:'integer'))], requestBody:new OA\RequestBody(required:true, content:new OA\JsonContent(type:'object', required:['title','content'], properties:[new OA\Property(property:'title', type:'string', maxLength:200),new OA\Property(property:'slug', type:'string'),new OA\Property(property:'content', type:'string')])), responses:[new OA\Response(response:200, description:'Página atualizada'),new OA\Response(response:404, description:'Não encontrada'),new OA\Response(response:422, description:'Validação')])]
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            return JsonResponse::success($response, ['page' => $this->pages->update((int)$args['id'], $this->body($request))->toArray()]);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        } catch (DomainException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                return JsonResponse::error($response, 'Esse slug já está em uso.', 409);
            } throw $e;
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods:['DELETE'], pattern:'admin/pages/{id}', placeholders:['id' => 'numeric'])]
    #[OA\Delete(path:'/api/admin/pages/{id}', tags:['Admin Pages'], security:[['cookieAuth' => []]], responses:[new OA\Response(response:204, description:'Página excluída')])]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            $this->pages->delete((int)$args['id']);
            return $response->withStatus(204);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods:['POST'], pattern:'admin/pages/{id}/publish', placeholders:['id' => 'numeric'])]
    #[OA\Post(path:'/api/admin/pages/{id}/publish', tags:['Admin Pages'], security:[['cookieAuth' => []]], responses:[new OA\Response(response:200, description:'Página publicada')])]
    public function publish(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->changeStatus($request, $response, (int)$args['id'], true);
    }
    /** @param array<string, string> $args */
    #[Route(methods:['POST'], pattern:'admin/pages/{id}/unpublish', placeholders:['id' => 'numeric'])]
    #[OA\Post(path:'/api/admin/pages/{id}/unpublish', tags:['Admin Pages'], security:[['cookieAuth' => []]], responses:[new OA\Response(response:200, description:'Página despublicada')])]
    public function unpublish(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->changeStatus($request, $response, (int)$args['id'], false);
    }
    private function changeStatus(ServerRequestInterface $request, ResponseInterface $response, int $id, bool $publish): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            $page = $publish ? $this->pages->publish($id) : $this->pages->unpublish($id);
            return JsonResponse::success($response, ['page' => $page->toArray()]);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        }
    }
}
