<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/** 아이라벨 IDF가 InnerDB 없이 외부 엑셀만 가리킬 때, 허용 폴더에서 파일을 찾아 준다. */
final class EditorIlabelExcelApiController extends BaseController
{
    private const int MaxBytes = 8 * 1024 * 1024;

    public function show(): never
    {
        $name = (string) ($_GET['name'] ?? '');
        $base = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name));
        $base = trim($base, " \t\n\r\0\x0B");
        if ($base === '' || $base === '.' || $base === '..' || str_contains($base, "\0")) {
            $this->jsonError('엑셀 파일 이름이 올바르지 않습니다.', null, 400);
        }

        if (!$this->hasExcelExt($base)) {
            $this->jsonError('xlsx/xls만 허용됩니다.', ['name' => $base], 400);
        }

        $found = $this->findFile($base);
        if ($found === null) {
            $this->jsonError('엑셀 파일을 찾지 못했습니다.', ['name' => $base], 404);
        }

        $bin = @file_get_contents($found);
        if (!is_string($bin) || $bin === '' || strlen($bin) > self::MaxBytes) {
            $this->jsonError('엑셀 파일을 읽지 못했습니다.', null, 502);
        }

        $mime = str_ends_with(strtolower($found), '.xls')
            ? 'application/vnd.ms-excel'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) strlen($bin));
        header('Cache-Control: private, max-age=60');
        echo $bin;
        exit;
    }

    private function findFile(string $base): ?string
    {
        $tokenHit = null;
        foreach ($this->searchDirs() as $dir) {
            if ($dir === '' || !is_dir($dir)) {
                continue;
            }

            $list = @scandir($dir);
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (!$this->hasExcelExt($entry)) {
                    continue;
                }
                $path = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $entry;
                if (!is_file($path) || !is_readable($path)) {
                    continue;
                }
                if ($this->namesMatch($base, $entry)) {
                    return $path;
                }
                if ($tokenHit === null && $this->tokensMatch($base, $entry)) {
                    $tokenHit = $path;
                }
            }
        }
        return $tokenHit;
    }

    /** @return list<string> */
    private function searchDirs(): array
    {
        return [
            'C:\\LabelupData',
            (string) (getenv('ILABEL_DATA_DIR') ?: ''),
            'C:\\win7share\\ilabelData',
            'Z:\\ilabelData',
        ];
    }

    private function namesMatch(string $want, string $entry): bool
    {
        $wantNorm = $this->norm($want);
        $wantStem = $this->stem($wantNorm);
        foreach ($this->nameVariants($entry) as $cand) {
            $got = $this->norm($cand);
            if ($got === $wantNorm || $this->stem($got) === $wantStem) {
                return true;
            }
        }
        return false;
    }

    private function tokensMatch(string $want, string $entry): bool
    {
        $tokens = $this->tokens($want);
        if (count($tokens) < 2) {
            return false;
        }
        foreach ($this->nameVariants($entry) as $cand) {
            $hay = $this->norm($cand);
            $ok = true;
            foreach ($tokens as $token) {
                if (!str_contains($hay, $this->norm($token))) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return true;
            }
        }
        return false;
    }

    /** @return list<string> */
    private function tokens(string $name): array
    {
        $base = preg_replace('/\.xlsx?$/i', '', $name) ?? $name;
        $parts = preg_split('/[\s_\-]+/u', $base) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '' && $this->strLen($part) >= 2) {
                $out[] = $part;
            }
        }
        return $out;
    }

    /** @return list<string> */
    private function nameVariants(string $entry): array
    {
        $out = [$entry];
        foreach (['UTF-8', 'CP949', 'UHC'] as $from) {
            if (function_exists('iconv')) {
                $conv = @iconv($from, 'UTF-8//IGNORE', $entry);
                if (is_string($conv) && $conv !== '') {
                    $out[] = $conv;
                }
            }
            if (function_exists('mb_convert_encoding')) {
                $conv = @mb_convert_encoding($entry, 'UTF-8', $from);
                if (is_string($conv) && $conv !== '') {
                    $out[] = $conv;
                }
            }
        }
        return array_values(array_unique($out));
    }

    private function hasExcelExt(string $name): bool
    {
        return (bool) preg_match('/\.xlsx?$/i', $name);
    }

    private function stem(string $normName): string
    {
        return preg_replace('/\.xlsx?$/i', '', $normName) ?? $normName;
    }

    private function norm(string $s): string
    {
        $s = str_replace(["\u{00A0}", "\xC2\xA0"], ' ', $s);
        $s = preg_replace('/\s+/u', '', $s) ?? (preg_replace('/\s+/', '', $s) ?? $s);
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($s, 'UTF-8');
        }
        return strtolower($s);
    }

    private function strLen(string $s): int
    {
        return function_exists('mb_strlen') ? (int) mb_strlen($s, 'UTF-8') : strlen($s);
    }
}
