-- เปลี่ยนข้อความแบรนด์: พัฒนาความรู้และทักษะ → พัฒนาสมรรถนะ
-- (เช่น เพื่อพัฒนาความรู้และทักษะ → เพื่อพัฒนาสมรรถนะ)

UPDATE `settings`
SET `setting_value` = REPLACE(`setting_value`, 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ')
WHERE `setting_value` LIKE '%พัฒนาความรู้และทักษะ%';

UPDATE `pages`
SET
  `content` = REPLACE(`content`, 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ'),
  `title` = REPLACE(`title`, 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ'),
  `seo_title` = REPLACE(IFNULL(`seo_title`, ''), 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ')
WHERE `content` LIKE '%พัฒนาความรู้และทักษะ%'
   OR `title` LIKE '%พัฒนาความรู้และทักษะ%'
   OR IFNULL(`seo_title`, '') LIKE '%พัฒนาความรู้และทักษะ%';

UPDATE `news`
SET
  `title` = REPLACE(`title`, 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ'),
  `excerpt` = REPLACE(IFNULL(`excerpt`, ''), 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ'),
  `content` = REPLACE(IFNULL(`content`, ''), 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ')
WHERE `title` LIKE '%พัฒนาความรู้และทักษะ%'
   OR IFNULL(`excerpt`, '') LIKE '%พัฒนาความรู้และทักษะ%'
   OR IFNULL(`content`, '') LIKE '%พัฒนาความรู้และทักษะ%';

UPDATE `announcements`
SET
  `title` = REPLACE(`title`, 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ'),
  `body` = REPLACE(`body`, 'พัฒนาความรู้และทักษะ', 'พัฒนาสมรรถนะ')
WHERE `title` LIKE '%พัฒนาความรู้และทักษะ%'
   OR `body` LIKE '%พัฒนาความรู้และทักษะ%';
