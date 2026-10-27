<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use RuntimeException;

/**
 * 네이버 클라우드 SENS SMS v2.
 * 서명 문자열은 "POST {path}\n{timestamp}\n{accessKey}" 이다.
 */
final class NaverSmsClient
{
    private const HOST = 'https://sens.apigw.ntruss.com';

    /** @param array{service_id: string, access_key: string, secret_key: string, sender: string} $config */
    public function sendAuthCode(array $config, string $to, string $code): void
    {
        $content = '[라벨업] 인증번호 ' . $code;
        $type = $this->eucKrBytes($content) > 90 ? 'LMS' : 'SMS';
        $path = '/sms/v2/services/' . $config['service_id'] . '/messages';
        $body = [
            'type' => $type,
            'contentType' => 'COMM',
            'countryCode' => '82',
            'from' => $config['sender'],
            'content' => $content,
            'messages' => [
                ['to' => $to],
            ],
        ];
        if ($type === 'LMS') {
            $body['subject'] = '라벨업 인증번호';
        }

        $timestamp = (string) (int) round(microtime(true) * 1000);
        $signature = $this->sign('POST', $path, $timestamp, $config['access_key'], $config['secret_key']);
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new RuntimeException('인증번호 문자를 만들지 못했습니다.');
        }

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'x-ncp-apigw-timestamp: ' . $timestamp,
            'x-ncp-iam-access-key: ' . $config['access_key'],
            'x-ncp-apigw-signature-v2: ' . $signature,
        ];
        [$status, $raw] = $this->post(self::HOST . $path, $headers, $payload);
        $decoded = json_decode($raw, true);
        $statusCode = is_array($decoded) ? (string) ($decoded['statusCode'] ?? '') : '';
        if ($status >= 200 && $status < 300 && ($statusCode === '' || $statusCode === '200' || $statusCode === '202')) {
            return;
        }

        Logger::error('Naver SMS send failed', [
            'http' => $status,
            'statusCode' => $statusCode,
            'message' => $this->safeError($decoded, $raw),
        ]);
        if ($status === 401 || $status === 403) {
            throw new RuntimeException('네이버 문자 API 인증에 실패했습니다. 환경설정의 Access Key와 Secret Key를 확인해 주세요.');
        }
        throw new RuntimeException('인증번호를 보내지 못했습니다. 서비스 ID와 발신번호를 확인해 주세요.');
    }

    private function sign(string $method, string $path, string $timestamp, string $accessKey, string $secretKey): string
    {
        $message = $method . ' ' . $path . "\n" . $timestamp . "\n" . $accessKey;
        return base64_encode(hash_hmac('sha256', $message, $secretKey, true));
    }

    private function eucKrBytes(string $text): int
    {
        $converted = iconv('UTF-8', 'EUC-KR//IGNORE', $text);
        if ($converted === false) {
            return strlen($text);
        }
        return strlen($converted);
    }

    /** @param array<int, string> $headers */
    private function post(string $url, array $headers, string $body): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $body,
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                Logger::error('Naver SMS connection failed', ['error' => $error]);
                throw new RuntimeException('문자 발송 서버에 연결하지 못했습니다. 잠시 후 다시 시도해 주세요.');
            }
            return [$status, (string) $raw];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers) . "\r\n",
                'content' => $body,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', (string) $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }
        if ($raw === false) {
            throw new RuntimeException('문자 발송 서버에 연결하지 못했습니다. 잠시 후 다시 시도해 주세요.');
        }
        return [$status, (string) $raw];
    }

    private function safeError(mixed $decoded, string $raw): string
    {
        if (is_array($decoded)) {
            $message = (string) ($decoded['errorMessage'] ?? $decoded['message'] ?? $decoded['statusName'] ?? '');
            if ($message !== '') {
                return mb_substr($message, 0, 180);
            }
        }
        return mb_substr(trim($raw), 0, 180);
    }
}
