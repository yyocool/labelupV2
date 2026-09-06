<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserAddressRepository;
use RuntimeException;

final class UserAddressService
{
    public const MAX_PER_USER = 10;

    private UserAddressRepository $repo;

    public function __construct()
    {
        $this->repo = new UserAddressRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function list(int $userId): array
    {
        return array_map([$this, 'present'], $this->repo->listByUser($userId));
    }

    public function defaultAddress(int $userId): ?array
    {
        $row = $this->repo->defaultForUser($userId);
        return $row ? $this->present($row) : null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function save(int $userId, array $payload): array
    {
        $id = (int) ($payload['id'] ?? 0);
        $data = $this->normalize($payload);
        $existing = $id > 0 ? $this->repo->findForUser($id, $userId) : null;
        if ($id > 0 && !$existing) {
            throw new RuntimeException('배송지를 찾을 수 없습니다.');
        }
        if ($id <= 0 && $this->repo->countByUser($userId) >= self::MAX_PER_USER) {
            throw new RuntimeException('배송지는 최대 ' . self::MAX_PER_USER . '개까지 저장할 수 있습니다.');
        }

        $makeDefault = !empty($payload['is_default']) || ($id <= 0 && $this->repo->countByUser($userId) === 0);
        if ($makeDefault) {
            $this->repo->clearDefault($userId);
        }
        $data['is_default'] = $makeDefault || !empty($existing['is_default']);

        if ($id > 0) {
            $this->repo->updateForUser($id, $userId, $data);
        } else {
            $id = $this->repo->insert(array_merge($data, ['user_id' => $userId]));
        }

        $row = $this->repo->findForUser($id, $userId);
        if (!$row) {
            throw new RuntimeException('배송지를 저장하지 못했습니다.');
        }
        return $this->present($row);
    }

    public function delete(int $userId, int $id): void
    {
        $row = $this->repo->findForUser($id, $userId);
        if (!$row) {
            throw new RuntimeException('배송지를 찾을 수 없습니다.');
        }
        $wasDefault = !empty($row['is_default']);
        $this->repo->deleteForUser($id, $userId);
        if ($wasDefault) {
            $next = $this->repo->defaultForUser($userId);
            if ($next) {
                $this->repo->setDefault((int) $next['id'], $userId);
            }
        }
    }

    public function setDefault(int $userId, int $id): array
    {
        $row = $this->repo->findForUser($id, $userId);
        if (!$row) {
            throw new RuntimeException('배송지를 찾을 수 없습니다.');
        }
        $this->repo->setDefault($id, $userId);
        $fresh = $this->repo->findForUser($id, $userId);
        return $this->present($fresh ?? $row);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function saveFromCheckout(int $userId, array $payload): ?array
    {
        if (empty($payload['save_address'])) {
            return null;
        }
        try {
            return $this->save($userId, [
                'label' => $payload['address_label'] ?? '배송지',
                'recipient_name' => $payload['shipping_name'] ?? '',
                'recipient_phone' => $payload['shipping_phone'] ?? '',
                'zip' => $payload['shipping_zip'] ?? '',
                'address_base' => $payload['shipping_base'] ?? '',
                'address_detail' => $payload['shipping_detail'] ?? '',
                'is_default' => $this->repo->countByUser($userId) === 0,
            ]);
        } catch (RuntimeException) {
            return null;
        }
    }

    /** @param array<string, mixed> $row */
    public function present(array $row): array
    {
        $zip = trim((string) ($row['zip'] ?? ''));
        $base = trim((string) ($row['address_base'] ?? ''));
        $detail = trim((string) ($row['address_detail'] ?? ''));
        $line = trim(implode(' ', array_filter([$zip, $base, $detail], static fn ($v) => $v !== '')));
        return [
            'id' => (int) ($row['id'] ?? 0),
            'label' => (string) ($row['label'] ?? '배송지'),
            'recipient_name' => (string) ($row['recipient_name'] ?? ''),
            'recipient_phone' => (string) ($row['recipient_phone'] ?? ''),
            'zip' => $zip,
            'address_base' => $base,
            'address_detail' => $detail,
            'address_line' => $line,
            'is_default' => !empty($row['is_default']),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload): array
    {
        $name = trim((string) ($payload['recipient_name'] ?? ''));
        $phone = trim((string) ($payload['recipient_phone'] ?? ''));
        $zip = trim((string) ($payload['zip'] ?? ''));
        $base = trim((string) ($payload['address_base'] ?? ''));
        $detail = trim((string) ($payload['address_detail'] ?? ''));
        $label = trim((string) ($payload['label'] ?? ''));
        if ($label === '') {
            $label = '배송지';
        }
        if ($name === '' || $phone === '' || $base === '') {
            throw new RuntimeException('수취인 이름, 연락처, 주소를 모두 입력해 주세요.');
        }
        if ($zip === '') {
            throw new RuntimeException('주소 검색으로 우편번호를 선택해 주세요.');
        }
        return [
            'label' => mb_substr($label, 0, 40),
            'recipient_name' => mb_substr($name, 0, 100),
            'recipient_phone' => mb_substr($phone, 0, 30),
            'zip' => mb_substr($zip, 0, 10),
            'address_base' => mb_substr($base, 0, 300),
            'address_detail' => mb_substr($detail, 0, 200),
        ];
    }
}
