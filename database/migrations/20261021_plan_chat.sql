-- กระดานสนทนาต่อแผน: ห้องรวม (group) + ตัวต่อตัว (direct)
-- ใช้กับฐานที่มีอยู่แล้ว (idempotent-ish — รันซ้ำอาจ error ถ้ามี object อยู่แล้ว)
-- แนะนำ production: php scripts/apply_plan_chat.php
-- หรือรันไฟล์นี้ด้วย:
--   /Applications/XAMPP/xamppfiles/bin/mysql -uroot tavis < database/migrations/20261021_plan_chat.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `plan_chat_threads` (
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

CREATE TABLE IF NOT EXISTS `plan_chat_messages` (
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

CREATE TABLE IF NOT EXISTS `plan_chat_reads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `last_read_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_chat_read` (`thread_id`, `user_id`),
  CONSTRAINT `fk_plan_chat_read_thread` FOREIGN KEY (`thread_id`) REFERENCES `plan_chat_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_chat_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- อัปเกรดจากเวอร์ชันแรก (มีแค่ direct / มี uk_plan_chat_pair)
-- หมายเหตุ: ถ้าตารางยังไม่มี หรืออัปเกรดครบแล้ว คำสั่งด้านล่างอาจ error — ใช้สคริปต์ PHP แทนได้

-- ALTER TABLE `plan_chat_threads`
--   ADD COLUMN IF NOT EXISTS `thread_type` ENUM('group','direct') NOT NULL DEFAULT 'direct' AFTER `plan_id`;
-- (MariaDB/MySQL บางเวอร์ชันไม่รองรับ IF NOT EXISTS ใน ADD COLUMN)

-- ขั้นตอนอัปเกรดด้วยมือเมื่อมีตารางเก่า:
-- 1) DROP FOREIGN KEY fk_plan_chat_learner, fk_plan_chat_trainer, fk_plan_chat_plan (ถ้ามี)
-- 2) DROP INDEX uk_plan_chat_pair (ถ้ามี)
-- 3) MODIFY learner_id / trainer_id เป็น NULL
-- 4) ADD thread_type, group_slot, UNIQUE uk_plan_chat_group / uk_plan_chat_direct
-- 5) ADD FOREIGN KEY กลับ
-- หรือรัน: php scripts/apply_plan_chat.php
