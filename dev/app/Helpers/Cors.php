<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * 공개 읽기 전용 API의 교차 출처 허용 처리.
 * 로컬에서 빌드한 편집기(dotnet run, http://localhost:5224)가 원격 서버 DB를 그대로 조회할 수 있게 한다.
 * 쿠키/세션은 허용하지 않으므로 인증이 필요한 API에는 사용하지 않는다.
 */
final class Cors
{
    private const DEFAULT_ORIGINS = [
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:5224',
        'http://127.0.0.1:5224',
        'https://localhost:7117',
        'https://127.0.0.1:7117',
        'http://localhost:60705',
        'https://localhost:44317',
    ];

    /** 허용 목록에 있는 출처일 때만 CORS 응답 헤더를 내보낸다. */
    public static function allowPublicRead(): void
    {
        $origin = self::requestOrigin();
        if ($origin === '' || headers_sent() || !self::isAllowed($origin)) {
            return;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Accept, Content-Type');
        header('Access-Control-Max-Age: 600');
    }

    /** OPTIONS 프리플라이트 응답 후 종료. */
    public static function endPreflight(): never
    {
        self::allowPublicRead();
        http_response_code(204);
        exit;
    }

    /** @return list<string> */
    public static function allowedOrigins(): array
    {
        $origins = self::DEFAULT_ORIGINS;
        foreach (explode(',', (string) app_config('cors_extra_origins', '')) as $extra) {
            $extra = rtrim(trim($extra), '/');
            if ($extra !== '' && preg_match('#^https?://[^/\s]+$#i', $extra) === 1) {
                $origins[] = $extra;
            }
        }

        return array_values(array_unique($origins));
    }

    private static function requestOrigin(): string
    {
        return rtrim(trim((string) ($_SERVER['HTTP_ORIGIN'] ?? '')), '/');
    }

    private static function isAllowed(string $origin): bool
    {
        foreach (self::allowedOrigins() as $allowed) {
            if (strcasecmp($allowed, $origin) === 0) {
                return true;
            }
        }

        return false;
    }
}
