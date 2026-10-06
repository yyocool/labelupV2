<?php

declare(strict_types=1);

namespace App\Services;

/** 카테고리 상세에 다는 해시태그. 저장값이 없으면 카테고리 성격에 맞는 기본값을 쓴다. */
final class ShopCategoryHashtag
{
    private const MAX_TAGS = 8;
    private const MAX_LENGTH = 20;

    /** @var array<string, list<string>> */
    private const BY_SLUG = [
        'logistics-label' => ['물류관리', '주소용', '바코드용', '인덱스용', '정부문서'],
        'logistics-use' => ['물류관리', '박스표기', '출고관리', '포장라벨', '재고정리'],
        'address-label' => ['주소표기', '배송라벨', '수신인', '발신인', '우편봉투'],
        'barcode-label' => ['바코드', '상품식별', '재고관리', '포장부착', '보관함'],
        'index-label' => ['문서정리', '파일분류', '인덱스', '수납함', '이름표'],
        'government-doc' => ['정부문서', '문서철', '제목표기', '분류정리', '보관용'],
        'gloss-label' => ['광택라벨', '브랜드', '상품명', '로고', '제품포장'],
        'waterproof-label' => ['방수', '습기차단', '용기표기', '필름소재', '외부포장'],
        'translucent-label' => ['반투명', '배경비침', '용기라벨', '포장분위기', '정보표시'],
        'inkjet-clear-label' => ['투명라벨', '잉크젯', '브랜드', '용기디자인', '배경비침'],
        'laser-clear-label' => ['투명라벨', '레이저', '브랜드', '용기디자인', '배경비침'],
        'protective-film' => ['보호필름', '오염방지', '표면보호', '덧붙임', '손상방지'],
        'color-label' => ['형광색', '시인성', '안내문구', '분류표시', '강조표기'],
        'pastel-color-label' => ['파스텔', '색상분류', '문서정리', '포장포인트', '수납구분'],
        'kraft-label' => ['크라프트', '선물포장', '자연색감', '브랜드', '소품포장'],
    ];

    /**
     * 슬러그가 없을 때 이름에 포함된 말로 고른다. 더 구체적인 이름을 앞에 둔다.
     *
     * @var array<string, list<string>>
     */
    private const BY_NAME = [
        '다용도' => ['물류관리', '주소용', '바코드용', '인덱스용', '정부문서'],
        '물류' => ['물류관리', '박스표기', '출고관리', '포장라벨', '재고정리'],
        '주소' => ['주소표기', '배송라벨', '수신인', '발신인', '우편봉투'],
        '바코드' => ['바코드', '상품식별', '재고관리', '포장부착', '보관함'],
        '인덱스' => ['문서정리', '파일분류', '인덱스', '수납함', '이름표'],
        '정부문서' => ['정부문서', '문서철', '제목표기', '분류정리', '보관용'],
        '잉크젯' => ['투명라벨', '잉크젯', '브랜드', '용기디자인', '배경비침'],
        '레이저' => ['투명라벨', '레이저', '브랜드', '용기디자인', '배경비침'],
        '반투명' => ['반투명', '배경비침', '용기라벨', '포장분위기', '정보표시'],
        '방수' => ['방수', '습기차단', '용기표기', '필름소재', '외부포장'],
        '광택' => ['광택라벨', '브랜드', '상품명', '로고', '제품포장'],
        '보호' => ['보호필름', '오염방지', '표면보호', '덧붙임', '손상방지'],
        '형광' => ['형광색', '시인성', '안내문구', '분류표시', '강조표기'],
        '파스텔' => ['파스텔', '색상분류', '문서정리', '포장포인트', '수납구분'],
        '크라프트' => ['크라프트', '선물포장', '자연색감', '브랜드', '소품포장'],
    ];

    /** @return list<string> */
    public static function defaultsFor(string $slug, string $name = ''): array
    {
        if (isset(self::BY_SLUG[$slug])) {
            return self::BY_SLUG[$slug];
        }
        $name = trim($name);
        foreach (self::BY_NAME as $needle => $tags) {
            if ($name !== '' && mb_strpos($name, $needle) !== false) {
                return $tags;
            }
        }
        return ['라벨', '분류', '표기', '정리'];
    }

    /**
     * 저장값이 없으면 기본값. 빈 배열로 저장된 경우는 비운 것으로 본다.
     *
     * @return list<string>
     */
    public static function resolve(mixed $stored, string $slug, string $name = ''): array
    {
        if ($stored === null) {
            return self::defaultsFor($slug, $name);
        }
        return self::normalize($stored);
    }

    /** @return list<string> */
    public static function normalize(mixed $value): array
    {
        $items = [];
        if (is_array($value)) {
            $items = $value;
        } else {
            $text = trim((string) $value);
            if ($text === '') {
                return [];
            }
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                $items = $decoded;
            } else {
                $items = preg_split('/[\r\n,]+/', $text) ?: [];
            }
        }

        $tags = [];
        foreach ($items as $item) {
            $tag = trim((string) $item);
            $tag = ltrim($tag, '#');
            $tag = trim(preg_replace('/\s+/u', ' ', $tag) ?? $tag);
            if ($tag === '') {
                continue;
            }
            if (mb_strlen($tag) > self::MAX_LENGTH) {
                $tag = mb_substr($tag, 0, self::MAX_LENGTH);
            }
            if (in_array($tag, $tags, true)) {
                continue;
            }
            $tags[] = $tag;
            if (count($tags) >= self::MAX_TAGS) {
                break;
            }
        }
        return $tags;
    }

    /** @param list<string> $tags */
    public static function encode(array $tags): string
    {
        return json_encode(array_values($tags), JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /** @param list<string> $tags */
    public static function same(array $tags, array $other): bool
    {
        return array_values($tags) === array_values($other);
    }
}
