<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CreditRepository;
use App\Repositories\ShopRepository;
use App\Repositories\UserLoginLogRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class CreditAdminService
{
    private CreditRepository $credits;
    private CreditService $creditService;

    public function __construct()
    {
        $this->credits = new CreditRepository();
        $this->creditService = new CreditService();
    }

    /** @return array<int, array<string, mixed>> */
    public function rewardRules(): array
    {
        return $this->credits->allRewardRules();
    }

    public function saveRewardRule(array $data): int
    {
        if (trim((string) ($data['code'] ?? '')) === '') {
            throw new RuntimeException('보상 코드를 입력해주세요.');
        }
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new RuntimeException('보상명을 입력해주세요.');
        }
        return $this->credits->saveRewardRule($data);
    }

    public function deleteRewardRule(int $id): void
    {
        $this->credits->deleteRewardRule($id);
    }

    /** @return array<int, array<string, mixed>> */
    public function purchaseProducts(): array
    {
        return $this->credits->allPurchaseProducts();
    }

    public function savePurchaseProduct(array $data): int
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new RuntimeException('제품명을 입력해주세요.');
        }
        return $this->credits->savePurchaseProduct($data);
    }

    public function deletePurchaseProduct(int $id): void
    {
        $this->credits->deletePurchaseProduct($id);
    }

    /** @return array{items: array, total: int, page: int, pages: int} */
    public function redemptionHistory(string $search = '', int $page = 1): array
    {
        return $this->credits->redemptionHistory($page, 20, $search);
    }

    /** @return array<int, array<string, mixed>> */
    public function purchaseCodes(int $productId = 0): array
    {
        return $this->credits->allCodes($productId);
    }

    public function generateCodes(int $productId, int $count, string $prefix = 'LU'): int
    {
        if ($productId <= 0 || $count <= 0) {
            throw new RuntimeException('제품과 생성 수량을 확인해주세요.');
        }
        $batch = 'BATCH-' . date('Ymd-His');
        $codes = [];
        for ($i = 0; $i < min(500, $count); $i++) {
            $codes[] = strtoupper($prefix . '-' . bin2hex(random_bytes(4)));
        }
        return $this->credits->createCodes($productId, $codes, $batch);
    }

    public function adjustUserCredit(int $userId, int $amount, string $description, int $adminId): int
    {
        return $this->creditService->adjust($userId, $amount, $description, $adminId);
    }

    public function grantUserCredit(int $userId, int $amount, string $reason, int $adminId): int
    {
        if ($userId <= 0) {
            throw new RuntimeException('회원을 선택해주세요.');
        }
        if (!(new UserRepository())->findById($userId)) {
            throw new RuntimeException('회원을 찾을 수 없습니다.');
        }
        return $this->creditService->grant($userId, $amount, $reason, $adminId);
    }

    /** @return array{items: array, total: int, page: int, pages: int, per_page: int} */
    public function grantHistory(?int $userId = null, int $page = 1, int $perPage = 20): array
    {
        return $this->credits->adminGrants($userId, $page, $perPage);
    }

    /** @return array<string, mixed> */
    public function userDetail(int $userId): array
    {
        $user = (new UserRepository())->findById($userId);
        if (!$user) {
            throw new RuntimeException('회원을 찾을 수 없습니다.');
        }
        unset($user['password_hash']);

        return [
            'user' => $user,
            'credit_balance' => $this->creditService->balance($userId),
            'credit_tx' => $this->credits->transactionsForUser($userId, 1, 30),
            'credit_grants' => $this->credits->adminGrants($userId, 1, 30),
            'cs_logs' => $this->credits->csLogsForUser($userId),
            'login_logs' => (new UserLoginLogRepository())->recentForUser($userId, 15),
            'orders' => (new ShopRepository())->ordersByUser($userId, 10),
        ];
    }

    public function saveCsLog(array $data, int $adminId): int
    {
        if (trim((string) ($data['subject'] ?? '')) === '') {
            throw new RuntimeException('제목을 입력해주세요.');
        }
        $data['admin_id'] = $adminId;
        return $this->credits->saveCsLog($data);
    }

    /** @return array{items: array, total: int, page: int, pages: int} */
    public function listUsers(string $search, int $page): array
    {
        return $this->credits->listUsersWithCredit($search, $page, 20);
    }
}
