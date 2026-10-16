<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Post\PostService;
use App\Application\Post\PostException;
use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Response\JsonResponse;
use App\Infrastructure\I18n\Localization;
use App\Infrastructure\View\MarkdownRenderer;
use Jgut\Slim\Routing\Mapping\Attribute\{Group, Route};
use OpenApi\Attributes as OA;
use PDOException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

#[Group(pattern: 'api')]
final readonly class PostController
{
    use ContentControllerSupport;

    public function __construct(private PostService $posts, private UserRepository $users, private MarkdownRenderer $markdown, private Localization $localization)
    {
    }

    #[Route(methods: ['GET'], pattern: 'posts')]
    #[OA\Get(path: '/api/posts', tags: ['Posts'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)), new OA\Parameter(name: 'perPage', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100))], responses: [new OA\Response(response: 200, description: 'Paginated published Posts', content: new OA\JsonContent(ref: '#/components/schemas/PostCollection'))])]
    public function publicList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $page = $this->posts->listPublished(...$this->pageQuery($request));
            return JsonResponse::success($response, ['items' => array_map(fn ($post) => $post->toArray(true), $page['items']), 'total' => $page['total'], 'page' => $page['page'], 'perPage' => $page['perPage']]);
        } catch (PostException $e) {
            return $this->postError($request, $response, $e->reason);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['GET'], pattern: 'posts/{slug}', placeholders: ['slug' => '[a-z0-9-]+'])]
    #[OA\Get(path: '/api/posts/{slug}', tags: ['Posts'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Published Post', content: new OA\JsonContent(ref: '#/components/schemas/PostResult')), new OA\Response(response: 404, description: 'Not found')])]
    public function publicShow(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $post = $this->posts->findPublished($args['slug']);
            $data = $post->toArray(true);
            $data['html'] = $this->markdown->render($post->content);
            return JsonResponse::success($response, ['post' => $data]);
        } catch (PostException $e) {
            return $this->postError($request, $response, $e->reason);
        }
    }

    #[Route(methods: ['GET'], pattern: 'me/posts')]
    #[OA\Get(path: '/api/me/posts', tags: ['Posts'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], security: [['cookieAuth' => []]], responses: [new OA\Response(response: 200, description: 'Posts belonging to the authenticated user', content: new OA\JsonContent(ref: '#/components/schemas/PostCollection')), new OA\Response(response: 401, description: 'Authentication required')])]
    public function myList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (($authorId = $this->authenticatedAuthorId()) === null) {
            return $this->postError($request, $response, 'post.unauthorized', 401);
        }
        try {
            $page = $this->posts->listMine($authorId, ...$this->pageQuery($request));
            return JsonResponse::success($response, ['items' => array_map(fn ($post) => $post->toArray(), $page['items']), 'total' => $page['total'], 'page' => $page['page'], 'perPage' => $page['perPage']]);
        } catch (PostException $e) {
            return $this->postError($request, $response, $e->reason);
        }
    }

    #[Route(methods: ['POST'], pattern: 'me/posts')]
    #[OA\Post(path: '/api/me/posts', tags: ['Posts'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], security: [['cookieAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['title','content'], properties: [new OA\Property(property: 'title', type: 'string', maxLength: 200), new OA\Property(property: 'slug', type: 'string'), new OA\Property(property: 'content', type: 'string'), new OA\Property(property: 'excerpt', type: 'string', nullable: true)])), responses: [new OA\Response(response: 201, description: 'Draft created'), new OA\Response(response: 401, description: 'Authentication required'), new OA\Response(response: 422, description: 'Validation failed')])]
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (($authorId = $this->authenticatedAuthorId()) === null) {
            return $this->postError($request, $response, 'post.unauthorized', 401);
        }
        try {
            return JsonResponse::success($response, ['post' => $this->posts->create($this->body($request), $authorId)->toArray()], 201);
        } catch (PostException $e) {
            return $this->postError($request, $response, $e->reason);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                return $this->postError($request, $response, 'post.slugConflict', 409);
            }
            throw $e;
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['GET'], pattern: 'me/posts/{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Get(path: '/api/me/posts/{id}', tags: ['Posts'], security: [['cookieAuth' => []]], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Post belonging to the current user'), new OA\Response(response: 401, description: 'Authentication required'), new OA\Response(response: 404, description: 'Not found')])]
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (($authorId = $this->authenticatedAuthorId()) === null) {
            return $this->postError($request, $response, 'post.unauthorized', 401);
        }
        try {
            return JsonResponse::success($response, ['post' => $this->posts->findMine((int) $args['id'], $authorId)->toArray()]);
        } catch (PostException $e) {
            return $this->postError($request, $response, $e->reason);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['PUT'], pattern: 'me/posts/{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Put(path: '/api/me/posts/{id}', tags: ['Posts'], security: [['cookieAuth' => []]], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['title','content'], properties: [new OA\Property(property: 'title', type: 'string', maxLength: 200), new OA\Property(property: 'slug', type: 'string'), new OA\Property(property: 'content', type: 'string'), new OA\Property(property: 'excerpt', type: 'string', nullable: true)])), responses: [new OA\Response(response: 200, description: 'Post updated'), new OA\Response(response: 401, description: 'Authentication required'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation failed')])]
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (($authorId = $this->authenticatedAuthorId()) === null) {
            return $this->postError($request, $response, 'post.unauthorized', 401);
        }
        try {
            return JsonResponse::success($response, ['post' => $this->posts->updateMine((int) $args['id'], $authorId, $this->body($request))->toArray()]);
        } catch (PostException $e) {
            return $this->postError($request, $response, $e->reason);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                return $this->postError($request, $response, 'post.slugConflict', 409);
            }
            throw $e;
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['DELETE'], pattern: 'me/posts/{id}', placeholders: ['id' => 'numeric'])]
    #[OA\Delete(path: '/api/me/posts/{id}', tags: ['Posts'], security: [['cookieAuth' => []]], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Post deleted'), new OA\Response(response: 401, description: 'Authentication required'), new OA\Response(response: 404, description: 'Not found')])]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (($authorId = $this->authenticatedAuthorId()) === null) {
            return $this->postError($request, $response, 'post.unauthorized', 401);
        }
        try {
            $this->posts->deleteMine((int) $args['id'], $authorId);
            return $response->withStatus(204);
        } catch (PostException $e) {
            return $this->postError($request, $response, $e->reason);
        }
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['POST'], pattern: 'me/posts/{id}/publish', placeholders: ['id' => 'numeric'])]
    #[OA\Post(path: '/api/me/posts/{id}/publish', tags: ['Posts'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], security: [['cookieAuth' => []]], responses: [new OA\Response(response: 200, description: 'Published Post'), new OA\Response(response: 401, description: 'Authentication required'), new OA\Response(response: 404, description: 'Not found')])]
    public function publish(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->changeStatus($request, $response, (int) $args['id'], true);
    }

    /** @param array<string, string> $args */
    #[Route(methods: ['POST'], pattern: 'me/posts/{id}/unpublish', placeholders: ['id' => 'numeric'])]
    #[OA\Post(path: '/api/me/posts/{id}/unpublish', tags: ['Posts'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], security: [['cookieAuth' => []]], responses: [new OA\Response(response: 200, description: 'Post unpublished'), new OA\Response(response: 401, description: 'Authentication required'), new OA\Response(response: 404, description: 'Not found')])]
    public function unpublish(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->changeStatus($request, $response, (int) $args['id'], false);
    }

    private function changeStatus(ServerRequestInterface $request, ResponseInterface $response, int $id, bool $publish): ResponseInterface
    {
        if (($authorId = $this->authenticatedAuthorId()) === null) {
            return $this->postError($request, $response, 'post.unauthorized', 401);
        }
        try {
            $post = $publish ? $this->posts->publishMine($id, $authorId) : $this->posts->unpublishMine($id, $authorId);
            return JsonResponse::success($response, ['post' => $post->toArray()]);
        } catch (PostException $e) {
            return $this->postError($request, $response, $e->reason);
        }
    }

    private function authenticatedAuthorId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) || $this->users->findById($id) === null) {
            return null;
        }
        return $id;
    }

    private function postError(ServerRequestInterface $request, ResponseInterface $response, string $key, ?int $status = null): ResponseInterface
    {
        return $this->postErrorForLocale($response, $key, $status, $this->locale($request));
    }

    private function postErrorForLocale(ResponseInterface $response, string $key, ?int $status = null, string $locale = 'en'): ResponseInterface
    {
        $status ??= $key === 'post.notFound' ? 404 : 422;
        return JsonResponse::error($response, $this->localization->translate($locale, $key), $status);
    }

    private function locale(ServerRequestInterface $request): string
    {
        $locale = $request->getAttribute('locale');
        return is_string($locale) ? $locale : $this->localization->negotiator->negotiate($request->getHeaderLine('Accept-Language'));
    }
}
