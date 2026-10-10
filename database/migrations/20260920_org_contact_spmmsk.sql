-- อัปเดตข้อมูลติดต่อเจ้าของโครงการ: สพม.มหาสารคาม
UPDATE `settings` SET `setting_value` = '134 ถนนเลี่ยงเมืองมหาสารคาม-ร้อยเอ็ด ตำบลแวงน่าง อำเภอเมือง จังหวัดมหาสารคาม 44000' WHERE `setting_key` = 'org_address';
UPDATE `settings` SET `setting_value` = '085-537-4109' WHERE `setting_key` = 'org_phone';
UPDATE `settings` SET `setting_value` = 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม' WHERE `setting_key` = 'org_name';
UPDATE `settings` SET `setting_value` = 'Secondary Educational Service Area Office Maha Sarakham' WHERE `setting_key` = 'org_name_en';
UPDATE `settings` SET `setting_value` = 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน' WHERE `setting_key` = 'org_affiliation';
UPDATE `settings` SET `setting_value` = 'tavis@spmmsk.go.th' WHERE `setting_key` = 'org_email';
UPDATE `settings` SET `setting_value` = 'https://tavis.thaiedu.in.th' WHERE `setting_key` = 'org_website';
UPDATE `settings` SET `setting_value` = 'สพม.มหาสารคาม' WHERE `setting_key` = 'developer_name';
UPDATE `settings` SET `setting_value` = 'จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม' WHERE `setting_key` = 'footer_credit';

INSERT INTO `settings` (`setting_key`, `setting_value`)
SELECT 'org_address', '134 ถนนเลี่ยงเมืองมหาสารคาม-ร้อยเอ็ด ตำบลแวงน่าง อำเภอเมือง จังหวัดมหาสารคาม 44000'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `setting_key` = 'org_address');

INSERT INTO `settings` (`setting_key`, `setting_value`)
SELECT 'org_phone', '085-537-4109'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `setting_key` = 'org_phone');
