<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RememberTokenRepository;

final class AuthService
{
    public const SESSION_USER_KEY = 'auth_user';
    public const SESSION_ADMIN_KEY = 'auth_admin';

    private const REMEMBER_USER_COOKIE = 'labelup_remember';
    private const REMEMBER_ADMIN_COOKIE = 'labelup_admin_remember';
    private const CONTEXT_USER = 'user';
    private const CONTEXT_ADMIN = 'admin';
    private const REMEMBER_DAYS = 30;

    private RememberTokenRepository $tokens;

    public function __construct()
    {
        $this->tokens = new RememberTokenRepository();
    }

    public function user(): ?array
    {
        return $_SESSION[self::SESSION_USER_KEY] ?? null;
    }

    public function admin(): ?array
    {
        return $_SESSION[self::SESSION_ADMIN_KEY] ?? null;
    }

    public function checkUser(): bool
    {
        return $this->user() !== null;
    }

    public function checkAdmin(): bool
    {
        return $this->admin() !== null;
    }

    public function check(): bool
    {
        return $this->checkUser();
    }

    public function id(): ?int
    {
        $user = $this->user();
        return isset($user['id']) ? (int) $user['id'] : null;
    }

    public function adminId(): ?int
    {
        $admin = $this->admin();
        return isset($admin['id']) ? (int) $admin['id'] : null;
    }

    public function isAdmin(): bool
    {
        return $this->checkAdmin();
    }

    public function loginUser(array $user, bool $remember = false): void
    {
        $this->loginContext(self::SESSION_USER_KEY, self::CONTEXT_USER, self::REMEMBER_USER_COOKIE, $user, $remember);
    }

    public function loginAdmin(array $user, bool $remember = false): void
    {
        $this->loginContext(self::SESSION_ADMIN_KEY, self::CONTEXT_ADMIN, self::REMEMBER_ADMIN_COOKIE, $user, $remember);
    }

    public function logoutUser(): void
    {
        $this->logoutContext(self::SESSION_USER_KEY, self::REMEMBER_USER_COOKIE);
    }

    public function logoutAdmin(): void
    {
        $this->logoutContext(self::SESSION_ADMIN_KEY, self::REMEMBER_ADMIN_COOKIE);
    }

    public function attemptRememberLogin(): void
    {
        $this->attemptRememberForContext(
            self::CONTEXT_USER,
            self::SESSION_USER_KEY,
            self::REMEMBER_USER_COOKIE,
            static fn (): bool => true
        );
        $this->attemptRememberForContext(
            self::CONTEXT_ADMIN,
            self::SESSION_ADMIN_KEY,
            self::REMEMBER_ADMIN_COOKIE,
            static fn (array $row): bool => ($row['role'] ?? '') === 'admin'
        );
    }

    private function loginContext(string $sessionKey, string $context, string $cookieName, array $user, bool $remember): void
    {
        if (!$this->checkUser() && !$this->checkAdmin()) {
            session_regenerate_id(true);
        }

        $_SESSION[$sessionKey] = $this->sessionPayload($user);

        if ($remember) {
            $this->setRememberToken((int) $user['id'], $context, $cookieName);
        }
    }

    private function logoutContext(string $sessionKey, string $cookieName): void
    {
        if (isset($_COOKIE[$cookieName])) {
            $this->tokens->deleteByHash(hash('sha256', $_COOKIE[$cookieName]));
            setcookie($cookieName, '', time() - 3600, '/', '', false, true);
        }
        unset($_SESSION[$sessionKey]);
    }

    private function attemptRememberForContext(
        string $context,
        string $sessionKey,
        string $cookieName,
        callable $validate
    ): void {
        if (isset($_SESSION[$sessionKey]) || empty($_COOKIE[$cookieName])) {
            return;
        }

        $hash = hash('sha256', $_COOKIE[$cookieName]);
        $row = $this->tokens->findValid($hash, $context);
        if (!$row || !$validate($row)) {
            setcookie($cookieName, '', time() - 3600, '/', '', false, true);
            return;
        }

        $_SESSION[$sessionKey] = $this->sessionPayload([
            'id' => $row['user_id'],
            'email' => $row['email'],
            'name' => $row['name'] ?? '',
            'role' => $row['role'],
        ]);
    }

    private function setRememberToken(int $userId, string $context, string $cookieName): void
    {
        $this->tokens->deleteByUser($userId, $context);
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + self::REMEMBER_DAYS * 86400);
        $this->tokens->create($userId, $hash, $expires, $context);
        setcookie($cookieName, $token, time() + self::REMEMBER_DAYS * 86400, '/', '', false, true);
    }

    private function sessionPayload(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'email' => $user['email'] ?? '',
            'name' => $user['name'] ?? '',
            'role' => $user['role'] ?? 'member',
        ];
    }
}
