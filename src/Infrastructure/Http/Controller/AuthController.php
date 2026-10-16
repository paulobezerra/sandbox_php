<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Auth\{AuthException, AuthService};
use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Response\JsonResponse;
use App\Infrastructure\Http\JsonObject;
use App\Infrastructure\I18n\Localization;
use Jgut\Slim\Routing\Mapping\Attribute\{Group, Route};
use OpenApi\Attributes as OA;
use PDOException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

#[Group(pattern: 'api/auth')]
final readonly class AuthController
{
    public function __construct(
        private AuthService $auth,
        private UserRepository $users,
        private Localization $localization
    ) {
    }

    #[Route(methods: ['GET'], pattern: 'csrf')]
    #[OA\Get(path: '/api/auth/csrf', tags: ['Auth'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'CSRF token for the session')])]
    public function csrf(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        return JsonResponse::success($response, ['csrfToken' => $_SESSION['csrf']]);
    }

    #[Route(methods: ['POST'], pattern: 'register')]
    #[OA\Post(path: '/api/auth/register', tags: ['Auth'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['name', 'email', 'password'], properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'email', type: 'string', format: 'email'), new OA\Property(property: 'password', type: 'string', minLength: 12)])), responses: [new OA\Response(response: 201, description: 'User created'), new OA\Response(response: 400, description: 'Invalid request payload'), new OA\Response(response: 409, description: 'Email already registered'), new OA\Response(response: 422, description: 'Invalid account data')])]
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $this->data($request);
            $user = $this->auth->register($this->string($data, 'name'), $this->string($data, 'email'), $this->string($data, 'password'));
            return JsonResponse::success($response, ['user' => $user->publicData()], 201);
        } catch (AuthException $error) {
            return $this->authError($request, $response, $error);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                return JsonResponse::error($response, $this->localization->translate($this->locale($request), 'auth.emailExists'), 409);
            }
            throw $error;
        }
    }

    #[Route(methods: ['POST'], pattern: 'login')]
    #[OA\Post(path: '/api/auth/login', tags: ['Auth'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email', 'password'], properties: [new OA\Property(property: 'email', type: 'string', format: 'email'), new OA\Property(property: 'password', type: 'string', minLength: 1)])), responses: [new OA\Response(response: 200, description: 'Session started and CSRF token renewed'), new OA\Response(response: 400, description: 'Invalid request payload'), new OA\Response(response: 401, description: 'Invalid credentials')])]
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $this->data($request);
            $user = $this->auth->login($this->string($data, 'email'), $this->string($data, 'password'));
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id;
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            return JsonResponse::success($response, ['user' => $user->publicData(), 'csrfToken' => $_SESSION['csrf']]);
        } catch (AuthException $error) {
            return $this->authError($request, $response, $error);
        }
    }

    #[Route(methods: ['POST'], pattern: 'logout')]
    #[OA\Post(path: '/api/auth/logout', tags: ['Auth'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Session ended')])]
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parameters['path'], $parameters['domain'], $parameters['secure'], $parameters['httponly']);
        }
        session_destroy();
        return JsonResponse::success($response, ['message' => $this->localization->translate($this->locale($request), 'auth.logoutSuccess')]);
    }

    #[Route(methods: ['GET'], pattern: 'me')]
    #[OA\Get(path: '/api/auth/me', tags: ['Auth'], parameters: [new OA\Parameter(name: 'Accept-Language', in: 'header', description: 'Selects the language of application-owned response messages.', schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Current user or anonymous session')])]
    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $id = $_SESSION['user_id'] ?? null;
        $user = is_int($id) ? $this->users->findById($id) : null;
        return JsonResponse::success($response, ['user' => $user?->publicData(), 'csrfToken' => $_SESSION['csrf']]);
    }

    /** @return array<string, mixed> */
    private function data(ServerRequestInterface $request): array
    {
        return JsonObject::fromRequest($request);
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        if (!isset($data[$key]) || !is_string($data[$key])) {
            throw new AuthException('invalid_field');
        }
        return $data[$key];
    }

    private function authError(ServerRequestInterface $request, ResponseInterface $response, AuthException $error): ResponseInterface
    {
        $status = match ($error->reason) {
            'invalid_credentials' => 401,
            'email_exists' => 409,
            default => 422,
        };
        $key = match ($error->reason) {
            'invalid_credentials' => 'auth.invalidCredentials',
            'email_exists' => 'auth.emailExists',
            'invalid_name' => 'auth.invalidName',
            'invalid_email' => 'auth.invalidEmail',
            'invalid_password' => 'auth.invalidPassword',
            default => 'auth.invalidData',
        };
        return JsonResponse::error($response, $this->localization->translate($this->locale($request), $key), $status);
    }

    private function locale(ServerRequestInterface $request): string
    {
        $locale = $request->getAttribute('locale');
        return is_string($locale) ? $locale : $this->localization->negotiator->negotiate($request->getHeaderLine('Accept-Language'));
    }
}
