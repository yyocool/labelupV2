<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AiUsageRepository;
use Throwable;

final class AiUsageService
{
    private AiUsageRepository $repo;

    public function __construct()
    {
        $this->repo = new AiUsageRepository();
    }

    public function log(array $data): void
    {
        try {
            $this->repo->insert($data);
        } catch (Throwable) {
            // 채팅 응답을 막지 않음
        }
    }

    /** @return array{period:string,from:?string,summary:array<string,int>,daily:array<int,array<string,mixed>>,recent:array<int,array<string,mixed>>} */
    public function dashboard(string $period = '7d'): array
    {
        $period = in_array($period, ['today', '7d', '30d', 'all'], true) ? $period : '7d';
        $from = match ($period) {
            'today' => date('Y-m-d 00:00:00'),
            '7d' => date('Y-m-d 00:00:00', strtotime('-6 days')),
            '30d' => date('Y-m-d 00:00:00', strtotime('-29 days')),
            default => null,
        };

        $raw = $this->repo->summary($from);
        $summary = [];
        foreach (['total', 'ok_count', 'error_count', 'chat_count', 'product_count', 'clipart_count', 'home_count', 'editor_count', 'image_count', 'tokens', 'users'] as $key) {
            $summary[$key] = (int) ($raw[$key] ?? 0);
        }

        return [
            'period' => $period,
            'from' => $from,
            'summary' => $summary,
            'daily' => $this->repo->daily($from),
            'recent' => $this->repo->recent(40, $from),
        ];
    }

    public static function intentLabel(string $intent): string
    {
        return match ($intent) {
            'recommend_product' => '상품 추천',
            'generate_clipart' => '클립아트',
            'generate_template' => '템플릿',
            'generate_data_template' => '데이터 템플릿',
            'ask_image_mode' => '이미지 선택',
            'chat' => '대화',
            default => $intent !== '' ? $intent : '—',
        };
    }

    public static function surfaceLabel(string $surface): string
    {
        return match ($surface) {
            'home' => '홈',
            'editor' => '편집기',
            default => '기타',
        };
    }
}
