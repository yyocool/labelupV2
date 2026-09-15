<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;
use Throwable;

final class QaReviewRepository extends BaseModel
{
    /**
     * @return array<string, array{
     *   dev_status:string,client_status:string,note:string,request_html:string,updated_at:?string
     * }>
     */
    public function allChecks(): array
    {
        try {
            $rows = $this->fetchAll('SELECT * FROM qa_review_checks');
        } catch (Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $key = (string) ($row['item_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $map[$key] = [
                'dev_status' => $this->normalizeStatus(
                    (string) ($row['dev_status'] ?? ''),
                    isset($row['dev_ok']) ? !empty($row['dev_ok']) : null
                ),
                'client_status' => $this->normalizeStatus(
                    (string) ($row['client_status'] ?? ''),
                    isset($row['client_ok']) ? !empty($row['client_ok']) : null
                ),
                'note' => (string) ($row['note'] ?? ''),
                'request_html' => (string) ($row['request_html'] ?? ''),
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }
        return $map;
    }

    public function upsertStatuses(
        string $itemKey,
        string $devStatus,
        string $clientStatus,
        string $note,
        ?int $updatedBy
    ): void {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO qa_review_checks (item_key, dev_status, client_status, note, updated_by, updated_at)
             VALUES (:k, :d, :c, :n, :u, :t)
             ON DUPLICATE KEY UPDATE
               dev_status = VALUES(dev_status),
               client_status = VALUES(client_status),
               note = VALUES(note),
               updated_by = VALUES(updated_by),
               updated_at = VALUES(updated_at)',
            [
                'k' => $itemKey,
                'd' => $devStatus,
                'c' => $clientStatus,
                'n' => $note !== '' ? mb_substr($note, 0, 500) : null,
                'u' => $updatedBy,
                't' => $now,
            ]
        );
    }

    public function upsertRequest(string $itemKey, string $requestHtml, ?int $updatedBy): void
    {
        $now = date('Y-m-d H:i:s');
        $html = trim($requestHtml);
        if ($html === '' || $html === '<p><br></p>' || $html === '<p></p>') {
            $html = '';
        }
        $this->execute(
            'INSERT INTO qa_review_checks (item_key, request_html, updated_by, updated_at)
             VALUES (:k, :h, :u, :t)
             ON DUPLICATE KEY UPDATE
               request_html = VALUES(request_html),
               updated_by = VALUES(updated_by),
               updated_at = VALUES(updated_at)',
            [
                'k' => $itemKey,
                'h' => $html !== '' ? $html : null,
                'u' => $updatedBy,
                't' => $now,
            ]
        );
    }

    private function normalizeStatus(string $status, ?bool $legacyOk): string
    {
        $status = trim($status);
        if ($status !== '') {
            return $status;
        }
        if ($legacyOk === true) {
            return 'done';
        }
        return '';
    }
}
