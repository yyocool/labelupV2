<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class SiteIntroRepository extends BaseModel
{
    public function get(): array
    {
        $row = $this->fetchOne('SELECT * FROM site_intro WHERE id = 1 LIMIT 1');
        if ($row) {
            return $row;
        }
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO site_intro (id, is_enabled, source_type, youtube_url, video_path, skip_label, updated_at)
             VALUES (1, 0, \'youtube\', \'\', \'\', \'건너뛰기\', :now)',
            ['now' => $now]
        );
        return [
            'id' => 1,
            'is_enabled' => 0,
            'source_type' => 'youtube',
            'youtube_url' => '',
            'video_path' => '',
            'skip_label' => '건너뛰기',
            'updated_at' => $now,
        ];
    }

    public function save(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO site_intro (id, is_enabled, source_type, youtube_url, video_path, skip_label, updated_at)
             VALUES (1, :en, :st, :yt, :vp, :sl, :now)
             ON DUPLICATE KEY UPDATE
               is_enabled = VALUES(is_enabled),
               source_type = VALUES(source_type),
               youtube_url = VALUES(youtube_url),
               video_path = VALUES(video_path),
               skip_label = VALUES(skip_label),
               updated_at = VALUES(updated_at)',
            [
                'en' => (int) ($data['is_enabled'] ?? 0),
                'st' => (string) ($data['source_type'] ?? 'youtube'),
                'yt' => (string) ($data['youtube_url'] ?? ''),
                'vp' => (string) ($data['video_path'] ?? ''),
                'sl' => (string) ($data['skip_label'] ?? '건너뛰기'),
                'now' => $now,
            ]
        );
    }
}
