<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * 폼텍/아이라벨/애니라벨 호환 상품코드 — 벤더당 복수 코드를 쉼표로 저장.
 */
final class ShopCompatHelper
{
    /**
     * 입력(쉼표·줄바꿈·세미콜론 구분)을 정규화해 저장용 문자열로 만든다.
     * 예: "3230\n3102, V100" → "3230,3102,V100"
     */
    public static function normalize(mixed $value): ?string
    {
        $codes = self::parse($value);
        return $codes === [] ? null : implode(',', $codes);
    }

    /**
     * @return list<string>
     */
    public static function parse(mixed $value): array
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/[,;\n\r|]+/u', $text) ?: [];
        $codes = [];
        foreach ($parts as $part) {
            $code = trim($part);
            if ($code === '') {
                continue;
            }
            if (!in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /** 관리자 textarea용: 한 줄에 코드 하나 */
    public static function toMultiline(mixed $value): string
    {
        return implode("\n", self::parse($value));
    }

    /**
     * FIND_IN_SET 용: 공백·구분자 정규화한 리스트 문자열.
     */
    public static function forFindInSet(mixed $value): string
    {
        return implode(',', self::parse($value));
    }
}
