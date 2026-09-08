<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/** 아이라벨 디자인 배경처럼 CORS로 직접 못 받는 원격 이미지를 같은 출처로 중계한다. */
final class EditorRemoteImageApiController extends BaseController
{
    private const int MaxBytes = 8 * 1024 * 1024;

    public function show(): never
    {
        $url = trim((string) ($_GET['url'] ?? ''));
        if (!$this->isAllowed($url)) {
            $this->jsonError('허용되지 않은 이미지 URL입니다.', null, 400);
        }

        $bin = $this->download($url);
        if ($bin === null) {
            $this->jsonError('이미지를 가져오지 못했습니다.', null, 502);
        }

        $mime = $this->detectMime($bin);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) strlen($bin));
        header('Cache-Control: public, max-age=86400');
        echo $bin;
        exit;
    }

    private function isAllowed(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        return $host === 'img.label.kr';
    }

    private function download(string $url): ?string
    {
        $bin = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            ]);
            $bin = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!is_string($bin) || $bin === '' || $code < 200 || $code >= 300) {
                return null;
            }
        } else {
            $ctx = stream_context_create([
                'http' => ['timeout' => 20, 'follow_location' => 1],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $bin = @file_get_contents($url, false, $ctx);
        }

        if (!is_string($bin) || $bin === '' || strlen($bin) > self::MaxBytes) {
            return null;
        }
        return $bin;
    }

    private function detectMime(string $bin): string
    {
        if (str_starts_with($bin, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($bin, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($bin, 'GIF8')) {
            return 'image/gif';
        }
        if (str_starts_with($bin, 'RIFF') && substr($bin, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
        return 'image/jpeg';
    }
}
