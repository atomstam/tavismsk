-- TAVIS: satisfaction survey stage + progress for every plan
SET NAMES utf8mb4;

ALTER TABLE `training_quizzes`
  MODIFY `quiz_type` ENUM('pretest','posttest','satisfaction') NOT NULL;

ALTER TABLE `training_plan_settings`
  ADD COLUMN `weight_satisfaction` DECIMAL(5,2) NOT NULL DEFAULT 15.00 AFTER `weight_posttest`,
  ADD COLUMN `require_satisfaction` TINYINT(1) NOT NULL DEFAULT 1 AFTER `require_pass_posttest`,
  ADD COLUMN `gate_satisfaction_after_posttest` TINYINT(1) NOT NULL DEFAULT 1 AFTER `gate_posttest_after_content`;

UPDATE `training_plan_settings`
SET
  `weight_pretest` = 10.00,
  `weight_content` = 35.00,
  `weight_task` = 15.00,
  `weight_posttest` = 25.00,
  `weight_satisfaction` = 15.00;

ALTER TABLE `training_module_progress`
  ADD COLUMN `satisfaction_percent` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `posttest_percent`,
  ADD COLUMN `satisfaction_done` TINYINT(1) NOT NULL DEFAULT 0 AFTER `posttest_done`;

ALTER TABLE `training_module_progress`
  MODIFY `stage` ENUM('pretest','content','task','posttest','satisfaction','completed') NOT NULL DEFAULT 'pretest';
