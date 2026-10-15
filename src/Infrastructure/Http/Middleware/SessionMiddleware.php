<?php
declare(strict_types=1);
namespace App\Infrastructure\Http\Middleware;
use App\Bootstrap\Config;
use App\Domain\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
final readonly class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private Config $config, private UserRepository $users) {}
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            session_name($this->config->sessionCookie);
            session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>str_starts_with($this->config->appUrl, 'https://'),'httponly'=>true,'samesite'=>'Lax']);
            session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;
        if (is_int($userId)) {
            $version = $this->users->sessionVersionFor($userId);
            if ($version === null || !isset($_SESSION['auth_version']) || $_SESSION['auth_version'] !== $version) {
                $_SESSION = [];
                session_regenerate_id(true);
            }
        }
        return $handler->handle($request);
    }
}
