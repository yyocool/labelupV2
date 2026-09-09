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
        return [
            'is_enabled' => (int) ($cfg['is_enabled'] ?? 1) === 1,
            'monthly_budget' => (int) ($cfg['monthly_budget'] ?? 0),
            'low_balance_threshold' => (int) ($cfg['low_balance_threshold'] ?? 10),
            'costs' => $this->repo->allCosts(),
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

    public function assertCanAfford(int $userId, string $intent): void
    {
        if (!$this->isEnabled() || $userId <= 0) {
            return;
        }
        $cost = $this->costForIntent($intent);
        if ($cost <= 0) {
            return;
        }
        $balance = $this->credits->balance($userId);
        if ($balance < $cost) {
            throw new RuntimeException(
                sprintf(
                    'AI 크레딧이 부족합니다. 필요 %s / 보유 %s. 마이페이지에서 잔액을 확인하거나 관리자에게 문의해 주세요.',
                    CreditService::format($cost),
                    CreditService::format($balance)
                )
            );
        }
    }

    /**
     * @return array{charged:int,balance:int,intent:string,label:string}|null
     */
    public function charge(int $userId, string $intent, ?string $sourceRef = null): ?array
    {
        if (!$this->isEnabled() || $userId <= 0) {
            return null;
        }
        $intent = trim($intent) !== '' ? trim($intent) : 'chat';
        $cost = $this->costForIntent($intent);
        if ($cost <= 0) {
            return [
                'charged' => 0,
                'balance' => $this->credits->balance($userId),
                'intent' => $intent,
                'label' => $this->labelForIntent($intent),
            ];
        }
        $label = $this->labelForIntent($intent);
        $balance = $this->credits->spend(
            $userId,
            $cost,
            sprintf('AI 사용 · %s', $label),
            'ai',
            $sourceRef
        );
        return [
            'charged' => $cost,
            'balance' => $balance,
            'intent' => $intent,
            'label' => $label,
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
