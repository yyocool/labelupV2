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
        $aiCredits = null;
        $creditInfo = null;

        try {
            $messages = $this->normalizeMessages($rawMessages);
            $aiCredits = new AiCreditService();
            if ($userId > 0 && $aiCredits->isEnabled()) {
                // 잔액이 이미 0 이하면 새 요청 차단. 양수면 비용 부족해도 시작 허용.
                $aiCredits->assertCanStart($userId);
            }
            $result = (new LabiDesignService())->handle($messages, $userId, $surface, $forceIntent);
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
                'templates' => $result['templates'] ?? null,
                'choices' => $result['choices'] ?? null,
                'usage' => $usageOut,
                'credit' => $creditInfo,
                'site_mode' => $siteMode->mode(),
                'show_ai_usage' => $siteMode->showAiUsage(),
                'show_ai_debug' => $siteMode->showAiDebug(),
            ]);
        } catch (\Throwable $caught) {
            $e = $caught instanceof RuntimeException
                ? $caught
                : new RuntimeException($caught->getMessage(), 0, $caught);
            $refund = $this->refundOnError($aiCredits, (int) ($userId ?? 0), $creditInfo);
            $status = $refund === null && str_contains($e->getMessage(), '잉크') ? 402 : 502;
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
            $public = $this->classifyPublicError($e);
            $message = $public['message'];
            if ($refund !== null) {
                $message .= sprintf(
                    "\n차감된 잉크 %s은(는) 오류로 인한 환불로 돌려드렸어요.",
                    \App\Services\CreditService::format($refund['refunded'])
                );
            }
            $this->jsonError($message, [
                'code' => $public['code'],
                'reason' => $public['reason'],
                'refund' => $refund,
            ], 502);
        }
    }

    /**
     * @param array<string, mixed>|null $creditInfo
     * @return array{refunded:int,balance:int}|null
     */
    private function refundOnError(?AiCreditService $aiCredits, int $userId, ?array $creditInfo): ?array
    {
        if ($aiCredits === null || $userId <= 0 || (int) ($creditInfo['charged'] ?? 0) <= 0) {
            return null;
        }
        try {
            $balance = $aiCredits->refundCharge($userId, $creditInfo);
        } catch (\Throwable) {
            return null;
        }
        if ($balance === null) {
            return null;
        }
        return ['refunded' => (int) $creditInfo['charged'], 'balance' => $balance];
    }

    /**
     * @return array{message:string, code:string, reason:string}
     */
    private function classifyPublicError(RuntimeException $e): array
    {
        $msg = trim($e->getMessage());
        if ($msg !== '' && (str_contains($msg, '잉크') || str_contains($msg, '로그인') || str_contains($msg, '메시지를 입력'))) {
            return [
                'message' => $msg,
                'code' => str_contains($msg, '잉크') ? 'credit' : 'client',
                'reason' => $msg,
            ];
        }

        $classified = $this->classifyLabiFailure($msg);
        $message = "지금은 라비를 잠시 사용할 수 없어요.\n"
            . '사유: ' . $classified['reason'] . "\n"
            . "잠시 후 다시 시도해 주세요. 계속되면 고객센터(02-6956-5511)로 문의해 주세요.\n"
            . '(CODE:' . $classified['code'] . ')';

        return [
            'message' => $message,
            'code' => $classified['code'],
            'reason' => $classified['reason'],
        ];
    }

    /**
     * @return array{code:string, reason:string}
     */
    private function classifyLabiFailure(string $msg): array
    {
        $t = strtolower($msg);

        if (
            str_contains($t, 'insufficient_quota')
            || str_contains($t, 'exceeded your current quota')
            || str_contains($t, 'billing_hard_limit')
            || str_contains($t, 'credit balance is too low')
            || str_contains($t, 'purchase credits')
            || str_contains($t, 'insufficient_funds')
            || str_contains($t, 'you exceeded your')
            || (str_contains($t, 'quota') && (str_contains($t, 'exceed') || str_contains($t, 'exhaust') || str_contains($t, 'deplet')))
        ) {
            return ['code' => '098', 'reason' => 'OpenAI API 잔액(사용 한도)이 부족합니다.'];
        }
        if (
            str_contains($t, 'invalid_api_key')
            || str_contains($t, 'incorrect api key')
            || str_contains($t, 'api 키가 설정되지')
            || str_contains($t, 'authentication')
            || str_contains($t, 'unauthorized')
            || str_contains($msg, 'OPENAI_API_KEY')
            || str_contains($msg, 'ANTHROPIC_API_KEY')
            || str_contains($msg, 'CLAUDE_API_KEY')
            || str_contains($msg, 'claude api')
        ) {
            return ['code' => '091', 'reason' => 'AI API 키 설정에 문제가 있습니다.'];
        }
        if (
            str_contains($t, 'rate_limit')
            || str_contains($t, 'rate limit')
            || str_contains($t, 'too many requests')
            || str_contains($t, '429')
        ) {
            return ['code' => '092', 'reason' => 'AI 요청이 잠시 너무 많아 제한되었습니다.'];
        }
        if (
            str_contains($t, '연결 실패')
            || str_contains($t, '연결에 실패')
            || str_contains($t, 'econn')
            || str_contains($t, 'timed out')
            || str_contains($t, 'timeout')
            || str_contains($t, 'ssl')
            || str_contains($t, 'curl')
            || str_contains($t, 'could not resolve')
        ) {
            return ['code' => '093', 'reason' => 'AI 서버와 연결하지 못했습니다.'];
        }
        if (
            str_contains($msg, 'AI 응답을 받지 못했습니다')
            || str_contains($msg, '응답을 해석할 수 없습니다')
            || str_contains($msg, '응답이 비어')
            || str_contains($t, 'empty response')
        ) {
            return ['code' => '094', 'reason' => 'AI 응답을 받지 못했거나 응답 형식이 올바르지 않습니다.'];
        }
        if (
            str_contains($t, 'model')
            && (str_contains($t, 'does not exist') || str_contains($t, 'not found') || str_contains($t, 'invalid'))
        ) {
            return ['code' => '097', 'reason' => '설정된 AI 모델을 사용할 수 없습니다.'];
        }
        if (
            str_contains($msg, '이미지 생성')
            || str_contains($msg, '생성된 이미지')
            || str_contains($msg, '이미지 디코딩')
            || str_contains($t, 'image')
        ) {
            return ['code' => '095', 'reason' => '이미지 생성에 실패했습니다.'];
        }
        if (
            str_contains($t, 'disk')
            || str_contains($t, 'quota')
            || str_contains($msg, '디스크 용량')
            || str_contains($msg, '할당량')
            || str_contains($t, 'no space')
            || str_contains($t, 'out of free')
        ) {
            return ['code' => '096', 'reason' => '서버 디스크 용량(할당량)이 부족합니다.'];
        }
        if (
            str_contains($msg, '저장')
            || str_contains($msg, '권한')
            || str_contains($t, 'permission')
        ) {
            return ['code' => '096', 'reason' => '서버 저장(파일 권한)에 문제가 있습니다.'];
        }
        if (
            str_contains($t, 'content_policy')
            || str_contains($t, 'safety')
            || str_contains($t, 'rejected')
            || str_contains($t, 'moderation')
        ) {
            return ['code' => '090', 'reason' => '요청 내용이 AI 이용 정책에 걸려 처리되지 않았습니다.'];
        }

        $detail = trim(preg_replace('/\s+/u', ' ', $msg) ?? '');
        if ($detail !== '') {
            // 키·토큰 노출 방지: 영문/숫자 위주 기술 문구만 짧게 붙인다.
            $detail = preg_replace('/sk-[A-Za-z0-9_-]+/', '[api-key]', $detail) ?? $detail;
            if (mb_strlen($detail) > 100) {
                $detail = mb_substr($detail, 0, 100) . '…';
            }
            return [
                'code' => '099',
                'reason' => '일시적인 AI 서비스 오류가 발생했습니다. 상세: ' . $detail,
            ];
        }

        return ['code' => '099', 'reason' => '일시적인 AI 서비스 오류가 발생했습니다.'];
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
