<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SiteSettingsRepository;
use RuntimeException;

/**
 * 사이트 런타임 모드: development | temp_open | production | maintenance
 */
final class SiteModeService
{
    public const MODE_DEVELOPMENT = 'development';
    public const MODE_TEMP_OPEN = 'temp_open';
    public const MODE_PRODUCTION = 'production';
    public const MODE_MAINTENANCE = 'maintenance';
    public const SNS_HOLD_MESSAGE = '준비중입니다 이용에 불편을 드려 죄송합니다';

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    private SiteSettingsRepository $repo;

    public function __construct(?SiteSettingsRepository $repo = null)
    {
        $this->repo = $repo ?? new SiteSettingsRepository();
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /** @return array<string, string> */
    private function settings(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        self::$cache = $this->repo->all();
        return self::$cache;
    }

    private function defaultMode(): string
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            return self::MODE_DEVELOPMENT;
        }
        $env = strtolower((string) app_config('environment', ''));
        if (in_array($env, ['local', 'development', 'dev'], true)) {
            return self::MODE_DEVELOPMENT;
        }
        return self::MODE_PRODUCTION;
    }

    public function mode(): string
    {
        $raw = strtolower(trim((string) ($this->settings()['runtime_mode'] ?? '')));
        if (in_array($raw, [self::MODE_DEVELOPMENT, self::MODE_TEMP_OPEN, self::MODE_PRODUCTION, self::MODE_MAINTENANCE], true)) {
            return $raw;
        }
        return $this->defaultMode();
    }

    public function isDevelopment(): bool
    {
        return $this->mode() === self::MODE_DEVELOPMENT;
    }

    public function isProduction(): bool
    {
        return $this->mode() === self::MODE_PRODUCTION;
    }

    public function isMaintenance(): bool
    {
        return $this->mode() === self::MODE_MAINTENANCE;
    }

    public function isTempOpen(): bool
    {
        return $this->mode() === self::MODE_TEMP_OPEN;
    }

    public function showAiUsage(): bool
    {
        return $this->isDevelopment();
    }

    public function showAiDebug(): bool
    {
        return $this->isDevelopment();
    }

    /** @return array{mode:string,show_ai_usage:bool,show_ai_debug:bool,maintenance_title:string,maintenance_message:string,maintenance_eta:string} */
    public function publicConfig(): array
    {
        $admin = $this->adminPayload();
        return [
            'mode' => $admin['mode'],
            'show_ai_usage' => $this->showAiUsage(),
            'show_ai_debug' => $this->showAiDebug(),
            'maintenance_title' => $admin['maintenance_title'],
            'maintenance_message' => $admin['maintenance_message'],
            'maintenance_eta' => $admin['maintenance_eta'],
        ];
    }

    /** @return array{mode:string,maintenance_title:string,maintenance_message:string,maintenance_eta:string,modes:list<array{value:string,label:string,desc:string}>} */
    public function adminPayload(): array
    {
        $s = $this->settings();
        return [
            'mode' => $this->mode(),
            'maintenance_title' => trim((string) ($s['maintenance_title'] ?? '')) ?: '잠시 점검 중입니다',
            'maintenance_message' => trim((string) ($s['maintenance_message'] ?? ''))
                ?: '더 안정적인 라벨업을 위해 시스템을 정비하고 있어요. 잠시 후 다시 찾아와 주세요.',
            'maintenance_eta' => trim((string) ($s['maintenance_eta'] ?? '')),
            'modes' => [
                [
                    'value' => self::MODE_DEVELOPMENT,
                    'label' => '개발 모드',
                    'desc' => 'AI 대화에 토큰·환산 금액과 디버그 정보를 표시합니다. 내부 점검용입니다.',
                ],
                [
                    'value' => self::MODE_TEMP_OPEN,
                    'label' => '임시 오픈',
                    'desc' => '사이트는 열려 있지만 네이버·카카오·구글 로그인과 회원가입은 막아 둡니다. 이메일 가입·로그인은 그대로 이용할 수 있습니다.',
                ],
                [
                    'value' => self::MODE_PRODUCTION,
                    'label' => '운영 모드',
                    'desc' => '일반 서비스 상태입니다. 토큰·비용·디버그 정보는 사용자에게 숨깁니다.',
                ],
                [
                    'value' => self::MODE_MAINTENANCE,
                    'label' => '유지보수 모드',
                    'desc' => '관리자·로그인 외 공개 사이트를 닫고 유지보수 안내 페이지만 보여줍니다.',
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): array
    {
        $mode = strtolower(trim((string) ($data['mode'] ?? '')));
        if (!in_array($mode, [self::MODE_DEVELOPMENT, self::MODE_TEMP_OPEN, self::MODE_PRODUCTION, self::MODE_MAINTENANCE], true)) {
            throw new RuntimeException('유효하지 않은 사이트 모드입니다.');
        }
        $title = trim((string) ($data['maintenance_title'] ?? ''));
        $message = trim((string) ($data['maintenance_message'] ?? ''));
        $eta = trim((string) ($data['maintenance_eta'] ?? ''));
        if (mb_strlen($title) > 120) {
            throw new RuntimeException('유지보수 제목은 120자 이내로 입력해 주세요.');
        }
        if (mb_strlen($message) > 1000) {
            throw new RuntimeException('유지보수 안내는 1000자 이내로 입력해 주세요.');
        }
        if (mb_strlen($eta) > 120) {
            throw new RuntimeException('재오픈 예정 문구는 120자 이내로 입력해 주세요.');
        }

        $this->repo->set('runtime_mode', $mode);
        $this->repo->set('maintenance_title', $title !== '' ? $title : '잠시 점검 중입니다');
        $this->repo->set(
            'maintenance_message',
            $message !== '' ? $message : '더 안정적인 라벨업을 위해 시스템을 정비하고 있어요. 잠시 후 다시 찾아와 주세요.'
        );
        $this->repo->set('maintenance_eta', $eta);
        self::clearCache();

        return $this->adminPayload();
    }

    /**
     * 유지보수 중에도 통과시킬 경로인지.
     */
    public function isBypassPath(string $path): bool
    {
        $path = rtrim($path, '/') ?: '/';
        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return true;
        }
        if ($path === '/partner' || str_starts_with($path, '/partner/')) {
            return true;
        }
        if (str_starts_with($path, '/api/admin')) {
            return true;
        }
        if ($path === '/api/health' || $path === '/api/system/migrate') {
            return true;
        }
        if ($path === '/api/site/runtime') {
            return true;
        }
        // 정적 서빙이 PHP를 안 타도, 혹시 라우팅되는 경우 대비
        if (preg_match('#^/(assets|css|js|editor)(/|$)#', $path) === 1) {
            return true;
        }
        return false;
    }
}
