<?php

$all = ['view', 'create', 'edit', 'delete', 'export', 'print', 'certify', 'issue_cert'];
$trainerCore = ['view', 'create', 'edit', 'delete', 'export', 'print', 'certify', 'issue_cert'];
$viewExport = ['view', 'export', 'print'];

/**
 * Module permissions by primary role.
 * Legacy roles (director/deputy/teacher) resolve via config/roles.php alias_of
 * in PermissionService / Auth — matrices below are for resolved roles only.
 */
return [
    'modules' => [
        'dashboard' => 'แดชบอร์ด',
        'curriculum' => 'หลักสูตร',
        'enrollments' => 'การลงทะเบียนหลักสูตร',
        'assessments' => 'วัดประเมินผล / LMS',
        'satisfaction' => 'แบบประเมินความพึงพอใจ',
        'trainers' => 'จัดการวิทยากร',
        'plan_owners' => 'เจ้าของหลักสูตร',
        'learn' => 'บทเรียนของฉัน',
        'plan_chat' => 'กระดานสนทนา',
        'certificates' => 'เกียรติบัตรออนไลน์',
        'hours' => 'นับชั่วโมงอบรม',
        'members' => 'ผู้เข้ารับการพัฒนา',
        'org_units' => 'โครงสร้างหน่วยงาน',
        'portfolio' => 'กิจกรรม/ผลงานอบรม',
        'ai' => 'AI ผู้ช่วย',
        'reports' => 'สารสนเทศ / รายงาน',
        'users' => 'ผู้ใช้งาน',
        'personnel' => 'บุคลากร',
        'departments' => 'หน่วยงานย่อย',
        'roles' => 'บทบาท',
        'forms' => 'แบบฟอร์มออนไลน์',
        'tickets' => 'ระบบบันทึก',
        'news' => 'ข่าวสาร/กิจกรรม',
        'pages' => 'หน้าเว็บไซต์',
        'notifications' => 'แจ้งเตือน',
        'logs' => 'Log / Audit',
        'settings' => 'ตั้งค่า',
        'backup' => 'สำรองข้อมูล',
        'contact' => 'ติดต่อเรา',
        'faqs' => 'คำถามที่พบบ่อย',
        'downloads' => 'ไฟล์ดาวน์โหลด',
        'announcements' => 'ประกาศ',
        'academic_ranks' => 'วิทยฐานะ',
        'academic_years' => 'ปีการศึกษา',
        'sc_prefixes' => 'คำนำหน้า',
        'sc_personal_types' => 'ตำแหน่ง / ระดับชั้น',
    ],
    'actions' => [
        'view' => 'ดู',
        'create' => 'เพิ่ม',
        'edit' => 'แก้ไข',
        'delete' => 'ลบ',
        'export' => 'ส่งออก',
        'print' => 'พิมพ์',
        'certify' => 'รับรองผล',
        'issue_cert' => 'ออกเกียรติบัตร',
    ],

    // —— Admin: full system ——
    'admin' => [
        '*' => $all,
    ],

    // —— Trainer: training operations (no user/role/settings/logs) ——
    'trainer' => [
        'dashboard' => ['view'],
        'curriculum' => $trainerCore,
        'learn' => ['view'],
        'plan_chat' => ['view', 'create'],
        'enrollments' => $trainerCore,
        'assessments' => $trainerCore,
        'certificates' => ['view', 'create', 'edit', 'export', 'print', 'issue_cert'],
        'hours' => ['view', 'create', 'edit', 'export'],
        'members' => $trainerCore,
        'portfolio' => ['view', 'create', 'edit', 'delete', 'export', 'print'],
        'ai' => ['view'],
        'reports' => ['view', 'export', 'print', 'certify'],
        'notifications' => ['view'],
        'contact' => ['view'],
    ],

    // —— User (learner): own learning only ——
    'user' => [
        'dashboard' => ['view'],
        'learn' => ['view'],
        'plan_chat' => ['view', 'create'],
        'enrollments' => ['view', 'create'],
        'assessments' => ['view'],
        'certificates' => ['view', 'print'],
        'hours' => ['view'],
        'portfolio' => ['view', 'create', 'edit'],
        'notifications' => ['view'],
        'contact' => ['view'],
    ],

    // —— Legacy matrices (kept for direct role keys before alias resolve) ——
    'director' => [
        '*' => $all,
    ],
    'deputy' => [
        'dashboard' => ['view'],
        'curriculum' => $trainerCore,
        'learn' => ['view'],
        'plan_chat' => ['view', 'create'],
        'enrollments' => $trainerCore,
        'assessments' => $trainerCore,
        'certificates' => ['view', 'create', 'edit', 'export', 'print', 'issue_cert'],
        'hours' => ['view', 'create', 'edit', 'export'],
        'members' => $trainerCore,
        'portfolio' => ['view', 'create', 'edit', 'delete', 'export', 'print'],
        'ai' => ['view'],
        'reports' => ['view', 'export', 'print', 'certify'],
        'notifications' => ['view'],
        'contact' => ['view'],
    ],
    'teacher' => [
        'dashboard' => ['view'],
        'learn' => ['view'],
        'plan_chat' => ['view', 'create'],
        'enrollments' => ['view', 'create'],
        'assessments' => ['view'],
        'certificates' => ['view', 'print'],
        'hours' => ['view'],
        'portfolio' => ['view', 'create', 'edit'],
        'notifications' => ['view'],
        'contact' => ['view'],
    ],
];
