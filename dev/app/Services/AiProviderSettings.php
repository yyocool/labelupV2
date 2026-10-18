<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SiteSettingsRepository;
use RuntimeException;

/**
 * 사용자(라비)가 쓰는 AI 제공자. site_settings.ai.provider = openai | claude
 */
final class AiProviderSettings
{
    public const PROVIDER_OPENAI = 'openai';
    public const PROVIDER_CLAUDE = 'claude';
    public const KEY = 'ai.provider';

    private static ?string $cache = null;

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    public static function provider(): string
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $raw = strtolower(trim((new SiteSettingsRepository())->get(self::KEY, self::PROVIDER_OPENAI)));
        if (in_array($raw, ['claude', 'anthropic', 'claud'], true)) {
            self::$cache = self::PROVIDER_CLAUDE;
            return self::$cache;
        }
        self::$cache = self::PROVIDER_OPENAI;
        return self::$cache;
    }

    public static function isClaude(): bool
    {
        return self::provider() === self::PROVIDER_CLAUDE;
    }

    public static function claudeApiKey(): string
    {
        $key = trim((string) env('ANTHROPIC_API_KEY', ''));
        if ($key === '') {
            $key = trim((string) env('CLAUDE_API_KEY', ''));
        }
        return $key;
    }

    public static function openaiApiKey(): string
    {
        return trim((string) env('OPENAI_API_KEY', ''));
    }

    /** @return array<string, mixed> */
    public static function adminPayload(): array
    {
        $provider = self::provider();
        $openaiModel = trim((string) env('OPENAI_MODEL', 'gpt-4o-mini')) ?: 'gpt-4o-mini';
        $claudeModel = trim((string) env('CLAUDE_MODEL', 'claude-sonnet-4-5')) ?: 'claude-sonnet-4-5';

        return [
            'provider' => $provider,
            'openai_key_ready' => self::openaiApiKey() !== '',
            'claude_key_ready' => self::claudeApiKey() !== '',
            'openai_model' => $openaiModel,
            'claude_model' => $claudeModel,
            'providers' => [
                [
                    'value' => self::PROVIDER_OPENAI,
                    'label' => 'Chat GPT',
                    'desc' => 'OpenAI ChatGPT로 라비 대화·라벨 설계·클립아트를 만듭니다. 서버 .env의 OPENAI_API_KEY를 사용합니다.',
                ],
                [
                    'value' => self::PROVIDER_CLAUDE,
                    'label' => 'Claude',
                    'desc' => 'Anthropic Claude로 동일한 라비 생성 흐름을 처리합니다. 서버 .env의 ANTHROPIC_API_KEY(또는 CLAUDE_API_KEY)를 사용합니다.',
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): array
    {
        $provider = strtolower(trim((string) ($data['provider'] ?? '')));
        if ($provider === 'chatgpt' || $provider === 'gpt' || $provider === 'openai') {
            $provider = self::PROVIDER_OPENAI;
        }
        if (in_array($provider, ['claud', 'anthropic'], true)) {
            $provider = self::PROVIDER_CLAUDE;
        }
        if (!in_array($provider, [self::PROVIDER_OPENAI, self::PROVIDER_CLAUDE], true)) {
            throw new RuntimeException('유효하지 않은 AI 제공자입니다.');
        }

        (new SiteSettingsRepository())->set(self::KEY, $provider);
        self::clearCache();

        return self::adminPayload();
    }
}
