-- Demo LMS seed for first learning plan (optional)
-- Run after 20260910_module_learning_lms.sql

SET NAMES utf8mb4;
SET @plan_id = (SELECT id FROM learning_plans ORDER BY plan_no LIMIT 1);
SET @uid = (SELECT id FROM users WHERE role IN ('trainer','admin') ORDER BY id LIMIT 1);

INSERT INTO training_plan_settings (plan_id) VALUES (@plan_id)
ON DUPLICATE KEY UPDATE plan_id = plan_id;

INSERT INTO training_quizzes (plan_id, quiz_type, title, description, pass_score, max_attempts, status, created_by)
VALUES
(@plan_id, 'pretest', 'ทดสอบก่อนเรียน', 'ทดสอบความรู้ก่อนเรียน', 50, 1, 1, @uid),
(@plan_id, 'posttest', 'สอบหลังเรียน', 'ทดสอบความรู้หลังเรียน', 70, 3, 1, @uid)
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description);
