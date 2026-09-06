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
            $this->repo->insert($this->withCost($data));
        } catch (Throwable) {
            // 채팅 응답을 막지 않음
        }
    }

    /** @return array{period:string,from:?string,summary:array<string,int|float>,daily:array<int,array<string,mixed>>,recent:array<int,array<string,mixed>>} */
    public function dashboard(string $period = '7d'): array
    {
        $this->backfillEstimatedCosts();
        $period = in_array($period, ['today', '7d', '30d', 'all'], true) ? $period : '7d';
        $from = match ($period) {
            'today' => date('Y-m-d 00:00:00'),
            '7d' => date('Y-m-d 00:00:00', strtotime('-6 days')),
            '30d' => date('Y-m-d 00:00:00', strtotime('-29 days')),
            default => null,
        };

        $raw = $this->repo->summary($from);
        $summary = [];
        foreach (['total', 'ok_count', 'error_count', 'chat_count', 'product_count', 'clipart_count', 'template_count', 'data_count', 'home_count', 'editor_count', 'image_count', 'tokens', 'users'] as $key) {
            $summary[$key] = (int) ($raw[$key] ?? 0);
        }
        $summary['cost_krw'] = round((float) ($raw['cost_krw'] ?? 0), 4);
        $summary['cost_usd'] = round((float) ($raw['cost_usd'] ?? 0), 6);

        return [
            'period' => $period,
            'from' => $from,
            'summary' => $summary,
            'daily' => $this->repo->daily($from),
            'recent' => array_map([$this, 'presentRow'], $this->repo->recent(40, $from)),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,per_page:int,pages:int,summary:array<string,mixed>,filters:array<string,string>}
     */
    public function searchLogs(array $filters, int $page = 1, int $perPage = 30): array
    {
        $this->backfillEstimatedCosts();
        $clean = $this->normalizeFilters($filters);
        $result = $this->repo->search($clean, $page, $perPage);
        $result['items'] = array_map([$this, 'presentRow'], $result['items']);
        $result['filters'] = $clean;
        return $result;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,filters:array<string,string>,summary:array<string,int|float>}
     */
    public function memberUsage(array $filters, int $limit = 100): array
    {
        $this->backfillEstimatedCosts();
        $clean = $this->normalizeFilters($filters);
        $items = $this->repo->memberStats($clean, $limit);
        $tokens = 0;
        $cost = 0;
        $requests = 0;
        foreach ($items as &$row) {
            $row['tokens'] = (int) ($row['tokens'] ?? 0);
            $row['cost_krw'] = round((float) ($row['cost_krw'] ?? 0), 4);
            $row['requests'] = (int) ($row['requests'] ?? 0);
            $row['ok_count'] = (int) ($row['ok_count'] ?? 0);
            $row['error_count'] = (int) ($row['error_count'] ?? 0);
            $row['member_label'] = $this->memberLabel($row);
            $tokens += $row['tokens'];
            $cost += $row['cost_krw'];
            $requests += $row['requests'];
        }
        unset($row);

        return [
            'items' => $items,
            'filters' => $clean,
            'summary' => [
                'members' => count($items),
                'requests' => $requests,
                'tokens' => $tokens,
                'cost_krw' => round($cost, 4),
            ],
        ];
    }

    private function backfillEstimatedCosts(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            foreach ($this->repo->rowsNeedingCost(2000) as $row) {
                $view = AiCostService::present([
                    'model' => $row['model'] ?? null,
                    'prompt_tokens' => $row['prompt_tokens'] ?? 0,
                    'completion_tokens' => $row['completion_tokens'] ?? 0,
                    'total_tokens' => $row['total_tokens'] ?? 0,
                    'image_count' => !empty($row['has_image']) ? 1 : 0,
                ], (string) ($row['intent'] ?? 'chat'), isset($row['difficulty']) ? (string) $row['difficulty'] : null);
                if ($view === null || ($view['krw'] ?? 0) <= 0) {
                    continue;
                }
                $this->repo->updateCost((int) $row['id'], (float) $view['usd'], (float) $view['krw'], (string) ($view['agent'] ?? ''));
            }
        } catch (Throwable) {
        }
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

    /** @return array<string, string> */
    public static function intentOptions(): array
    {
        return [
            '' => '유형 전체',
            'chat' => '대화',
            'recommend_product' => '상품 추천',
            'generate_clipart' => '클립아트',
            'generate_template' => '템플릿',
            'generate_data_template' => '데이터 템플릿',
            'ask_image_mode' => '이미지 선택',
        ];
    }

    /** @return array<string, string> */
    public static function surfaceOptions(): array
    {
        return [
            '' => '위치 전체',
            'home' => '홈',
            'editor' => '편집기',
        ];
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed> */
    private function withCost(array $data): array
    {
        if (isset($data['cost_krw']) && $data['cost_krw'] !== null && $data['cost_krw'] !== '') {
            return $data;
        }
        $view = AiCostService::present([
            'model' => $data['model'] ?? null,
            'prompt_tokens' => $data['prompt_tokens'] ?? 0,
            'completion_tokens' => $data['completion_tokens'] ?? 0,
            'total_tokens' => $data['total_tokens'] ?? 0,
            'image_count' => !empty($data['has_image']) ? 1 : 0,
        ], (string) ($data['intent'] ?? 'chat'), isset($data['difficulty']) ? (string) $data['difficulty'] : null);
        if ($view === null) {
            return $data;
        }
        $data['cost_usd'] = $view['usd'];
        $data['cost_krw'] = $view['krw'];
        $data['agent'] = $data['agent'] ?? $view['agent'];
        return $data;
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed> */
    private function presentRow(array $row): array
    {
        $stored = isset($row['cost_krw']) && $row['cost_krw'] !== null && $row['cost_krw'] !== '';
        $staleZero = $stored && (float) $row['cost_krw'] <= 0 && (int) ($row['total_tokens'] ?? 0) > 0;
        if (!$stored || $staleZero) {
            $view = AiCostService::present([
                'model' => $row['model'] ?? null,
                'prompt_tokens' => $row['prompt_tokens'] ?? 0,
                'completion_tokens' => $row['completion_tokens'] ?? 0,
                'total_tokens' => $row['total_tokens'] ?? 0,
                'image_count' => !empty($row['has_image']) ? 1 : 0,
            ], (string) ($row['intent'] ?? 'chat'), isset($row['difficulty']) ? (string) $row['difficulty'] : null);
            if ($view !== null) {
                $row['cost_krw'] = $view['krw'];
                $row['cost_usd'] = $view['usd'];
                $row['agent'] = $row['agent'] ?? $view['agent'];
                $row['agent_label'] = $view['agent_label'];
            }
        }
        $row['cost_krw'] = round((float) ($row['cost_krw'] ?? 0), 4);
        $row['cost_usd'] = (float) ($row['cost_usd'] ?? 0);
        $row['total_tokens'] = (int) ($row['total_tokens'] ?? 0);
        $row['prompt_tokens'] = (int) ($row['prompt_tokens'] ?? 0);
        $row['completion_tokens'] = (int) ($row['completion_tokens'] ?? 0);
        $row['intent_label'] = self::intentLabel((string) ($row['intent'] ?? ''));
        $row['surface_label'] = self::surfaceLabel((string) ($row['surface'] ?? ''));
        $row['agent_label'] = $row['agent_label'] ?? (AiCostService::agentLabel((string) ($row['agent'] ?? '')));
        $row['member_label'] = $this->memberLabel($row);
        $row['status_label'] = ($row['status'] ?? '') === 'ok' ? '성공' : '실패';
        return $row;
    }

    /** @param array<string, mixed> $row */
    private function memberLabel(array $row): string
    {
        $name = trim((string) ($row['name'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $id = (int) ($row['user_id'] ?? 0);
        if ($name !== '' && $email !== '') {
            return $name . ' · ' . $email;
        }
        if ($email !== '') {
            return $email;
        }
        if ($name !== '') {
            return $name;
        }
        return $id > 0 ? '#' . $id : '비회원';
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    private function normalizeFilters(array $filters): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        return [
            'q' => $q,
            'user_id' => (string) max(0, (int) ($filters['user_id'] ?? 0)),
            'intent' => trim((string) ($filters['intent'] ?? '')),
            'surface' => trim((string) ($filters['surface'] ?? '')),
            'status' => trim((string) ($filters['status'] ?? '')),
            'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '',
            'to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : '',
        ];
    }
}
