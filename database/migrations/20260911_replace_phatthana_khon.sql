-- Replace พัฒนาคน → พัฒนาความรู้และทักษะ
UPDATE `settings` SET `setting_value` = REPLACE(`setting_value`, 'พัฒนาคน', 'พัฒนาความรู้และทักษะ') WHERE `setting_value` LIKE '%พัฒนาคน%';
UPDATE `pages` SET
  `content` = REPLACE(`content`, 'พัฒนาคน', 'พัฒนาความรู้และทักษะ'),
  `title` = REPLACE(`title`, 'พัฒนาคน', 'พัฒนาความรู้และทักษะ'),
  `seo_title` = REPLACE(IFNULL(`seo_title`, ''), 'พัฒนาคน', 'พัฒนาความรู้และทักษะ')
WHERE `content` LIKE '%พัฒนาคน%' OR `title` LIKE '%พัฒนาคน%' OR IFNULL(`seo_title`, '') LIKE '%พัฒนาคน%';
UPDATE `news` SET
  `title` = REPLACE(`title`, 'พัฒนาคน', 'พัฒนาความรู้และทักษะ'),
  `excerpt` = REPLACE(IFNULL(`excerpt`, ''), 'พัฒนาคน', 'พัฒนาความรู้และทักษะ'),
  `content` = REPLACE(IFNULL(`content`, ''), 'พัฒนาคน', 'พัฒนาความรู้และทักษะ')
WHERE `title` LIKE '%พัฒนาคน%' OR IFNULL(`excerpt`, '') LIKE '%พัฒนาคน%' OR IFNULL(`content`, '') LIKE '%พัฒนาคน%';
UPDATE `announcements` SET
  `title` = REPLACE(`title`, 'พัฒนาคน', 'พัฒนาความรู้และทักษะ'),
  `body` = REPLACE(`body`, 'พัฒนาคน', 'พัฒนาความรู้และทักษะ')
WHERE `title` LIKE '%พัฒนาคน%' OR `body` LIKE '%พัฒนาคน%';
