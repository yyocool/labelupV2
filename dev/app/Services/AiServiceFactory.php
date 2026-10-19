<?php

declare(strict_types=1);

namespace App\Services;

final class AiServiceFactory
{
    public static function make(): OpenAIService
    {
        if (AiProviderSettings::isClaude()) {
            return new ClaudeService();
        }

        return new OpenAIService();
    }
}
