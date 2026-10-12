<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Repositories\CreditRepository;
use App\Repositories\ErrorReportRepository;
use RuntimeException;

final class ErrorReportService
{
    private ErrorReportRepository $repo;

    public function __construct()
    {
        $this->repo = new ErrorReportRepository();
        $this->repo->ensureSchema();
    }

    /** @return array<string, string> */
    public static function kinds(): array
    {
        return [
            'error' => '오류',
            'suggestion' => '제안',
        ];
    }

    public static function kindLabel(string $kind): string
    {
        return self::kinds()[$kind] ?? '오류';
    }

    /** @return array<string, string> */
    public static function categories(): array
    {
        return [
            'site' => '사이트 전반',
            'ai' => 'AI',
            'editor' => '편집기',
            'shop' => '쇼핑몰·주문',
            'account' => '계정·결제',
            'other' => '기타',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'open' => '접수',
            'reviewing' => '확인중',
            'resolved' => '처리완료',
            'rejected' => '반려',
        ];
    }

    public static function categoryLabel(string $category): string
    {
        return self::categories()[$category] ?? $category;
    }

    public static function statusLabel(string $status): string
    {
        return self::statuses()[$status] ?? $status;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array<string, mixed>> $files
     */
    public function submit(int $userId, array $payload, array $files = []): int
    {
        if ($userId <= 0) {
            throw new RuntimeException('로그인 후 오류나 제안을 보낼 수 있습니다.');
        }
        $kind = (string) ($payload['kind'] ?? 'error');
        if (!isset(self::kinds()[$kind])) {
            throw new RuntimeException('오류 또는 제안을 선택해주세요.');
        }
        $category = (string) ($payload['category'] ?? 'site');
        if (!isset(self::categories()[$category])) {
            throw new RuntimeException('분류를 선택해주세요.');
        }
        $title = trim((string) ($payload['title'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));
        if ($title === '' || mb_strlen($title) < 2) {
            throw new RuntimeException('제목을 2자 이상 입력해주세요.');
        }
        if (mb_strlen($title) > 200) {
            throw new RuntimeException('제목은 200자 이내로 입력해주세요.');
        }
        if (mb_strlen($content) < 10) {
            throw new RuntimeException('내용을 10자 이상 입력해주세요.');
        }
        if (mb_strlen($content) > 4000) {
            throw new RuntimeException('내용은 4000자 이내로 입력해주세요.');
        }
        if ($this->repo->countTodayByUser($userId) >= 20) {
            throw new RuntimeException('오늘은 오류와 제안을 20건까지 보낼 수 있습니다.');
        }
        $pageUrl = trim((string) ($payload['page_url'] ?? ''));
        if (mb_strlen($pageUrl) > 500) {
            $pageUrl = mb_substr($pageUrl, 0, 500);
        }
        $paths = $this->storeImages($files);
        try {
            $id = $this->repo->insert([
                'user_id' => $userId,
                'kind' => $kind,
                'category' => $category,
                'title' => $title,
                'content' => $content,
                'page_url' => $pageUrl !== '' ? $pageUrl : null,
            ]);
            if ($paths !== []) {
                $this->repo->insertImages($id, $paths);
            }
            return $id;
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                $this->deletePublicFile($path);
            }
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('접수하지 못했습니다.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function adminList(string $status, string $kind = 'all'): array
    {
        if ($status !== 'all' && $status !== '' && !isset(self::statuses()[$status])) {
            $status = 'all';
        }
        if ($kind !== 'all' && $kind !== '' && !isset(self::kinds()[$kind])) {
            $kind = 'all';
        }
        $rows = $this->repo->listForAdmin($status === '' ? 'all' : $status, $kind === '' ? 'all' : $kind);
        foreach ($rows as &$row) {
            $row['kind_label'] = self::kindLabel((string) ($row['kind'] ?? 'error'));
            $row['category_label'] = self::categoryLabel((string) ($row['category'] ?? ''));
            $row['status_label'] = self::statusLabel((string) ($row['status'] ?? ''));
            $row['reward_total'] = (int) ($row['reward_total'] ?? 0);
            $row['image_count'] = (int) ($row['image_count'] ?? 0);
        }
        unset($row);
        return $rows;
    }

    /** @return array<string, mixed> */
    public function adminDetail(int $id): array
    {
        $report = $this->repo->find($id);
        if (!$report) {
            throw new RuntimeException('신고를 찾을 수 없습니다.');
        }
        $report['kind_label'] = self::kindLabel((string) ($report['kind'] ?? 'error'));
        $report['category_label'] = self::categoryLabel((string) ($report['category'] ?? ''));
        $report['status_label'] = self::statusLabel((string) ($report['status'] ?? ''));
        $images = [];
        foreach ($this->repo->images($id) as $image) {
            $path = (string) ($image['path'] ?? '');
            if ($path === '' || !str_starts_with($path, '/assets/error-reports/')) {
                continue;
            }
            $images[] = [
                'id' => (int) ($image['id'] ?? 0),
                'path' => $path,
                'url' => url(ltrim($path, '/')),
            ];
        }
        $report['images'] = $images;
        $actions = $this->repo->actions($id);
        foreach ($actions as &$action) {
            $action['status_label'] = self::statusLabel((string) ($action['status'] ?? ''));
            $action['reward_credits'] = (int) ($action['reward_credits'] ?? 0);
        }
        unset($action);
        return ['report' => $report, 'actions' => $actions];
    }

    /** @param array<string, mixed> $payload */
    public function process(int $id, array $payload, int $adminId): array
    {
        if ($adminId <= 0) {
            throw new RuntimeException('관리자 로그인이 필요합니다.');
        }
        $report = $this->repo->find($id);
        if (!$report) {
            throw new RuntimeException('신고를 찾을 수 없습니다.');
        }
        $status = (string) ($payload['status'] ?? '');
        if (!isset(self::statuses()[$status]) || $status === 'open') {
            throw new RuntimeException('처리 상태를 선택해주세요.');
        }
        $note = trim((string) ($payload['result_note'] ?? ''));
        if (mb_strlen($note) < 2) {
            throw new RuntimeException('처리 결과를 2자 이상 입력해주세요.');
        }
        if (mb_strlen($note) > 4000) {
            throw new RuntimeException('처리 결과는 4000자 이내로 입력해주세요.');
        }
        $reward = (int) ($payload['reward_credits'] ?? 0);
        if ($reward < 0) {
            throw new RuntimeException('포상 잉크는 0 이상으로 입력해주세요.');
        }
        if ($reward > 10000000) {
            throw new RuntimeException('한 번에 지급할 수 있는 잉크는 10,000,000 C까지입니다.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->repo->updateStatus($id, $status);
            $actionId = $this->repo->insertAction([
                'report_id' => $id,
                'admin_id' => $adminId,
                'status' => $status,
                'result_note' => $note,
                'reward_credits' => $reward,
                'credit_tx_id' => null,
            ]);
            $txId = 0;
            if ($reward > 0) {
                $title = trim((string) ($report['title'] ?? ''));
                $reason = '오류&제안 포상 · ' . ($title !== '' ? $title : ('#' . $id));
                if (mb_strlen($reason) > 255) {
                    $reason = mb_substr($reason, 0, 252) . '...';
                }
                $sourceRef = 'error-report-' . $id . '-' . $actionId;
                (new CreditService())->grant((int) $report['user_id'], $reward, $reason, $adminId, $sourceRef);
                $txId = (new CreditRepository())->findIdBySourceRef('admin', $sourceRef);
                if ($txId > 0) {
                    $this->repo->setActionCreditTx($actionId, $txId);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('처리 결과를 저장하지 못했습니다.');
        }

        $body = $note;
        if ($reward > 0) {
            $body .= "\n포상 잉크 " . number_format($reward) . ' C가 지급되었습니다.';
        }
        (new NotificationService())->notify(
            (int) $report['user_id'],
            NotificationService::TYPE_SYSTEM,
            '오류&제안 처리 결과가 등록되었습니다',
            $body,
            url('account') . '#credits',
            'error_report',
            (string) $id
        );

        return $this->adminDetail($id);
    }

    /**
     * @param array<int, array<string, mixed>> $files
     * @return array<int, string>
     */
    private function storeImages(array $files): array
    {
        $pending = [];
        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }
            $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $pending[] = $file;
        }
        if (count($pending) > 4) {
            throw new RuntimeException('이미지는 최대 4장까지 첨부할 수 있습니다.');
        }

        $saved = [];
        try {
            foreach ($pending as $file) {
                $saved[] = $this->storeOneImage($file);
            }
        } catch (\Throwable $e) {
            foreach ($saved as $path) {
                $this->deletePublicFile($path);
            }
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('이미지를 저장하지 못했습니다.');
        }
        return $saved;
    }

    /** @param array<string, mixed> $file */
    private function storeOneImage(array $file): string
    {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('이미지는 한 장당 4MB까지 첨부할 수 있습니다.');
        }
        if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('이미지 업로드에 실패했습니다.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 4 * 1024 * 1024) {
            throw new RuntimeException('이미지는 한 장당 4MB까지 첨부할 수 있습니다.');
        }
        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new RuntimeException('이미지 파일이 아닙니다.');
        }
        $mime = strtolower((string) ($info['mime'] ?? ''));
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($extMap[$mime])) {
            throw new RuntimeException('jpg, png, gif, webp 이미지만 첨부할 수 있습니다.');
        }

        $subdir = date('Ym');
        $dir = public_path('assets/error-reports/' . $subdir);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('이미지 저장 폴더를 만들 수 없습니다.');
        }
        $filename = 'er_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extMap[$mime];
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('이미지를 저장하지 못했습니다.');
        }
        @chmod($dest, 0664);

        return '/assets/error-reports/' . $subdir . '/' . $filename;
    }

    private function deletePublicFile(string $path): void
    {
        if (!str_starts_with($path, '/assets/error-reports/')) {
            return;
        }
        $abs = public_path(ltrim($path, '/'));
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
