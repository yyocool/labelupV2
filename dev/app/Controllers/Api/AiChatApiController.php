<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
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

        try {
            $messages = $this->normalizeMessages($rawMessages);
            $result = (new LabiDesignService())->handle($messages);
            $this->jsonSuccess([
                'reply' => $result['reply'],
                'role' => 'assistant',
                'intent' => $result['intent'],
                'product' => $result['product'],
                'clipart' => $result['clipart'],
            ]);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 502);
        }
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
