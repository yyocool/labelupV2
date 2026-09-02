<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\StringHelper;
use App\Helpers\Validator;
use App\Repositories\PasswordResetTokenRepository;
use App\Repositories\RememberTokenRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class AccountRecoveryService
{
    private UserRepository $users;
    private PasswordResetTokenRepository $tokens;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->tokens = new PasswordResetTokenRepository();
    }

    public function findEmail(string $name, string $phone): string
    {
        if ($err = Validator::name($name)) {
            throw new RuntimeException($err);
        }

        $phoneDigits = StringHelper::normalizePhone($phone);
        if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 11) {
            throw new RuntimeException('올바른 휴대폰 번호를 입력해주세요.');
        }

        $user = $this->users->findByNameAndPhone(trim($name), $phoneDigits);
        if (!$user) {
            throw new RuntimeException('입력하신 정보와 일치하는 계정을 찾을 수 없습니다.');
        }

        return StringHelper::maskEmail((string) $user['email']);
    }

    /** @return array{message:string, reset_url?:string} */
    public function requestPasswordReset(string $email, string $name): array
    {
        $email = strtolower(trim($email));
        if (!Validator::email($email)) {
            throw new RuntimeException('올바른 이메일 형식이 아닙니다.');
        }
        if ($err = Validator::name($name)) {
            throw new RuntimeException($err);
        }

        $user = $this->users->findByEmailAndName($email, trim($name));
        if (!$user || ($user['status'] ?? '') !== 'active') {
            throw new RuntimeException('입력하신 정보와 일치하는 계정을 찾을 수 없습니다.');
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 1800);
        $this->tokens->invalidateByUser((int) $user['id']);
        $this->tokens->create((int) $user['id'], $token, $expiresAt);

        $resetUrl = url('reset-password?token=' . $token);
        $sent = (new MailService())->sendPasswordReset($email, trim($name), $resetUrl);

        $result = [
            'message' => $sent
                ? '등록된 이메일로 비밀번호 재설정 링크를 발송했습니다. 메일함을 확인해주세요.'
                : '비밀번호 재설정 링크가 발급되었습니다. 아래 버튼에서 재설정을 진행해주세요.',
        ];

        if (!$sent || APP_DEBUG) {
            $result['reset_url'] = $resetUrl;
        }

        return $result;
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        $token = trim($token);
        if ($token === '') {
            throw new RuntimeException('유효하지 않은 재설정 링크입니다.');
        }
        if ($err = Validator::password($newPassword)) {
            throw new RuntimeException($err);
        }

        $row = $this->tokens->findValid($token);
        if (!$row || ($row['status'] ?? '') !== 'active') {
            throw new RuntimeException('만료되었거나 유효하지 않은 재설정 링크입니다.');
        }

        $this->users->updatePassword((int) $row['user_id'], password_hash($newPassword, PASSWORD_DEFAULT));
        $this->tokens->markUsed((int) $row['id']);
        (new RememberTokenRepository())->deleteByUser((int) $row['user_id']);
    }

    public function tokenValid(string $token): bool
    {
        return $this->tokens->findValid(trim($token)) !== null;
    }
}
