-- Member type (staff/student), position/grade, and prefix audiences for registration + CRUD

ALTER TABLE `users`
  ADD COLUMN `member_type` ENUM('staff','student') NOT NULL DEFAULT 'staff' AFTER `role`,
  ADD KEY `idx_users_member_type` (`member_type`);

ALTER TABLE `sc_personal_types`
  ADD COLUMN `kind` ENUM('staff','student') NOT NULL DEFAULT 'staff' AFTER `title`,
  ADD KEY `idx_sc_personal_types_kind` (`kind`);

ALTER TABLE `sc_prefixes`
  ADD COLUMN `audience` ENUM('staff','student','both') NOT NULL DEFAULT 'staff' AFTER `title`,
  ADD KEY `idx_sc_prefixes_audience` (`audience`);

-- Staff prefixes keep default audience=staff; common titles usable by both
UPDATE `sc_prefixes` SET `audience` = 'both' WHERE `title` IN ('นาย', 'นางสาว', 'Mr.', 'Miss.');

INSERT INTO `sc_prefixes` (`id`, `title`, `audience`) VALUES
  (16, 'เด็กชาย', 'student'),
  (17, 'เด็กหญิง', 'student')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `audience` = VALUES(`audience`);

-- Student grade levels (ม.1–ม.6) in the same lookup used for ตำแหน่ง/ระดับชั้น
INSERT INTO `sc_personal_types` (`id`, `title`, `kind`) VALUES
  (101, 'ม.1', 'student'),
  (102, 'ม.2', 'student'),
  (103, 'ม.3', 'student'),
  (104, 'ม.4', 'student'),
  (105, 'ม.5', 'student'),
  (106, 'ม.6', 'student')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `kind` = VALUES(`kind`);
