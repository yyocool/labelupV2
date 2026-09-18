<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class ShopProductPageCategorySettingsRepository extends BaseModel
{
    /** @return array{header_html:string,footer_html:string,header_image:string,footer_image:string}|null */
    public function findByCategoryId(int $categoryId): ?array
    {
        if ($categoryId <= 0) {
            return null;
        }
        $row = $this->fetchOne(
            'SELECT * FROM shop_product_page_category_settings WHERE category_id = :id LIMIT 1',
            ['id' => $categoryId]
        );
        if (!$row) {
            return null;
        }
        return [
            'header_html' => (string) ($row['header_html'] ?? ''),
            'footer_html' => (string) ($row['footer_html'] ?? ''),
            'header_image' => (string) ($row['header_image'] ?? ''),
            'footer_image' => (string) ($row['footer_image'] ?? ''),
        ];
    }

    /** @return array<int, array{category_id:int,header_html:string,footer_html:string,header_image:string,footer_image:string,has_custom:bool}> */
    public function allIndexed(): array
    {
        $rows = $this->fetchAll('SELECT * FROM shop_product_page_category_settings');
        $map = [];
        foreach ($rows as $row) {
            $id = (int) ($row['category_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $headerHtml = (string) ($row['header_html'] ?? '');
            $footerHtml = (string) ($row['footer_html'] ?? '');
            $headerImage = (string) ($row['header_image'] ?? '');
            $footerImage = (string) ($row['footer_image'] ?? '');
            $map[$id] = [
                'category_id' => $id,
                'header_html' => $headerHtml,
                'footer_html' => $footerHtml,
                'header_image' => $headerImage,
                'footer_image' => $footerImage,
                'has_custom' => $this->hasContent($headerHtml, $footerHtml, $headerImage, $footerImage),
            ];
        }
        return $map;
    }

    public function save(int $categoryId, array $data): void
    {
        if ($categoryId <= 0) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $params = [
            'id' => $categoryId,
            'header_html' => $this->nullableHtml($data['header_html'] ?? null),
            'footer_html' => $this->nullableHtml($data['footer_html'] ?? null),
            'header_image' => $this->nullableText($data['header_image'] ?? null),
            'footer_image' => $this->nullableText($data['footer_image'] ?? null),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $existing = $this->fetchOne(
            'SELECT category_id FROM shop_product_page_category_settings WHERE category_id = :id LIMIT 1',
            ['id' => $categoryId]
        );

        if ($existing) {
            // If everything empty, remove row
            if ($params['header_html'] === null
                && $params['footer_html'] === null
                && $params['header_image'] === null
                && $params['footer_image'] === null) {
                $this->execute(
                    'DELETE FROM shop_product_page_category_settings WHERE category_id = :id',
                    ['id' => $categoryId]
                );
                return;
            }
            $this->execute(
                'UPDATE shop_product_page_category_settings
                 SET header_html = :header_html,
                     footer_html = :footer_html,
                     header_image = :header_image,
                     footer_image = :footer_image,
                     updated_at = :updated_at
                 WHERE category_id = :id',
                [
                    'id' => $params['id'],
                    'header_html' => $params['header_html'],
                    'footer_html' => $params['footer_html'],
                    'header_image' => $params['header_image'],
                    'footer_image' => $params['footer_image'],
                    'updated_at' => $params['updated_at'],
                ]
            );
            return;
        }

        if ($params['header_html'] === null
            && $params['footer_html'] === null
            && $params['header_image'] === null
            && $params['footer_image'] === null) {
            return;
        }

        $this->execute(
            'INSERT INTO shop_product_page_category_settings
             (category_id, header_html, footer_html, header_image, footer_image, created_at, updated_at)
             VALUES (:id, :header_html, :footer_html, :header_image, :footer_image, :created_at, :updated_at)',
            $params
        );
    }

    public function hasContent(string $headerHtml, string $footerHtml, string $headerImage, string $footerImage): bool
    {
        return trim($headerHtml) !== ''
            || trim($footerHtml) !== ''
            || trim($headerImage) !== ''
            || trim($footerImage) !== '';
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
