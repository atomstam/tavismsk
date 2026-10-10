<?php

return [
    'admin' => [
        'label' => 'ผู้ดูแลระบบ (Admin)',
        'level' => 100,
        'assignable' => ['admin', 'trainer', 'user'],
        'scope' => 'nationwide',
    ],
    'trainer' => [
        'label' => 'วิทยากร / ผู้จัดอบรม (Trainer)',
        'level' => 60,
        'assignable' => ['user'],
        'scope' => 'nationwide',
    ],
    'user' => [
        'label' => 'ผู้ใช้งาน (User)',
        'level' => 20,
        'assignable' => [],
        'scope' => 'school', // ต้องสังกัด cluster → area → school
    ],
    // legacy aliases
    'director' => [
        'label' => 'ผู้อำนวยการ (legacy)',
        'level' => 90,
        'assignable' => ['trainer', 'user'],
        'alias_of' => 'admin',
    ],
    'deputy' => [
        'label' => 'รองผู้อำนวยการ (legacy)',
        'level' => 70,
        'assignable' => ['user'],
        'alias_of' => 'trainer',
    ],
    'teacher' => [
        'label' => 'ครู (legacy)',
        'level' => 50,
        'assignable' => [],
        'alias_of' => 'user',
    ],
];
