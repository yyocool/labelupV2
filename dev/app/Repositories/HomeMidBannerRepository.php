<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class HomeMidBannerRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT * FROM home_mid_banners ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function active(): array
    {
        return $this->fetchAll(
            'SELECT * FROM home_mid_banners WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    public function save(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'title' => trim((string) ($data['title'] ?? '')),
            'alt_text' => trim((string) ($data['alt_text'] ?? '')),
            'main_text' => trim((string) ($data['main_text'] ?? '')),
            'main_font' => trim((string) ($data['main_font'] ?? 'pretendard-extrabold')),
            'main_size' => (int) ($data['main_size'] ?? 50),
            'main_color' => trim((string) ($data['main_color'] ?? '#FFFFFF')),
            'sub_text' => trim((string) ($data['sub_text'] ?? '')),
            'sub_font' => trim((string) ($data['sub_font'] ?? 'pretendard-regular')),
            'sub_size' => (int) ($data['sub_size'] ?? 25),
            'sub_color' => trim((string) ($data['sub_color'] ?? '#FFFFFF')),
            'text_align_h' => trim((string) ($data['text_align_h'] ?? 'left')),
            'text_align_v' => trim((string) ($data['text_align_v'] ?? 'middle')),
            'image_url' => trim((string) ($data['image_url'] ?? '')),
            'link_url' => trim((string) ($data['link_url'] ?? '')) ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) !empty($data['is_active']),
            'now' => $now,
        ];
        if ($id > 0) {
            $this->execute(
                'UPDATE home_mid_banners SET title=:title,alt_text=:alt_text,main_text=:main_text,main_font=:main_font,main_size=:main_size,main_color=:main_color,sub_text=:sub_text,sub_font=:sub_font,sub_size=:sub_size,sub_color=:sub_color,text_align_h=:text_align_h,text_align_v=:text_align_v,image_url=:image_url,link_url=:link_url,sort_order=:sort_order,is_active=:is_active,updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }
        $this->execute(
            'INSERT INTO home_mid_banners (title,alt_text,main_text,main_font,main_size,main_color,sub_text,sub_font,sub_size,sub_color,text_align_h,text_align_v,image_url,link_url,sort_order,is_active,created_at,updated_at)
             VALUES (:title,:alt_text,:main_text,:main_font,:main_size,:main_color,:sub_text,:sub_font,:sub_size,:sub_color,:text_align_h,:text_align_v,:image_url,:link_url,:sort_order,:is_active,:now,:now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM home_mid_banners WHERE id = :id', ['id' => $id]);
    }
}
