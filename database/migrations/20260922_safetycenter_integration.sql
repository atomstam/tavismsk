-- Safety Center master data integration (nationwide org + registration)
-- Source dump: db/safetycenter-2.sql

ALTER TABLE `org_units`
  ADD COLUMN `external_id` VARCHAR(40) DEFAULT NULL AFTER `code`,
  ADD COLUMN `cluster_code` VARCHAR(10) DEFAULT NULL AFTER `province`,
  ADD COLUMN `latitude` VARCHAR(40) DEFAULT NULL AFTER `address`,
  ADD COLUMN `longitude` VARCHAR(40) DEFAULT NULL AFTER `latitude`;

ALTER TABLE `org_units`
  ADD KEY `idx_org_external` (`external_id`),
  ADD KEY `idx_org_cluster` (`cluster_code`);

ALTER TABLE `users`
  ADD COLUMN `personal_type_id` INT UNSIGNED DEFAULT NULL AFTER `org_unit_id`,
  ADD COLUMN `sc_position_id` INT UNSIGNED DEFAULT NULL AFTER `personal_type_id`;

ALTER TABLE `users`
  ADD KEY `idx_users_org` (`org_unit_id`),
  ADD KEY `idx_users_ptype` (`personal_type_id`);

CREATE TABLE IF NOT EXISTS `sc_clusters` (
  `id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `short_name` VARCHAR(80) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sc_prefixes` (
  `id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(80) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sc_personal_types` (
  `id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sc_positions` (
  `id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sc_import_meta` (
  `meta_key` VARCHAR(60) NOT NULL,
  `meta_value` TEXT,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
