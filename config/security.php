<?php

return [
    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'expire' => (int) env('JWT_EXPIRE', 86400),
        'algo' => 'HS256',
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', ''),
    ],
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
    ],
    'line_notify' => [
        'token' => env('LINE_NOTIFY_TOKEN', ''),
    ],
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'chat_id' => env('TELEGRAM_CHAT_ID', ''),
    ],
    'password' => [
        'min_length' => 8,
        'require_upper' => true,
        'require_lower' => true,
        'require_number' => true,
        'require_special' => false,
    ],
    'otp' => [
        'length' => 6,
        'expire_minutes' => 10,
    ],
    '2fa' => [
        'enabled' => true,
        'issuer' => 'TAVIS CYP',
    ],
    'csrf_token_name' => '_token',
    'login_max_attempts' => 5,
    'login_lockout_minutes' => 15,
];
