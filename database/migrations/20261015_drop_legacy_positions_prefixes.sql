-- positions and prefixes are replaced by sc_personal_types and sc_prefixes.
ALTER TABLE `personnel` DROP FOREIGN KEY `fk_personnel_pos`;
ALTER TABLE `personnel` DROP COLUMN `position_id`;
DROP TABLE IF EXISTS `positions`;
DROP TABLE IF EXISTS `prefixes`;
