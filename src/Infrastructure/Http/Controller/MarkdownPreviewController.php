<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Response\JsonResponse;
use App\Infrastructure\View\MarkdownRenderer;
use Jgut\Slim\Routing\Mapping\Attribute\Group;
use Jgut\Slim\Routing\Mapping\Attribute\Route;
use OpenApi\Attributes as OA;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

#[Group(pattern: 'api/me')]
final readonly class MarkdownPreviewController
{
    public function __construct(private UserRepository $users, private MarkdownRenderer $markdown)
    {
    }

    #[Route(methods: ['POST'], pattern: 'markdown-preview')]
    #[OA\Post(path: '/api/me/markdown-preview', tags: ['Posts'], security: [['cookieAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['content'], properties: [new OA\Property(property: 'content', type: 'string', maxLength: 60000)])), responses: [new OA\Response(response: 200, description: 'Markdown renderizado com HTML bruto escapado'), new OA\Response(response: 401, description: 'Não autenticado')])]
    public function render(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) || $this->users->findById($id) === null) {
            return JsonResponse::error($response, 'Autenticação necessária.', 401);
        }
        $body = $request->getParsedBody();
        $content = is_array($body) && is_string($body['content'] ?? null) ? $body['content'] : null;
        if ($content === null || mb_strlen($content) > 60000) {
            return JsonResponse::error($response, 'Conteúdo de pré-visualização inválido.', 422);
        }
        return JsonResponse::success($response, ['html' => $this->markdown->render($content)]);
    }
}
