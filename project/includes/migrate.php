<?php

function migrate_check()
{
    try {
        $db = Database::getConnection();
        $tables = $db->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$tables) {
            return;
        }
        $cols = $db->query("SHOW COLUMNS FROM users LIKE 'username'")->fetch();
        if (!$cols) {
            $db->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL UNIQUE AFTER id");
            $db->exec("UPDATE users SET username = CONCAT('user', id) WHERE username IS NULL OR username = ''");
            $db->exec("UPDATE users SET username = 'admin' WHERE role = 'admin' ORDER BY id LIMIT 1");
            $db->exec("ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL");
        }

        migrate_storyboard_collab_tables($db);
        migrate_project_phase_columns($db);
        migrate_archive_documents_table($db);
        migrate_storyboard_visibility_column($db);
        migrate_menu_progress_rows($db);
        migrate_menu_code_column($db);
        migrate_policies_table($db);
        migrate_meeting_minutes_table($db);
        migrate_progress_phase_enum($db);
        migrate_menu_design_status_column($db);
        migrate_project_db_design_status_column($db);
        migrate_format_analysis_tables($db);
        migrate_feature_map_tables($db);
        migrate_dev_scope_items_table($db);
        migrate_resume_tables($db);
        migrate_company_history_tables($db);
        migrate_schedule_tasks_table($db);
    } catch (Exception $e) {
        // DB 미연결 등은 무시
    }
}

function migrate_schedule_tasks_table($db)
{
    $tbl = $db->query("SHOW TABLES LIKE 'schedule_tasks'")->fetch();
    if (!$tbl) {
        $db->exec("CREATE TABLE IF NOT EXISTS `schedule_tasks` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT UNSIGNED NOT NULL,
            `phase` VARCHAR(100) NOT NULL DEFAULT '',
            `title` VARCHAR(200) NOT NULL,
            `detail` VARCHAR(300) DEFAULT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `color` VARCHAR(7) DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `updated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL,
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_schedule_tasks_project` (`project_id`, `sort_order`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return;
    }

    // AFTER 절 없이 추가 (기존 테이블 컬럼 구성이 달라도 실패하지 않도록)
    $need = array(
        'phase' => "ALTER TABLE `schedule_tasks` ADD COLUMN `phase` VARCHAR(100) NOT NULL DEFAULT ''",
        'detail' => "ALTER TABLE `schedule_tasks` ADD COLUMN `detail` VARCHAR(300) DEFAULT NULL",
        'color' => "ALTER TABLE `schedule_tasks` ADD COLUMN `color` VARCHAR(7) DEFAULT NULL",
        'sort_order' => "ALTER TABLE `schedule_tasks` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0",
        'created_by' => "ALTER TABLE `schedule_tasks` ADD COLUMN `created_by` INT UNSIGNED DEFAULT NULL",
        'updated_by' => "ALTER TABLE `schedule_tasks` ADD COLUMN `updated_by` INT UNSIGNED DEFAULT NULL",
        'created_at' => "ALTER TABLE `schedule_tasks` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE `schedule_tasks` ADD COLUMN `updated_at` DATETIME NULL",
    );
    foreach ($need as $col => $sql) {
        $exists = $db->query("SHOW COLUMNS FROM `schedule_tasks` LIKE " . $db->quote($col))->fetch();
        if (!$exists) {
            try {
                $db->exec($sql);
            } catch (Exception $e) {
                // 개별 컬럼 추가 실패는 다른 컬럼 시도를 막지 않음
                if (function_exists('labelup_log_error')) {
                    labelup_log_error('[migrate_schedule_tasks] ' . $col . ': ' . $e->getMessage());
                }
            }
        }
    }
}

function migrate_dev_scope_items_table($db)
{
    $tbl = $db->query("SHOW TABLES LIKE 'dev_scope_items'")->fetch();
    if (!$tbl) {
        $db->exec("CREATE TABLE IF NOT EXISTS `dev_scope_items` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT UNSIGNED NOT NULL,
            `parent_id` INT UNSIGNED DEFAULT NULL,
            `depth` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=영역 2=블록 3=항목',
            `phase_key` VARCHAR(40) NOT NULL DEFAULT 'phase-1',
            `title` VARCHAR(500) NOT NULL,
            `description` TEXT NULL,
            `priority` VARCHAR(10) NOT NULL DEFAULT 'P1',
            `status` VARCHAR(20) NOT NULL DEFAULT 'planned',
            `sort_order` INT NOT NULL DEFAULT 0,
            `style_json` TEXT NULL COMMENT '셀 스타일 JSON',
            `created_by` INT UNSIGNED DEFAULT NULL,
            `updated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            INDEX `idx_dev_scope_project_phase` (`project_id`, `phase_key`),
            INDEX `idx_dev_scope_parent` (`parent_id`),
            INDEX `idx_dev_scope_depth` (`depth`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return;
    }
    $col = $db->query("SHOW COLUMNS FROM dev_scope_items LIKE 'style_json'")->fetch();
    if (!$col) {
        $db->exec("ALTER TABLE `dev_scope_items` ADD COLUMN `style_json` TEXT NULL COMMENT '셀 스타일 JSON' AFTER `sort_order`");
    }
}

function migrate_resume_tables($db)
{
    $people = $db->query("SHOW TABLES LIKE 'resume_people'")->fetch();
    if (!$people) {
        $db->exec("CREATE TABLE IF NOT EXISTS `resume_people` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(120) NOT NULL,
            `job_title` VARCHAR(200) DEFAULT NULL,
            `organization` VARCHAR(200) DEFAULT NULL,
            `email` VARCHAR(200) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `summary` TEXT DEFAULT NULL,
            `skills` TEXT DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `updated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            INDEX `idx_resume_people_project` (`project_id`),
            INDEX `idx_resume_people_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    $entries = $db->query("SHOW TABLES LIKE 'resume_entries'")->fetch();
    if (!$entries) {
        $db->exec("CREATE TABLE IF NOT EXISTS `resume_entries` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `person_id` INT UNSIGNED NOT NULL,
            `category` VARCHAR(30) NOT NULL COMMENT 'education|career|award|project',
            `title` VARCHAR(300) NOT NULL,
            `organization` VARCHAR(200) DEFAULT NULL,
            `period_start` VARCHAR(40) DEFAULT NULL,
            `period_end` VARCHAR(40) DEFAULT NULL,
            `is_current` TINYINT(1) NOT NULL DEFAULT 0,
            `description` TEXT DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            INDEX `idx_resume_entries_person` (`person_id`, `category`),
            CONSTRAINT `fk_resume_entries_person` FOREIGN KEY (`person_id`) REFERENCES `resume_people`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

function migrate_company_history_tables($db)
{
    $companies = $db->query("SHOW TABLES LIKE 'company_profiles'")->fetch();
    if (!$companies) {
        $db->exec("CREATE TABLE IF NOT EXISTS `company_profiles` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(200) NOT NULL,
            `founded_year` VARCHAR(20) DEFAULT NULL,
            `industry` VARCHAR(120) DEFAULT NULL,
            `website` VARCHAR(255) DEFAULT NULL,
            `summary` TEXT DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `updated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            INDEX `idx_company_profiles_project` (`project_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    $events = $db->query("SHOW TABLES LIKE 'company_history_events'")->fetch();
    if (!$events) {
        $db->exec("CREATE TABLE IF NOT EXISTS `company_history_events` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT UNSIGNED NOT NULL,
            `category` VARCHAR(30) NOT NULL DEFAULT 'other',
            `event_year` VARCHAR(20) DEFAULT NULL,
            `event_month` TINYINT UNSIGNED DEFAULT NULL,
            `title` VARCHAR(300) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            INDEX `idx_company_history_company` (`company_id`),
            CONSTRAINT `fk_company_history_company` FOREIGN KEY (`company_id`) REFERENCES `company_profiles`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    $achievements = $db->query("SHOW TABLES LIKE 'company_achievements'")->fetch();
    if (!$achievements) {
        $db->exec("CREATE TABLE IF NOT EXISTS `company_achievements` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT UNSIGNED NOT NULL,
            `category` VARCHAR(30) NOT NULL DEFAULT 'project',
            `title` VARCHAR(300) NOT NULL,
            `client` VARCHAR(200) DEFAULT NULL,
            `metric` VARCHAR(200) DEFAULT NULL,
            `achieved_year` VARCHAR(20) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            INDEX `idx_company_achievements_company` (`company_id`),
            CONSTRAINT `fk_company_achievements_company` FOREIGN KEY (`company_id`) REFERENCES `company_profiles`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

function migrate_feature_map_tables($db)
{
    $docs = $db->query("SHOW TABLES LIKE 'feature_map_docs'")->fetch();
    if (!$docs) {
        $db->exec("CREATE TABLE IF NOT EXISTS `feature_map_docs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT UNSIGNED NOT NULL,
            `map_key` VARCHAR(80) NOT NULL DEFAULT 'default',
            `title` VARCHAR(300) NOT NULL,
            `subtitle` VARCHAR(500) DEFAULT NULL,
            `version` VARCHAR(20) NOT NULL DEFAULT '1.0',
            `basis` VARCHAR(500) DEFAULT NULL,
            `map_json` MEDIUMTEXT NULL,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `updated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL,
            UNIQUE KEY `uk_fmap_project_key` (`project_id`, `map_key`),
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $slides = $db->query("SHOW TABLES LIKE 'feature_map_slides'")->fetch();
    if (!$slides) {
        $db->exec("CREATE TABLE IF NOT EXISTS `feature_map_slides` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT UNSIGNED NOT NULL,
            `doc_id` INT UNSIGNED NOT NULL,
            `slide_key` VARCHAR(80) NOT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `slide_type` VARCHAR(30) NOT NULL DEFAULT 'custom',
            `tone` VARCHAR(20) NOT NULL DEFAULT 'teal',
            `kicker` VARCHAR(200) DEFAULT NULL,
            `title` VARCHAR(300) NOT NULL,
            `subtitle` TEXT NULL,
            `lead_text` TEXT NULL,
            `body_json` MEDIUMTEXT NULL,
            `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `updated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL,
            UNIQUE KEY `uk_fmap_slide_key` (`project_id`, `slide_key`),
            INDEX `idx_fmap_slides_order` (`project_id`, `sort_order`),
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`doc_id`) REFERENCES `feature_map_docs`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    require_once __DIR__ . '/FeatureMapService.php';
    $projects = $db->query('SELECT id FROM projects')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($projects as $projectId) {
        FeatureMapService::ensureDefaults((int) $projectId);
    }
}

function migrate_storyboard_collab_tables($db)
{
    $db->exec("CREATE TABLE IF NOT EXISTS `storyboard_comments` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `storyboard_id` INT UNSIGNED NOT NULL,
        `frame_id` INT UNSIGNED DEFAULT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `content` TEXT NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`storyboard_id`) REFERENCES `storyboards`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`frame_id`) REFERENCES `storyboard_frames`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX `idx_sb_comments` (`storyboard_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `storyboard_history` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `storyboard_id` INT UNSIGNED NOT NULL,
        `frame_id` INT UNSIGNED DEFAULT NULL,
        `user_id` INT UNSIGNED DEFAULT NULL,
        `action` ENUM('frame_create','frame_update','frame_delete','comment','status_change') NOT NULL,
        `summary` VARCHAR(500) NOT NULL,
        `detail` TEXT,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`storyboard_id`) REFERENCES `storyboards`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`frame_id`) REFERENCES `storyboard_frames`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        INDEX `idx_sb_history` (`storyboard_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function migrate_project_phase_columns($db)
{
    $cols = $db->query("SHOW COLUMNS FROM projects LIKE 'current_phase'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE projects ADD COLUMN current_phase ENUM('planning','storyboard','publishing','coding','review','launch') DEFAULT NULL AFTER progress");
        $db->exec("ALTER TABLE projects ADD COLUMN phase_mode ENUM('auto','manual') NOT NULL DEFAULT 'auto' AFTER current_phase");
    }
}

function migrate_archive_documents_table($db)
{
    $tables = $db->query("SHOW TABLES LIKE 'archive_documents'")->fetch();
    if ($tables) {
        return;
    }
    $db->exec("CREATE TABLE IF NOT EXISTS `archive_documents` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT UNSIGNED NOT NULL,
        `category` ENUM('contract','specification','design','reference','legal','other') NOT NULL DEFAULT 'reference',
        `title` VARCHAR(300) NOT NULL,
        `description` TEXT,
        `original_name` VARCHAR(255) NOT NULL,
        `stored_name` VARCHAR(255) NOT NULL,
        `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
        `mime_type` VARCHAR(100) DEFAULT NULL,
        `uploaded_by` INT UNSIGNED DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        INDEX `idx_archive_project` (`project_id`, `category`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function migrate_storyboard_visibility_column($db)
{
    $cols = $db->query("SHOW COLUMNS FROM storyboards LIKE 'visibility'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE storyboards ADD COLUMN visibility ENUM('working','public') NOT NULL DEFAULT 'working' AFTER status");
    }
}

function migrate_menu_progress_rows($db)
{
    $tables = $db->query("SHOW TABLES LIKE 'menu_progress'")->fetch();
    if (!$tables) {
        return;
    }
    $db->exec('
        INSERT INTO menu_progress (menu_id)
        SELECT m.id FROM menus m
        LEFT JOIN menu_progress mp ON mp.menu_id = m.id
        WHERE mp.menu_id IS NULL AND m.is_active = 1
    ');
}

function migrate_menu_code_column($db)
{
    $tables = $db->query("SHOW TABLES LIKE 'menus'")->fetch();
    if (!$tables) {
        return;
    }
    $cols = $db->query("SHOW COLUMNS FROM menus LIKE 'menu_code'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE menus ADD COLUMN menu_code VARCHAR(50) DEFAULT NULL COMMENT '계층 메뉴코드' AFTER depth");
        $db->exec("ALTER TABLE menus ADD INDEX idx_menu_code (project_id, menu_code)");
        require_once __DIR__ . '/MenuService.php';
        $projects = $db->query('SELECT DISTINCT project_id FROM menus WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($projects as $projectId) {
            MenuService::rebuildCodes((int) $projectId);
        }
    }
}

function migrate_policies_table($db)
{
    $tables = $db->query("SHOW TABLES LIKE 'policies'")->fetch();
    if (!$tables) {
        $db->exec("CREATE TABLE IF NOT EXISTS `policies` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT UNSIGNED NOT NULL,
            `policy_key` VARCHAR(80) NOT NULL,
            `category` ENUM('service','privacy','commerce','design','ai','payment','operation') NOT NULL DEFAULT 'service',
            `title` VARCHAR(300) NOT NULL,
            `summary` VARCHAR(500) DEFAULT NULL,
            `content` MEDIUMTEXT NOT NULL,
            `version` VARCHAR(20) NOT NULL DEFAULT '1.0',
            `status` ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
            `audience` ENUM('customer','internal','both') NOT NULL DEFAULT 'customer',
            `related_menu_code` VARCHAR(50) DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `updated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL,
            UNIQUE KEY `uk_project_policy_key` (`project_id`, `policy_key`),
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_policies_project` (`project_id`, `category`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    require_once __DIR__ . '/PolicyService.php';
    $projects = $db->query('SELECT id FROM projects')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($projects as $projectId) {
        PolicyService::ensureDefaults((int) $projectId);
    }
}

function migrate_meeting_minutes_table($db)
{
    $tables = $db->query("SHOW TABLES LIKE 'meeting_minutes'")->fetch();
    if (!$tables) {
        $db->exec("CREATE TABLE IF NOT EXISTS `meeting_minutes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT UNSIGNED NOT NULL,
            `title` VARCHAR(300) NOT NULL,
            `meeting_date` DATE NOT NULL,
            `meeting_time` TIME NULL,
            `location` VARCHAR(200) DEFAULT NULL,
            `attendees` TEXT DEFAULT NULL,
            `agenda` TEXT DEFAULT NULL,
            `content` MEDIUMTEXT NOT NULL,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `updated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL,
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_meeting_minutes_project` (`project_id`, `meeting_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

function migrate_progress_phase_enum($db)
{
    $cols = $db->query("SHOW COLUMNS FROM projects LIKE 'current_phase'")->fetch();
    if (!$cols) {
        return;
    }
    $type = isset($cols['Type']) ? $cols['Type'] : '';
    if (strpos($type, 'policy_setup') !== false) {
        return;
    }
    $db->exec("ALTER TABLE projects MODIFY current_phase ENUM(
        'policy_setup','menu_setup','storyboard','design','publishing','db_design',
        'dev_front','dev_admin','test','review','launch',
        'planning','coding'
    ) DEFAULT NULL");
}

function migrate_menu_design_status_column($db)
{
    $tables = $db->query("SHOW TABLES LIKE 'menu_progress'")->fetch();
    if (!$tables) {
        return;
    }
    $cols = $db->query("SHOW COLUMNS FROM menu_progress LIKE 'design_status'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE menu_progress
            ADD COLUMN design_status ENUM('pending','in_progress','done','na') NOT NULL DEFAULT 'pending' AFTER storyboard_status,
            ADD COLUMN design_note TEXT NULL AFTER storyboard_note");
    }
}

function migrate_project_db_design_status_column($db)
{
    $cols = $db->query("SHOW COLUMNS FROM projects LIKE 'db_design_status'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE projects
            ADD COLUMN db_design_status ENUM('pending','in_progress','done','na') NOT NULL DEFAULT 'pending' AFTER phase_mode,
            ADD COLUMN db_design_note TEXT NULL AFTER db_design_status");
    }
}

function migrate_format_analysis_tables($db)
{
    $db->exec("CREATE TABLE IF NOT EXISTS `format_profiles` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT UNSIGNED NOT NULL,
        `vendor` VARCHAR(80) NOT NULL DEFAULT 'Other',
        `format_key` VARCHAR(80) NOT NULL,
        `format_name` VARCHAR(200) NOT NULL,
        `extensions` VARCHAR(120) DEFAULT NULL,
        `magic_signature` VARCHAR(255) DEFAULT NULL,
        `container_type` VARCHAR(40) DEFAULT NULL,
        `structure_notes` TEXT,
        `field_schema` MEDIUMTEXT,
        `status` ENUM('draft','active','archived') NOT NULL DEFAULT 'active',
        `notes` TEXT,
        `created_by` INT UNSIGNED DEFAULT NULL,
        `updated_by` INT UNSIGNED DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        UNIQUE KEY `uk_format_profile` (`project_id`, `format_key`),
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        INDEX `idx_format_profiles_vendor` (`project_id`, `vendor`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `format_analyses` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT UNSIGNED NOT NULL,
        `profile_id` INT UNSIGNED DEFAULT NULL,
        `original_name` VARCHAR(255) NOT NULL,
        `stored_name` VARCHAR(255) NOT NULL,
        `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
        `file_hash` CHAR(64) DEFAULT NULL,
        `detected_format` VARCHAR(80) DEFAULT NULL,
        `detected_vendor` VARCHAR(80) DEFAULT NULL,
        `detected_version` VARCHAR(40) DEFAULT NULL,
        `product_sku` VARCHAR(80) DEFAULT NULL,
        `product_name` VARCHAR(200) DEFAULT NULL,
        `paper` VARCHAR(40) DEFAULT NULL,
        `category` VARCHAR(80) DEFAULT NULL,
        `confidence` TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `summary_json` MEDIUMTEXT,
        `analyst_notes` TEXT,
        `uploaded_by` INT UNSIGNED DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`profile_id`) REFERENCES `format_profiles`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        INDEX `idx_format_analyses_project` (`project_id`, `created_at`),
        INDEX `idx_format_analyses_hash` (`project_id`, `file_hash`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
