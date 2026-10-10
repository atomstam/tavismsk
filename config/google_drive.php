<?php

declare(strict_types=1);

return [
    'enabled' => filter_var(env('GOOGLE_DRIVE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'root_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
    'credentials' => env('GOOGLE_DRIVE_CREDENTIALS', 'storage/credentials/google-drive.json'),
    'impersonate_user' => env('GOOGLE_DRIVE_IMPERSONATE_USER', ''),
    'date_folder_format' => env('GOOGLE_DRIVE_DATE_FORMAT', 'Y-m-d'),
    /** โฟลเดอร์รวมของระบบ (โลโก้โรงเรียน ฯลฯ ที่ไม่ผูกบุคคล) */
    'system_folder_name' => env('GOOGLE_DRIVE_SYSTEM_FOLDER', '_system'),
    /** แยกชัด: รูปภาพ vs ไฟล์เอกสาร ภายใต้โฟลเดอร์วันที่ */
    'images_folder_name' => env('GOOGLE_DRIVE_IMAGES_FOLDER', 'images'),
    'files_folder_name' => env('GOOGLE_DRIVE_FILES_FOLDER', 'files'),
    'thumb_folder_name' => env('GOOGLE_DRIVE_THUMB_FOLDER', 'thb'),
    'thumb_max_width' => (int) env('GOOGLE_DRIVE_THUMB_WIDTH', 400),
    'media_cache_bust' => env('GOOGLE_DRIVE_MEDIA_CACHE_BUST', '1'),
];
