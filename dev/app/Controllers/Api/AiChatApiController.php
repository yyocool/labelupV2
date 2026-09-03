<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AiUsageService;
use App\Services\AuthService;
use App\Services\LabiDesignService;
use RuntimeException;

final class AiChatApiController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function chat(): never
    {
        (new AuthMiddleware($this->auth))->handle();

        $data = request_json();
        $rawMessages = $data['messages'] ?? [];

        if (!is_array($rawMessages) || $rawMessages === []) {
            $this->jsonError('메시지를 입력해 주세요.', null, 422);
        }

        $surface = $this->normalizeSurface((string) ($data['surface'] ?? ''));
        $forceIntent = trim((string) ($data['force_intent'] ?? $data['forceIntent'] ?? ''));
        $userId = $this->auth->id();

        try {
            $messages = $this->normalizeMessages($rawMessages);
            $result = (new LabiDesignService())->handle($messages, $userId, $surface, $forceIntent);
            $this->jsonSuccess([
                'reply' => $result['reply'],
                'role' => 'assistant',
                'intent' => $result['intent'],
                'product' => $result['product'],
                'clipart' => $result['clipart'],
                'template' => $result['template'] ?? null,
                'choices' => $result['choices'] ?? null,
                'usage' => $result['usage'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            (new AiUsageService())->log([
                'user_id' => $userId ?? 0,
                'surface' => $surface,
                'intent' => null,
                'status' => 'error',
                'error_message' => mb_substr($e->getMessage(), 0, 255),
                'has_image' => $this->rawHasImage($rawMessages),
            ]);
            $this->jsonError($e->getMessage(), null, 502);
        }
    }

    private function normalizeSurface(string $surface): string
    {
        $surface = strtolower(trim($surface));
        return in_array($surface, ['home', 'editor'], true) ? $surface : 'unknown';
    }

    /** @param mixed $raw */
    private function rawHasImage(mixed $raw): bool
    {
        if (!is_array($raw)) {
            return false;
        }
        foreach ($raw as $item) {
            if (!is_array($item) || !is_array($item['content'] ?? null)) {
                continue;
            }
            foreach ($item['content'] as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'image_url') {
                    return true;
                }
            }
        }
        return false;
    }

    /** @param array<int, mixed> $rawMessages
     *  @return array<int, array{role:string, content:mixed}>
     */
    private function normalizeMessages(array $rawMessages): array
    {
        $messages = [];
        $maxMessages = 30;

        foreach (array_slice($rawMessages, -$maxMessages) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = (string) ($item['role'] ?? '');
            if (!in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $content = $item['content'] ?? '';
            if (is_string($content)) {
                $text = trim($content);
                if ($text === '') {
                    continue;
                }
                if (mb_strlen($text) > 12000) {
                    throw new RuntimeException('메시지가 너무 깁니다.');
                }
                $messages[] = ['role' => $role, 'content' => $text];
                continue;
            }

            if (!is_array($content)) {
                continue;
            }

            $parts = [];
            foreach ($content as $part) {
                if (!is_array($part)) {
                    continue;
                }
                $type = (string) ($part['type'] ?? '');
                if ($type === 'text') {
                    $text = trim((string) ($part['text'] ?? ''));
                    if ($text === '') {
                        continue;
                    }
                    if (mb_strlen($text) > 12000) {
                        throw new RuntimeException('메시지가 너무 깁니다.');
                    }
                    $parts[] = ['type' => 'text', 'text' => $text];
                    continue;
                }

                if ($type === 'image_url') {
                    $url = trim((string) ($part['image_url']['url'] ?? ''));
                    if ($url === '' || !str_starts_with($url, 'data:image/')) {
                        continue;
                    }
                    if (strlen($url) > 6_000_000) {
                        throw new RuntimeException('첨부 이미지 용량이 너무 큽니다.');
                    }
                    $parts[] = [
                        'type' => 'image_url',
                        'image_url' => ['url' => $url],
                    ];
                    continue;
                }

                if ($type === 'file') {
                    $name = trim((string) ($part['name'] ?? $part['file']['name'] ?? ''));
                    $url = trim((string) ($part['file']['url'] ?? $part['data_url'] ?? $part['url'] ?? ''));
                    if ($name === '' || $url === '' || !str_starts_with($url, 'data:')) {
                        continue;
                    }
                    if (!preg_match('/\.(xlsx|xls|csv|tsv|docx|doc)$/i', $name)) {
                        continue;
                    }
                    if (strlen($url) > 5_000_000) {
                        throw new RuntimeException('첨부 파일 용량이 너무 큽니다. 3MB 이하로 올려 주세요.');
                    }
                    $parts[] = [
                        'type' => 'file',
                        'name' => mb_substr($name, 0, 180),
                        'file' => ['url' => $url, 'name' => mb_substr($name, 0, 180)],
                    ];
                }
            }

            if ($parts === []) {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => count($parts) === 1 && ($parts[0]['type'] ?? '') === 'text'
                    ? (string) $parts[0]['text']
                    : $parts,
            ];
        }

        if ($messages === []) {
            throw new RuntimeException('유효한 메시지가 없습니다.');
        }

        $last = $messages[array_key_last($messages)];
        if (($last['role'] ?? '') !== 'user') {
            throw new RuntimeException('마지막 메시지는 사용자 입력이어야 합니다.');
        }

        return $messages;
    }
}
