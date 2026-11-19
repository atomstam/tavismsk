<?php

declare(strict_types=1);

/**
 * OAuth Social + Government Login — Facebook / Google / LINE / ThaID / EduID
 * ว่าง Client ID = ปิดปุ่มบนหน้า login
 *
 * Callback URL ตัวอย่าง (local):
 *   http://localhost/traffic_vaccine/auth/facebook/cb
 *   http://localhost/traffic_vaccine/auth/google/cb
 *   http://localhost/traffic_vaccine/auth/line/cb
 *   http://localhost/traffic_vaccine/auth/thaid/cb
 *   http://localhost/traffic_vaccine/auth/eduid/cb
 *
 * Production: ตั้ง OAUTH_CANONICAL_BASE_URL ให้ตรงโดเมนในแอปผู้ให้บริการ
 * ThaID: ลงทะเบียน RP กับ กรมการปกครอง (imauth.bora.dopa.go.th)
 * EduID: ลงทะเบียนแอปกับ Education ID (edu.id.th)
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
    : rtrim($env('APP_URL', 'https://tavis.spmmsk.go.th'), '/');
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
    if ($explicit !== '' && !($requestHost === 'tavis.spmmsk.go.th' && $explicitIsLocal)) {
        return $explicit;
    }

    return $buildPublicUrl('/auth/' . $provider . '/cb');
};

$fbId = $env('OAUTH_FACEBOOK_CLIENT_ID');
$googleId = $env('OAUTH_GOOGLE_CLIENT_ID') ?: $env('GOOGLE_CLIENT_ID');
$googleSecret = $env('OAUTH_GOOGLE_CLIENT_SECRET') ?: $env('GOOGLE_CLIENT_SECRET');
$lineId = $env('OAUTH_LINE_CHANNEL_ID');
$thaidId = $env('OAUTH_THAID_CLIENT_ID');
$eduidId = $env('OAUTH_EDUID_CLIENT_ID');

return [
    'facebook' => [
        'enabled' => $fbId !== '',
        'client_id' => $fbId,
        'client_secret' => $env('OAUTH_FACEBOOK_CLIENT_SECRET'),
        'redirect_uri' => $resolveRedirect('facebook', 'OAUTH_FACEBOOK_REDIRECT_URI'),
        'label' => 'Facebook',
        'group' => 'social',
    ],
    'google' => [
        'enabled' => $googleId !== '',
        'client_id' => $googleId,
        'client_secret' => $googleSecret,
        'redirect_uri' => $resolveRedirect('google', 'OAUTH_GOOGLE_REDIRECT_URI')
            ?: ($env('GOOGLE_REDIRECT_URI') ?: $buildPublicUrl('/auth/google/cb')),
        'label' => 'Google',
        'group' => 'social',
    ],
    'line' => [
        'enabled' => $lineId !== '',
        'client_id' => $lineId,
        'client_secret' => $env('OAUTH_LINE_CHANNEL_SECRET'),
        'redirect_uri' => $resolveRedirect('line', 'OAUTH_LINE_REDIRECT_URI'),
        'label' => 'LINE',
        'group' => 'social',
    ],
    'thaid' => [
        'enabled' => $thaidId !== '',
        'client_id' => $thaidId,
        'client_secret' => $env('OAUTH_THAID_CLIENT_SECRET'),
        'redirect_uri' => $resolveRedirect('thaid', 'OAUTH_THAID_REDIRECT_URI'),
        'label' => 'ThaID',
        'group' => 'gov',
        'authorize_url' => 'https://imauth.bora.dopa.go.th/api/v2/oauth2/auth/',
        'token_url' => 'https://imauth.bora.dopa.go.th/api/v2/oauth2/token/',
        'userinfo_url' => 'https://imauth.bora.dopa.go.th/api/v2/oauth2/userinfo/',
        'scope' => $env('OAUTH_THAID_SCOPE', 'openid pid name given_name family_name birthdate'),
        'token_auth' => 'basic',
    ],
    'eduid' => [
        'enabled' => $eduidId !== '',
        'client_id' => $eduidId,
        'client_secret' => $env('OAUTH_EDUID_CLIENT_SECRET'),
        'redirect_uri' => $resolveRedirect('eduid', 'OAUTH_EDUID_REDIRECT_URI'),
        'label' => 'EduID',
        'group' => 'gov',
        'authorize_url' => 'https://edu.id.th/oauth2/v1/authorize',
        'token_url' => 'https://edu.id.th/oauth2/v1/token',
        'userinfo_url' => 'https://edu.id.th/oauth2/v1/userinfo',
        'scope' => $env('OAUTH_EDUID_SCOPE', 'person_id prefix first_name last_name birth_date'),
        'token_auth' => 'basic',
    ],
];
