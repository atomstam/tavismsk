-- Branding sync: วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน
UPDATE `settings` SET `setting_value` = 'TAVIS – วัคซีนจราจร' WHERE `setting_key` = 'site_name';
UPDATE `settings` SET `setting_value` = 'TAVIS' WHERE `setting_key` = 'site_short_name';
UPDATE `settings` SET `setting_value` = 'TAVIS วัคซีนจราจร' WHERE `setting_key` = 'site_nav_title';
UPDATE `settings` SET `setting_value` = 'วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน ตามโมดูล มีวัดประเมินผล ผ่านหลักสูตร ออกเกียรติบัตรออนไลน์ และนับชั่วโมง — ใช้งานได้ทั่วประเทศ' WHERE `setting_key` = 'system_purpose';

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_full_name', 'วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน'),
('site_tagline', 'เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน'),
('site_name_en', 'Traffic Vaccine Intelligent System'),
('site_nav_tag', 'เพื่อพัฒนาความรู้และทักษะด้านจราจร')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

UPDATE `pages`
SET `title` = 'เกี่ยวกับ TAVIS',
    `content` = '<p><strong>TAVIS – วัคซีนจราจร</strong> เป็นระบบ <strong>วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน</strong> ตามโมดูล มีการวัดประเมินผล ผ่านหลักสูตร ออกเกียรติบัตรออนไลน์ และนับชั่วโมงอบรม ใช้งานได้ทั่วประเทศผ่านการลงทะเบียน โดยแยกสารสนเทศตามกระทรวง หน่วยงาน เขตพื้นที่ และโรงเรียน</p><p>บทบาทการใช้งาน: <strong>Admin</strong>, <strong>Trainer</strong>, <strong>User</strong></p><p>จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม</p>',
    `seo_title` = 'เกี่ยวกับ TAVIS – วัคซีนจราจร'
WHERE `slug` = 'about';
