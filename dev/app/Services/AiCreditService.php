<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AiCreditRepository;
use App\Repositories\AiUsageRepository;
use App\Repositories\CreditRepository;
use RuntimeException;

final class AiCreditService
{
    private AiCreditRepository $repo;
    private CreditService $credits;

    public function __construct()
    {
        $this->repo = new AiCreditRepository();
        $this->credits = new CreditService();
    }

    public function isEnabled(): bool
    {
        return (int) ($this->repo->getConfig()['is_enabled'] ?? 1) === 1;
    }

    /** @return array{is_enabled:bool,monthly_budget:int,low_balance_threshold:int,costs:array<int,array<string,mixed>>} */
    public function adminSettings(): array
    {
        $cfg = $this->repo->getConfig();
        $costs = $this->repo->allCosts();
        $stats = [];
        try {
            $stats = (new AiUsageRepository())->averagesByIntent();
        } catch (\Throwable) {
            $stats = [];
        }
        foreach ($costs as &$row) {
            $intent = (string) ($row['intent'] ?? '');
            $stat = $stats[$intent] ?? null;
            $row['avg_tokens'] = $stat ? (float) $stat['avg_tokens'] : null;
            $row['avg_cost_krw'] = $stat ? (float) $stat['avg_cost_krw'] : null;
            $row['total_cost_krw'] = $stat ? (float) $stat['total_cost_krw'] : null;
            $row['usage_samples'] = $stat ? (int) $stat['samples'] : 0;
        }
        unset($row);

        return [
            'is_enabled' => (int) ($cfg['is_enabled'] ?? 1) === 1,
            'monthly_budget' => (int) ($cfg['monthly_budget'] ?? 0),
            'low_balance_threshold' => (int) ($cfg['low_balance_threshold'] ?? 10),
            'costs' => $costs,
        ];
    }

    /** @param array<string, mixed> $data */
    public function saveAdminSettings(array $data): void
    {
        $this->repo->saveConfig([
            'is_enabled' => !empty($data['is_enabled']),
            'monthly_budget' => (int) ($data['monthly_budget'] ?? 0),
            'low_balance_threshold' => (int) ($data['low_balance_threshold'] ?? 10),
        ]);
        $costs = $data['costs'] ?? [];
        if (is_array($costs)) {
            $normalized = [];
            foreach ($costs as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $intent = trim((string) ($row['intent'] ?? ''));
                if ($intent === '') {
                    continue;
                }
                $normalized[] = [
                    'intent' => $intent,
                    'label' => trim((string) ($row['label'] ?? $intent)),
                    'credit_cost' => max(0, (int) ($row['credit_cost'] ?? 0)),
                    'description' => trim((string) ($row['description'] ?? '')),
                    'is_active' => !empty($row['is_active']),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ];
            }
            $this->repo->saveCosts($normalized);
        }
    }

    public function costForIntent(string $intent): int
    {
        $intent = trim($intent);
        if ($intent === '') {
            $intent = 'chat';
        }
        $row = $this->repo->costForIntent($intent);
        if (!$row || !(int) ($row['is_active'] ?? 0)) {
            // unknown intents fall back to chat cost if active
            if ($intent !== 'chat') {
                return $this->costForIntent('chat');
            }
            return 0;
        }
        return max(0, (int) ($row['credit_cost'] ?? 0));
    }

    public function labelForIntent(string $intent): string
    {
        $row = $this->repo->costForIntent($intent);
        if ($row && trim((string) ($row['label'] ?? '')) !== '') {
            return (string) $row['label'];
        }
        return AiUsageService::intentLabel($intent);
    }

    /**
     * 새 AI 요청 시작 가능 여부.
     * 잔액이 이미 0 이하(부채)면 차단하고, 양수면 비용이 잔액보다 커도 시작을 허용한다.
     * (작업은 마무리한 뒤 마이너스 잔액으로 기록 → 다음 충전 시 자동 상계)
     */
    public function assertCanStart(int $userId): void
    {
        if (!$this->isEnabled() || $userId <= 0) {
            return;
        }
        $balance = $this->credits->balance($userId);
        if ($balance <= 0) {
            $msg = $balance < 0
                ? sprintf(
                    'AI 크레딧이 마이너스(%s)입니다. 충전하면 부족한 만큼 먼저 차감된 뒤 사용할 수 있어요.',
                    CreditService::format($balance)
                )
                : 'AI 크레딧이 없습니다. 마이페이지에서 충전한 뒤 다시 이용해 주세요.';
            throw new RuntimeException($msg);
        }
    }

    /** @deprecated use assertCanStart — 하위 호환용 */
    public function assertCanAfford(int $userId, string $intent = ''): void
    {
        $this->assertCanStart($userId);
    }

    /**
     * @return array{charged:int,balance:int,intent:string,label:string,overdraft:int,was_overdraft:bool}|null
     */
    public function charge(int $userId, string $intent, ?string $sourceRef = null): ?array
    {
        if (!$this->isEnabled() || $userId <= 0) {
            return null;
        }
        $intent = trim($intent) !== '' ? trim($intent) : 'chat';
        $cost = $this->costForIntent($intent);
        $balanceBefore = $this->credits->balance($userId);
        if ($cost <= 0) {
            return [
                'charged' => 0,
                'balance' => $balanceBefore,
                'intent' => $intent,
                'label' => $this->labelForIntent($intent),
                'overdraft' => 0,
                'was_overdraft' => false,
            ];
        }
        $label = $this->labelForIntent($intent);
        $overdraft = max(0, $cost - max(0, $balanceBefore));
        $desc = sprintf('AI 사용 · %s', $label);
        if ($overdraft > 0) {
            $desc .= sprintf(' · 초과 %s (다음 충전 시 차감)', CreditService::format($overdraft));
        }
        $balance = $this->credits->spend(
            $userId,
            $cost,
            $desc,
            'ai',
            $sourceRef,
            true
        );
        return [
            'charged' => $cost,
            'balance' => $balance,
            'intent' => $intent,
            'label' => $label,
            'overdraft' => $overdraft,
            'was_overdraft' => $balance < 0,
        ];
    }

    /** @return array{used:int,limit:int,label:string,balance:int,items:array<int,array<string,mixed>>} */
    public function memberSummary(int $userId, int $page = 1, int $perPage = 20): array
    {
        $cfg = $this->repo->getConfig();
        $creditRepo = new CreditRepository();
        $monthStart = date('Y-m-01 00:00:00');
        $used = $creditRepo->sumSpendBySourceSince($userId, 'ai', $monthStart);
        $usageLogs = (new AiUsageRepository())->listForUser($userId, $page, $perPage);
        $items = [];
        foreach ($usageLogs['items'] as $row) {
            $intent = (string) ($row['intent'] ?? '');
            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'surface' => (string) ($row['surface'] ?? ''),
                'surface_label' => AiUsageService::surfaceLabel((string) ($row['surface'] ?? '')),
                'intent' => $intent,
                'intent_label' => AiUsageService::intentLabel($intent),
                'status' => (string) ($row['status'] ?? ''),
                'total_tokens' => (int) ($row['total_tokens'] ?? 0),
                'cost_krw' => isset($row['cost_krw']) ? (float) $row['cost_krw'] : null,
            ];
        }
        return [
            'used' => $used,
            'limit' => (int) ($cfg['monthly_budget'] ?? 0),
            'label' => '이번 달 AI 사용 크레딧',
            'balance' => $this->credits->balance($userId),
            'enabled' => $this->isEnabled(),
            'items' => $items,
            'total' => (int) ($usageLogs['total'] ?? 0),
            'page' => (int) ($usageLogs['page'] ?? $page),
            'pages' => (int) ($usageLogs['pages'] ?? 1),
        ];
    }
}
