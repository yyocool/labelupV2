<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class ShopProductPageSettingsRepository extends BaseModel
{
    /** @return array{header_html:string,footer_html:string,header_image:string,footer_image:string} */
    public function get(): array
    {
        $row = $this->fetchOne('SELECT * FROM shop_product_page_settings WHERE id = 1 LIMIT 1');
        if (!$row) {
            $now = date('Y-m-d H:i:s');
            $this->execute(
                'INSERT INTO shop_product_page_settings (id, header_html, footer_html, header_image, footer_image, created_at, updated_at)
                 VALUES (1, NULL, NULL, NULL, NULL, :c, :u)',
                ['c' => $now, 'u' => $now]
            );
            $row = [];
        }

        return [
            'header_html' => (string) ($row['header_html'] ?? ''),
            'footer_html' => (string) ($row['footer_html'] ?? ''),
            'header_image' => (string) ($row['header_image'] ?? ''),
            'footer_image' => (string) ($row['footer_image'] ?? ''),
        ];
    }

    public function save(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->fetchOne('SELECT id FROM shop_product_page_settings WHERE id = 1 LIMIT 1');
        $params = [
            'header_html' => $this->nullableHtml($data['header_html'] ?? null),
            'footer_html' => $this->nullableHtml($data['footer_html'] ?? null),
            'header_image' => $this->nullableText($data['header_image'] ?? null),
            'footer_image' => $this->nullableText($data['footer_image'] ?? null),
            'now' => $now,
        ];

        if ($existing) {
            $this->execute(
                'UPDATE shop_product_page_settings
                 SET header_html = :header_html,
                     footer_html = :footer_html,
                     header_image = :header_image,
                     footer_image = :footer_image,
                     updated_at = :now
                 WHERE id = 1',
                $params
            );
            return;
        }

        $this->execute(
            'INSERT INTO shop_product_page_settings
             (id, header_html, footer_html, header_image, footer_image, created_at, updated_at)
             VALUES (1, :header_html, :footer_html, :header_image, :footer_image, :now, :now)',
            $params
        );
    }

    private function nullableHtml(mixed $value): ?string
    {
        $html = trim((string) ($value ?? ''));
        if ($html === '' || $html === '<p><br></p>' || $html === '<p></p>') {
            return null;
        }
        return $html;
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }
}
