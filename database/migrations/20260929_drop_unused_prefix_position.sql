-- Drop unused legacy Safety Center lookup tables (not referenced by TAVIS app logic).
-- Active tables kept:
--   sc_prefixes, sc_personal_types  → register / user member type
--   prefixes, positions             → personnel / club members

DROP TABLE IF EXISTS `prefix`;
DROP TABLE IF EXISTS `position`;
DROP TABLE IF EXISTS `personaltype`;
DROP TABLE IF EXISTS `sc_positions`;

-- Column mirrored from unused sc_positions; never set in UI / no data
ALTER TABLE `users` DROP COLUMN IF EXISTS `sc_position_id`;
