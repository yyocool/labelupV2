<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SmsVerificationRepository;
use App\Repositories\UserProfileRepository;
use RuntimeException;

final class SmsVerificationService
{
    private const CODE_TTL_SECONDS = 180;
    private const TOKEN_TTL_SECONDS = 1800;
    private const RESEND_SECONDS = 60;
    private const MAX_PER_PHONE_HOUR = 5;
    private const MAX_PER_IP_HOUR = 10;
    private const MAX_ATTEMPTS = 5;

    private SmsVerificationRepository $repo;
    private UserProfileRepository $profiles;
    private NaverSmsClient $client;

    public function __construct()
    {
        $this->repo = new SmsVerificationRepository();
        $this->profiles = new UserProfileRepository();
        $this->client = new NaverSmsClient();
    }

    /** @return array{phone_mask: string, expires_in: int, cooldown: int} */
    public function sendSignupCode(string $phone): array
    {
        if (!NaverSmsSettings::isSignupRequired()) {
            throw new RuntimeException('현재 문자인증을 사용하지 않습니다.');
        }
        $phone = self::normalizePhone($phone);
        if ($this->profiles->phoneExists($phone)) {
            throw new RuntimeException('이미 가입된 휴대폰 번호입니다.');
        }
        $this->assertSendAllowed($phone);

        $config = NaverSmsSettings::requireReady();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + self::CODE_TTL_SECONDS);
        $id = $this->repo->insert($phone, password_hash($code, PASSWORD_DEFAULT), $expiresAt, self::clientIp());
        try {
            $this->client->sendAuthCode($config, $phone, $code);
        } catch (RuntimeException $e) {
            $this->repo->deleteById($id);
            throw $e;
        }
        $this->repo->invalidateOthers($phone, $id);

        return [
            'phone_mask' => self::maskPhone($phone),
            'expires_in' => self::CODE_TTL_SECONDS,
            'cooldown' => self::RESEND_SECONDS,
        ];
    }

    public function verifySignupCode(string $phone, string $code): string
    {
        if (!NaverSmsSettings::isSignupRequired()) {
            throw new RuntimeException('현재 문자인증을 사용하지 않습니다.');
        }
        $phone = self::normalizePhone($phone);
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            throw new RuntimeException('인증번호 6자리를 입력해 주세요.');
        }
        $row = $this->repo->latestOpen($phone);
        if ($row === null) {
            throw new RuntimeException('인증번호를 먼저 받아 주세요.');
        }
        if ((string) ($row['verified_at'] ?? '') !== '' && (string) ($row['token_hash'] ?? '') !== '') {
            throw new RuntimeException('이미 확인한 인증번호입니다. 가입을 진행하거나, 화면을 닫았다면 인증번호를 다시 받아 주세요.');
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            throw new RuntimeException('인증번호가 만료되었습니다. 다시 받아 주세요.');
        }
        if ((int) ($row['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            throw new RuntimeException('인증 횟수를 초과했습니다. 인증번호를 다시 받아 주세요.');
        }
        if (!password_verify($code, (string) ($row['code_hash'] ?? ''))) {
            $this->repo->incrementAttempts((int) $row['id']);
            throw new RuntimeException('인증번호가 올바르지 않습니다.');
        }

        $token = bin2hex(random_bytes(16));
        $this->repo->markVerified(
            (int) $row['id'],
            hash('sha256', $token),
            date('Y-m-d H:i:s', time() + self::TOKEN_TTL_SECONDS)
        );
        return $token;
    }

    public function consumeSignupToken(string $phone, string $token): string
    {
        if (!NaverSmsSettings::isSignupRequired()) {
            throw new RuntimeException('현재 문자인증을 사용하지 않습니다.');
        }
        $phone = self::normalizePhone($phone);
        $token = trim($token);
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new RuntimeException('휴대폰 인증을 완료해 주세요.');
        }
        if ($this->profiles->phoneExists($phone)) {
            throw new RuntimeException('이미 가입된 휴대폰 번호입니다.');
        }
        $row = $this->repo->findByTokenHash($phone, hash('sha256', $token));
        if (
            $row === null
            || (string) ($row['verified_at'] ?? '') === ''
            || (string) ($row['consumed_at'] ?? '') !== ''
            || strtotime((string) ($row['expires_at'] ?? '')) < time()
        ) {
            throw new RuntimeException('휴대폰 인증이 만료되었습니다. 인증번호를 다시 받아 주세요.');
        }
        if (!$this->repo->consume((int) $row['id'])) {
            throw new RuntimeException('휴대폰 인증이 만료되었습니다. 인증번호를 다시 받아 주세요.');
        }
        return $phone;
    }

    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (str_starts_with($digits, '82')) {
            $digits = '0' . substr($digits, 2);
        }
        if (!preg_match('/^01[016789]\d{7,8}$/', $digits)) {
            throw new RuntimeException('올바른 휴대폰 번호를 입력해 주세요.');
        }
        return $digits;
    }

    public static function maskPhone(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }
        return substr($phone, 0, 3) . '-****-' . substr($phone, -4);
    }

    private function assertSendAllowed(string $phone): void
    {
        $latest = $this->repo->latestOpen($phone);
        if ($latest !== null) {
            $elapsed = time() - strtotime((string) ($latest['created_at'] ?? 'now'));
            if ($elapsed >= 0 && $elapsed < self::RESEND_SECONDS) {
                $wait = self::RESEND_SECONDS - $elapsed;
                throw new RuntimeException('인증번호는 ' . $wait . '초 후에 다시 받을 수 있습니다.');
            }
        }
        $since = date('Y-m-d H:i:s', time() - 3600);
        if ($this->repo->countSince($phone, $since) >= self::MAX_PER_PHONE_HOUR) {
            throw new RuntimeException('인증번호 요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.');
        }
        $ip = self::clientIp();
        if ($ip !== null && $this->repo->countIpSince($ip, $since) >= self::MAX_PER_IP_HOUR) {
            throw new RuntimeException('인증번호 요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.');
        }
    }

    private static function clientIp(): ?string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip === '' || strlen($ip) > 45) {
            return null;
        }
        return $ip;
    }
}
