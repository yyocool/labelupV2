<?php

declare(strict_types=1);

namespace App\Helpers;

final class StringHelper
{
    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    public static function maskEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (!str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);
        $len = mb_strlen($local);
        if ($len <= 2) {
            $maskedLocal = mb_substr($local, 0, 1) . '*';
        } elseif ($len <= 4) {
            $maskedLocal = mb_substr($local, 0, 2) . str_repeat('*', max(1, $len - 2));
        } else {
            $maskedLocal = mb_substr($local, 0, 3) . str_repeat('*', $len - 3);
        }

        return $maskedLocal . '@' . $domain;
    }
}
