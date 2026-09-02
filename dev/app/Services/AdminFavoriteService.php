<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminFavoriteRepository;
use RuntimeException;

final class AdminFavoriteService
{
    private AdminFavoriteRepository $repo;

    public function __construct()
    {
        $this->repo = new AdminFavoriteRepository();
    }

    /** @return array<int, array{slot:int,menu_key:?string,label:?string,href:?string,ic:?string}> */
    public function slotsFor(int $adminUserId): array
    {
        $map = [];
        foreach ($this->repo->forAdmin($adminUserId) as $row) {
            $map[(int) $row['slot']] = (string) $row['menu_key'];
        }
        $slots = [];
        for ($i = 1; $i <= 10; $i++) {
            $key = $map[$i] ?? null;
            $menu = $key ? admin_menu_by_key($key) : null;
            if ($menu && !admin_can_menu($menu['key'])) {
                $menu = null;
                $key = null;
            }
            $slots[] = [
                'slot' => $i,
                'menu_key' => $menu['key'] ?? $key,
                'label' => $menu['label'] ?? null,
                'href' => $menu ? url($menu['href']) : null,
                'ic' => $menu['ic'] ?? null,
            ];
        }
        return $slots;
    }

    public function save(int $adminUserId, array $incoming): void
    {
        $usedKeys = [];
        $slots = [];
        foreach ($incoming as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slot = (int) ($row['slot'] ?? 0);
            $key = trim((string) ($row['menu_key'] ?? ''));
            if ($slot < 1 || $slot > 10 || $key === '') {
                continue;
            }
            if (admin_menu_by_key($key) === null) {
                throw new RuntimeException('알 수 없는 메뉴입니다: ' . $key);
            }
            if (!admin_can_menu($key)) {
                throw new RuntimeException('권한이 없는 메뉴입니다: ' . $key);
            }
            if (isset($usedKeys[$key])) {
                throw new RuntimeException('같은 메뉴를 두 칸에 넣을 수 없습니다.');
            }
            $usedKeys[$key] = true;
            $slots[] = ['slot' => $slot, 'menu_key' => $key];
        }
        $this->repo->replaceAll($adminUserId, $slots);
    }
}
