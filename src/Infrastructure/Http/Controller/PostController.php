<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Post\PostService;
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
final readonly class PostController
{
    use ContentControllerSupport;
    public function __construct(private PostService $posts, private UserRepository $users, private MarkdownRenderer $markdown)
    {
    }

    #[Route(methods: ['GET'], pattern: 'posts')]
    #[OA\Get(path: '/api/posts', tags: ['Posts'], parameters: [new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)), new OA\Parameter(name: 'perPage', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100))], responses: [new OA\Response(response: 200, description: 'Posts publicados paginados', content: new OA\JsonContent(ref: '#/components/schemas/PostCollection'))])]
    public function publicList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $page = $this->posts->listPublished(...$this->pageQuery($request));
            return JsonResponse::success($response, ['items' => array_map(fn ($post) => $post->toArray(true), $page['items']), 'total' => $page['total'], 'page' => $page['page'], 'perPage' => $page['perPage']]);
        } catch (DomainException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['GET'], pattern: 'posts/{slug}', placeholders: ['slug' => '[a-z0-9-]+'])]
    #[OA\Get(path: '/api/posts/{slug}', tags: ['Posts'], parameters: [new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Post publicado', content: new OA\JsonContent(ref: '#/components/schemas/PostResult')), new OA\Response(response: 404, description: 'Não encontrado')])]
    public function publicShow(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $post = $this->posts->findPublished($args['slug']);
            $data = $post->toArray(true);
            $data['html'] = $this->markdown->render($post->content);
            return JsonResponse::success($response, ['post' => $data]);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        }
    }

    #[Route(methods: ['GET'], pattern: 'admin/posts')]
    #[OA\Get(path: '/api/admin/posts', tags: ['Admin Posts'], security: [['cookieAuth' => []]], responses: [new OA\Response(response: 200, description: 'Posts administrativos paginados', content: new OA\JsonContent(ref: '#/components/schemas/PostCollection')), new OA\Response(response: 401, description: 'Não autenticado'), new OA\Response(response: 403, description: 'Apenas ADMIN')])]
    public function adminList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            $page = $this->posts->listAdmin(...$this->pageQuery($request));
            return JsonResponse::success($response, ['items' => array_map(fn ($post) => $post->toArray(), $page['items']), 'total' => $page['total'], 'page' => $page['page'], 'perPage' => $page['perPage']]);
        } catch (DomainException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        }
    }

    #[Route(methods: ['POST'], pattern: 'admin/posts')]
    #[OA\Post(path: '/api/admin/posts', tags: ['Admin Posts'], security: [['cookieAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['title','content'], properties: [new OA\Property(property: 'title', type: 'string', maxLength: 200), new OA\Property(property: 'slug', type: 'string'), new OA\Property(property: 'content', type: 'string'), new OA\Property(property: 'excerpt', type: 'string', nullable: true)])), responses: [new OA\Response(response: 201, description: 'Rascunho criado'), new OA\Response(response: 401, description: 'Não autenticado'), new OA\Response(response: 403, description: 'Apenas ADMIN'), new OA\Response(response: 422, description: 'Validação')])]
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            $id = $_SESSION['user_id'];
            return JsonResponse::success($response, ['post' => $this->posts->create($this->body($request), $id)->toArray()], 201);
        } catch (DomainException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                return JsonResponse::error($response, 'Esse slug já está em uso.', 409);
            } throw $e;
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['GET'], pattern: 'admin/posts/{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Get(path: '/api/admin/posts/{id}', tags: ['Admin Posts'], security: [['cookieAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Post', content: new OA\JsonContent(ref: '#/components/schemas/PostResult')), new OA\Response(response: 404, description: 'Não encontrado')])]
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            return JsonResponse::success($response, ['post' => $this->posts->find((int)$args['id'])->toArray()]);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['PUT'], pattern: 'admin/posts/{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Put(path: '/api/admin/posts/{id}', tags: ['Admin Posts'], security: [['cookieAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['title', 'content'], properties: [new OA\Property(property: 'title', type: 'string', maxLength: 200), new OA\Property(property: 'slug', type: 'string'), new OA\Property(property: 'content', type: 'string'), new OA\Property(property: 'excerpt', type: 'string', nullable: true)])), responses: [new OA\Response(response: 200, description: 'Post atualizado'), new OA\Response(response: 404, description: 'Não encontrado'), new OA\Response(response: 422, description: 'Validação')])]
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            return JsonResponse::success($response, ['post' => $this->posts->update((int)$args['id'], $this->body($request))->toArray()]);
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
    #[Route(methods: ['DELETE'], pattern: 'admin/posts/{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Delete(path: '/api/admin/posts/{id}', tags: ['Admin Posts'], security: [['cookieAuth' => []]], responses: [new OA\Response(response: 204, description: 'Post excluído')])]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($failure = $this->adminFailure($request, $response)) {
            return $failure;
        }
        try {
            $this->posts->delete((int)$args['id']);
            return $response->withStatus(204);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['POST'], pattern: 'admin/posts/{id}/publish', placeholders: ['id' => 'numeric'])]
    #[OA\Post(path: '/api/admin/posts/{id}/publish', tags: ['Admin Posts'], security: [['cookieAuth' => []]], responses: [new OA\Response(response: 200, description: 'Post publicado')])]
    public function publish(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->changeStatus($request, $response, (int)$args['id'], true);
    }
    /** @param array<string, string> $args */
    #[Route(methods: ['POST'], pattern: 'admin/posts/{id}/unpublish', placeholders: ['id' => 'numeric'])]
    #[OA\Post(path: '/api/admin/posts/{id}/unpublish', tags: ['Admin Posts'], security: [['cookieAuth' => []]], responses: [new OA\Response(response: 200, description: 'Post despublicado')])]
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
            $post = $publish ? $this->posts->publish($id) : $this->posts->unpublish($id);
            return JsonResponse::success($response, ['post' => $post->toArray()]);
        } catch (ContentNotFound $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        }
    }
}
