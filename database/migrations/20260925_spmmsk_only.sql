-- Keep only สพม.มหาสารคาม (area_id 27 / org_units id 31) and its schools.
-- Re-point the demo user off the old seed school before deleting it.

UPDATE `users`
SET `org_unit_id` = 1479,
    `area_id` = 31,
    `school_code` = '1044410601',
    `cluster_code` = '13'
WHERE `id` = 3 AND `org_unit_id` = 4;

DELETE FROM `school`
WHERE NOT (`sch_area` = '1000440001' OR `sch_area2` = '44000100');

DELETE FROM `area`
WHERE `area_id` <> 27;

DELETE FROM `org_units`
WHERE `org_type` = 'school' AND (`parent_id` IS NULL OR `parent_id` <> 31);

DELETE FROM `org_units`
WHERE `org_type` = 'area' AND `id` <> 31;

ALTER TABLE `users`
  ADD COLUMN `org_name` VARCHAR(255) NULL DEFAULT NULL AFTER `school_code`,
  ADD COLUMN `affiliation` VARCHAR(255) NULL DEFAULT NULL AFTER `org_name`;
