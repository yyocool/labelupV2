<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 전자상거래·카카오 비즈앱 검수용 사업자정보.
 * SEO 설정(seo.company_*)과 환경변수를 합쳐 공개 푸터/소개에 노출한다.
 */
final class CompanyInfoService
{
    /** @return array<string, string> */
    public function all(): array
    {
        $seo = new SeoService();
        $get = static function (string $key, string $envKey = '', string $fallback = '') use ($seo): string {
            $fromSeo = trim((string) $seo->setting('seo.' . $key, ''));
            if ($fromSeo !== '') {
                return $fromSeo;
            }
            if ($envKey !== '') {
                $fromEnv = trim((string) (env($envKey) ?? ''));
                if ($fromEnv !== '') {
                    return $fromEnv;
                }
            }
            return $fallback;
        };

        $name = $get('company_name', 'COMPANY_NAME', '라벨업');
        if ($name === '') {
            $name = trim((string) $seo->setting('seo.org_name', '라벨업')) ?: '라벨업';
        }

        return [
            'name' => $name,
            'ceo' => $get('company_ceo', 'COMPANY_CEO'),
            'biz_no' => $get('company_biz_no', 'COMPANY_BIZ_NO'),
            'mail_order_no' => $get('company_mail_order_no', 'COMPANY_MAIL_ORDER_NO'),
            'address' => $get('company_address', 'COMPANY_ADDRESS'),
            'phone' => $get('company_phone', 'COMPANY_PHONE') ?: trim((string) $seo->setting('seo.org_phone', '')),
            'email' => $get('company_email', 'COMPANY_EMAIL') ?: trim((string) $seo->setting('seo.org_email', '')),
            'privacy_officer' => $get('company_privacy_officer', 'COMPANY_PRIVACY_OFFICER'),
        ];
    }

    public function hasBusinessIdentity(): bool
    {
        $info = $this->all();
        return $info['biz_no'] !== ''
            || $info['mail_order_no'] !== ''
            || $info['ceo'] !== ''
            || $info['address'] !== '';
    }

    /** @return list<array{label:string,value:string}> */
    public function publicRows(): array
    {
        $info = $this->all();
        $rows = [
            ['label' => '상호', 'value' => $info['name']],
            ['label' => '대표자', 'value' => $info['ceo']],
            ['label' => '사업자등록번호', 'value' => $info['biz_no']],
            ['label' => '통신판매업 신고번호', 'value' => $info['mail_order_no']],
            ['label' => '사업장 주소', 'value' => $info['address']],
            ['label' => '대표 전화', 'value' => $info['phone']],
            ['label' => '이메일', 'value' => $info['email']],
            ['label' => '개인정보보호책임자', 'value' => $info['privacy_officer']],
        ];
        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => trim($row['value']) !== ''
        ));
    }

    /** @return list<array{kind:string,label:string,href:string}> */
    public function socialLinks(): array
    {
        $raw = trim((string) (new SeoService())->setting('seo.org_same_as', ''));
        if ($raw === '') {
            return [];
        }
        $urls = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $raw) ?: [])));
        $out = [];
        $seen = [];
        foreach ($urls as $url) {
            if (!preg_match('~^https?://~i', $url) || isset($seen[$url])) {
                continue;
            }
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $kind = 'link';
            $label = '바로가기';
            if (str_contains($host, 'youtube') || str_contains($host, 'youtu.be')) {
                $kind = 'youtube';
                $label = '유튜브';
            } elseif (str_contains($host, 'instagram')) {
                $kind = 'instagram';
                $label = '인스타그램';
            } elseif (str_contains($host, 'facebook') || str_contains($host, 'fb.com')) {
                $kind = 'facebook';
                $label = '페이스북';
            } elseif (str_contains($host, 'blog.naver') || str_contains($host, 'naver')) {
                $kind = 'blog';
                $label = '블로그';
            }
            $seen[$url] = true;
            $out[] = ['kind' => $kind, 'label' => $label, 'href' => $url];
        }
        return $out;
    }
}
