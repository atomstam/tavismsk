-- Add member type "general" with free-text position

ALTER TABLE `users`
  MODIFY COLUMN `member_type` ENUM('staff','student','general') NOT NULL DEFAULT 'staff';

ALTER TABLE `users`
  ADD COLUMN `position_text` VARCHAR(120) NULL DEFAULT NULL AFTER `personal_type_id`;

ALTER TABLE `sc_prefixes`
  MODIFY COLUMN `audience` ENUM('staff','student','general','both') NOT NULL DEFAULT 'staff';

-- Common civilian titles for general members
UPDATE `sc_prefixes` SET `audience` = 'both' WHERE `title` IN ('นาย', 'นาง', 'นางสาว', 'Mr.', 'Miss.');
