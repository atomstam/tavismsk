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
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tps_plan` (`plan_id`),
  CONSTRAINT `fk_tps_plan` FOREIGN KEY (`plan_id`) REFERENCES `learning_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_quizzes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `quiz_type` ENUM('pretest','posttest') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `pass_score` DECIMAL(5,2) NOT NULL DEFAULT 60.00,
  `time_limit_minutes` INT UNSIGNED DEFAULT NULL,
  `max_attempts` INT UNSIGNED NOT NULL DEFAULT 3,
  `shuffle_questions` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
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
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_tqc_q` (`question_id`),
  CONSTRAINT `fk_tqc_q` FOREIGN KEY (`question_id`) REFERENCES `training_quiz_questions` (`id`) ON DELETE CASCADE
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
