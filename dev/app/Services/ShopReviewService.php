<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopReviewRepository;
use App\Repositories\SiteSettingsRepository;
use RuntimeException;

final class ShopReviewService
{
    public const SETTING_APPROVAL = 'shop_review_approval';
    public const PAGE_SIZE = 10;
    private const MAX_IMAGES = 10;
    private const MAX_CONTENT = 20000;
    private const MIN_TEXT = 5;

    private ShopReviewRepository $repo;
    private SiteSettingsRepository $settings;

    public function __construct()
    {
        $this->repo = new ShopReviewRepository();
        $this->settings = new SiteSettingsRepository();
    }

    /** @return 'auto'|'manual' */
    public function approvalMode(): string
    {
        return $this->settings->get(self::SETTING_APPROVAL, 'auto') === 'manual' ? 'manual' : 'auto';
    }

    public function setApprovalMode(string $mode): void
    {
        if ($mode !== 'auto' && $mode !== 'manual') {
            throw new RuntimeException('노출 방식을 선택해 주세요.');
        }
        $this->settings->set(self::SETTING_APPROVAL, $mode);
    }

    /** @return array{count: int, photo: int, text: int, avg: float, dist: array<int, int>} */
    public function summary(int $productId): array
    {
        try {
            $s = $this->repo->summaryForProduct($productId);
        } catch (\Throwable) {
            $s = ['count' => 0, 'photo' => 0, 'avg' => 0.0, 'dist' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
        }
        $s['text'] = $s['count'] - $s['photo'];
        return $s;
    }

    /** @return array{items: array<int, array<string, mixed>>, total: int, page: int, has_more: bool} */
    public function listForProduct(int $productId, string $type, int $page, ?int $viewerId = null): array
    {
        $type = in_array($type, ['photo', 'text'], true) ? $type : 'all';
        $page = max(1, $page);
        $total = $this->repo->countApprovedForProduct($productId, $type);
        $rows = $this->repo->approvedForProduct($productId, $type, self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE);
        return [
            'items' => array_map(fn (array $r): array => $this->presentPublic($r, $viewerId), $rows),
            'total' => $total,
            'page' => $page,
            'has_more' => $page * self::PAGE_SIZE < $total,
        ];
    }

    /** @return array<string, mixed>|null */
    public function myReview(int $userId, int $productId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        try {
            $row = $this->repo->userReviewForProduct($userId, $productId);
        } catch (\Throwable) {
            return null;
        }
        if ($row === null) {
            return null;
        }
        return [
            'id' => (int) $row['id'],
            'rating' => (int) $row['rating'],
            'content' => (string) $row['content'],
            'status' => (string) $row['status'],
        ];
    }

    /**
     * 회원 리뷰 작성/수정. 상품당 1개이며, 다시 쓰면 기존 리뷰를 고친다.
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $data
     * @return array{id: int, status: string}
     */
    public function submitByUser(array $user, array $data): array
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('로그인 후 리뷰를 작성할 수 있습니다.');
        }
        $productId = (int) ($data['product_id'] ?? 0);
        $this->assertProduct($productId);
        $rating = $this->rating($data['rating'] ?? 0);
        $body = $this->content((string) ($data['content'] ?? ''));
        $status = $this->approvalMode() === 'manual' ? 'pending' : 'approved';

        $existing = $this->repo->userReviewForProduct($userId, $productId);
        $payload = [
            'product_id' => $productId,
            'user_id' => $userId,
            'author_name' => $this->authorName($user),
            'rating' => $rating,
            'status' => $status,
            'is_purchased' => $this->repo->userPurchased($userId, $productId),
        ] + $body;

        if ($existing !== null) {
            $payload['created_at'] = (string) $existing['created_at'];
            $this->repo->update((int) $existing['id'], $payload);
            return ['id' => (int) $existing['id'], 'status' => $status];
        }
        return ['id' => $this->repo->insert($payload), 'status' => $status];
    }

    public function deleteByUser(int $userId, int $reviewId): void
    {
        $row = $this->repo->find($reviewId);
        if ($row === null || (int) ($row['user_id'] ?? 0) !== $userId || !empty($row['is_admin_created'])) {
            throw new RuntimeException('삭제할 리뷰가 없습니다.');
        }
        $this->repo->delete([$reviewId]);
    }

    /**
     * 관리자 등록/수정. 작성자명·작성일·상태를 직접 정할 수 있다.
     *
     * @param array<string, mixed> $data
     */
    public function saveByAdmin(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $productId = (int) ($data['product_id'] ?? 0);
        $this->assertProduct($productId, false);
        $author = trim((string) ($data['author_name'] ?? ''));
        if ($author === '') {
            throw new RuntimeException('작성자명을 입력해 주세요.');
        }
        $status = (string) ($data['status'] ?? 'approved');
        if (!in_array($status, ['pending', 'approved', 'hidden'], true)) {
            $status = 'approved';
        }
        $payload = [
            'product_id' => $productId,
            'author_name' => mb_substr($author, 0, 50),
            'rating' => $this->rating($data['rating'] ?? 0),
            'status' => $status,
            'created_at' => $this->dateTime((string) ($data['created_at'] ?? '')),
        ] + $this->content((string) ($data['content'] ?? ''));

        if ($id > 0) {
            if ($this->repo->find($id) === null) {
                throw new RuntimeException('리뷰를 찾을 수 없습니다.');
            }
            $this->repo->update($id, $payload);
            return $id;
        }
        $payload['user_id'] = null;
        $payload['is_admin_created'] = true;
        $payload['is_purchased'] = false;
        return $this->repo->insert($payload);
    }

    /** @param array<int, int> $ids */
    public function setStatus(array $ids, string $status): int
    {
        if (!in_array($status, ['pending', 'approved', 'hidden'], true)) {
            throw new RuntimeException('잘못된 상태입니다.');
        }
        $n = $this->repo->setStatus($ids, $status);
        if ($n === 0) {
            throw new RuntimeException('선택한 리뷰가 없습니다.');
        }
        return $n;
    }

    /** @param array<int, int> $ids */
    public function delete(array $ids): int
    {
        $n = $this->repo->delete($ids);
        if ($n === 0) {
            throw new RuntimeException('선택한 리뷰가 없습니다.');
        }
        return $n;
    }

    /**
     * @param array{status?: string, type?: string, q?: string, product_id?: int} $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, counts: array<string, int>}
     */
    public function adminList(array $filters, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $total = $this->repo->adminCount($filters);
        return [
            'items' => $this->repo->adminList($filters, $perPage, ($page - 1) * $perPage),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'counts' => $this->repo->statusCounts(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function productOptions(): array
    {
        return $this->repo->productOptions();
    }

    /**
     * 에디터 본문 이미지 업로드. /assets/reviews/YYYYMM/ 에 저장한다.
     *
     * @param array<string, mixed> $file
     */
    public function uploadImage(array $file): string
    {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE || $size > 5 * 1024 * 1024) {
            throw new RuntimeException('이미지는 한 장당 5MB까지 올릴 수 있습니다.');
        }
        if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp) || $size <= 0) {
            throw new RuntimeException('이미지 업로드에 실패했습니다.');
        }
        $info = @getimagesize($tmp);
        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $mime = strtolower((string) ($info['mime'] ?? ''));
        if ($info === false || !isset($extMap[$mime])) {
            throw new RuntimeException('jpg, png, gif, webp 이미지만 올릴 수 있습니다.');
        }
        $subdir = date('Ym');
        $dir = public_path('assets/reviews/' . $subdir);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('이미지 저장 폴더를 만들 수 없습니다.');
        }
        $filename = 'rv_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extMap[$mime];
        if (!move_uploaded_file($tmp, $dir . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException('이미지를 저장하지 못했습니다.');
        }
        @chmod($dir . DIRECTORY_SEPARATOR . $filename, 0664);
        return '/assets/reviews/' . $subdir . '/' . $filename;
    }

    public static function maskName(string $name): string
    {
        $name = trim($name);
        $len = mb_strlen($name);
        if ($len <= 1) {
            return $name === '' ? '고객' : $name . '*';
        }
        if ($len === 2) {
            return mb_substr($name, 0, 1) . '*';
        }
        return mb_substr($name, 0, 1) . str_repeat('*', $len - 2) . mb_substr($name, -1);
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function presentPublic(array $row, ?int $viewerId): array
    {
        $isAdmin = !empty($row['is_admin_created']);
        return [
            'id' => (int) $row['id'],
            'author' => $isAdmin ? (string) $row['author_name'] : self::maskName((string) $row['author_name']),
            'rating' => (int) $row['rating'],
            'content' => (string) $row['content'],
            'type' => (string) $row['review_type'],
            'photo_count' => (int) $row['photo_count'],
            'thumb' => (string) ($row['thumb_url'] ?? ''),
            'purchased' => !empty($row['is_purchased']),
            'date' => substr((string) ($row['created_at'] ?? ''), 0, 10),
            'mine' => $viewerId !== null && $viewerId > 0 && (int) ($row['user_id'] ?? 0) === $viewerId && !$isAdmin,
        ];
    }

    private function assertProduct(int $productId, bool $activeOnly = true): void
    {
        $exists = $productId > 0 && ($activeOnly
            ? (bool) (new ShopService())->productDetail($productId)
            : $this->repo->productExists($productId));
        if (!$exists) {
            throw new RuntimeException('상품을 선택해 주세요.');
        }
    }

    private function rating(mixed $value): int
    {
        $rating = (int) $value;
        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('별점을 선택해 주세요.');
        }
        return $rating;
    }

    /** @return array{content: string, review_type: string, photo_count: int, thumb_url: ?string} */
    private function content(string $html): array
    {
        if (mb_strlen($html) > self::MAX_CONTENT * 2) {
            throw new RuntimeException('리뷰 내용이 너무 깁니다.');
        }
        $clean = ProductDetailPageService::sanitizeDetailHtml($html);
        preg_match_all('/<img\b[^>]*\bsrc="([^"]+)"/iu', $clean, $m);
        $images = $m[1] ?? [];
        if (count($images) > self::MAX_IMAGES) {
            throw new RuntimeException('사진은 최대 ' . self::MAX_IMAGES . '장까지 넣을 수 있습니다.');
        }
        $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($clean), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        if (mb_strlen($plain) < self::MIN_TEXT) {
            throw new RuntimeException('리뷰 내용을 ' . self::MIN_TEXT . '자 이상 입력해 주세요.');
        }
        if (mb_strlen($clean) > self::MAX_CONTENT) {
            throw new RuntimeException('리뷰 내용이 너무 깁니다.');
        }
        return [
            'content' => $clean,
            'review_type' => $images === [] ? 'text' : 'photo',
            'photo_count' => count($images),
            'thumb_url' => $images === [] ? null : html_entity_decode($images[0], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ];
    }

    /** @param array<string, mixed> $user */
    private function authorName(array $user): string
    {
        $name = trim((string) ($user['name'] ?? ''));
        if ($name === '') {
            $name = (string) strstr((string) ($user['email'] ?? ''), '@', true);
        }
        return mb_substr($name !== '' ? $name : '고객', 0, 50);
    }

    private function dateTime(string $value): string
    {
        $value = trim(str_replace('T', ' ', $value));
        $ts = $value !== '' ? strtotime($value) : false;
        return date('Y-m-d H:i:s', $ts !== false ? $ts : time());
    }
}
