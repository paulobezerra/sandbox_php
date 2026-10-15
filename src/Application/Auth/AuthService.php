<?php
declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\User\User;
use App\Domain\User\UserRepository;

final readonly class AuthService
{
    private const int RECOVERY_CODE_COUNT = 10;

    public function __construct(private UserRepository $users) {}

    public function register(string $name, string $email, string $password, string $clientKey = 'local'): User
    {
        $this->limit('register|' . $clientKey, 10);
        $name = trim($name);
        $email = mb_strtolower(trim($email));
        if ($name === '' || mb_strlen($name) > 120) throw new AuthException('invalid_name');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 254) throw new AuthException('invalid_email');
        $this->validatePassword($password);
        if ($this->users->findByEmail($email) !== null) throw new AuthException('email_exists');
        return $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
    }

    public function login(string $email, string $password, string $clientKey): User
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $accountKey = hash('sha256', 'login-account|' . $normalizedEmail . '|' . $clientKey);
        $originKey = hash('sha256', 'login-origin|' . $clientKey);
        $accountAttempts = $this->users->recordAttempt($accountKey, date('Y-m-d H:i:s'));
        $originAttempts = $this->users->recordAttempt($originKey, date('Y-m-d H:i:s'));
        if ($accountAttempts > 10 || $originAttempts > 30) {
            throw new AuthException('rate_limited');
        }
        $user = $this->users->findByEmail($normalizedEmail);
        if ($user === null || !password_verify($password, $user->passwordHash)) throw new AuthException('invalid_credentials');
        $this->users->clearAttempts($accountKey);
        $this->users->clearAttempts($originKey);
        return $user;
    }

    /** @return list<string> Codes are returned once; only their hashes are persisted. */
    public function regenerateRecoveryCodes(int $userId, string $currentPassword, string $clientKey): array
    {
        $this->limit('recovery-generation|' . $userId . '|' . $clientKey, 5);
        $user = $this->users->findById($userId);
        if ($user === null || !password_verify($currentPassword, $user->passwordHash)) {
            throw new AuthException('current_password');
        }
        $codes = [];
        $hashes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $code = bin2hex(random_bytes(16));
            $codes[] = $code;
            $hashes[] = hash('sha256', $code);
        }
        $this->users->replaceRecoveryCodes($userId, $hashes);
        return $codes;
    }

    public function reset(string $code, string $password, string $clientKey): void
    {
        $this->validatePassword($password);
        $code = strtolower(trim($code));
        $originKey = hash('sha256', 'reset-origin|' . $clientKey);
        $codeKey = hash('sha256', 'reset-code|' . $code . '|' . $clientKey);
        $originAttempts = $this->users->recordAttempt($originKey, date('Y-m-d H:i:s'));
        $codeAttempts = $this->users->recordAttempt($codeKey, date('Y-m-d H:i:s'));
        if ($originAttempts > 20 || $codeAttempts > 10) {
            throw new AuthException('rate_limited');
        }
        if (preg_match('/^[a-f0-9]{32}$/', $code) !== 1) throw new AuthException('invalid_recovery_code');
        $userId = $this->users->consumeRecoveryCodeAndReplacePassword(
            hash('sha256', $code),
            password_hash($password, PASSWORD_DEFAULT),
            date('Y-m-d H:i:s')
        );
        if ($userId === null) throw new AuthException('invalid_recovery_code');
        $this->users->clearAttempts($codeKey);
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 12 || strlen($password) > 4096) throw new AuthException('invalid_password');
    }

    private function limit(string $key, int $maximum): void
    {
        $key = hash('sha256', $key);
        if ($this->users->recordAttempt($key, date('Y-m-d H:i:s')) > $maximum) throw new AuthException('rate_limited');
    }
}
