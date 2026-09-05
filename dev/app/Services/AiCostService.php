<?php

declare(strict_types=1);

namespace App\Services;

/**
 * OpenAI 사용량 → USD/KRW 추정 + 난이도별 모델 선택.
 * 단가·환율은 .env로 조정 (기본값은 공개 가격표 근사치).
 */
final class AiCostService
{
    /** @return array{easy:string,normal:string,hard:string} */
    public static function chatModels(): array
    {
        $default = trim((string) env('OPENAI_MODEL', 'gpt-4o-mini')) ?: 'gpt-4o-mini';
        $easy = trim((string) env('OPENAI_MODEL_EASY', $default)) ?: $default;
        $hard = trim((string) env('OPENAI_MODEL_HARD', $default)) ?: $default;

        return [
            'easy' => $easy,
            'normal' => $default,
            'hard' => $hard,
        ];
    }

    public static function routingEnabled(): bool
    {
        $raw = strtolower(trim((string) env('OPENAI_ROUTE_BY_DIFFICULTY', '1')));
        return !in_array($raw, ['0', 'false', 'off', 'no'], true);
    }

    public static function modelForDifficulty(string $level): string
    {
        $models = self::chatModels();
        return $models[$level] ?? $models['normal'];
    }

    /**
     * @param array<int, array{role:string, content:mixed}> $messages
     */
    public static function assessDifficulty(array $messages, string $forceIntent = '', bool $hasImage = false, bool $hasOffice = false): string
    {
        if (!self::routingEnabled()) {
            return 'normal';
        }

        if ($hasOffice || $forceIntent === 'generate_data_template') {
            return 'hard';
        }
        if ($hasImage || in_array($forceIntent, ['generate_clipart', 'generate_template'], true)) {
            return 'hard';
        }

        $text = self::lastUserText($messages);
        $len = mb_strlen($text);
        $msgCount = count($messages);

        if ($forceIntent === 'ask_image_mode' || ($len > 0 && $len <= 40 && $msgCount <= 2 && !$hasImage)) {
            return 'easy';
        }
        if ($len >= 220 || $msgCount >= 8) {
            return 'hard';
        }
        if (preg_match('/상세|복잡|여러\s*칸|데이터|엑셀|표\s*형식|레이아웃|고급|전문/', $text)) {
            return 'hard';
        }
        if ($len <= 80 && $msgCount <= 4) {
            return 'easy';
        }

        return 'normal';
    }

    /**
     * @param array{
     *   model:?string,
     *   prompt_tokens:?int,
     *   completion_tokens:?int,
     *   total_tokens:?int,
     *   image_count?:int,
     *   image_model?:?string,
     *   image_quality?:?string,
     *   steps?:array<int, array<string, mixed>>,
     *   models?:array<int, string>
     * }|null $usage
     * @return array{
     *   model:?string,
     *   models:array<int, string>,
     *   agent:string,
     *   agent_label:string,
     *   difficulty:?string,
     *   difficulty_label:?string,
     *   prompt_tokens:int,
     *   completion_tokens:int,
     *   total_tokens:int,
     *   image_count:int,
     *   usd:float,
     *   krw:int,
     *   usd_krw:float,
     *   currency_note:string,
     *   steps:array<int, array<string, mixed>>
     * }|null
     */
    public static function present(?array $usage, string $intent = 'chat', ?string $difficulty = null): ?array
    {
        if ($usage === null) {
            return null;
        }

        $prompt = (int) ($usage['prompt_tokens'] ?? 0);
        $completion = (int) ($usage['completion_tokens'] ?? 0);
        $total = (int) ($usage['total_tokens'] ?? ($prompt + $completion));
        $imageCount = (int) ($usage['image_count'] ?? 0);
        $models = [];
        if (!empty($usage['models']) && is_array($usage['models'])) {
            foreach ($usage['models'] as $m) {
                $m = trim((string) $m);
                if ($m !== '' && !in_array($m, $models, true)) {
                    $models[] = $m;
                }
            }
        }
        $primary = trim((string) ($usage['model'] ?? ''));
        if ($primary !== '' && !in_array($primary, $models, true)) {
            array_unshift($models, $primary);
        }
        if ($primary === '' && $models !== []) {
            $primary = $models[0];
        }

        $usd = self::estimateUsd($usage);
        $rate = self::usdKrwRate();
        $krw = (int) max(0, (int) round($usd * $rate));

        $agentKey = self::agentKey($intent, $imageCount > 0);
        $diffLabel = match ($difficulty) {
            'easy' => '쉬움',
            'hard' => '어려움',
            'normal' => '보통',
            default => null,
        };

        return [
            'model' => $primary !== '' ? $primary : null,
            'models' => $models,
            'agent' => $agentKey,
            'agent_label' => self::agentLabel($agentKey),
            'difficulty' => $difficulty,
            'difficulty_label' => $diffLabel,
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $total,
            'image_count' => $imageCount,
            'usd' => round($usd, 6),
            'krw' => $krw,
            'usd_krw' => $rate,
            'currency_note' => '추정 비용(USD→KRW, 단가·환율은 서버 설정 기준)',
            'steps' => is_array($usage['steps'] ?? null) ? $usage['steps'] : [],
        ];
    }

    public static function agentKey(string $intent, bool $usedImage): string
    {
        return match ($intent) {
            'recommend_product' => 'labi-shop',
            'generate_clipart' => 'labi-draw',
            'generate_template' => 'labi-design',
            'generate_data_template' => 'labi-data',
            'ask_image_mode' => 'labi-guide',
            default => $usedImage ? 'labi-draw' : 'labi-chat',
        };
    }

    public static function agentLabel(string $agentKey): string
    {
        return match ($agentKey) {
            'labi-shop' => '라비 · 상품추천',
            'labi-draw' => '라비 · 이미지생성',
            'labi-design' => '라비 · 템플릿디자인',
            'labi-data' => '라비 · 데이터라벨',
            'labi-guide' => '라비 · 안내',
            default => '라비 · 대화',
        };
    }

    public static function usdKrwRate(): float
    {
        $rate = (float) env('OPENAI_USD_KRW', 1400);
        return $rate > 0 ? $rate : 1400.0;
    }

    /**
     * @param array<string, mixed> $usage
     */
    public static function estimateUsd(array $usage): float
    {
        $usd = 0.0;
        $steps = is_array($usage['steps'] ?? null) ? $usage['steps'] : [];

        if ($steps !== []) {
            foreach ($steps as $step) {
                if (!is_array($step)) {
                    continue;
                }
                $kind = (string) ($step['kind'] ?? 'chat');
                if ($kind === 'image') {
                    $usd += self::imageUsd(
                        (string) ($step['model'] ?? ''),
                        (string) ($step['quality'] ?? ''),
                        max(1, (int) ($step['image_count'] ?? 1))
                    );
                    continue;
                }
                $usd += self::chatUsd(
                    (string) ($step['model'] ?? ''),
                    (int) ($step['prompt_tokens'] ?? 0),
                    (int) ($step['completion_tokens'] ?? 0)
                );
            }
            return $usd;
        }

        $usd += self::chatUsd(
            (string) ($usage['model'] ?? ''),
            (int) ($usage['prompt_tokens'] ?? 0),
            (int) ($usage['completion_tokens'] ?? 0)
        );
        $imageCount = (int) ($usage['image_count'] ?? 0);
        if ($imageCount > 0) {
            $usd += self::imageUsd(
                (string) ($usage['image_model'] ?? $usage['model'] ?? ''),
                (string) ($usage['image_quality'] ?? env('OPENAI_IMAGE_QUALITY', 'medium')),
                $imageCount
            );
        }

        return $usd;
    }

    public static function chatUsd(string $model, int $promptTokens, int $completionTokens): float
    {
        [$inPerM, $outPerM] = self::chatRatesPerMillion($model);
        return ($promptTokens / 1_000_000) * $inPerM + ($completionTokens / 1_000_000) * $outPerM;
    }

    public static function imageUsd(string $model, string $quality, int $count = 1): float
    {
        $count = max(1, $count);
        $model = strtolower(trim($model));
        $quality = strtolower(trim($quality ?: (string) env('OPENAI_IMAGE_QUALITY', 'medium')));

        if (str_starts_with($model, 'dall-e-3')) {
            $each = (float) env('OPENAI_PRICE_DALLE3_STANDARD', 0.04);
            return $each * $count;
        }
        if (str_starts_with($model, 'dall-e-2')) {
            return (float) env('OPENAI_PRICE_DALLE2', 0.02) * $count;
        }

        // gpt-image-1 approx by quality (configurable)
        $each = match ($quality) {
            'low' => (float) env('OPENAI_PRICE_GPT_IMAGE_LOW', 0.02),
            'high' => (float) env('OPENAI_PRICE_GPT_IMAGE_HIGH', 0.16),
            default => (float) env('OPENAI_PRICE_GPT_IMAGE_MEDIUM', 0.07),
        };

        return $each * $count;
    }

    /** @return array{0:float,1:float} input/output USD per 1M tokens */
    private static function chatRatesPerMillion(string $model): array
    {
        $m = strtolower(trim($model));

        if (str_contains($m, 'gpt-4.1-nano') || str_contains($m, '4o-mini') || str_contains($m, 'gpt-3.5')) {
            return [
                (float) env('OPENAI_PRICE_MINI_INPUT', 0.15),
                (float) env('OPENAI_PRICE_MINI_OUTPUT', 0.60),
            ];
        }
        if (str_contains($m, 'gpt-4.1-mini')) {
            return [
                (float) env('OPENAI_PRICE_41MINI_INPUT', 0.40),
                (float) env('OPENAI_PRICE_41MINI_OUTPUT', 1.60),
            ];
        }
        if (str_contains($m, 'gpt-4o') || str_contains($m, 'gpt-4.1')) {
            return [
                (float) env('OPENAI_PRICE_4O_INPUT', 2.50),
                (float) env('OPENAI_PRICE_4O_OUTPUT', 10.00),
            ];
        }

        return [
            (float) env('OPENAI_PRICE_DEFAULT_INPUT', 0.15),
            (float) env('OPENAI_PRICE_DEFAULT_OUTPUT', 0.60),
        ];
    }

    /** @param array<int, array{role:string, content:mixed}> $messages */
    private static function lastUserText(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'user') {
                continue;
            }
            $content = $messages[$i]['content'] ?? '';
            if (is_string($content)) {
                return trim($content);
            }
            if (!is_array($content)) {
                return '';
            }
            $parts = [];
            foreach ($content as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'text') {
                    $parts[] = (string) ($part['text'] ?? '');
                }
            }
            return trim(implode(' ', $parts));
        }
        return '';
    }
}
