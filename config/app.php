<?php
/**
 * Application Configuration
 * TAVIS – Traffic Awareness Vaccine Intelligent System
 * วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน
 * จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม
 */

return [
    'name' => env('APP_NAME', 'TAVIS - วัคซีนจราจร'),
    'short_name' => env('APP_SHORT_NAME', 'TAVIS'),
    'full_name' => env('APP_FULL_NAME', 'วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน'),
    'tagline' => env('APP_TAGLINE', 'เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน'),
    'name_en' => env('APP_NAME_EN', 'Traffic Awareness Vaccine Intelligent System'),
    'nav_title' => env('APP_NAV_TITLE', 'TAVIS วัคซีนจราจร'),
    'nav_tag' => env('APP_NAV_TAG', 'เพื่อพัฒนาความรู้และทักษะด้านจราจร'),
    'description' => env(
        'APP_DESCRIPTION',
        'วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน ตามโมดูล มีวัดประเมินผล ผ่านหลักสูตร ออกเกียรติบัตรออนไลน์ และนับชั่วโมง — ใช้งานได้ทั่วประเทศ'
    ),
    'dashboard' => [
        'kicker' => 'OVERVIEW',
        'title' => 'ภาพรวมระบบ',
        'subtitle_admin' => 'ภาพรวมวัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน',
        'subtitle_trainer' => 'ภาพรวมการจัดอบรมและติดตามผู้เรียนในหน่วยงาน',
        'subtitle_user' => 'ภาพรวมการอบรมของคุณ — Pretest · เนื้อหา · ภาระงาน · Posttest',
    ],
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(env('APP_URL', 'https://tavis.thaiedu.in.th'), '/'),
    'key' => env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'Asia/Bangkok'),
    'locale' => env('APP_LOCALE', 'th'),
    'organization' => [
        'name' => 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม',
        'name_en' => 'Secondary Educational Service Area Office Maha Sarakham',
        'affiliation' => 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน',
        'address' => '134 ถนนเลี่ยงเมืองมหาสารคาม-ร้อยเอ็ด ตำบลแวงน่าง อำเภอเมือง จังหวัดมหาสารคาม 44000',
        'phone' => '085-537-4109',
        'email' => 'tavis@spmmsk.go.th',
        'website' => 'https://tavis.thaiedu.in.th',
        'facebook' => '',
        'line' => '',
        'school_code' => '',
        'subcommittee' => 'คณะอนุกรรมการด้านความปลอดภัยทางถนนในกลุ่มเด็กและเยาวชน จังหวัดมหาสารคาม',
    ],
    'developer' => [
        'name' => 'สพม.มหาสารคาม',
        'credit' => 'จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม',
    ],
    'org_types' => [
        'ministry' => 'กระทรวง',
        'agency' => 'หน่วยงาน / กรม / สำนักงาน',
        'area' => 'เขตพื้นที่การศึกษา',
        'school' => 'สถานศึกษา / โรงเรียน',
    ],
    'roles' => [
        'admin' => 'ผู้ดูแลระบบ',
        'trainer' => 'วิทยากร',
        'user' => 'ผู้ใช้งานทั่วไป',
    ],
    'training' => [
        'total_hours' => 14,
        'pass_score' => 60,
        'attendance_percent' => 80,
        'certificate_prefix' => 'TAVIS',
        'course_title' => 'หลักสูตรวัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน',
    ],
    'session_lifetime' => (int) env('SESSION_LIFETIME', 120),
    'session_timeout' => (int) env('SESSION_TIMEOUT', 3600),
    'upload_max_size' => (int) env('UPLOAD_MAX_SIZE', 20971520),
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'mp4'],
    'upload' => [
        'max_size' => (int) env('UPLOAD_MAX_SIZE', 20971520),
        'allowed'  => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'mp4'],
        'path'     => PUBLIC_PATH . '/uploads',
        'image_sizes' => [
            'avatar'    => ['w' => 400, 'h' => 400, 'label' => 'รูปโปรไฟล์', 'folder' => 'avatars', 'shape' => 'circle'],
            'photo'     => ['w' => 400, 'h' => 400, 'label' => 'รูปบุคลากร', 'folder' => 'personnel', 'shape' => 'circle'],
            'logo'      => ['w' => 400, 'h' => 400, 'label' => 'โลโก้หน่วยงาน', 'folder' => 'logo', 'shape' => 'square'],
            'site_logo' => ['w' => 600, 'h' => 200, 'label' => 'โลโก้เว็บไซต์', 'folder' => 'logo', 'shape' => 'rect'],
            'favicon'   => ['w' => 180, 'h' => 180, 'label' => 'Favicon', 'folder' => 'logo', 'shape' => 'square'],
            'cover'     => [
                'w' => 1280, 'h' => 720, 'label' => 'รูปปก', 'folder' => 'portfolio', 'shape' => 'rect',
                'accept' => [[1280, 720], [1920, 1080]],
            ],
        ],
    ],
    'maintenance_mode' => filter_var(env('MAINTENANCE_MODE', false), FILTER_VALIDATE_BOOLEAN),
    'fiscal_year' => date('Y') + 543,
];
