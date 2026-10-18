<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Anthropic Messages API. OpenAIService와 같은 공개 메서드를 써서 라비가 동일하게 생성한다.
 * 클립아트는 Claude가 SVG를 그리고 PNG로 바꾼다. 변환이 안 되면 OpenAI 이미지 키가 있을 때만 보조한다.
 */
class ClaudeService extends OpenAIService
{
    private const MESSAGES_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    protected function apiKey(): string
    {
        $apiKey = AiProviderSettings::claudeApiKey();
        if ($apiKey === '') {
            throw new RuntimeException('Claude API 키가 설정되지 않았습니다. 서버 .env에 ANTHROPIC_API_KEY 또는 CLAUDE_API_KEY를 추가해 주세요.');
        }

        return $apiKey;
    }

    /** @param array<string, mixed> $overrides */
    protected function chatRequest(array $messages, array $overrides = []): array
    {
        $apiKey = $this->apiKey();
        $model = $this->chatModelOverride
            ?? trim((string) env('CLAUDE_MODEL', 'claude-sonnet-4-5'));
        if ($model === '') {
            $model = 'claude-sonnet-4-5';
        }

        $converted = $this->toClaudeMessages($messages);
        $maxTokens = (int) ($overrides['max_tokens'] ?? env('CLAUDE_MAX_TOKENS', env('OPENAI_MAX_TOKENS', 1800)));
        $temperature = isset($overrides['temperature']) ? (float) $overrides['temperature'] : 0.7;

        $system = $converted['system'];
        if (isset($overrides['response_format'])) {
            $system = trim($system . "\n\nRespond with a single JSON object only. Do not wrap it in markdown fences.");
        }

        $payload = [
            'model' => $model,
            'max_tokens' => max(256, $maxTokens),
            'temperature' => $temperature,
            'messages' => $converted['messages'],
        ];
        if ($system !== '') {
            $payload['system'] = $system;
        }

        $decoded = $this->claudeRequest($apiKey, $payload, 120);
        $text = $this->stripCodeFence($this->claudeText($decoded));
        if (isset($overrides['response_format'])) {
            $text = $this->extractJsonObject($text);
        }

        $usage = is_array($decoded['usage'] ?? null) ? $decoded['usage'] : [];
        $promptTokens = (int) ($usage['input_tokens'] ?? 0);
        $completionTokens = (int) ($usage['output_tokens'] ?? 0);
        $usedModel = trim((string) ($decoded['model'] ?? $model)) ?: $model;
        $this->recordChatUsage($usedModel, $promptTokens, $completionTokens, $promptTokens + $completionTokens);

        return [
            'model' => $usedModel,
            'choices' => [
                [
                    'message' => [
                        'content' => $text,
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
            ],
        ];
    }

    public function generateClipart(string $prompt, bool $knockoutBackdrop = true): array
    {
        $cleanPrompt = $this->withTransparentBackgroundPrompt($prompt, $knockoutBackdrop);
        if ($cleanPrompt === '') {
            throw new RuntimeException('이미지 생성 프롬프트가 비어 있습니다.');
        }

        $svgPrompt = 'Create a print-ready sticker illustration as one complete SVG document.'
            . "\nSubject: {$cleanPrompt}"
            . "\nRules:"
            . "\n- root <svg viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\">"
            . "\n- fully transparent background, no checkerboard, no studio floor"
            . "\n- no raster images, no foreignObject, no external URLs, no scripts"
            . "\n- no letters or numbers unless the prompt explicitly asks for them"
            . "\n- Output ONLY the SVG markup.";

        $response = $this->chatRequest([
            ['role' => 'user', 'content' => $svgPrompt],
        ], [
            'temperature' => 0.55,
            'max_tokens' => (int) env('CLAUDE_IMAGE_MAX_TOKENS', 8000),
        ]);

        $svg = $this->extractSvg((string) ($response['choices'][0]['message']['content'] ?? ''));
        if ($svg === '') {
            return $this->fallbackOpenAiClipart($prompt, $knockoutBackdrop);
        }

        $png = $this->rasterizeSvg($svg);
        if ($png !== '') {
            $url = $this->storeClipartFromBase64(base64_encode($png), $knockoutBackdrop);
            $this->recordImageUsage((string) env('CLAUDE_MODEL', 'claude-sonnet-4-5'), 'svg', 1);
            return [
                'url' => $url,
                'prompt' => $cleanPrompt,
                'title' => '라비가 그린 클립아트',
            ];
        }

        $fallback = $this->tryOpenAiClipart($prompt, $knockoutBackdrop);
        if ($fallback !== null) {
            return $fallback;
        }

        $url = $this->storeSvgClipart($svg);
        $this->recordImageUsage((string) env('CLAUDE_MODEL', 'claude-sonnet-4-5'), 'svg', 1);

        return [
            'url' => $url,
            'prompt' => $cleanPrompt,
            'title' => '라비가 그린 클립아트',
        ];
    }

    /**
     * @param array<int, array{role:string, content:mixed}> $messages
     * @return array{system:string, messages:list<array{role:string, content:mixed}>}
     */
    private function toClaudeMessages(array $messages): array
    {
        $systemParts = [];
        $out = [];

        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }
            $role = (string) ($message['role'] ?? '');
            $content = $message['content'] ?? '';
            if ($role === 'system') {
                $text = $this->plainText($content);
                if ($text !== '') {
                    $systemParts[] = $text;
                }
                continue;
            }
            if ($role !== 'user' && $role !== 'assistant') {
                $role = 'user';
            }
            $parts = $this->toClaudeContent($content);
            if ($parts === []) {
                continue;
            }
            if ($out !== [] && ($out[array_key_last($out)]['role'] ?? '') === $role) {
                $prev = $out[array_key_last($out)]['content'];
                $out[array_key_last($out)]['content'] = array_merge(
                    is_array($prev) ? $prev : [['type' => 'text', 'text' => (string) $prev]],
                    $parts
                );
                continue;
            }
            $out[] = [
                'role' => $role,
                'content' => $parts,
            ];
        }

        if ($out === []) {
            $out[] = [
                'role' => 'user',
                'content' => [['type' => 'text', 'text' => '계속해 주세요.']],
            ];
        }
        if (($out[0]['role'] ?? '') === 'assistant') {
            array_unshift($out, [
                'role' => 'user',
                'content' => [['type' => 'text', 'text' => '아래 내용을 이어서 처리해 주세요.']],
            ]);
        }

        return [
            'system' => trim(implode("\n\n", $systemParts)),
            'messages' => $out,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function toClaudeContent(mixed $content): array
    {
        if (is_string($content)) {
            $text = trim($content);
            return $text === '' ? [] : [['type' => 'text', 'text' => $text]];
        }
        if (!is_array($content)) {
            return [];
        }

        $parts = [];
        foreach ($content as $part) {
            if (is_string($part)) {
                $text = trim($part);
                if ($text !== '') {
                    $parts[] = ['type' => 'text', 'text' => $text];
                }
                continue;
            }
            if (!is_array($part)) {
                continue;
            }
            $type = (string) ($part['type'] ?? '');
            if ($type === 'text') {
                $text = trim((string) ($part['text'] ?? ''));
                if ($text !== '') {
                    $parts[] = ['type' => 'text', 'text' => $text];
                }
                continue;
            }
            if ($type === 'image_url') {
                $image = $this->toClaudeImage($part);
                if ($image !== null) {
                    $parts[] = $image;
                }
            }
        }

        return $parts;
    }

    /** @param array<string, mixed> $part */
    private function toClaudeImage(array $part): ?array
    {
        $url = '';
        $imageUrl = $part['image_url'] ?? null;
        if (is_array($imageUrl)) {
            $url = trim((string) ($imageUrl['url'] ?? ''));
        } elseif (is_string($imageUrl)) {
            $url = trim($imageUrl);
        }
        if ($url === '') {
            return null;
        }

        if (preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#s', $url, $m) === 1) {
            $media = strtolower($m[1]);
            if ($media === 'image/jpg') {
                $media = 'image/jpeg';
            }
            $data = preg_replace('/\s+/', '', $m[2]) ?? $m[2];
            if ($data === '') {
                return null;
            }
            return [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $media,
                    'data' => $data,
                ],
            ];
        }

        $fetched = $this->fetchRemoteImage($url);
        if ($fetched === null) {
            return [
                'type' => 'text',
                'text' => '[첨부 이미지를 읽지 못했습니다]',
            ];
        }

        return [
            'type' => 'image',
            'source' => [
                'type' => 'base64',
                'media_type' => $fetched['media'],
                'data' => $fetched['data'],
            ],
        ];
    }

    /** @return array{media:string, data:string}|null */
    private function fetchRemoteImage(string $url): ?array
    {
        $bin = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $bin = curl_exec($ch);
            curl_close($ch);
        } else {
            $bin = @file_get_contents($url);
        }
        if (!is_string($bin) || $bin === '') {
            return null;
        }

        $media = 'image/png';
        if (str_starts_with($bin, "\xFF\xD8\xFF")) {
            $media = 'image/jpeg';
        } elseif (str_starts_with($bin, 'GIF8')) {
            $media = 'image/gif';
        } elseif (str_starts_with($bin, 'RIFF') && str_contains(substr($bin, 0, 16), 'WEBP')) {
            $media = 'image/webp';
        }

        return [
            'media' => $media,
            'data' => base64_encode($bin),
        ];
    }

    private function plainText(mixed $content): string
    {
        if (is_string($content)) {
            return trim($content);
        }
        if (!is_array($content)) {
            return '';
        }
        $parts = [];
        foreach ($content as $part) {
            if (is_string($part)) {
                $parts[] = $part;
                continue;
            }
            if (is_array($part) && ($part['type'] ?? '') === 'text') {
                $parts[] = (string) ($part['text'] ?? '');
            }
        }

        return trim(implode("\n", $parts));
    }

    /** @param array<string, mixed> $payload */
    private function claudeRequest(string $apiKey, array $payload, int $timeout = 120): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('요청 데이터를 만들 수 없습니다.');
        }

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: ' . self::API_VERSION,
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init(self::MESSAGES_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($raw === false) {
                throw new RuntimeException('Claude API 연결 실패: ' . ($error ?: 'unknown'));
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers) . "\r\n",
                    'content' => $body,
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = file_get_contents(self::MESSAGES_URL, false, $context);
            $status = 0;
            if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }
            if ($raw === false) {
                throw new RuntimeException('Claude API 연결에 실패했습니다.');
            }
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Claude API 응답을 해석할 수 없습니다.');
        }

        if ($status >= 400 || isset($decoded['error'])) {
            $err = is_array($decoded['error'] ?? null) ? $decoded['error'] : [];
            $message = (string) ($err['message'] ?? 'Claude API 오류');
            $errType = trim((string) ($err['type'] ?? $err['code'] ?? ''));
            if ($errType !== '') {
                $message .= ' [' . $errType . ']';
            }
            throw new RuntimeException($message);
        }

        return $decoded;
    }

    /** @param array<string, mixed> $decoded */
    private function claudeText(array $decoded): string
    {
        $content = $decoded['content'] ?? null;
        if (is_string($content)) {
            return trim($content);
        }
        if (!is_array($content)) {
            return '';
        }
        $parts = [];
        foreach ($content as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (($block['type'] ?? '') === 'text') {
                $parts[] = (string) ($block['text'] ?? '');
            }
        }

        return trim(implode("\n", $parts));
    }

    private function stripCodeFence(string $text): string
    {
        $text = trim($text);
        if (preg_match('/^```(?:json|svg|xml|html)?\s*([\s\S]*?)\s*```$/i', $text, $m) === 1) {
            return trim($m[1]);
        }
        return $text;
    }

    private function extractJsonObject(string $text): string
    {
        $text = trim($text);
        if ($text !== '' && ($text[0] === '{' || $text[0] === '[')) {
            return $text;
        }
        if (preg_match('/\{[\s\S]*\}/', $text, $m) === 1) {
            return trim($m[0]);
        }
        return $text;
    }

    private function extractSvg(string $text): string
    {
        $text = $this->stripCodeFence($text);
        if (preg_match('/<svg\b[\s\S]*<\/svg>/i', $text, $m) === 1) {
            return trim($m[0]);
        }
        return '';
    }

    private function rasterizeSvg(string $svg): string
    {
        if (class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick();
                $im->setBackgroundColor(new \ImagickPixel('transparent'));
                $im->readImageBlob($svg);
                $im->setImageFormat('png');
                $bin = (string) $im->getImageBlob();
                $im->clear();
                $im->destroy();
                if ($bin !== '') {
                    return $bin;
                }
            } catch (\Throwable) {
                // CLI 변환으로 이어간다.
            }
        }

        $tmpSvg = tempnam(sys_get_temp_dir(), 'lu_svg_');
        if ($tmpSvg === false) {
            return '';
        }
        $svgPath = $tmpSvg . '.svg';
        $pngPath = $tmpSvg . '.png';
        @unlink($tmpSvg);
        if (@file_put_contents($svgPath, $svg) === false) {
            return '';
        }

        $commands = [
            'rsvg-convert -w 1024 -h 1024 -f png -o ' . escapeshellarg($pngPath) . ' ' . escapeshellarg($svgPath),
            'magick ' . escapeshellarg($svgPath) . ' -background none -resize 1024x1024 ' . escapeshellarg($pngPath),
            'convert -background none -resize 1024x1024 ' . escapeshellarg($svgPath) . ' ' . escapeshellarg($pngPath),
        ];
        foreach ($commands as $cmd) {
            if (!function_exists('exec')) {
                break;
            }
            $out = [];
            $code = 1;
            @exec($cmd . ' 2>&1', $out, $code);
            if ($code === 0 && is_file($pngPath)) {
                $bin = (string) @file_get_contents($pngPath);
                @unlink($svgPath);
                @unlink($pngPath);
                return $bin;
            }
        }
        @unlink($svgPath);
        @unlink($pngPath);

        return '';
    }

    private function storeSvgClipart(string $svg): string
    {
        $dir = public_path('assets/ai-clipart');
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $dir = dirname(__DIR__, 2) . '/storage/ai-clipart';
            if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('클립아트 저장 경로를 만들 수 없습니다.');
            }
        }
        @chmod($dir, 0777);
        $name = 'clip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.svg';
        $full = $dir . DIRECTORY_SEPARATOR . $name;
        if (@file_put_contents($full, $svg) === false) {
            throw new RuntimeException('클립아트 파일 저장에 실패했습니다.');
        }
        @chmod($full, 0666);

        return url('assets/ai-clipart/' . $name);
    }

    /** @return array{url:string, prompt:string, title:string}|null */
    private function tryOpenAiClipart(string $prompt, bool $knockoutBackdrop): ?array
    {
        if (AiProviderSettings::openaiApiKey() === '') {
            return null;
        }
        try {
            return (new OpenAIService())->generateClipart($prompt, $knockoutBackdrop);
        } catch (RuntimeException) {
            return null;
        }
    }

    /** @return array{url:string, prompt:string, title:string} */
    private function fallbackOpenAiClipart(string $prompt, bool $knockoutBackdrop): array
    {
        $fallback = $this->tryOpenAiClipart($prompt, $knockoutBackdrop);
        if ($fallback !== null) {
            return $fallback;
        }
        throw new RuntimeException('클로드가 클립아트를 만들지 못했습니다.');
    }
}
