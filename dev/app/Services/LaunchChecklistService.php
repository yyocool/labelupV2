<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * 로그인 없이 공유되는 오픈 체크리스트 상태 (storage JSON).
 */
final class LaunchChecklistService
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? storage_path('launch-checklist.json');
    }

    /**
     * @return array{
     *   items: list<array<string, mixed>>,
     *   categories: list<string>,
     *   checks: array<string, array{done:bool, note:string, by:string, at:string}>,
     *   progress: array{total:int, done:int, p0_total:int, p0_done:int},
     *   updated_at: string
     * }
     */
    public function snapshot(): array
    {
        $checks = $this->readChecks();
        $items = [];
        $done = 0;
        $p0Total = 0;
        $p0Done = 0;

        foreach (LaunchChecklistCatalog::items() as $item) {
            $key = $item['key'];
            $state = $checks[$key] ?? ['done' => false, 'note' => '', 'by' => '', 'at' => ''];
            $isDone = !empty($state['done']);
            if ($isDone) {
                $done++;
            }
            if (($item['priority'] ?? '') === 'P0') {
                $p0Total++;
                if ($isDone) {
                    $p0Done++;
                }
            }
            $items[] = array_merge($item, [
                'done' => $isDone,
                'note' => (string) ($state['note'] ?? ''),
                'by' => (string) ($state['by'] ?? ''),
                'at' => (string) ($state['at'] ?? ''),
            ]);
        }

        return [
            'items' => $items,
            'categories' => LaunchChecklistCatalog::categories(),
            'checks' => $checks,
            'progress' => [
                'total' => count($items),
                'done' => $done,
                'p0_total' => $p0Total,
                'p0_done' => $p0Done,
            ],
            'updated_at' => (string) ($this->readMeta()['updated_at'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{key:string, done:bool, note:string, by:string, at:string}
     */
    public function saveItem(array $payload): array
    {
        $key = trim((string) ($payload['key'] ?? ''));
        if ($key === '' || !$this->knownKey($key)) {
            throw new RuntimeException('알 수 없는 체크 항목입니다.');
        }

        $checks = $this->readChecks();
        $prev = $checks[$key] ?? ['done' => false, 'note' => '', 'by' => '', 'at' => ''];

        $done = array_key_exists('done', $payload)
            ? (bool) $payload['done']
            : (bool) ($prev['done'] ?? false);
        $note = array_key_exists('note', $payload)
            ? trim(mb_substr((string) $payload['note'], 0, 500))
            : (string) ($prev['note'] ?? '');
        $by = array_key_exists('by', $payload)
            ? trim(mb_substr((string) $payload['by'], 0, 40))
            : (string) ($prev['by'] ?? '');

        $at = $done || $note !== '' || $by !== ''
            ? date('c')
            : '';

        $checks[$key] = [
            'done' => $done,
            'note' => $note,
            'by' => $by,
            'at' => $at,
        ];

        $this->writeAll($checks);

        return array_merge(['key' => $key], $checks[$key]);
    }

    public function resetAll(): void
    {
        $this->writeAll([]);
    }

    private function knownKey(string $key): bool
    {
        foreach (LaunchChecklistCatalog::items() as $item) {
            if ($item['key'] === $key) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, array{done:bool, note:string, by:string, at:string}> */
    private function readChecks(): array
    {
        $data = $this->readFile();
        $checks = $data['checks'] ?? [];
        if (!is_array($checks)) {
            return [];
        }

        $out = [];
        foreach ($checks as $key => $row) {
            if (!is_string($key) || !is_array($row)) {
                continue;
            }
            $out[$key] = [
                'done' => !empty($row['done']),
                'note' => trim((string) ($row['note'] ?? '')),
                'by' => trim((string) ($row['by'] ?? '')),
                'at' => trim((string) ($row['at'] ?? '')),
            ];
        }

        return $out;
    }

    /** @return array{updated_at?:string} */
    private function readMeta(): array
    {
        $data = $this->readFile();

        return [
            'updated_at' => (string) ($data['updated_at'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function readFile(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $raw = @file_get_contents($this->file);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, array{done:bool, note:string, by:string, at:string}> $checks */
    private function writeAll(array $checks): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('체크리스트 저장 폴더를 만들 수 없습니다.');
        }

        $payload = [
            'updated_at' => date('c'),
            'checks' => $checks,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException('체크리스트 저장에 실패했습니다.');
        }

        $tmp = $this->file . '.tmp';
        $fp = @fopen($tmp, 'cb');
        if ($fp === false) {
            throw new RuntimeException('체크리스트 파일을 열 수 없습니다.');
        }
        try {
            if (!flock($fp, LOCK_EX)) {
                throw new RuntimeException('체크리스트 잠금에 실패했습니다.');
            }
            ftruncate($fp, 0);
            fwrite($fp, $json);
            fflush($fp);
            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }
        if (!@rename($tmp, $this->file)) {
            @unlink($tmp);
            throw new RuntimeException('체크리스트 저장에 실패했습니다.');
        }
    }
}
