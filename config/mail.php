<?php

return [
    'mailer' => env('MAIL_MAILER', 'smtp'),
    'host' => env('MAIL_HOST', 'smtp.gmail.com'),
    'port' => (int) env('MAIL_PORT', 587),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@cyp.ac.th'),
        'name' => env('MAIL_FROM_NAME', 'TAVIS CYP'),
    ],
];
