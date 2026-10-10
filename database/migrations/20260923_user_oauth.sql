-- Social login: Facebook / Google / LINE
CREATE TABLE IF NOT EXISTS `user_oauth` (
  `oauth_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `provider` ENUM('facebook','google','line') NOT NULL,
  `provider_user_id` VARCHAR(128) NOT NULL,
  `email` VARCHAR(150) NULL,
  `display_name` VARCHAR(200) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`oauth_id`),
  UNIQUE KEY `uk_oauth_provider` (`provider`, `provider_user_id`),
  KEY `idx_oauth_user` (`user_id`),
  CONSTRAINT `fk_oauth_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
