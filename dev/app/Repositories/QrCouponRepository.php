<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use PDO;
use RuntimeException;

final class QrCouponRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return list<array<string, mixed>> */
    public function allGroups(): array
    {
        $sql = 'SELECT g.*,
                       c.id AS shop_category_id,
                       (
                           SELECT COUNT(*)
                           FROM shop_products p
                           INNER JOIN shop_categories pc ON pc.id = p.category_id
                           WHERE pc.slug = g.category_slug
                             AND COALESCE(
                                 NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(IFNULL(p.meta_json, \'{}\'), \'$.sheets_per_pack\')) AS UNSIGNED), 0),
                                 CAST(SUBSTRING_INDEX(p.sku, \'-\', -1) AS UNSIGNED)
                             ) = g.sheets_per_pack
                       ) AS product_count,
                       (
                           SELECT COUNT(*)
                           FROM qr_coupon_codes qc
                           WHERE qc.group_no = g.group_no
                       ) AS generated_qr_count,
                       (
                           SELECT COUNT(*)
                           FROM qr_coupon_codes qc
                           WHERE qc.group_no = g.group_no
                             AND qc.printed_at IS NOT NULL
                       ) AS printed_qr_count
                FROM qr_coupon_groups g
                LEFT JOIN shop_categories c ON c.slug = g.category_slug
                WHERE g.is_active = 1
                ORDER BY g.group_no ASC';

        try {
            $stmt = $this->db->query($sql);
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (\Throwable $e) {
            $stmt = $this->db->query(
                'SELECT g.*,
                        c.id AS shop_category_id,
                        (
                            SELECT COUNT(*)
                            FROM shop_products p
                            INNER JOIN shop_categories pc ON pc.id = p.category_id
                            WHERE pc.slug = g.category_slug
                              AND CAST(SUBSTRING_INDEX(p.sku, \'-\', -1) AS UNSIGNED) = g.sheets_per_pack
                        ) AS product_count,
                        (
                            SELECT COUNT(*)
                            FROM qr_coupon_codes qc
                            WHERE qc.group_no = g.group_no
                        ) AS generated_qr_count,
                        0 AS printed_qr_count
                 FROM qr_coupon_groups g
                 LEFT JOIN shop_categories c ON c.slug = g.category_slug
                 WHERE g.is_active = 1
                 ORDER BY g.group_no ASC'
            );
            try {
                return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            } catch (\Throwable $e2) {
                $stmt = $this->db->query(
                    'SELECT g.*, c.id AS shop_category_id, 0 AS product_count, 0 AS generated_qr_count, 0 AS printed_qr_count
                     FROM qr_coupon_groups g
                     LEFT JOIN shop_categories c ON c.slug = g.category_slug
                     WHERE g.is_active = 1
                     ORDER BY g.group_no ASC'
                );
                return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            }
        }
    }

    public function updateCreditAmount(int $groupNo, ?int $creditAmount): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE qr_coupon_groups
             SET credit_amount = :credit_amount, updated_at = NOW()
             WHERE group_no = :group_no AND is_active = 1'
        );
        $stmt->bindValue(':group_no', $groupNo, PDO::PARAM_INT);
        if ($creditAmount === null) {
            $stmt->bindValue(':credit_amount', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':credit_amount', $creditAmount, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount() >= 0;
    }

    public function findByGroupNo(int $groupNo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM qr_coupon_groups WHERE group_no = :group_no AND is_active = 1 LIMIT 1'
        );
        $stmt->execute(['group_no' => $groupNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT qc.*, g.category_name, g.list_price, g.credit_amount, g.color_hex, g.category_no
             FROM qr_coupon_codes qc
             LEFT JOIN qr_coupon_groups g ON g.group_no = qc.group_no
             WHERE qc.code = :code
             LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function productsForGroup(string $categorySlug, int $sheetsPerPack, int $limit = 24): array
    {
        $limit = max(1, min(60, $limit));
        $sql = "SELECT p.id, p.name, p.sku, p.price, p.sale_price, p.thumbnail, p.status,
                       c.name AS category_name, c.slug AS category_slug,
                       s.name AS spec_name, s.width_mm, s.height_mm, s.material, s.labels_per_sheet
                FROM shop_products p
                INNER JOIN shop_categories c ON c.id = p.category_id
                LEFT JOIN label_specs s ON s.id = p.spec_id
                WHERE c.slug = :slug
                  AND p.status = 'active'
                  AND COALESCE(
                      NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(IFNULL(p.meta_json, '{}'), '$.sheets_per_pack')) AS UNSIGNED), 0),
                      CAST(SUBSTRING_INDEX(p.sku, '-', -1) AS UNSIGNED)
                  ) = :sheets
                ORDER BY p.sort_order ASC, p.id ASC
                LIMIT {$limit}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['slug' => $categorySlug, 'sheets' => $sheetsPerPack]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $stmt = $this->db->prepare(
                "SELECT p.id, p.name, p.sku, p.price, p.sale_price, p.thumbnail, p.status,
                        c.name AS category_name, c.slug AS category_slug,
                        s.name AS spec_name, s.width_mm, s.height_mm, s.material, s.labels_per_sheet
                 FROM shop_products p
                 INNER JOIN shop_categories c ON c.id = p.category_id
                 LEFT JOIN label_specs s ON s.id = p.spec_id
                 WHERE c.slug = :slug
                   AND p.status = 'active'
                   AND CAST(SUBSTRING_INDEX(p.sku, '-', -1) AS UNSIGNED) = :sheets
                 ORDER BY p.sort_order ASC, p.id ASC
                 LIMIT {$limit}"
            );
            $stmt->execute(['slug' => $categorySlug, 'sheets' => $sheetsPerPack]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    /**
     * @return array{batch_id:int, codes:list<array<string,mixed>>}
     */
    public function createBatch(
        array $group,
        string $couponPageUrlBase,
        int $quantity,
        ?int $createdBy
    ): array {
        if ($quantity < 1 || $quantity > 2000) {
            throw new RuntimeException('생성 수량은 1~2000개까지 가능합니다.');
        }

        $groupNo = (int) $group['group_no'];
        $slug = (string) $group['category_slug'];
        $name = (string) $group['category_name'];
        $sheets = (int) $group['sheets_per_pack'];

        $this->db->beginTransaction();
        try {
            $insBatch = $this->db->prepare(
                'INSERT INTO qr_coupon_batches
                    (group_no, category_slug, category_name, sheets_per_pack, coupon_page_url, quantity, created_by, created_at)
                 VALUES
                    (:group_no, :category_slug, :category_name, :sheets_per_pack, :coupon_page_url, :quantity, :created_by, NOW())'
            );
            $insBatch->execute([
                'group_no' => $groupNo,
                'category_slug' => $slug,
                'category_name' => $name,
                'sheets_per_pack' => $sheets,
                'coupon_page_url' => $couponPageUrlBase,
                'quantity' => $quantity,
                'created_by' => $createdBy,
            ]);
            $batchId = (int) $this->db->lastInsertId();

            $insCode = $this->db->prepare(
                'INSERT INTO qr_coupon_codes
                    (batch_id, group_no, category_slug, sheets_per_pack, code, coupon_page_url, status, created_at)
                 VALUES
                    (:batch_id, :group_no, :category_slug, :sheets_per_pack, :code, :coupon_page_url, \'unused\', NOW())'
            );

            $codes = [];
            for ($i = 0; $i < $quantity; $i++) {
                $code = $this->makeUniqueCode($groupNo);
                // 고유 쿠폰번호(code)를 URL에 포함 — 스캔 시 쿠폰번호·그룹 정보가 함께 전달됨
                $pageUrl = qr_public_url('qr-coupon', [
                    'g' => $groupNo,
                    'cat' => $slug,
                    'sheets' => $sheets,
                    'code' => $code,
                ]);
                $insCode->execute([
                    'batch_id' => $batchId,
                    'group_no' => $groupNo,
                    'category_slug' => $slug,
                    'sheets_per_pack' => $sheets,
                    'code' => $code,
                    'coupon_page_url' => $pageUrl,
                ]);
                $codes[] = [
                    'id' => (int) $this->db->lastInsertId(),
                    'code' => $code,
                    'coupon_page_url' => $pageUrl,
                    'status' => 'unused',
                    'printed' => false,
                    'printed_at' => null,
                    'print_count' => 0,
                ];
            }

            $this->db->commit();
            return ['batch_id' => $batchId, 'codes' => $codes];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** @return list<array<string, mixed>> */
    public function batchesByGroup(int $groupNo, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = $this->db->prepare(
            "SELECT b.*,
                    (SELECT COUNT(*) FROM qr_coupon_codes c WHERE c.batch_id = b.id) AS code_count,
                    (SELECT COUNT(*) FROM qr_coupon_codes c WHERE c.batch_id = b.id AND c.status = 'used') AS used_count
             FROM qr_coupon_batches b
             WHERE b.group_no = :group_no
             ORDER BY b.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['group_no' => $groupNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function codesByBatch(int $batchId, int $limit = 500): array
    {
        $limit = max(1, min(2000, $limit));
        $stmt = $this->db->prepare(
            "SELECT * FROM qr_coupon_codes WHERE batch_id = :batch_id ORDER BY id ASC LIMIT {$limit}"
        );
        $stmt->execute(['batch_id' => $batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function codesByGroup(int $groupNo, int $limit = 2000): array
    {
        $limit = max(1, min(2000, $limit));
        $stmt = $this->db->prepare(
            "SELECT * FROM qr_coupon_codes WHERE group_no = :group_no ORDER BY id DESC LIMIT {$limit}"
        );
        $stmt->execute(['group_no' => $groupNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param list<int> $ids
     */
    public function markPrinted(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id) => (int) $id, $ids),
            static fn (int $id) => $id > 0
        )));
        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "UPDATE qr_coupon_codes
             SET printed_at = IFNULL(printed_at, NOW()),
                 print_count = print_count + 1
             WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /** @return list<array<string, mixed>> */
    public function usageByGroup(int $groupNo, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->db->prepare(
            "SELECT qc.*, g.category_name, g.sheets_per_pack, g.credit_amount,
                    u.name AS used_by_name, u.email AS used_by_email
             FROM qr_coupon_codes qc
             LEFT JOIN qr_coupon_groups g ON g.group_no = qc.group_no
             LEFT JOIN users u ON u.id = qc.used_by
             WHERE qc.group_no = :group_no AND qc.status = 'used'
             ORDER BY qc.used_at DESC, qc.id DESC
             LIMIT {$limit}"
        );
        try {
            $stmt->execute(['group_no' => $groupNo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $stmt = $this->db->prepare(
                "SELECT * FROM qr_coupon_codes
                 WHERE group_no = :group_no AND status = 'used'
                 ORDER BY used_at DESC, id DESC
                 LIMIT {$limit}"
            );
            $stmt->execute(['group_no' => $groupNo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    /**
     * 전체 QR 쿠폰 지급(사용) 이력
     * @return array{items: list<array<string,mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function usageHistoryAll(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $search = trim($search);

        $where = "qc.status = 'used'";
        $params = [];
        if ($search !== '') {
            $where .= ' AND (
                qc.code LIKE :q
                OR g.category_name LIKE :q
                OR g.category_slug LIKE :q
                OR u.name LIKE :q
                OR u.email LIKE :q
                OR CAST(qc.group_no AS CHAR) = :q_exact
            )';
            $params['q'] = '%' . $search . '%';
            $params['q_exact'] = $search;
        }

        try {
            $countSql = "SELECT COUNT(*) AS cnt
                         FROM qr_coupon_codes qc
                         LEFT JOIN qr_coupon_groups g ON g.group_no = qc.group_no
                         LEFT JOIN users u ON u.id = qc.used_by
                         WHERE {$where}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) ($countStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

            $sql = "SELECT qc.*, g.category_name, g.category_slug, g.sheets_per_pack, g.credit_amount, g.list_price,
                           u.id AS user_id, u.name AS used_by_name, u.email AS used_by_email
                    FROM qr_coupon_codes qc
                    LEFT JOIN qr_coupon_groups g ON g.group_no = qc.group_no
                    LEFT JOIN users u ON u.id = qc.used_by
                    WHERE {$where}
                    ORDER BY qc.used_at DESC, qc.id DESC
                    LIMIT {$perPage} OFFSET {$offset}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [
                'items' => [],
                'total' => 0,
                'page' => $page,
                'pages' => 1,
                'per_page' => $perPage,
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
        ];
    }

    /** @return array<string, mixed>|null */
    public function findPrintTemplate(string $key = 'default'): ?array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM qr_print_templates WHERE template_key = :key LIMIT 1'
            );
            $stmt->execute(['key' => $key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $paper
     * @param list<array<string, mixed>> $objects
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function upsertPrintTemplate(
        string $key,
        string $name,
        array $paper,
        array $objects,
        array $settings,
        ?int $adminId = null
    ): array {
        $paperJson = json_encode($paper, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $objectsJson = json_encode(array_values($objects), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($paperJson === false || $objectsJson === false || $settingsJson === false) {
            throw new RuntimeException('출력템플릿 JSON을 만들지 못했습니다.');
        }

        $existing = $this->findPrintTemplate($key);
        if ($existing) {
            $stmt = $this->db->prepare(
                'UPDATE qr_print_templates
                 SET name = :name, paper_json = :paper_json, objects_json = :objects_json,
                     settings_json = :settings_json, updated_by = :updated_by, updated_at = NOW()
                 WHERE template_key = :template_key'
            );
            $stmt->execute([
                'name' => $name,
                'paper_json' => $paperJson,
                'objects_json' => $objectsJson,
                'settings_json' => $settingsJson,
                'updated_by' => $adminId,
                'template_key' => $key,
            ]);
        } else {
            $stmt = $this->db->prepare(
                'INSERT INTO qr_print_templates
                    (template_key, name, paper_json, objects_json, settings_json, updated_by, created_at, updated_at)
                 VALUES
                    (:template_key, :name, :paper_json, :objects_json, :settings_json, :updated_by, NOW(), NOW())'
            );
            $stmt->execute([
                'template_key' => $key,
                'name' => $name,
                'paper_json' => $paperJson,
                'objects_json' => $objectsJson,
                'settings_json' => $settingsJson,
                'updated_by' => $adminId,
            ]);
        }

        $row = $this->findPrintTemplate($key);
        if (!$row) {
            throw new RuntimeException('출력템플릿을 저장하지 못했습니다. 마이그레이션을 실행해 주세요.');
        }

        return [
            'key' => (string) ($row['template_key'] ?? $key),
            'name' => (string) ($row['name'] ?? $name),
            'paper' => $paper,
            'objects' => array_values($objects),
            'settings' => $settings,
            'updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s'),
        ];
    }

    private function makeUniqueCode(int $groupNo): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($i = 0; $i < 12; $i++) {
            $rand = '';
            for ($j = 0; $j < 8; $j++) {
                $rand .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            // 예: LU01-A7K9M2X4 (그룹번호 + 고유번호)
            $code = sprintf('LU%02d-%s', $groupNo, $rand);
            $check = $this->db->prepare('SELECT id FROM qr_coupon_codes WHERE code = ? LIMIT 1');
            $check->execute([$code]);
            if (!$check->fetchColumn()) {
                return $code;
            }
        }
        throw new RuntimeException('고유 쿠폰번호 생성에 실패했습니다.');
    }
}
