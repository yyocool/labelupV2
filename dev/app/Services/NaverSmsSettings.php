<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SiteSettingsRepository;
use RuntimeException;

/**
 * 네이버 클라우드 플랫폼 SENS 문자 API 설정.
 * 키는 site_settings.naver_sms JSON 으로 두고, 화면에는 Secret Key 를 다시 내려주지 않는다.
 */
final class NaverSmsSettings
{
    public const KEY = 'naver_sms';
    public const ENABLED_KEY = 'signup_sms_enabled';

    public static function isSignupRequired(): bool
    {
        $raw = strtolower(trim((new SiteSettingsRepository())->get(self::ENABLED_KEY, '1')));
        return !in_array($raw, ['0', 'false', 'off', 'no'], true);
    }

    public static function saveEnabled(bool $enabled): void
    {
        (new SiteSettingsRepository())->set(self::ENABLED_KEY, $enabled ? '1' : '0');
    }

    /** @return array{service_id: string, access_key: string, secret_key: string, sender: string} */
    public static function requireReady(): array
    {
        if (!self::isSignupRequired()) {
            throw new RuntimeException('현재 문자인증을 사용하지 않습니다.');
        }
        $config = self::stored();
        if (!self::isReady($config)) {
            throw new RuntimeException('문자 인증 API가 설정되지 않았습니다. 관리자 환경설정에서 네이버 문자 API를 입력해 주세요.');
        }
        return $config;
    }

    /** @return array<string, mixed> */
    public static function adminPayload(): array
    {
        $config = self::stored();
        return [
            'service_id' => $config['service_id'],
            'access_key' => $config['access_key'],
            'secret_configured' => $config['secret_key'] !== '',
            'sender' => $config['sender'],
            'ready' => self::isReady($config),
            'signup_enabled' => self::isSignupRequired(),
        ];
    }

    /** @param array<string, mixed> $input */
    public static function save(array $input): array
    {
        if (array_key_exists('signup_enabled', $input) || array_key_exists('enabled', $input)) {
            self::saveEnabled(self::parseEnabled($input['signup_enabled'] ?? $input['enabled']));
        }

        $current = self::stored();
        $serviceId = trim((string) ($input['service_id'] ?? ''));
        $accessKey = trim((string) ($input['access_key'] ?? ''));
        $secretIn = trim((string) ($input['secret_key'] ?? ''));
        $sender = preg_replace('/\D+/', '', (string) ($input['sender'] ?? '')) ?? '';
        $secret = $secretIn !== '' ? $secretIn : $current['secret_key'];

        if ($serviceId === '' && $accessKey === '' && $secretIn === '' && $sender === '') {
            return self::adminPayload();
        }

        if (!preg_match('/^[A-Za-z0-9:_\-\.]{8,100}$/', $serviceId)) {
            throw new RuntimeException('서비스 ID 형식을 확인해 주세요. 예: ncp:sms:kr:123456789012:labelup');
        }
        if (!preg_match('/^[A-Za-z0-9_\-]{8,120}$/', $accessKey)) {
            throw new RuntimeException('Access Key 형식을 확인해 주세요.');
        }
        if ($secret === '') {
            throw new RuntimeException('Secret Key를 입력해 주세요.');
        }
        if (strlen($secret) < 8 || strlen($secret) > 200 || preg_match('/\s/', $secret)) {
            throw new RuntimeException('Secret Key 형식을 확인해 주세요.');
        }
        if (!preg_match('/^\d{8,12}$/', $sender)) {
            throw new RuntimeException('발신번호는 숫자 8~12자리로 입력해 주세요.');
        }

        $payload = [
            'service_id' => $serviceId,
            'access_key' => $accessKey,
            'secret_key' => $secret,
            'sender' => $sender,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('문자 API 설정을 저장하지 못했습니다.');
        }
        (new SiteSettingsRepository())->set(self::KEY, $json);

        return self::adminPayload();
    }

    /** @param array{service_id: string, access_key: string, secret_key: string, sender: string} $config */
    private static function isReady(array $config): bool
    {
        return $config['service_id'] !== ''
            && $config['access_key'] !== ''
            && $config['secret_key'] !== ''
            && $config['sender'] !== '';
    }

    /** @return array{service_id: string, access_key: string, secret_key: string, sender: string} */
    private static function stored(): array
    {
        $raw = json_decode((new SiteSettingsRepository())->get(self::KEY, ''), true);
        if (!is_array($raw)) {
            $raw = [];
        }
        $sender = preg_replace('/\D+/', '', (string) ($raw['sender'] ?? '')) ?? '';

        return [
            'service_id' => trim((string) ($raw['service_id'] ?? '')),
            'access_key' => trim((string) ($raw['access_key'] ?? '')),
            'secret_key' => trim((string) ($raw['secret_key'] ?? '')),
            'sender' => $sender,
        ];
    }

    private static function parseEnabled(mixed $raw): bool
    {
        if (is_bool($raw)) {
            return $raw;
        }
        $value = strtolower(trim((string) $raw));
        return !in_array($value, ['0', 'false', 'off', 'no', ''], true);
    }
}
