<?php

declare(strict_types=1);

/**
 * OAuth Social Login — Facebook / Google / LINE
 * ว่าง Client ID = ปิดปุ่มบนหน้า login
 *
 * Callback URL ตัวอย่าง (local):
 *   http://localhost/traffic_vaccine/auth/facebook/cb
 *   http://localhost/traffic_vaccine/auth/google/cb
 *   http://localhost/traffic_vaccine/auth/line/cb
 *
 * Production: ตั้ง OAUTH_CANONICAL_BASE_URL ให้ตรงโดเมนในแอปผู้ให้บริการ
 */

$env = static function (string $key, string $default = ''): string {
    if (function_exists('env')) {
        return (string) env($key, $default);
    }
    $v = getenv($key);

    return ($v === false || $v === '') ? $default : (string) $v;
};

$configuredBase = function_exists('app_base_url')
    ? app_base_url()
    : rtrim($env('APP_URL', 'https://tavis.thaiedu.in.th'), '/');
$appPath = $configuredBase;
if (preg_match('#^https?://#i', $appPath)) {
    $parsed = parse_url($appPath);
    $appPath = rtrim((string) ($parsed['path'] ?? ''), '/');
}
if ($appPath === '' && isset($_SERVER['SCRIPT_NAME'])) {
    $script = (string) $_SERVER['SCRIPT_NAME'];
    if (preg_match('#^(.+)/public/index\.php$#', $script, $m)) {
        $appPath = rtrim($m[1], '/');
    }
}

$canonicalBase = rtrim($env('OAUTH_CANONICAL_BASE_URL'), '/');

$buildPublicUrl = static function (string $suffixPath) use ($appPath, $canonicalBase): string {
    $path = ($appPath !== '' ? $appPath : '') . $suffixPath;
    if ($canonicalBase !== '') {
        return $canonicalBase . $path;
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return $path;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']);
    }

    return $scheme . '://' . $host . $path;
};

$resolveRedirect = static function (string $provider, string $envKey) use ($buildPublicUrl, $env): string {
    $explicit = trim($env($envKey));
    $requestHost = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    $explicitHost = strtolower((string) (parse_url($explicit, PHP_URL_HOST) ?: ''));
    $explicitIsLocal = in_array($explicitHost, ['localhost', '127.0.0.1'], true);
    if ($explicit !== '' && !($requestHost === 'tavis.thaiedu.in.th' && $explicitIsLocal)) {
        return $explicit;
    }

    return $buildPublicUrl('/auth/' . $provider . '/cb');
};

$fbId = $env('OAUTH_FACEBOOK_CLIENT_ID');
$googleId = $env('OAUTH_GOOGLE_CLIENT_ID') ?: $env('GOOGLE_CLIENT_ID');
$googleSecret = $env('OAUTH_GOOGLE_CLIENT_SECRET') ?: $env('GOOGLE_CLIENT_SECRET');
$lineId = $env('OAUTH_LINE_CHANNEL_ID');

return [
    'facebook' => [
        'enabled' => $fbId !== '',
        'client_id' => $fbId,
        'client_secret' => $env('OAUTH_FACEBOOK_CLIENT_SECRET'),
        'redirect_uri' => $resolveRedirect('facebook', 'OAUTH_FACEBOOK_REDIRECT_URI'),
        'label' => 'Facebook',
    ],
    'google' => [
        'enabled' => $googleId !== '',
        'client_id' => $googleId,
        'client_secret' => $googleSecret,
        'redirect_uri' => $resolveRedirect('google', 'OAUTH_GOOGLE_REDIRECT_URI')
            ?: ($env('GOOGLE_REDIRECT_URI') ?: $buildPublicUrl('/auth/google/cb')),
        'label' => 'Google',
    ],
    'line' => [
        'enabled' => $lineId !== '',
        'client_id' => $lineId,
        'client_secret' => $env('OAUTH_LINE_CHANNEL_SECRET'),
        'redirect_uri' => $resolveRedirect('line', 'OAUTH_LINE_REDIRECT_URI'),
        'label' => 'LINE',
    ],
];
