<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PartnerRepository;
use RuntimeException;

final class PartnerAuthService
{
    public const SESSION_KEY = 'auth_partner';

    private PartnerRepository $partners;

    public function __construct()
    {
        $this->partners = new PartnerRepository();
    }

    public function current(): ?array
    {
        $row = $_SESSION[self::SESSION_KEY] ?? null;
        return is_array($row) ? $row : null;
    }

    /** 세션이 있어도 비활성·삭제 계정이면 로그아웃한다. */
    public function fresh(): ?array
    {
        $session = $this->current();
        $id = (int) ($session['id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $row = $this->partners->find($id);
        if (!$row || ($row['status'] ?? '') !== 'active') {
            $this->logout();
            return null;
        }

        return $this->present($row);
    }

    public function attempt(string $loginId, string $password): array
    {
        $loginId = trim($loginId);
        $row = $this->partners->findByLoginId($loginId);
        $hash = (string) ($row['password_hash'] ?? '');
        if (!$row || $hash === '' || !password_verify($password, $hash)) {
            throw new RuntimeException('아이디 또는 비밀번호가 올바르지 않습니다.');
        }
        if (($row['status'] ?? '') !== 'active') {
            throw new RuntimeException('사용이 중지된 계정입니다. 관리자에게 문의해 주세요.');
        }

        if (empty($_SESSION[AuthService::SESSION_USER_KEY]) && empty($_SESSION[AuthService::SESSION_ADMIN_KEY])) {
            session_regenerate_id(true);
        }
        $this->partners->touchLogin((int) $row['id']);
        $partner = $this->present($row);
        $_SESSION[self::SESSION_KEY] = $partner;

        return $partner;
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    /** @param array<string, mixed> $row */
    private function present(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'login_id' => (string) ($row['login_id'] ?? ''),
            'company_name' => (string) ($row['company_name'] ?? ''),
            'contact_name' => (string) ($row['contact_name'] ?? ''),
        ];
    }
}
