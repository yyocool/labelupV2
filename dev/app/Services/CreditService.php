<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CreditRepository;
use RuntimeException;

final class CreditService
{
    private CreditRepository $repo;

    public function __construct()
    {
        $this->repo = new CreditRepository();
    }

    public function balance(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        return $this->repo->getBalance($userId);
    }

    public function adjust(int $userId, int $amount, string $description, ?int $adminId = null, string $txType = 'adjust'): int
    {
        if ($userId <= 0) {
            throw new RuntimeException('유효하지 않은 회원입니다.');
        }
        $this->repo->ensureBalanceRow($userId);
        $current = $this->repo->getBalance($userId);
        $next = $current + $amount;
        if ($next < 0) {
            throw new RuntimeException('크레딧 잔액이 부족합니다.');
        }
        $this->repo->setBalance($userId, $next);
        $this->repo->addTransaction([
            'user_id' => $userId,
            'amount' => $amount,
            'balance_after' => $next,
            'tx_type' => $txType,
            'source' => $adminId ? 'admin' : 'system',
            'source_ref' => null,
            'description' => $description,
            'admin_id' => $adminId,
        ]);
        return $next;
    }

    public function grant(int $userId, int $amount, string $reason, int $adminId): int
    {
        if ($amount <= 0) {
            throw new RuntimeException('지급 크레딧은 1 이상이어야 합니다.');
        }
        if ($amount > 1000000) {
            throw new RuntimeException('한 번에 지급할 수 있는 크레딧은 1,000,000 C까지입니다.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('지급 사유를 입력해주세요.');
        }
        if (mb_strlen($reason) > 255) {
            throw new RuntimeException('지급 사유는 255자 이내로 입력해주세요.');
        }
        return $this->adjust($userId, $amount, $reason, $adminId, 'earn');
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
}
