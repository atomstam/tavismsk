-- Demo teachers for hero randomization (optional)
USE `tavis`;

INSERT INTO `users` (`username`,`email`,`password`,`title`,`first_name`,`last_name`,`phone`,`role`,`status`,`created_at`,`updated_at`) VALUES
('teacher01','teacher01@cyp.ac.th','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','นาย','วิรัตน์','หาดคำ','0811111111','teacher',1,NOW(),NOW()),
('teacher02','teacher02@cyp.ac.th','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','นาย','สมชาย','ใจดี','0822222222','teacher',1,NOW(),NOW()),
('teacher03','teacher03@cyp.ac.th','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','นางสาว','น้ำใส','วงศ์ดี','0833333333','teacher',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `role`='teacher', `status`=1;

INSERT INTO `personnel` (`user_id`,`code`,`title`,`first_name`,`last_name`,`position_id`,`academic_rank_id`,`department_id`,`phone`,`email`,`photo`,`status`)
SELECT u.id, CONCAT('T00', u.id), u.title, u.first_name, u.last_name, 3, 2, 2, u.phone, u.email,
  CASE u.username WHEN 'teacher01' THEN 'personnel/t1.jpg' WHEN 'teacher02' THEN 'personnel/t2.jpg' ELSE 'personnel/t3.jpg' END, 1
FROM users u
WHERE u.username IN ('teacher01','teacher02','teacher03')
  AND NOT EXISTS (SELECT 1 FROM personnel p WHERE p.user_id = u.id);

UPDATE personnel p
JOIN users u ON u.id = p.user_id
SET p.photo = CASE u.username
  WHEN 'teacher01' THEN 'personnel/t1.jpg'
  WHEN 'teacher02' THEN 'personnel/t2.jpg'
  ELSE 'personnel/t3.jpg' END
WHERE u.username IN ('teacher01','teacher02','teacher03');

UPDATE users u
JOIN personnel p ON p.user_id = u.id
SET u.personnel_id = p.id, u.avatar = p.photo
WHERE u.username IN ('teacher01','teacher02','teacher03');
