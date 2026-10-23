-- Replace พัฒนาคน → พัฒนาสมรรถนะ
UPDATE `settings` SET `setting_value` = REPLACE(`setting_value`, 'พัฒนาคน', 'พัฒนาสมรรถนะ') WHERE `setting_value` LIKE '%พัฒนาคน%';
UPDATE `pages` SET
  `content` = REPLACE(`content`, 'พัฒนาคน', 'พัฒนาสมรรถนะ'),
  `title` = REPLACE(`title`, 'พัฒนาคน', 'พัฒนาสมรรถนะ'),
  `seo_title` = REPLACE(IFNULL(`seo_title`, ''), 'พัฒนาคน', 'พัฒนาสมรรถนะ')
WHERE `content` LIKE '%พัฒนาคน%' OR `title` LIKE '%พัฒนาคน%' OR IFNULL(`seo_title`, '') LIKE '%พัฒนาคน%';
UPDATE `news` SET
  `title` = REPLACE(`title`, 'พัฒนาคน', 'พัฒนาสมรรถนะ'),
  `excerpt` = REPLACE(IFNULL(`excerpt`, ''), 'พัฒนาคน', 'พัฒนาสมรรถนะ'),
  `content` = REPLACE(IFNULL(`content`, ''), 'พัฒนาคน', 'พัฒนาสมรรถนะ')
WHERE `title` LIKE '%พัฒนาคน%' OR IFNULL(`excerpt`, '') LIKE '%พัฒนาคน%' OR IFNULL(`content`, '') LIKE '%พัฒนาคน%';
UPDATE `announcements` SET
  `title` = REPLACE(`title`, 'พัฒนาคน', 'พัฒนาสมรรถนะ'),
  `body` = REPLACE(`body`, 'พัฒนาคน', 'พัฒนาสมรรถนะ')
WHERE `title` LIKE '%พัฒนาคน%' OR `body` LIKE '%พัฒนาคน%';
