-- Address geo fields for public registration
ALTER TABLE `users`
  ADD COLUMN `province_id` INT UNSIGNED NULL DEFAULT NULL AFTER `phone`,
  ADD COLUMN `amphur_id` INT UNSIGNED NULL DEFAULT NULL AFTER `province_id`,
  ADD COLUMN `tumbon_id` INT UNSIGNED NULL DEFAULT NULL AFTER `amphur_id`,
  ADD COLUMN `zipcode` VARCHAR(10) NULL DEFAULT NULL AFTER `tumbon_id`,
  ADD KEY `idx_users_province` (`province_id`),
  ADD KEY `idx_users_amphur` (`amphur_id`),
  ADD KEY `idx_users_tumbon` (`tumbon_id`);
