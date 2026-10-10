-- Keep prefixes suitable for secondary-school staff and students (ม.1–ม.6).
-- Remove primary-school child titles and university academic ranks.

DELETE FROM `sc_prefixes`
WHERE `title` IN (
  'เด็กชาย',
  'เด็กหญิง',
  'อาจารย์',
  'ผศ.',
  'รศ.',
  'ศ.',
  'ผศ.ดร.',
  'อาจารย์ ดร.'
);

-- Secondary students use นาย / นางสาว (and English equivalents)
UPDATE `sc_prefixes`
SET `audience` = 'both'
WHERE `title` IN ('นาย', 'นาง', 'นางสาว', 'Mr.', 'Miss.');
