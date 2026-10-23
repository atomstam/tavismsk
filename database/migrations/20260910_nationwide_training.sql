-- TAVIS nationwide training platform migration
-- Roles: admin / trainer / user
-- Org hierarchy: ministry → agency → area → school
-- Enrollment → Assessment → Hours → Certificate

USE `tavis`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `org_units` (
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

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'org_unit_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `users` ADD COLUMN `org_unit_id` INT UNSIGNED NULL AFTER `department_id`, ADD KEY `idx_users_org` (`org_unit_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'club_members' AND COLUMN_NAME = 'user_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `club_members` ADD COLUMN `user_id` INT UNSIGNED NULL AFTER `id`, ADD COLUMN `org_unit_id` INT UNSIGNED NULL AFTER `user_id`, ADD KEY `idx_cm_user` (`user_id`), ADD KEY `idx_cm_org` (`org_unit_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `course_enrollments` (
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

CREATE TABLE IF NOT EXISTS `module_assessments` (
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

CREATE TABLE IF NOT EXISTS `training_hour_logs` (
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

CREATE TABLE IF NOT EXISTS `certificates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cert_no` VARCHAR(60) NOT NULL,
  `verify_code` VARCHAR(64) NOT NULL,
  `enrollment_id` INT UNSIGNED NOT NULL,
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
  CONSTRAINT `fk_cert_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cert_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `org_units` (`id`,`code`,`name`,`name_en`,`org_type`,`parent_id`,`province`,`color`,`sort_order`,`status`) VALUES
(1,'MOE','กระทรวงศึกษาธิการ','Ministry of Education','ministry',NULL,'กรุงเทพมหานคร','#6b21a8',1,1),
(2,'OBEC','สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน','OBEC','agency',1,'กรุงเทพมหานคร','#7c3aed',2,1),
(3,'SPMMSK','สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม','Secondary ESAO Maha Sarakham','area',2,'มหาสารคาม','#f59e0b',3,1),
(4,'CYP','โรงเรียนเชียงยืนพิทยาคม','Chiang Yuen Pittayakom School','school',3,'มหาสารคาม','#fbbf24',4,1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

UPDATE `settings` SET `setting_value` = 'TAVIS – ระบบอบรมวัคซีนจราจร' WHERE `setting_key` = 'site_name';
UPDATE `settings` SET `setting_value` = 'TAVIS ระบบอบรมพัฒนาสมรรถนะ' WHERE `setting_key` = 'site_nav_title';
UPDATE `settings` SET `setting_value` = 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม' WHERE `setting_key` = 'org_name';
UPDATE `settings` SET `setting_value` = 'Secondary Educational Service Area Office Maha Sarakham' WHERE `setting_key` = 'org_name_en';
UPDATE `settings` SET `setting_value` = 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน' WHERE `setting_key` = 'org_affiliation';
UPDATE `settings` SET `setting_value` = '134 ถนนเลี่ยงเมืองมหาสารคาม-ร้อยเอ็ด ตำบลแวงน่าง อำเภอเมือง จังหวัดมหาสารคาม 44000' WHERE `setting_key` = 'org_address';
UPDATE `settings` SET `setting_value` = '085-537-4109' WHERE `setting_key` = 'org_phone';
UPDATE `settings` SET `setting_value` = 'tavis@spmmsk.go.th' WHERE `setting_key` = 'org_email';
UPDATE `settings` SET `setting_value` = 'สพม.มหาสารคาม' WHERE `setting_key` = 'developer_name';
UPDATE `settings` SET `setting_value` = 'จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม' WHERE `setting_key` = 'footer_credit';

INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('system_purpose','ระบบอบรมพัฒนาบุคลากรด้านความปลอดภัยทางถนน ใช้งานได้ทั่วประเทศ'),
('pass_score','60'),
('required_hours','14'),
('attendance_percent','80'),
('certificate_prefix','TAVIS')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);

INSERT INTO `users` (`username`,`email`,`password`,`title`,`first_name`,`last_name`,`phone`,`role`,`org_unit_id`,`status`,`created_at`,`updated_at`)
SELECT 'trainer','trainer@spmmsk.go.th','$2y$10$B3heFn/E1r0U64Y0AR7BNO9mjaiMN2yWInwR2uy4StHZz1l.9JM.W','นาย','วิทยากร','ตัวอย่าง','043000001','trainer',3,1,NOW(),NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='trainer');

INSERT INTO `users` (`username`,`email`,`password`,`title`,`first_name`,`last_name`,`phone`,`role`,`org_unit_id`,`status`,`created_at`,`updated_at`)
SELECT 'user01','user01@school.ac.th','$2y$10$B3heFn/E1r0U64Y0AR7BNO9mjaiMN2yWInwR2uy4StHZz1l.9JM.W','นาย','ผู้เข้ารับ','การพัฒนา','043000002','user',4,1,NOW(),NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='user01');

UPDATE `users` SET `org_unit_id` = 3 WHERE `username` = 'admin' AND (`org_unit_id` IS NULL OR `org_unit_id` = 0);
