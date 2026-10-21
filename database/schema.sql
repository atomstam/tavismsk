-- TAVIS – Traffic Vaccine Intelligent System
-- ระบบวัคซีนจราจร สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม
-- MySQL 8 / MariaDB 10.4+  utf8mb4
-- หมายเหตุ: ข้อมูลภูมิศาสตร์ (province/amphur/tumbon/school) อยู่ที่ db/tv.sql — นำเข้าทางเลือก แยกจาก schema นี้

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE IF NOT EXISTS `tavis` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tavis`;

-- --------------------------------------------------------
-- Master data
-- --------------------------------------------------------

CREATE TABLE `academic_ranks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `name_en` VARCHAR(100) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `academic_years` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `year_be` SMALLINT NOT NULL,
  `name` VARCHAR(40) NOT NULL,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_be` (`year_be`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `head_name` VARCHAR(200) DEFAULT NULL,
  `description` TEXT,
  `logo` VARCHAR(255) DEFAULT NULL,
  `color` VARCHAR(20) DEFAULT '#7c3aed',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(80) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `title` VARCHAR(50) DEFAULT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `role` VARCHAR(30) NOT NULL DEFAULT 'user',
  `department_id` INT UNSIGNED DEFAULT NULL,
  `org_unit_id` INT UNSIGNED DEFAULT NULL,
  `personnel_id` INT UNSIGNED DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verified_at` DATETIME DEFAULT NULL,
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `two_factor_secret` VARCHAR(64) DEFAULT NULL,
  `otp_code` VARCHAR(10) DEFAULT NULL,
  `otp_expires_at` DATETIME DEFAULT NULL,
  `remember_token` VARCHAR(64) DEFAULT NULL,
  `password_reset_token` VARCHAR(64) DEFAULT NULL,
  `password_reset_expires` DATETIME DEFAULT NULL,
  `google_id` VARCHAR(80) DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `login_attempts` INT NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `theme` VARCHAR(10) NOT NULL DEFAULT 'light',
  `locale` VARCHAR(10) NOT NULL DEFAULT 'th',
  `legacy_user_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_dept` (`department_id`),
  KEY `idx_users_org` (`org_unit_id`),
  KEY `idx_legacy` (`legacy_user_id`),
  CONSTRAINT `fk_users_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personnel` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `code` VARCHAR(40) DEFAULT NULL,
  `id_card` VARCHAR(20) DEFAULT NULL,
  `title` VARCHAR(50) DEFAULT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,
  `academic_rank_id` INT UNSIGNED DEFAULT NULL,
  `salary_step` VARCHAR(20) DEFAULT NULL,
  `salary` DECIMAL(10,2) DEFAULT NULL,
  `level_name` VARCHAR(80) DEFAULT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `work_group` VARCHAR(120) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `line_id` VARCHAR(80) DEFAULT NULL,
  `facebook` VARCHAR(150) DEFAULT NULL,
  `address` TEXT,
  `education` TEXT,
  `work_history` TEXT,
  `training_history` TEXT,
  `photo` VARCHAR(255) DEFAULT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_personnel_user` (`user_id`),
  KEY `idx_code` (`code`),
  KEY `idx_idcard` (`id_card`),
  KEY `idx_name` (`first_name`,`last_name`),
  CONSTRAINT `fk_personnel_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_personnel_rank` FOREIGN KEY (`academic_rank_id`) REFERENCES `academic_ranks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_personnel_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Portfolio (กิจกรรมชมรม / TAVIS modules)
-- --------------------------------------------------------

CREATE TABLE `portfolio_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT,
  `organization` VARCHAR(255) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `role_name` VARCHAR(150) DEFAULT NULL,
  `category` VARCHAR(150) DEFAULT NULL,
  `status_name` VARCHAR(80) DEFAULT NULL,
  `level_name` VARCHAR(80) DEFAULT NULL,
  `group_name` VARCHAR(150) DEFAULT NULL,
  `instance_no` VARCHAR(40) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `hours` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `academic_year` SMALLINT DEFAULT NULL,
  `year_be` SMALLINT DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 0,
  `extra` JSON DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_user` (`type`,`user_id`),
  KEY `idx_year` (`academic_year`),
  KEY `idx_date` (`start_date`),
  KEY `idx_featured` (`is_featured`,`is_public`),
  KEY `idx_public` (`is_public`,`views`),
  CONSTRAINT `fk_pf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `portfolio_files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT UNSIGNED NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(100) DEFAULT NULL,
  `file_size` INT UNSIGNED DEFAULT 0,
  `kind` ENUM('file','photo','signature') NOT NULL DEFAULT 'file',
  `caption` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_item` (`item_id`),
  CONSTRAINT `fk_pf_file` FOREIGN KEY (`item_id`) REFERENCES `portfolio_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Forms
-- --------------------------------------------------------

CREATE TABLE `forms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_form_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `form_fields` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(200) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `options` TEXT,
  `required` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_form` (`form_id`),
  CONSTRAINT `fk_ffield_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `form_submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `payload` JSON NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_form_sub` (`form_id`),
  CONSTRAINT `fk_fsub_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tickets / diary
-- --------------------------------------------------------

CREATE TABLE `tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_no` VARCHAR(30) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('open','in_progress','replied','closed') NOT NULL DEFAULT 'open',
  `priority` ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ticket_no` (`ticket_no`),
  KEY `idx_ticket_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ticket_replies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_treply` (`ticket_id`),
  CONSTRAINT `fk_treply` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ticket_files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tfile` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- CMS / website
-- --------------------------------------------------------

CREATE TABLE `pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(120) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `content` MEDIUMTEXT,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `seo_title` VARCHAR(200) DEFAULT NULL,
  `seo_description` VARCHAR(300) DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `news` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `excerpt` TEXT,
  `content` MEDIUMTEXT,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT 'กิจกรรม',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `published_at` DATETIME DEFAULT NULL,
  `views` INT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faqs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `downloads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT NULL,
  `downloads` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `subject` VARCHAR(200) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Notifications / logs / settings
-- --------------------------------------------------------

CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT,
  `type` VARCHAR(30) DEFAULT 'info',
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `username` VARCHAR(80) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL,
  `message` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_user` (`user_id`),
  KEY `idx_login_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `module` VARCHAR(50) DEFAULT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `old_data` JSON DEFAULT NULL,
  `new_data` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_act_user` (`user_id`),
  KEY `idx_act_mod` (`module`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(80) NOT NULL,
  `setting_value` TEXT,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `api_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `name` VARCHAR(80) DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `visitors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `path` VARCHAR(255) DEFAULT NULL,
  `visited_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vis_date` (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `backups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED DEFAULT 0,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Gallery (แกลเลอรี่กิจกรรม)
-- --------------------------------------------------------

CREATE TABLE `gallery_albums` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `event_date` DATE DEFAULT NULL,
  `academic_year` SMALLINT DEFAULT NULL,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gallery_slug` (`slug`),
  KEY `idx_gallery_user` (`user_id`),
  KEY `idx_gallery_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gallery_media` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `album_id` INT UNSIGNED NOT NULL,
  `media_type` ENUM('image','video') NOT NULL DEFAULT 'image',
  `file_path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_album` (`album_id`),
  CONSTRAINT `fk_gallery_media` FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- TAVIS Curriculum / Club
-- --------------------------------------------------------

CREATE TABLE `learning_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_no` INT UNSIGNED NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT,
  `hours` DECIMAL(4,1) NOT NULL DEFAULT 1,
  `instructor_type` VARCHAR(40) DEFAULT NULL,
  `instructor_label` VARCHAR(120) DEFAULT NULL,
  `topic_group` VARCHAR(200) DEFAULT NULL,
  `objectives` TEXT,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_no` (`plan_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `learning_resources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `resource_type` ENUM('video','link','pdf','knowledge','other') NOT NULL DEFAULT 'link',
  `url` VARCHAR(500) DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `description` TEXT,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lr_plan` (`plan_id`),
  CONSTRAINT `fk_lr_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `worksheets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ws_plan` (`plan_id`),
  CONSTRAINT `fk_ws_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `role` VARCHAR(30) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role`),
  KEY `idx_user_roles_role` (`role`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `learning_plan_owners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `note` VARCHAR(255) DEFAULT NULL,
  `reviewed_by` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_owner` (`plan_id`, `user_id`),
  KEY `idx_plan_owner_status` (`status`),
  CONSTRAINT `fk_plan_owner_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_owner_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_chat_threads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `thread_type` ENUM('group','direct') NOT NULL DEFAULT 'direct',
  `learner_id` INT UNSIGNED DEFAULT NULL,
  `trainer_id` INT UNSIGNED DEFAULT NULL,
  `last_message_at` DATETIME DEFAULT NULL,
  `learner_last_read_at` DATETIME DEFAULT NULL,
  `trainer_last_read_at` DATETIME DEFAULT NULL,
  `group_slot` TINYINT UNSIGNED GENERATED ALWAYS AS (IF(`thread_type` = 'group', 1, NULL)) STORED,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_chat_group` (`plan_id`, `group_slot`),
  UNIQUE KEY `uk_plan_chat_direct` (`plan_id`, `learner_id`, `trainer_id`),
  KEY `idx_plan_chat_learner` (`learner_id`),
  KEY `idx_plan_chat_trainer` (`trainer_id`),
  KEY `idx_plan_chat_last` (`last_message_at`),
  KEY `idx_plan_chat_type` (`plan_id`, `thread_type`),
  CONSTRAINT `fk_plan_chat_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_chat_learner` FOREIGN KEY (`learner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_chat_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_chat_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` INT UNSIGNED NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_plan_chat_msg_thread` (`thread_id`, `id`),
  KEY `idx_plan_chat_msg_sender` (`sender_id`),
  CONSTRAINT `fk_plan_chat_msg_thread` FOREIGN KEY (`thread_id`) REFERENCES `plan_chat_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_chat_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_chat_reads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `last_read_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_chat_read` (`thread_id`, `user_id`),
  CONSTRAINT `fk_plan_chat_read_thread` FOREIGN KEY (`thread_id`) REFERENCES `plan_chat_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_chat_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `club_members` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `org_unit_id` INT UNSIGNED DEFAULT NULL,
  `personnel_id` INT UNSIGNED DEFAULT NULL,
  `student_code` VARCHAR(40) DEFAULT NULL,
  `id_card` VARCHAR(20) DEFAULT NULL,
  `title` VARCHAR(50) DEFAULT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,
  `grade_level` VARCHAR(20) DEFAULT NULL,
  `room` VARCHAR(20) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `academic_year` SMALLINT DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cm_code` (`student_code`),
  KEY `idx_cm_name` (`first_name`,`last_name`),
  KEY `idx_cm_year` (`academic_year`),
  KEY `idx_cm_personnel` (`personnel_id`),
  CONSTRAINT `fk_cm_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `learning_progress` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','in_progress','completed','absent') NOT NULL DEFAULT 'pending',
  `attended_at` DATETIME DEFAULT NULL,
  `score` DECIMAL(5,2) DEFAULT NULL,
  `note` TEXT,
  `recorded_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_plan` (`member_id`,`plan_id`),
  KEY `idx_lp_plan` (`plan_id`),
  CONSTRAINT `fk_lp_member` FOREIGN KEY (`member_id`) REFERENCES `club_members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lp_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `learning_objectives` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `objective_no` INT UNSIGNED NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_objective_no` (`objective_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Nationwide org + training lifecycle
-- --------------------------------------------------------

CREATE TABLE `org_units` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) DEFAULT NULL,
  `org_type` ENUM('ministry','agency','area','school') NOT NULL,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `province` VARCHAR(120) DEFAULT NULL,
  `address` TEXT,
  `phone` VARCHAR(40) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `color` VARCHAR(20) DEFAULT '#7c3aed',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_org_type` (`org_type`),
  KEY `idx_org_parent` (`parent_id`),
  KEY `idx_org_code` (`code`),
  CONSTRAINT `fk_org_parent` FOREIGN KEY (`parent_id`) REFERENCES `org_units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `course_enrollments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_no` VARCHAR(40) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `org_unit_id` INT UNSIGNED DEFAULT NULL,
  `member_id` INT UNSIGNED DEFAULT NULL,
  `academic_year` SMALLINT DEFAULT NULL,
  `status` ENUM('pending','approved','in_progress','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `registered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` DATETIME DEFAULT NULL,
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `started_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `total_hours` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `required_hours` DECIMAL(8,2) NOT NULL DEFAULT 14,
  `attendance_percent` DECIMAL(5,2) DEFAULT NULL,
  `final_score` DECIMAL(5,2) DEFAULT NULL,
  `pass_score` DECIMAL(5,2) NOT NULL DEFAULT 60,
  `is_passed` TINYINT(1) NOT NULL DEFAULT 0,
  `note` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_enroll_no` (`enrollment_no`),
  KEY `idx_enroll_user` (`user_id`),
  KEY `idx_enroll_org` (`org_unit_id`),
  KEY `idx_enroll_status` (`status`),
  CONSTRAINT `fk_enroll_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enroll_org` FOREIGN KEY (`org_unit_id`) REFERENCES `org_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_enroll_member` FOREIGN KEY (`member_id`) REFERENCES `club_members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module_assessments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `score` DECIMAL(5,2) DEFAULT NULL,
  `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100,
  `is_passed` TINYINT(1) NOT NULL DEFAULT 0,
  `hours_earned` DECIMAL(4,1) NOT NULL DEFAULT 0,
  `assessed_at` DATETIME DEFAULT NULL,
  `assessed_by` INT UNSIGNED DEFAULT NULL,
  `note` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_enroll_plan` (`enrollment_id`,`plan_id`),
  KEY `idx_ma_plan` (`plan_id`),
  CONSTRAINT `fk_ma_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ma_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `training_hour_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `enrollment_id` INT UNSIGNED DEFAULT NULL,
  `plan_id` INT UNSIGNED DEFAULT NULL,
  `hours` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `source` VARCHAR(40) NOT NULL DEFAULT 'module',
  `description` VARCHAR(500) DEFAULT NULL,
  `recorded_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_th_user` (`user_id`),
  KEY `idx_th_enroll` (`enrollment_id`),
  CONSTRAINT `fk_th_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_th_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `certificates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cert_no` VARCHAR(60) NOT NULL,
  `verify_code` VARCHAR(64) NOT NULL,
  `enrollment_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED DEFAULT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `org_unit_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `recipient_name` VARCHAR(200) NOT NULL,
  `org_name` VARCHAR(255) DEFAULT NULL,
  `hours` DECIMAL(8,2) NOT NULL DEFAULT 14,
  `final_score` DECIMAL(5,2) DEFAULT NULL,
  `issued_at` DATETIME NOT NULL,
  `issued_by` INT UNSIGNED DEFAULT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','revoked') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cert_no` (`cert_no`),
  UNIQUE KEY `uk_verify` (`verify_code`),
  KEY `idx_cert_user` (`user_id`),
  KEY `idx_cert_enroll` (`enrollment_id`),
  KEY `idx_cert_plan` (`plan_id`),
  CONSTRAINT `fk_cert_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cert_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cert_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ===== Module Learning LMS (Pretest/Content/Task/Posttest) =====
-- TAVIS Module Learning LMS: Pretest → Content → Tasks → Posttest (+ progress 100%)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `training_plan_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `weight_pretest` DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  `weight_content` DECIMAL(5,2) NOT NULL DEFAULT 40.00,
  `weight_task` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
  `weight_posttest` DECIMAL(5,2) NOT NULL DEFAULT 25.00,
  `require_pretest` TINYINT(1) NOT NULL DEFAULT 1,
  `require_pass_posttest` TINYINT(1) NOT NULL DEFAULT 1,
  `gate_content_after_pretest` TINYINT(1) NOT NULL DEFAULT 1,
  `gate_posttest_after_content` TINYINT(1) NOT NULL DEFAULT 1,
  `quiz_draw_count` INT UNSIGNED NOT NULL DEFAULT 10,
  `quiz_shuffle` TINYINT(1) NOT NULL DEFAULT 1,
  `pass_percent` DECIMAL(5,2) NOT NULL DEFAULT 80.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tps_plan` (`plan_id`),
  CONSTRAINT `fk_tps_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_quizzes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `quiz_type` ENUM('pretest','posttest','satisfaction') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `pass_score` DECIMAL(5,2) NOT NULL DEFAULT 60.00,
  `time_limit_minutes` INT UNSIGNED DEFAULT NULL,
  `max_attempts` INT UNSIGNED NOT NULL DEFAULT 3,
  `draw_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `shuffle_questions` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `is_central` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_quiz_plan_type` (`plan_id`,`quiz_type`),
  CONSTRAINT `fk_quiz_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_quiz_questions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quiz_id` INT UNSIGNED NOT NULL,
  `question_text` TEXT NOT NULL,
  `image_path` VARCHAR(500) DEFAULT NULL,
  `question_type` ENUM('single','true_false') NOT NULL DEFAULT 'single',
  `points` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `explanation` TEXT,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tqq_quiz` (`quiz_id`),
  CONSTRAINT `fk_tqq_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `training_quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_quiz_choices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED NOT NULL,
  `choice_text` VARCHAR(1000) NOT NULL,
  `image_path` VARCHAR(500) DEFAULT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_tqc_q` (`question_id`),
  CONSTRAINT `fk_tqc_q` FOREIGN KEY (`question_id`) REFERENCES `training_quiz_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_quiz_papers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `enrollment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `question_ids` JSON NOT NULL,
  `post_question_ids` JSON NOT NULL,
  `choice_orders` JSON NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_quiz_paper` (`plan_id`,`enrollment_id`),
  CONSTRAINT `fk_paper_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_paper_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_quiz_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quiz_id` INT UNSIGNED NOT NULL,
  `enrollment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `attempt_no` INT UNSIGNED NOT NULL DEFAULT 1,
  `score` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `max_score` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `is_passed` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('in_progress','submitted','expired') NOT NULL DEFAULT 'in_progress',
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tqa_quiz_user` (`quiz_id`,`user_id`),
  KEY `idx_tqa_enroll` (`enrollment_id`),
  CONSTRAINT `fk_tqa_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `training_quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tqa_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tqa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_quiz_answers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` INT UNSIGNED NOT NULL,
  `question_id` INT UNSIGNED NOT NULL,
  `choice_id` INT UNSIGNED DEFAULT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `points_earned` DECIMAL(5,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ans_attempt_q` (`attempt_id`,`question_id`),
  CONSTRAINT `fk_ans_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `training_quiz_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ans_q` FOREIGN KEY (`question_id`) REFERENCES `training_quiz_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_contents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `content_type` ENUM('pdf','word','excel','gdrive','youtube','link','text') NOT NULL DEFAULT 'link',
  `file_path` VARCHAR(500) DEFAULT NULL,
  `external_url` VARCHAR(1000) DEFAULT NULL,
  `body_text` MEDIUMTEXT,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tc_plan` (`plan_id`),
  CONSTRAINT `fk_tc_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_content_views` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `content_id` INT UNSIGNED NOT NULL,
  `enrollment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tcv` (`content_id`,`enrollment_id`),
  KEY `idx_tcv_user` (`user_id`),
  CONSTRAINT `fk_tcv_content` FOREIGN KEY (`content_id`) REFERENCES `training_contents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tcv_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tcv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `instructions` TEXT,
  `require_file` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_link` TINYINT(1) NOT NULL DEFAULT 1,
  `require_review` TINYINT(1) NOT NULL DEFAULT 1,
  `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `pass_score` DECIMAL(5,2) NOT NULL DEFAULT 60.00,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tt_plan` (`plan_id`),
  CONSTRAINT `fk_tt_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_task_submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT UNSIGNED NOT NULL,
  `enrollment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `answer_text` TEXT,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `link_url` VARCHAR(1000) DEFAULT NULL,
  `status` ENUM('draft','submitted','approved','rejected','revision') NOT NULL DEFAULT 'submitted',
  `score` DECIMAL(5,2) DEFAULT NULL,
  `feedback` TEXT,
  `reviewed_by` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `submitted_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tts` (`task_id`,`enrollment_id`),
  KEY `idx_tts_user` (`user_id`),
  CONSTRAINT `fk_tts_task` FOREIGN KEY (`task_id`) REFERENCES `training_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tts_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_module_progress` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `pretest_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `content_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `task_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `posttest_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `overall_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `is_passed` TINYINT(1) NOT NULL DEFAULT 0,
  `pretest_done` TINYINT(1) NOT NULL DEFAULT 0,
  `content_done` TINYINT(1) NOT NULL DEFAULT 0,
  `task_done` TINYINT(1) NOT NULL DEFAULT 0,
  `posttest_done` TINYINT(1) NOT NULL DEFAULT 0,
  `stage` ENUM('pretest','content','posttest','completed') NOT NULL DEFAULT 'pretest',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tmp` (`enrollment_id`,`plan_id`),
  KEY `idx_tmp_user` (`user_id`),
  CONSTRAINT `fk_tmp_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tmp_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tmp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings for all existing plans
INSERT INTO `training_plan_settings` (`plan_id`)
SELECT `id` FROM `learning_plans`
WHERE `id` NOT IN (SELECT `plan_id` FROM `training_plan_settings`)
ON DUPLICATE KEY UPDATE `plan_id` = `plan_id`;
