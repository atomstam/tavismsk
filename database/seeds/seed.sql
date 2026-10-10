USE `tavis`;

INSERT INTO `positions` (`id`,`name`,`name_en`,`standard`,`role_key`,`sort_order`,`status`) VALUES
(1,'ผู้อำนวยการสถานศึกษา','School Director','w10','director',1,1),
(2,'รองผู้อำนวยการสถานศึกษา','Deputy School Director','w10','deputy',2,1),
(3,'ครู','Teacher','w9','teacher',3,1),
(4,'ครูผู้ช่วย','Assistant Teacher','w9','teacher',4,1);

INSERT INTO `academic_ranks` (`id`,`name`,`name_en`,`sort_order`,`status`) VALUES
(1,'ไม่มีวิทยฐานะ','No Academic Rank',1,1),
(2,'ชำนาญการ','Professional',2,1),
(3,'ชำนาญการพิเศษ','Senior Professional',3,1),
(4,'เชี่ยวชาญ','Expert',4,1),
(5,'เชี่ยวชาญพิเศษ','Senior Expert',5,1);

INSERT INTO `prefixes` (`title`,`sort_order`) VALUES
('นาย',1),('นาง',2),('นางสาว',3),('ว่าที่ร้อยตรี',4),('ว่าที่ร้อยโท',5),('ว่าที่ร้อยเอก',6);

INSERT INTO `academic_years` (`year_be`,`name`,`is_current`,`status`) VALUES
(2567,'2567',0,1),(2568,'2568',0,1),(2569,'2569',1,1),(2570,'2570',0,1);

INSERT INTO `departments` (`code`,`name`,`head_name`,`color`,`sort_order`,`status`) VALUES
('ADMIN','กลุ่มบริหารงานทั่วไป','นายชัดสกร พิกุลทอง','#7c3aed',1,1),
('ACAD','กลุ่มบริหารวิชาการ','','#0d9488',2,1),
('PERSON','กลุ่มบริหารงานบุคคล','','#6b21a8',3,1),
('BUDGET','กลุ่มบริหารงบประมาณ','','#f59e0b',4,1),
('TAVIS','ชมรมวัคซีนจราจร (TAVIS)','','#fbbf24',5,1);

-- password: Admin@1234
INSERT INTO `users` (`username`,`email`,`password`,`title`,`first_name`,`last_name`,`phone`,`role`,`status`,`created_at`,`updated_at`) VALUES
('admin','tavis@cyp.ac.th','$2y$10$B3heFn/E1r0U64Y0AR7BNO9mjaiMN2yWInwR2uy4StHZz1l.9JM.W','นาย','ผู้ดูแล','ระบบ','043000000','admin',1,NOW(),NOW());

INSERT INTO `personnel` (`user_id`,`code`,`title`,`first_name`,`last_name`,`position_id`,`academic_rank_id`,`department_id`,`phone`,`email`,`status`) VALUES
(1,'ADMIN','นาย','ผู้ดูแล','ระบบ',1,3,1,'043000000','tavis@cyp.ac.th',1);

UPDATE `users` SET `personnel_id` = 1 WHERE `id` = 1;

INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','TAVIS – วัคซีนจราจร'),
('site_short_name','TAVIS'),
('site_full_name','วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน'),
('site_tagline','เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน'),
('site_name_en','Traffic Vaccine Intelligent System'),
('site_nav_title','TAVIS วัคซีนจราจร'),
('site_nav_tag','เพื่อพัฒนาความรู้และทักษะด้านจราจร'),
('org_name','สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม'),
('org_address','134 ถนนเลี่ยงเมืองมหาสารคาม-ร้อยเอ็ด ตำบลแวงน่าง อำเภอเมือง จังหวัดมหาสารคาม 44000'),
('org_phone','085-537-4109'),
('org_email','tavis@spmmsk.go.th'),
('org_facebook',''),
('org_line',''),
('org_website','https://tavis.spmmsk.go.th'),
('director_name',''),
('director_position',''),
('academic_year','2569'),
('maintenance_mode','0'),
('maps_embed',''),
('org_name_en','Secondary Educational Service Area Office Maha Sarakham'),
('org_affiliation','สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน'),
('subcommittee_footer','คณะอนุกรรมการด้านความปลอดภัยทางถนนในกลุ่มเด็กและเยาวชน จังหวัดมหาสารคาม'),
('developer_name','สพม.มหาสารคาม'),
('footer_credit','จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม'),
('system_purpose','วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน ตามโมดูล มีวัดประเมินผล ผ่านหลักสูตร ออกเกียรติบัตรออนไลน์ และนับชั่วโมง — ใช้งานได้ทั่วประเทศ'),
('pass_score','60'),
('required_hours','14'),
('attendance_percent','80'),
('certificate_prefix','TAVIS');

INSERT INTO `org_units` (`id`,`code`,`name`,`name_en`,`org_type`,`parent_id`,`province`,`color`,`sort_order`,`status`) VALUES
(1,'MOE','กระทรวงศึกษาธิการ','Ministry of Education','ministry',NULL,'กรุงเทพมหานคร','#6b21a8',1,1),
(2,'OBEC','สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน','OBEC','agency',1,'กรุงเทพมหานคร','#7c3aed',2,1),
(3,'SPMMSK','สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม','Secondary ESAO Maha Sarakham','area',2,'มหาสารคาม','#f59e0b',3,1),
(4,'CYP','โรงเรียนเชียงยืนพิทยาคม','Chiang Yuen Pittayakom School','school',3,'มหาสารคาม','#fbbf24',4,1);

UPDATE `users` SET `org_unit_id` = 3 WHERE `id` = 1;

INSERT INTO `users` (`username`,`email`,`password`,`title`,`first_name`,`last_name`,`phone`,`role`,`org_unit_id`,`status`,`created_at`,`updated_at`) VALUES
('trainer','trainer@spmmsk.go.th','$2y$10$B3heFn/E1r0U64Y0AR7BNO9mjaiMN2yWInwR2uy4StHZz1l.9JM.W','นาย','วิทยากร','ตัวอย่าง','043000001','trainer',3,1,NOW(),NOW()),
('user01','user01@school.ac.th','$2y$10$B3heFn/E1r0U64Y0AR7BNO9mjaiMN2yWInwR2uy4StHZz1l.9JM.W','นาย','ผู้เข้ารับ','การพัฒนา','043000002','user',4,1,NOW(),NOW());

INSERT INTO `pages` (`slug`,`title`,`content`,`status`,`seo_title`) VALUES
('about','เกี่ยวกับระบบ','<p><strong>TAVIS – วัคซีนจราจร</strong> เป็นระบบ <strong>วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน</strong> ตามโมดูล มีการวัดประเมินผล ผ่านหลักสูตร ออกเกียรติบัตรออนไลน์ และนับชั่วโมงอบรม ใช้งานได้ทั่วประเทศผ่านการลงทะเบียน โดยแยกสารสนเทศตามกระทรวง หน่วยงาน เขตพื้นที่ และโรงเรียน</p><p>บทบาทการใช้งาน: <strong>Admin</strong>, <strong>Trainer</strong>, <strong>User</strong></p><p>จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม</p>',1,'เกี่ยวกับ TAVIS – วัคซีนจราจร');

INSERT INTO `faqs` (`question`,`answer`,`sort_order`,`status`) VALUES
('ใครใช้งานระบบได้บ้าง','หน่วยงานราชการ โรงเรียน มหาวิทยาลัย และองค์กรทั่วประเทศ ลงทะเบียนแล้วแยกตามกระทรวง/หน่วยงาน/เขต/โรงเรียน',1,1),
('บทบาทผู้ใช้มีอะไรบ้าง','Admin ดูแลทั้งระบบ, Trainer จัดการอบรมประเมินและออกเกียรติบัตร, User ลงทะเบียนเรียนและรับเกียรติบัตร',2,1),
('ผ่านหลักสูตรอย่างไร','เข้าเรียนครบโมดูล นับชั่วโมงถึงเกณฑ์ ผ่านการประเมินผล จึงออกเกียรติบัตรออนไลน์ได้',3,1),
('ตรวจสอบเกียรติบัตรอย่างไร','ใช้รหัส Verify Code ที่หน้า ตรวจสอบเกียรติบัตร ของเว็บไซต์สาธารณะ',4,1);

INSERT INTO `announcements` (`title`,`body`,`is_active`) VALUES
('เปิดระบบ TAVIS วัคซีนจราจร','ลงทะเบียนเข้ารับการพัฒนาตามโมดูล วัดประเมินผล นับชั่วโมง และรับเกียรติบัตรออนไลน์',1);

INSERT INTO `news` (`title`,`slug`,`excerpt`,`content`,`category`,`status`,`published_at`,`created_at`) VALUES
('เปิดหลักสูตรวัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน','open-tavis-training-2569','ประชาสัมพันธ์ระบบวัคซีนจราจร','<p>เชิญหน่วยงานทั่วประเทศลงทะเบียนใช้งานระบบ TAVIS – วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน</p>','อบรม',1,NOW(),NOW());

INSERT INTO `learning_plans` (`plan_no`,`title`,`description`,`hours`,`instructor_type`,`instructor_label`,`topic_group`,`sort_order`,`status`) VALUES
(1,'ปัญหาอุบัติเหตุทางถนน ปัจจัยเสี่ยง และการเตรียมความพร้อมเป็นผู้ใช้รถใช้ถนนอย่างปลอดภัย','พื้นฐานสถานการณ์และปัจจัยเสี่ยง',1,'teacher','ครูที่ปรึกษาชมรม','พื้นฐานความปลอดภัยทางถนน',1,1),
(2,'จิตสำนึกและมารยาทในการใช้รถใช้ถนน','ปลูกฝังจิตสำนึกและมารยาทบนท้องถนน',1,'teacher','ครูที่ปรึกษาชมรม','พื้นฐานความปลอดภัยทางถนน',2,1),
(3,'กฎหมายว่าด้วยรถยนต์ ทางหลวง จราจรทางบก และ พ.ร.บ.คุ้มครองผู้ประสบภัยจากรถ','กฎหมายหลักที่เกี่ยวข้องกับผู้ใช้รถใช้ถนน',1,'police','เจ้าหน้าที่ตำรวจ / วิทยากรกฎหมาย','กฎหมายจราจร',3,1),
(4,'กฎหมายจราจรที่สำคัญ สัญญาณ เครื่องหมายจราจร','สัญญาณ เครื่องหมาย และกฎสำคัญบนท้องถนน',1,'police','เจ้าหน้าที่ตำรวจจราจร','กฎหมายจราจร',4,1),
(5,'การจัดการความเสี่ยงในการใช้รถใช้ถนน (TSY Module 1 ทักษะคิดเอาชีวิตรอด)','TSY Module 1',1,'honda','วิทยากร Honda Safety / TSY','TSY – ทักษะคิดเอาชีวิตรอด',5,1),
(6,'สมรรถนะ ข้อจำกัด และการประมวลผลของมนุษย์ (TSY Module 2)','TSY Module 2',1,'honda','วิทยากร Honda Safety / TSY','TSY – ทักษะคิดเอาชีวิตรอด',6,1),
(7,'การตระหนักรู้สถานการณ์และการคาดการณ์อุบัติเหตุ (TSY Module 3) — SHELL Model','TSY Module 3 + SHELL',1,'honda','วิทยากร Honda Safety / TSY','TSY – ทักษะคิดเอาชีวิตรอด',7,1),
(8,'การตัดสินใจ (TSY Module 4) — DECIDE / KYT','TSY Module 4',1,'honda','วิทยากร Honda Safety / TSY','TSY – ทักษะคิดเอาชีวิตรอด',8,1),
(9,'ทัศนคติที่เป็นอันตราย (TSY Module 5)','TSY Module 5',1,'honda','วิทยากร Honda Safety / TSY','TSY – ทักษะคิดเอาชีวิตรอด',9,1),
(10,'การจัดการความเหนื่อยล้า ยา สารเสพติด โรค และปัจจัยสุขภาพ (TSY Module 6) — AMISAFE','TSY Module 6 + AMISAFE',1,'hospital','บุคลากรสาธารณสุข / โรงพยาบาล','TSY – สุขภาพและความปลอดภัย',10,1),
(11,'ข้อปฏิบัติเมื่อเกิดเหตุฉุกเฉิน การให้ความช่วยเหลือและการปฐมพยาบาล','ฉุกเฉินและปฐมพยาบาลเบื้องต้น',1,'hospital','บุคลากรสาธารณสุข / กู้ชีพ','เหตุฉุกเฉินและปฐมพยาบาล',11,1),
(12,'สาธิตการขับขี่ปลอดภัยโดยผู้เชี่ยวชาญ และการดูแลยานพาหนะ','ทักษะปฏิบัติและดูแลรถ',1,'honda','วิทยากร Honda Safety / ผู้เชี่ยวชาญขับขี่','ทักษะปฏิบัติและยานพาหนะ',12,1),
(13,'สรุปเนื้อหาและขั้นตอนการขอใบอนุญาตขับรถจักรยานยนต์','เตรียมขอใบอนุญาตขับขี่',1,'transport','เจ้าหน้าที่กรมการขนส่งทางบก','ใบอนุญาตขับขี่',13,1),
(14,'ประเมินผลการเรียนรู้ (แบบทดสอบออนไลน์)','ประเมินผลหลักสูตร 14 ชั่วโมง',1,'teacher','ครูที่ปรึกษาชมรม','ประเมินผล',14,1);

INSERT INTO `learning_objectives` (`objective_no`,`title`,`description`,`sort_order`,`status`) VALUES
(1,'มีความรู้ความเข้าใจเกี่ยวกับปัญหาอุบัติเหตุทางถนนและปัจจัยเสี่ยง','อธิบายสถานการณ์และปัจจัยเสี่ยงได้อย่างถูกต้อง',1,1),
(2,'มีจิตสำนึกและมารยาทในการใช้รถใช้ถนน','แสดงพฤติกรรมรับผิดชอบบนท้องถนน',2,1),
(3,'เข้าใจกฎหมายจราจรและเครื่องหมายจราจร','อ่านสัญญาณ/เครื่องหมายและอ้างอิงกฎหมายสำคัญได้',3,1),
(4,'สามารถจัดการความเสี่ยงและใช้ทักษะคิดเอาชีวิตรอด (TSY)','ประยุกต์ TSY Module 1–2',4,1),
(5,'ตระหนักรู้สถานการณ์และคาดการณ์อุบัติเหตุ','ใช้ SHELL และสถานการณ์จำลองได้',5,1),
(6,'ตัดสินใจอย่างปลอดภัยเมื่อเผชิญความเสี่ยง','ใช้ DECIDE / KYT ได้',6,1),
(7,'หลีกเลี่ยงทัศนคติและพฤติกรรมที่เป็นอันตราย','ระบุและปรับทัศนคติเสี่ยง',7,1),
(8,'จัดการความเหนื่อยล้าและปัจจัยสุขภาพ','ใช้ AMISAFE และรู้ข้อจำกัดสุขภาพ',8,1),
(9,'สามารถปฐมพยาบาลเบื้องต้นและเตรียมขอใบอนุญาตขับขี่ได้','ปฏิบัติตามขั้นตอนฉุกเฉินและขอใบขับขี่',9,1);
