-- Label-UP Database Schema
-- charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ── 사용자 ──
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin','pm','designer','developer','qa','viewer') NOT NULL DEFAULT 'developer',
    `avatar_color` VARCHAR(7) DEFAULT '#6366f1',
    `phone` VARCHAR(20) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 프로젝트 ──
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `client_name` VARCHAR(200) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `status` ENUM('planning','active','review','completed','on_hold') NOT NULL DEFAULT 'planning',
    `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `current_phase` ENUM('planning','storyboard','publishing','coding','review','launch') DEFAULT NULL,
    `phase_mode` ENUM('auto','manual') NOT NULL DEFAULT 'auto',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 프로젝트 참여자 ──
CREATE TABLE IF NOT EXISTS `project_members` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `role` ENUM('owner','pm','designer','developer','qa','viewer') NOT NULL DEFAULT 'developer',
    `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_project_user` (`project_id`, `user_id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 다단계 메뉴 ──
CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) DEFAULT NULL,
    `description` TEXT,
    `sort_order` INT NOT NULL DEFAULT 0,
    `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `menu_code` VARCHAR(50) DEFAULT NULL COMMENT '계층 메뉴코드 (예: 01-01-03)',
    `url_path` VARCHAR(500) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE,
    INDEX `idx_project_parent` (`project_id`, `parent_id`),
    INDEX `idx_menu_code` (`project_id`, `menu_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 메뉴별 진행상황 ──
CREATE TABLE IF NOT EXISTS `menu_progress` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `menu_id` INT UNSIGNED NOT NULL UNIQUE,
    `storyboard_status` ENUM('pending','in_progress','done','na') NOT NULL DEFAULT 'pending',
    `publishing_status` ENUM('pending','in_progress','done','na') NOT NULL DEFAULT 'pending',
    `coding_status` ENUM('pending','in_progress','done','na') NOT NULL DEFAULT 'pending',
    `review_status` ENUM('pending','in_progress','done','na') NOT NULL DEFAULT 'pending',
    `storyboard_note` TEXT,
    `publishing_note` TEXT,
    `coding_note` TEXT,
    `review_note` TEXT,
    `general_note` TEXT,
    `assignee_id` INT UNSIGNED DEFAULT NULL,
    `due_date` DATE DEFAULT NULL,
    `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `updated_at` DATETIME NULL,
    FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assignee_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 스토리보드 ──
CREATE TABLE IF NOT EXISTS `storyboards` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `menu_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `version` VARCHAR(20) DEFAULT '1.0',
    `status` ENUM('draft','review','approved') NOT NULL DEFAULT 'draft',
    `visibility` ENUM('working','public') NOT NULL DEFAULT 'working',
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 스토리보드 화면(프레임) ──
CREATE TABLE IF NOT EXISTS `storyboard_frames` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `storyboard_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `image_path` VARCHAR(500) DEFAULT NULL,
    `wireframe_data` TEXT DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `notes` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    FOREIGN KEY (`storyboard_id`) REFERENCES `storyboards`(`id`) ON DELETE CASCADE,
    INDEX `idx_storyboard_order` (`storyboard_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 스토리보드 의견 ──
CREATE TABLE IF NOT EXISTS `storyboard_comments` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 스토리보드 변경 이력 ──
CREATE TABLE IF NOT EXISTS `storyboard_history` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 이슈/버그 트래킹 ──
CREATE TABLE IF NOT EXISTS `issues` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `menu_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(300) NOT NULL,
    `description` TEXT,
    `type` ENUM('bug','feature','improvement','task','question') NOT NULL DEFAULT 'task',
    `status` ENUM('open','in_progress','resolved','closed','wont_fix') NOT NULL DEFAULT 'open',
    `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `reporter_id` INT UNSIGNED DEFAULT NULL,
    `assignee_id` INT UNSIGNED DEFAULT NULL,
    `due_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    `closed_at` DATETIME DEFAULT NULL,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assignee_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 이슈 댓글 ──
CREATE TABLE IF NOT EXISTS `issue_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `issue_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`issue_id`) REFERENCES `issues`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 일정(간트) 작업 ──
CREATE TABLE IF NOT EXISTS `schedule_tasks` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 마일스톤 ──
CREATE TABLE IF NOT EXISTS `milestones` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `due_date` DATE NOT NULL,
    `status` ENUM('upcoming','in_progress','completed','overdue') NOT NULL DEFAULT 'upcoming',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 활동 로그 ──
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_project_created` (`project_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 파일 첨부 ──
CREATE TABLE IF NOT EXISTS `attachments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `entity_type` ENUM('menu','issue','storyboard','frame','general') NOT NULL DEFAULT 'general',
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `uploaded_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 공지/메모 ──
CREATE TABLE IF NOT EXISTS `notices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(300) NOT NULL,
    `content` TEXT NOT NULL,
    `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 자료실 ──
CREATE TABLE IF NOT EXISTS `archive_documents` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
