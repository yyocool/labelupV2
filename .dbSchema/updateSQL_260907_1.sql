-- site_intro: first-visit fullscreen intro video
CREATE TABLE IF NOT EXISTS site_intro (
    id TINYINT UNSIGNED NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    source_type VARCHAR(20) NOT NULL DEFAULT 'youtube',
    youtube_url VARCHAR(500) NOT NULL DEFAULT '',
    video_path VARCHAR(500) NOT NULL DEFAULT '',
    skip_label VARCHAR(80) NOT NULL DEFAULT '건너뛰기',
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO site_intro (id, is_enabled, source_type, youtube_url, video_path, skip_label, updated_at)
VALUES (1, 0, 'youtube', '', '', '건너뛰기', NOW());
