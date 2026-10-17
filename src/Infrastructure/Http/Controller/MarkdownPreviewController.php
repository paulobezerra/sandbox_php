<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Response\JsonResponse;
use App\Infrastructure\I18n\Localization;
use App\Infrastructure\View\MarkdownRenderer;
use Jgut\Slim\Routing\Mapping\Attribute\Group;
use Jgut\Slim\Routing\Mapping\Attribute\Route;
use OpenApi\Attributes as OA;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

#[Group(pattern: 'api/me')]
final readonly class MarkdownPreviewController
{
    public function __construct(private UserRepository $users, private MarkdownRenderer $markdown, private Localization $localization)
    {
    }

    #[Route(methods: ['POST'], pattern: 'markdown-preview')]
    #[OA\Post(path: '/api/me/markdown-preview', tags: ['Posts'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], security: [['cookieAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['content'], properties: [new OA\Property(property: 'content', type: 'string', maxLength: 60000)])), responses: [new OA\Response(response: 200, description: 'Markdown rendered with raw HTML escaped'), new OA\Response(response: 401, description: 'Authentication required')])]
    public function render(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) || $this->users->findById($id) === null) {
            return JsonResponse::error($response, $this->localization->translate($this->locale($request), 'post.previewUnauthorized'), 401);
        }
        $body = $request->getParsedBody();
        $content = is_array($body) && is_string($body['content'] ?? null) ? $body['content'] : null;
        if ($content === null || mb_strlen($content) > 60000) {
            return JsonResponse::error($response, $this->localization->translate($this->locale($request), 'post.invalidPreview'), 422);
        }
        return JsonResponse::success($response, ['html' => $this->markdown->render($content)]);
    }

    private function locale(ServerRequestInterface $request): string
    {
        $locale = $request->getAttribute('locale');
        return is_string($locale) ? $locale : $this->localization->negotiator->negotiate($request->getHeaderLine('Accept-Language'));
    }
}
