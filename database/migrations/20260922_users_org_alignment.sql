-- Align tavis.users with Safety Center org binding
-- SC: user_clus, user_area, user_school/sch_id, user_idcard, user_role(level)

ALTER TABLE `users`
  ADD COLUMN `cluster_code` VARCHAR(10) DEFAULT NULL AFTER `org_unit_id`,
  ADD COLUMN `area_id` INT UNSIGNED DEFAULT NULL AFTER `cluster_code`,
  ADD COLUMN `school_code` VARCHAR(40) DEFAULT NULL AFTER `area_id`,
  ADD COLUMN `id_card` VARCHAR(20) DEFAULT NULL AFTER `school_code`,
  ADD COLUMN `org_level` ENUM('admin','cluster','area','school','user') NOT NULL DEFAULT 'user' AFTER `id_card`;

ALTER TABLE `users`
  ADD KEY `idx_users_cluster` (`cluster_code`),
  ADD KEY `idx_users_area` (`area_id`),
  ADD KEY `idx_users_school_code` (`school_code`),
  ADD KEY `idx_users_org_level` (`org_level`),
  ADD KEY `idx_users_id_card` (`id_card`);

-- Backfill from org_units when org_unit_id already set
UPDATE users u
INNER JOIN org_units sch ON sch.id = u.org_unit_id AND sch.deleted_at IS NULL
LEFT JOIN org_units area ON area.id = CASE
    WHEN sch.org_type = 'school' THEN sch.parent_id
    WHEN sch.org_type = 'area' THEN sch.id
    ELSE NULL END AND area.deleted_at IS NULL
SET
  u.school_code = CASE WHEN sch.org_type = 'school' THEN COALESCE(sch.code, sch.external_id) ELSE u.school_code END,
  u.area_id = CASE
    WHEN sch.org_type = 'school' THEN sch.parent_id
    WHEN sch.org_type = 'area' THEN sch.id
    ELSE u.area_id END,
  u.cluster_code = COALESCE(NULLIF(sch.cluster_code,''), NULLIF(area.cluster_code,''), u.cluster_code),
  u.org_level = CASE
    WHEN u.role = 'admin' THEN 'admin'
    WHEN sch.org_type = 'area' THEN 'area'
    WHEN sch.org_type = 'school' THEN 'school'
    ELSE u.org_level END
WHERE u.deleted_at IS NULL;
