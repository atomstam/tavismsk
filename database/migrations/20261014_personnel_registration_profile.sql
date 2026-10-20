-- Personnel uses the same profile fields as public registration.
-- Prefer scripts/apply_personnel_profile.php (it skips the change when the column already exists).

ALTER TABLE `personnel`
  ADD COLUMN `member_type` ENUM('staff','student','general') NOT NULL DEFAULT 'staff' AFTER `title`,
  ADD COLUMN `personal_type_id` INT UNSIGNED NULL DEFAULT NULL AFTER `member_type`,
  ADD COLUMN `position_text` VARCHAR(120) NULL DEFAULT NULL AFTER `personal_type_id`,
  ADD COLUMN `org_unit_id` INT UNSIGNED NULL DEFAULT NULL AFTER `position_text`,
  ADD COLUMN `area_id` INT UNSIGNED NULL DEFAULT NULL AFTER `org_unit_id`,
  ADD COLUMN `cluster_code` VARCHAR(10) NULL DEFAULT NULL AFTER `area_id`,
  ADD COLUMN `school_code` VARCHAR(40) NULL DEFAULT NULL AFTER `cluster_code`,
  ADD COLUMN `org_name` VARCHAR(255) NULL DEFAULT NULL AFTER `school_code`,
  ADD COLUMN `affiliation` VARCHAR(255) NULL DEFAULT NULL AFTER `org_name`,
  ADD COLUMN `province_id` INT UNSIGNED NULL DEFAULT NULL AFTER `affiliation`,
  ADD COLUMN `amphur_id` INT UNSIGNED NULL DEFAULT NULL AFTER `province_id`,
  ADD COLUMN `tumbon_id` INT UNSIGNED NULL DEFAULT NULL AFTER `amphur_id`,
  ADD COLUMN `zipcode` VARCHAR(10) NULL DEFAULT NULL AFTER `tumbon_id`,
  ADD KEY `idx_personnel_member_type` (`member_type`),
  ADD KEY `idx_personnel_province` (`province_id`);
