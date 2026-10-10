<?php

/**
 * Backend sidebar structure.
 * Each item: label, icon, path, module (for can_perm), optional action, optional roles (restrict further).
 * Groups are shown only if at least one child is visible.
 */
return [
    [
        'type' => 'link',
        'label' => 'หน้าหลัก',
        'icon' => 'fa-th-large',
        'path' => '/admin/dashboard',
        'exact' => true,
        'module' => 'dashboard',
    ],

    // —— Learner / shared learning ——
    [
        'type' => 'label',
        'label' => 'การเรียนของฉัน',
        'roles' => ['user'],
    ],
    [
        'type' => 'link',
        'label' => 'บทเรียนของฉัน',
        'icon' => 'fa-graduation-cap',
        'path' => '/learn',
        'module' => 'learn',
    ],
    [
        'type' => 'link',
        'label' => 'ลงทะเบียนอบรม',
        'icon' => 'fa-clipboard-list',
        'path' => '/admin/enrollments',
        'module' => 'enrollments',
        'roles' => ['user'],
    ],
    [
        'type' => 'link',
        'label' => 'เกียรติบัตรของฉัน',
        'icon' => 'fa-certificate',
        'path' => '/admin/certificates',
        'module' => 'certificates',
        'roles' => ['user'],
    ],

    // —— Trainer / Admin training ops ——
    [
        'type' => 'label',
        'label' => 'จัดการอบรม',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'จัดการบทเรียน LMS',
        'icon' => 'fa-chalkboard-user',
        'path' => '/admin/training',
        'module' => 'assessments',
        'action' => 'create',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'โมดูลการอบรม',
        'icon' => 'fa-book-open-reader',
        'path' => '/admin/curriculum',
        'module' => 'curriculum',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'ลงทะเบียนอบรม',
        'icon' => 'fa-clipboard-list',
        'path' => '/admin/enrollments',
        'module' => 'enrollments',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'เกียรติบัตรออนไลน์',
        'icon' => 'fa-certificate',
        'path' => '/admin/certificates',
        'module' => 'certificates',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'ผู้เข้ารับการพัฒนา',
        'icon' => 'fa-user-graduate',
        'path' => '/admin/members',
        'module' => 'members',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'โครงสร้างหน่วยงาน',
        'icon' => 'fa-sitemap',
        'path' => '/admin/org-units',
        'module' => 'org_units',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'AI ผู้ช่วย',
        'icon' => 'fa-robot',
        'path' => '/admin/ai',
        'module' => 'ai',
        'roles' => ['admin', 'trainer'],
    ],

    // —— Portfolio (staff) ——
    [
        'type' => 'label',
        'label' => 'กิจกรรม / ผลงาน',
        'roles' => ['admin', 'trainer'],
        'module' => 'portfolio',
    ],
    [
        'type' => 'portfolio',
        'roles' => ['admin', 'trainer'],
        'module' => 'portfolio',
    ],
    [
        'type' => 'link',
        'label' => 'แกลเลอรี่',
        'icon' => 'fa-images',
        'path' => '/admin/gallery',
        'module' => 'portfolio',
        'roles' => ['admin', 'trainer'],
    ],

    // —— Learner own portfolio (simple) ——
    [
        'type' => 'label',
        'label' => 'ผลงานของฉัน',
        'roles' => ['user'],
        'module' => 'portfolio',
    ],
    [
        'type' => 'portfolio',
        'roles' => ['user'],
        'module' => 'portfolio',
    ],

    // —— Reports / org (staff) ——
    [
        'type' => 'label',
        'label' => 'ระบบ',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'reports',
        'label' => 'รายงาน',
        'icon' => 'fa-file-lines',
        'module' => 'reports',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'บุคลากร',
        'icon' => 'fa-id-card',
        'path' => '/admin/personnel',
        'module' => 'personnel',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'จัดการผู้ใช้งาน',
        'icon' => 'fa-users',
        'path' => '/admin/users',
        'module' => 'users',
        'roles' => ['admin'],
    ],
    [
        'type' => 'link',
        'label' => 'หน่วยงาน',
        'icon' => 'fa-building',
        'path' => '/admin/departments',
        'module' => 'departments',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'link',
        'label' => 'แบบฟอร์ม',
        'icon' => 'fa-wpforms',
        'path' => '/admin/forms',
        'module' => 'forms',
        'roles' => ['admin', 'trainer'],
    ],
    [
        'type' => 'label',
        'label' => 'อื่นๆ',
        'roles' => ['user'],
    ],
    [
        'type' => 'link',
        'label' => 'ระบบบันทึก',
        'icon' => 'fa-inbox',
        'path' => '/admin/tickets',
        'module' => 'tickets',
    ],
    [
        'type' => 'link',
        'label' => 'แจ้งเตือน',
        'icon' => 'fa-bell',
        'path' => '/admin/notifications',
        'module' => 'notifications',
        'badge' => 'notifications',
    ],
    [
        'type' => 'link',
        'label' => 'Log / Audit',
        'icon' => 'fa-clipboard-list',
        'path' => '/admin/logs',
        'module' => 'logs',
        'roles' => ['admin'],
    ],
    [
        'type' => 'link',
        'label' => 'ตั้งค่า',
        'icon' => 'fa-gear',
        'path' => '/admin/settings',
        'module' => 'settings',
        'roles' => ['admin'],
    ],
    [
        'type' => 'link',
        'label' => 'บทบาท',
        'icon' => 'fa-user-shield',
        'path' => '/admin/roles',
        'module' => 'roles',
        'roles' => ['admin'],
    ],
];
