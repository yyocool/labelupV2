<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CreditRepository;
use RuntimeException;

final class CreditService
{
    /** 원화 1원당 잉크 수 (1원 : 10 C) */
    public const CREDITS_PER_KRW = 10;

    private CreditRepository $repo;

    public function __construct()
    {
        $this->repo = new CreditRepository();
    }

    /** UTF-8이 Latin-1로 잘못 읽힌 한글을 복구한다. */
    public static function repairUtf8(string $text): string
    {
        $text = trim($text);
        if ($text === '' || preg_match('/[가-힣]/u', $text) === 1) {
            return $text;
        }
        foreach (['ISO-8859-1', 'Windows-1252'] as $from) {
            $try = @mb_convert_encoding($text, $from, 'UTF-8');
            if (is_string($try) && $try !== '' && preg_match('/[가-힣]/u', $try) === 1) {
                return $try;
            }
        }
        return $text;
    }

    public static function rewardLabel(string $code, string $name = ''): string
    {
        $code = strtoupper(trim($code));
        $name = self::repairUtf8($name);
        if ($name !== '' && preg_match('/[가-힣]/u', $name) === 1) {
            return $name;
        }
        return match ($code) {
            'SIGNUP_WELCOME' => '회원가입 웰컴 잉크',
            'DAILY_LOGIN' => '일일 접속 보상',
            'DESIGN_COMPLETE' => '디자인 완료 보상',
            'REFERRAL' => '친구 추천 보상',
            'EVENT_BONUS' => '이벤트 보너스',
            default => $name !== '' ? $name : ($code !== '' ? $code : '잉크 보상'),
        };
    }

    public static function krwToCredits(float|int $krw): int
    {
        return (int) round(((float) $krw) * self::CREDITS_PER_KRW);
    }

    public static function creditsToKrw(int $credits): float
    {
        if (self::CREDITS_PER_KRW <= 0) {
            return 0.0;
        }
        return round($credits / self::CREDITS_PER_KRW, 4);
    }

    public static function ratioLabel(): string
    {
        return '1원 = ' . self::CREDITS_PER_KRW . ' C';
    }

    public static function formatKrwEquivalent(int $credits): string
    {
        $krw = self::creditsToKrw($credits);
        if (abs($krw - (int) $krw) < 0.0001) {
            return number_format((int) $krw) . '원';
        }
        return rtrim(rtrim(number_format($krw, 2, '.', ','), '0'), '.') . '원';
    }

    public function balance(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        return $this->repo->getBalance($userId);
    }

    public function adjust(
        int $userId,
        int $amount,
        string $description,
        ?int $adminId = null,
        string $txType = 'adjust',
        ?string $sourceRef = null
    ): int {
        if ($userId <= 0) {
            throw new RuntimeException('유효하지 않은 회원입니다.');
        }
        $this->repo->ensureBalanceRow($userId);
        $current = $this->repo->getBalance($userId);
        $next = $current + $amount;
        if ($next < 0) {
            throw new RuntimeException('잉크 잔액이 부족합니다.');
        }
        $this->repo->setBalance($userId, $next);
        $this->repo->addTransaction([
            'user_id' => $userId,
            'amount' => $amount,
            'balance_after' => $next,
            'tx_type' => $txType,
            'source' => $adminId ? 'admin' : 'system',
            'source_ref' => $sourceRef,
            'description' => $description,
            'admin_id' => $adminId,
        ]);
        $notifier = new NotificationService();
        $notifier->notifyCreditChange($userId, $amount, $next, $description);
        $notifier->maybeNotifyCreditLow($userId, $current, $next);
        return $next;
    }

    /**
     * 관리자 잉크보상 규칙(trigger_type)에 따라 지급한다.
     * 회원가입은 SIGNUP_WELCOME 등 signup 트리거 활성 규칙이 있으면 1회 지급.
     */
    public function grantByTrigger(int $userId, string $trigger): int
    {
        if ($userId <= 0 || $trigger === '') {
            return 0;
        }
        $granted = 0;
        foreach ($this->repo->activeRulesByTrigger($trigger) as $rule) {
            $amount = (int) ($rule['credit_amount'] ?? 0);
            $code = strtoupper(trim((string) ($rule['code'] ?? '')));
            if ($amount <= 0 || $code === '') {
                continue;
            }
            $maxTotal = $rule['max_total_per_user'] ?? null;
            if ($maxTotal !== null && $maxTotal !== '') {
                if ($this->repo->countUserRewardByCode($userId, $code) >= (int) $maxTotal) {
                    continue;
                }
            }
            $dailyLimit = $rule['daily_limit'] ?? null;
            if ($dailyLimit !== null && $dailyLimit !== '') {
                if ($this->repo->countUserRewardByCode($userId, $code, date('Y-m-d 00:00:00')) >= (int) $dailyLimit) {
                    continue;
                }
            }
            $sourceRef = $code . ':' . $userId;
            if ($trigger === 'daily_login') {
                $sourceRef .= ':' . date('Ymd');
            } elseif ($trigger !== 'signup') {
                $sourceRef .= ':' . date('YmdHis');
            }
            if ($this->repo->hasSourceRef('reward', $sourceRef)) {
                continue;
            }
            $this->grantReward($userId, $amount, self::rewardLabel($code, (string) ($rule['name'] ?? '')), $sourceRef);
            $granted += $amount;
        }
        return $granted;
    }

    public function grantReward(int $userId, int $amount, string $description, string $sourceRef): int
    {
        if ($userId <= 0 || $amount <= 0) {
            return $this->balance($userId);
        }
        if ($sourceRef !== '' && $this->repo->hasSourceRef('reward', $sourceRef)) {
            return $this->balance($userId);
        }
        $description = self::repairUtf8(trim($description));
        if ($description === '') {
            $description = '잉크 보상';
        }
        if (mb_strlen($description) > 255) {
            $description = mb_substr($description, 0, 252) . '...';
        }
        $this->repo->ensureBalanceRow($userId);
        $current = $this->repo->getBalance($userId);
        $next = $current + $amount;
        $this->repo->setBalance($userId, $next);
        $this->repo->addTransaction([
            'user_id' => $userId,
            'amount' => $amount,
            'balance_after' => $next,
            'tx_type' => 'earn',
            'source' => 'reward',
            'source_ref' => $sourceRef !== '' ? $sourceRef : null,
            'description' => $description,
            'admin_id' => null,
        ]);
        $notifier = new NotificationService();
        $notifier->notifyCreditChange($userId, $amount, $next, $description);
        $notifier->maybeNotifyCreditLow($userId, $current, $next);
        return $next;
    }

    public function grantPurchase(int $userId, int $amount, string $description, string $sourceRef): int
    {
        if ($userId <= 0 || $amount <= 0) {
            return $this->balance($userId);
        }
        if ($this->repo->hasSourceRef('order', $sourceRef)) {
            return $this->balance($userId);
        }
        $description = trim($description);
        if ($description === '') {
            $description = '잉크 충전 구매';
        }
        if (mb_strlen($description) > 255) {
            $description = mb_substr($description, 0, 252) . '...';
        }
        $this->repo->ensureBalanceRow($userId);
        $current = $this->repo->getBalance($userId);
        $next = $current + $amount;
        $this->repo->setBalance($userId, $next);
        $this->repo->addTransaction([
            'user_id' => $userId,
            'amount' => $amount,
            'balance_after' => $next,
            'tx_type' => 'earn',
            'source' => 'order',
            'source_ref' => $sourceRef,
            'description' => $description,
            'admin_id' => null,
        ]);
        $notifier = new NotificationService();
        $notifier->notifyCreditChange($userId, $amount, $next, $description);
        $notifier->maybeNotifyCreditLow($userId, $current, $next);
        return $next;
    }

    public function grant(int $userId, int $amount, string $reason, int $adminId, ?string $sourceRef = null): int
    {
        if ($amount <= 0) {
            throw new RuntimeException('지급 잉크는 1 이상이어야 합니다.');
        }
        if ($amount > 10000000) {
            throw new RuntimeException('한 번에 지급할 수 있는 잉크는 10,000,000 C까지입니다.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('지급 사유를 입력해주세요.');
        }
        if (mb_strlen($reason) > 255) {
            throw new RuntimeException('지급 사유는 255자 이내로 입력해주세요.');
        }
        $sourceRef = trim((string) $sourceRef);
        return $this->adjust($userId, $amount, $reason, $adminId, 'earn', $sourceRef !== '' ? $sourceRef : null);
    }

    public function spend(
        int $userId,
        int $amount,
        string $description,
        string $source = 'system',
        ?string $sourceRef = null,
        bool $allowOverdraft = false
    ): int {
        if ($userId <= 0) {
            throw new RuntimeException('유효하지 않은 회원입니다.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('사용 잉크는 1 이상이어야 합니다.');
        }
        $this->repo->ensureBalanceRow($userId);
        $current = $this->repo->getBalance($userId);
        if (!$allowOverdraft && $current < $amount) {
            throw new RuntimeException('잉크 잔액이 부족합니다.');
        }
        $next = $current - $amount;
        $this->repo->setBalance($userId, $next);
        $this->repo->addTransaction([
            'user_id' => $userId,
            'amount' => -$amount,
            'balance_after' => $next,
            'tx_type' => 'spend',
            'source' => $source,
            'source_ref' => $sourceRef,
            'description' => mb_substr(trim($description), 0, 255),
            'admin_id' => null,
        ]);
        $notifier = new NotificationService();
        $notifier->notifyCreditChange($userId, -$amount, $next, $description);
        $notifier->maybeNotifyCreditLow($userId, $current, $next);
        return $next;
    }

    public static function format(int $amount): string
    {
        return number_format($amount) . ' C';
    }

    public static function txTypeLabel(string $type): string
    {
        return match ($type) {
            'earn' => '적립',
            'spend' => '사용',
            'adjust' => '조정',
            'refund' => '환불',
            default => $type,
        };
    }

    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'reward' => '보상',
            'purchase_code' => '구매코드',
            'admin' => '관리자',
            'order' => '주문/사용',
            'ai' => 'AI 사용',
            default => '시스템',
        };
    }

    public static function triggerLabel(string $trigger): string
    {
        return match ($trigger) {
            'signup' => '회원가입',
            'daily_login' => '일일 접속',
            'design_complete' => '디자인 완료',
            'referral' => '친구 추천',
            'purchase_code' => '구매 코드',
            'event' => '이벤트',
            'manual' => '수동',
            default => $trigger,
        };
    }

    public static function csCategoryLabel(string $cat): string
    {
        return match ($cat) {
            'inquiry' => '문의',
            'complaint' => '불만',
            'refund' => '환불',
            'account' => '계정',
            'technical' => '기술',
            default => '기타',
        };
    }

    public static function csStatusLabel(string $status): string
    {
        return match ($status) {
            'open' => '접수',
            'in_progress' => '처리중',
            'resolved' => '완료',
            default => $status,
        };
    }

    /** @return array{balance:int,items:array,total:int,page:int,pages:int,per_page:int} */
    public function summaryForUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $tx = $this->repo->transactionsForUser($userId, $page, $perPage);
        $items = [];
        foreach ($tx['items'] as $row) {
            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'amount' => (int) ($row['amount'] ?? 0),
                'balance_after' => (int) ($row['balance_after'] ?? 0),
                'tx_type' => (string) ($row['tx_type'] ?? ''),
                'tx_type_label' => self::txTypeLabel((string) ($row['tx_type'] ?? '')),
                'source' => (string) ($row['source'] ?? ''),
                'source_label' => self::sourceLabel((string) ($row['source'] ?? '')),
                'description' => self::repairUtf8((string) ($row['description'] ?? '')),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }
        return [
            'balance' => $this->balance($userId),
            'items' => $items,
            'total' => (int) ($tx['total'] ?? 0),
            'page' => (int) ($tx['page'] ?? $page),
            'pages' => (int) ($tx['pages'] ?? 1),
            'per_page' => (int) ($tx['per_page'] ?? $perPage),
        ];
    }
}
