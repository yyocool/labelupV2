<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AiCreditService;
use App\Services\AiUsageService;
use App\Services\AuthService;
use App\Services\LabiDesignService;
use App\Services\SiteModeService;
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
            $aiCredits = new AiCreditService();
            if ($userId > 0 && $aiCredits->isEnabled()) {
                // 잔액이 이미 0 이하면 새 요청 차단. 양수면 비용 부족해도 시작 허용.
                $aiCredits->assertCanStart($userId);
            }
            $result = (new LabiDesignService())->handle($messages, $userId, $surface, $forceIntent);
            $creditInfo = null;
            if ($userId > 0 && $aiCredits->isEnabled()) {
                $intent = (string) ($result['intent'] ?? 'chat');
                // 작업은 이미 끝났으므로 잔액이 부족해도 차감(마이너스 허용) 후 결과 제공
                $creditInfo = $aiCredits->charge($userId, $intent, $surface);
                if (is_array($creditInfo) && !empty($creditInfo['was_overdraft'])) {
                    $debt = abs((int) ($creditInfo['balance'] ?? 0));
                    $note = sprintf(
                        "\n\n이번 사용으로 잉크가 마이너스(%s)가 되었어요. 다음에 충전하면 부족한 %s이(가) 먼저 차감됩니다.",
                        \App\Services\CreditService::format((int) ($creditInfo['balance'] ?? 0)),
                        \App\Services\CreditService::format($debt)
                    );
                    $result['reply'] = rtrim((string) ($result['reply'] ?? '')) . $note;
                }
            }
            $usageOut = null;
            $siteMode = new SiteModeService();
            if ($siteMode->showAiUsage()) {
                $usageOut = $result['usage'] ?? null;
                if (is_array($usageOut) && $siteMode->showAiDebug()) {
                    $usageOut['debug'] = true;
                } elseif (is_array($usageOut)) {
                    unset($usageOut['steps'], $usageOut['usd'], $usageOut['usd_krw'], $usageOut['currency_note']);
                }
            }
            $this->jsonSuccess([
                'reply' => $result['reply'],
                'role' => 'assistant',
                'intent' => $result['intent'],
                'product' => $result['product'],
                'clipart' => $result['clipart'],
                'template' => $result['template'] ?? null,
                'choices' => $result['choices'] ?? null,
                'usage' => $usageOut,
                'credit' => $creditInfo,
                'site_mode' => $siteMode->mode(),
                'show_ai_usage' => $siteMode->showAiUsage(),
                'show_ai_debug' => $siteMode->showAiDebug(),
            ]);
        } catch (RuntimeException $e) {
            $status = str_contains($e->getMessage(), '잉크') ? 402 : 502;
            if ($status === 402) {
                $this->jsonError($e->getMessage(), ['code' => 'insufficient_credit'], $status);
            }
            (new AiUsageService())->log([
                'user_id' => $userId ?? 0,
                'surface' => $surface,
                'intent' => null,
                'status' => 'error',
                'error_message' => mb_substr($e->getMessage(), 0, 255),
                'has_image' => $this->rawHasImage($rawMessages),
            ]);
            $this->jsonError($this->publicErrorMessage($e), null, 502);
        }
    }

    private function publicErrorMessage(RuntimeException $e): string
    {
        $msg = trim($e->getMessage());
        if ($msg !== '' && (str_contains($msg, '잉크') || str_contains($msg, '로그인') || str_contains($msg, '메시지를 입력'))) {
            return $msg;
        }

        return '지금은 라비를 잠시 사용할 수 없어요. 잠시 후 다시 시도해 주세요. 계속되면 고객센터(02-6956-5511)로 문의해 주세요.';
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
