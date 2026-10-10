<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserOAuth;

final class SocialAuthService
{
    private const PROVIDERS = ['facebook', 'google', 'line'];

    /** @return array<string,array<string,mixed>> */
    public static function providersForLogin(): array
    {
        $cfg = config('oauth');
        $out = [];
        foreach (self::PROVIDERS as $p) {
            if (!empty($cfg[$p]['enabled'])) {
                $out[$p] = $cfg[$p];
            }
        }

        return $out;
    }

    public static function isProviderEnabled(string $provider): bool
    {
        $cfg = config('oauth');

        return !empty($cfg[$provider]['enabled']);
    }

    /**
     * @return array{linked: bool, display_name: string, provider_user_id: string, linked_at: string}|null
     */
    public static function linkStatusForUser(int $userId, string $provider = 'line'): ?array
    {
        $oauth = new UserOAuth();
        if (!$oauth->tableExists()) {
            return null;
        }

        $row = $oauth->findByUserAndProvider($userId, $provider);
        if ($row === null) {
            return [
                'linked' => false,
                'display_name' => '',
                'provider_user_id' => '',
                'linked_at' => '',
            ];
        }

        $linkedAt = trim((string) ($row['updated_at'] ?? ''));
        if ($linkedAt === '') {
            $linkedAt = trim((string) ($row['created_at'] ?? ''));
        }

        return [
            'linked' => true,
            'display_name' => trim((string) ($row['display_name'] ?? '')),
            'provider_user_id' => trim((string) ($row['provider_user_id'] ?? '')),
            'linked_at' => $linkedAt,
        ];
    }

    public static function assertProvider(string $provider): void
    {
        if (!in_array($provider, self::PROVIDERS, true)) {
            throw new \InvalidArgumentException('ผู้ให้บริการไม่รองรับ');
        }
    }

    /** @return array<string,mixed> */
    private function providerConfig(string $provider): array
    {
        $cfg = config('oauth');
        if (empty($cfg[$provider]['enabled'])) {
            throw new \RuntimeException('ยังไม่ได้ตั้งค่า ' . $provider);
        }

        return $cfg[$provider];
    }

    public function beginLinkRedirect(string $provider, int $userId): never
    {
        if ($userId <= 0) {
            throw new \RuntimeException('ไม่พบบัญชีผู้ใช้');
        }
        $this->beginRedirect($provider, 'link', $userId);
    }

    public function beginRedirect(string $provider, string $mode = 'login', ?int $linkUserId = null): never
    {
        self::assertProvider($provider);
        $c = $this->providerConfig($provider);
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state_' . $provider] = $state;
        $_SESSION['oauth_mode'] = $mode === 'link' ? 'link' : 'login';
        if ($mode === 'link' && $linkUserId !== null && $linkUserId > 0) {
            $_SESSION['oauth_link_user_id'] = $linkUserId;
            $_SESSION['oauth_return_route'] = 'account_profile';
        } else {
            unset($_SESSION['oauth_link_user_id']);
            $_SESSION['oauth_return_route'] = 'login';
        }

        $url = match ($provider) {
            'facebook' => $this->facebookAuthorizeUrl($c, $state),
            'google' => $this->googleAuthorizeUrl($c, $state),
            'line' => $this->lineAuthorizeUrl($c, $state),
            default => throw new \InvalidArgumentException('unknown'),
        };

        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * @return array{user: array<string,mixed>, provider: string, provider_user_id: string, mode: string}
     */
    public function finishCallback(string $provider, string $code, string $state): array
    {
        self::assertProvider($provider);
        $mode = (string) ($_SESSION['oauth_mode'] ?? 'login');
        $stateKey = 'oauth_state_' . $provider;
        $expected = (string) ($_SESSION[$stateKey] ?? '');
        unset($_SESSION[$stateKey], $_SESSION['oauth_mode']);
        if ($expected === '' || !hash_equals($expected, $state)) {
            throw new \RuntimeException(
                'คำขอไม่ถูกต้อง (state) — ลองใหม่ ห้ามเปิดลิงก์ callback ซ้ำ และใช้โดเมนเดียวกัน (www / ไม่มี www)'
            );
        }
        if ($code === '') {
            throw new \RuntimeException($mode === 'link' ? 'ยกเลิกการเชื่อมบัญชี' : 'ยกเลิกการเข้าสู่ระบบ');
        }

        $c = $this->providerConfig($provider);
        $profile = match ($provider) {
            'facebook' => $this->facebookProfile($c, $code),
            'google' => $this->googleProfile($c, $code),
            'line' => $this->lineProfile($c, $code),
            default => throw new \InvalidArgumentException('unknown'),
        };

        $oauth = new UserOAuth();
        if (!$oauth->tableExists()) {
            throw new \RuntimeException('ระบบยังไม่ได้ติดตั้งตาราง OAuth — รัน 20260923_user_oauth.sql');
        }

        $pid = trim((string) ($profile['id'] ?? ''));
        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $name = trim((string) ($profile['name'] ?? ''));

        if ($pid === '') {
            throw new \RuntimeException('ไม่ได้รับรหัสผู้ใช้จาก ' . $provider . ' — ตรวจสอบ Client ID/Secret และ Callback URL');
        }

        if ($mode === 'link') {
            return $this->finishLinkCallback($provider, $pid, $email, $name, $oauth);
        }

        $linked = $oauth->findByProvider($provider, $pid);
        $um = new User();
        $user = null;
        if ($linked !== null) {
            $user = $um->find((int) $linked['user_id']);
            if ($user && !empty($user['deleted_at'])) {
                $user = null;
            }
        }
        if ($user === null && $email !== '') {
            $user = $um->findBy('email', $email);
            if ($user && !empty($user['deleted_at'])) {
                $user = null;
            }
        }
        if ($user === null) {
            if ($provider === 'line' && $email === '') {
                throw new \RuntimeException(
                    'ไม่พบบัญชีที่ผูกกับ LINE นี้ — ให้เข้าสู่ระบบด้วยชื่อผู้ใช้/รหัสผ่านก่อน แล้วเชื่อม LINE ที่ข้อมูลส่วนตัว'
                );
            }
            throw new \RuntimeException('ไม่พบบัญชีในระบบ — อีเมลต้องตรงกับที่ลงทะเบียนไว้');
        }
        if ((int) ($user['status'] ?? 0) !== 1) {
            throw new \RuntimeException('บัญชีถูกปิดใช้งาน');
        }

        $oauth->link((int) $user['id'], $provider, $pid, $email, $name);

        return [
            'user' => $user,
            'provider' => $provider,
            'provider_user_id' => $pid,
            'mode' => 'login',
        ];
    }

    /**
     * @return array{user: array<string,mixed>, provider: string, provider_user_id: string, mode: string}
     */
    private function finishLinkCallback(
        string $provider,
        string $pid,
        string $email,
        string $name,
        UserOAuth $oauth,
    ): array {
        $userId = (int) ($_SESSION['oauth_link_user_id'] ?? 0);
        unset($_SESSION['oauth_link_user_id']);
        if ($userId <= 0) {
            throw new \RuntimeException('ไม่พบบัญชีผู้ใช้ — กรุณาเข้าสู่ระบบแล้วลองเชื่อมใหม่');
        }

        $um = new User();
        $user = $um->find($userId);
        if ($user === null || !empty($user['deleted_at']) || (int) ($user['status'] ?? 0) !== 1) {
            throw new \RuntimeException('บัญชีถูกปิดใช้งานหรือไม่พบในระบบ');
        }

        $existing = $oauth->findByProvider($provider, $pid);
        if ($existing !== null && (int) ($existing['user_id'] ?? 0) !== $userId) {
            throw new \RuntimeException('บัญชี ' . strtoupper($provider) . ' นี้ผูกกับผู้ใช้อื่นในระบบแล้ว');
        }

        $oauth->link($userId, $provider, $pid, $email, $name);

        return [
            'user' => $user,
            'provider' => $provider,
            'provider_user_id' => $pid,
            'mode' => 'link',
        ];
    }

    public static function consumeReturnRoute(): string
    {
        $route = (string) ($_SESSION['oauth_return_route'] ?? 'login');
        unset($_SESSION['oauth_return_route']);

        return $route !== '' ? $route : 'login';
    }

    /** @param array<string,mixed> $c */
    private function facebookAuthorizeUrl(array $c, string $state): string
    {
        $redirectUri = (string) ($c['redirect_uri'] ?? '');
        $q = http_build_query([
            'client_id' => $c['client_id'],
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'email,public_profile',
            'response_type' => 'code',
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://www.facebook.com/v21.0/dialog/oauth?' . $q;
    }

    /** @param array<string,mixed> $c */
    private function googleAuthorizeUrl(array $c, string $state): string
    {
        $q = http_build_query([
            'client_id' => $c['client_id'],
            'redirect_uri' => $c['redirect_uri'],
            'state' => $state,
            'scope' => 'openid email profile',
            'response_type' => 'code',
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $q;
    }

    /** @param array<string,mixed> $c */
    private function lineAuthorizeUrl(array $c, string $state): string
    {
        $q = http_build_query([
            'response_type' => 'code',
            'client_id' => $c['client_id'],
            'redirect_uri' => $c['redirect_uri'],
            'state' => $state,
            'scope' => 'profile openid email',
        ]);

        return 'https://access.line.me/oauth2/v2.1/authorize?' . $q;
    }

    /**
     * @param array<string,mixed> $c
     * @return array{id: string, email: string, name: string}
     */
    private function facebookProfile(array $c, string $code): array
    {
        $redirectUri = (string) ($c['redirect_uri'] ?? '');
        $params = [
            'client_id' => (string) ($c['client_id'] ?? ''),
            'client_secret' => (string) ($c['client_secret'] ?? ''),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ];

        try {
            $token = $this->httpGet('https://graph.facebook.com/v21.0/oauth/access_token', $params);
        } catch (\Throwable) {
            $token = $this->httpPostForm('https://graph.facebook.com/v21.0/oauth/access_token', $params);
        }

        $access = (string) ($token['access_token'] ?? '');
        if ($access === '') {
            throw new \RuntimeException('Facebook ไม่ส่ง access token — ตรวจสอบ App ID/Secret และ Redirect URI ใน Meta Developers');
        }

        $me = $this->httpGet('https://graph.facebook.com/v21.0/me', [
            'fields' => 'id,name,email',
            'access_token' => $access,
        ]);

        return [
            'id' => (string) ($me['id'] ?? ''),
            'email' => strtolower(trim((string) ($me['email'] ?? ''))),
            'name' => (string) ($me['name'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $c
     * @return array{id: string, email: string, name: string}
     */
    private function googleProfile(array $c, string $code): array
    {
        $token = $this->httpPostForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $c['client_id'],
            'client_secret' => $c['client_secret'],
            'redirect_uri' => $c['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);
        $access = (string) ($token['access_token'] ?? '');
        if ($access === '') {
            throw new \RuntimeException('Google ไม่ส่ง access token');
        }
        $me = $this->httpGetAuth('https://www.googleapis.com/oauth2/v3/userinfo', $access);

        return [
            'id' => (string) ($me['sub'] ?? ''),
            'email' => strtolower(trim((string) ($me['email'] ?? ''))),
            'name' => (string) ($me['name'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $c
     * @return array{id: string, email: string, name: string}
     */
    private function lineProfile(array $c, string $code): array
    {
        $token = $this->httpPostForm('https://api.line.me/oauth2/v2.1/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $c['redirect_uri'],
            'client_id' => $c['client_id'],
            'client_secret' => $c['client_secret'],
        ]);

        $access = trim((string) ($token['access_token'] ?? ''));
        if ($access === '') {
            throw new \RuntimeException('LINE ไม่ส่ง access token — ตรวจสอบ Channel Secret และ Callback URL ให้ตรงกับ LINE Console');
        }

        $id = '';
        $name = '';
        $email = '';

        try {
            $me = $this->httpGetAuth('https://api.line.me/v2/profile', $access);
            $id = trim((string) ($me['userId'] ?? ''));
            $name = trim((string) ($me['displayName'] ?? ''));
        } catch (\Throwable) {
            // ลองจาก id_token / userinfo ต่อ
        }

        $idToken = trim((string) ($token['id_token'] ?? ''));
        if ($idToken !== '') {
            try {
                $verified = $this->httpPostForm('https://api.line.me/oauth2/v2.1/verify', [
                    'id_token' => $idToken,
                    'client_id' => $c['client_id'],
                ]);
                if ($id === '') {
                    $id = trim((string) ($verified['sub'] ?? ''));
                }
                if ($name === '') {
                    $name = trim((string) ($verified['name'] ?? ''));
                }
                if ($email === '') {
                    $email = strtolower(trim((string) ($verified['email'] ?? '')));
                }
            } catch (\Throwable) {
                $payload = $this->decodeJwtPayload($idToken);
                if ($id === '') {
                    $id = trim((string) ($payload['sub'] ?? ''));
                }
                if ($name === '') {
                    $name = trim((string) ($payload['name'] ?? ''));
                }
                if ($email === '') {
                    $email = strtolower(trim((string) ($payload['email'] ?? '')));
                }
            }
        }

        if ($email === '') {
            try {
                $userinfo = $this->httpGetAuth('https://api.line.me/oauth2/v2.1/userinfo', $access);
                $email = strtolower(trim((string) ($userinfo['email'] ?? '')));
            } catch (\Throwable) {
                // ไม่มีสิทธิ์ email จาก LINE
            }
        }

        if ($id === '') {
            throw new \RuntimeException('LINE ไม่ส่ง User ID');
        }

        return [
            'id' => $id,
            'email' => $email,
            'name' => $name,
        ];
    }

    /** @return array<string,mixed> */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (!isset($parts[1])) {
            return [];
        }
        $b64 = strtr($parts[1], '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        $json = base64_decode($b64, true);
        if ($json === false) {
            return [];
        }
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string,string> $params
     * @return array<string,mixed>
     */
    private function httpPostForm(string $url, array $params): array
    {
        return $this->requestJson('POST', $url, $params, [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
    }

    /**
     * @param array<string,string> $params
     * @return array<string,mixed>
     */
    private function httpGet(string $url, array $params): array
    {
        $full = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params);

        return $this->requestJson('GET', $full);
    }

    /** @return array<string,mixed> */
    private function httpGetAuth(string $url, string $accessToken): array
    {
        return $this->requestJson('GET', $url, [], [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
    }

    /**
     * @param array<string,string> $body
     * @param array<string,string> $headers
     * @return array<string,mixed>
     */
    private function requestJson(string $method, string $url, array $body = [], array $headers = []): array
    {
        $method = strtoupper($method);
        $errors = [];

        if (function_exists('curl_init')) {
            try {
                return $this->decodeJson($this->curlRequest($method, $url, $body, $headers));
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($method === 'GET' && ini_get('allow_url_fopen')) {
            try {
                return $this->decodeJson($this->streamGet($url, $headers));
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($method === 'POST' && ini_get('allow_url_fopen')) {
            try {
                return $this->decodeJson($this->streamPost($url, $body, $headers));
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        throw new \RuntimeException($errors[0] ?? 'เชื่อมต่อผู้ให้บริการไม่สำเร็จ (ต้องการ PHP cURL หรือ allow_url_fopen)');
    }

    /**
     * @param array<string,string> $body
     * @param array<string,string> $headers
     * @return array{status:int,body:string}
     */
    private function curlRequest(string $method, string $url, array $body, array $headers): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('cURL init failed');
        }

        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }

        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headerLines,
        ];

        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
            if (!isset($headers['Content-Type'])) {
                $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
            }
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException($err !== '' ? $err : 'cURL request failed');
        }

        return ['status' => $status, 'body' => (string) $response];
    }

    /**
     * @param array<string,string> $headers
     * @return array{status:int,body:string}
     */
    private function streamGet(string $url, array $headers): array
    {
        $headerStr = '';
        foreach ($headers as $k => $v) {
            $headerStr .= $k . ': ' . $v . "\r\n";
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $headerStr,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $raw = file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new \RuntimeException('stream GET failed');
        }

        return ['status' => $this->lastHttpStatus($http_response_header ?? []), 'body' => $raw];
    }

    /**
     * @param array<string,string> $body
     * @param array<string,string> $headers
     * @return array{status:int,body:string}
     */
    private function streamPost(string $url, array $body, array $headers): array
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        $headerStr = '';
        foreach ($headers as $k => $v) {
            $headerStr .= $k . ': ' . $v . "\r\n";
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headerStr,
                'content' => http_build_query($body),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $raw = file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new \RuntimeException('stream POST failed');
        }

        return ['status' => $this->lastHttpStatus($http_response_header ?? []), 'body' => $raw];
    }

    /** @param array<int,string> $headers */
    private function lastHttpStatus(array $headers): int
    {
        foreach ($headers as $line) {
            if (preg_match('#HTTP/\d\.\d\s+(\d+)#', $line, $m)) {
                return (int) $m[1];
            }
        }

        return 0;
    }

    /** @param array{status:int,body:string} $response */
    private function decodeJson(array $response): array
    {
        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            throw new \RuntimeException('ตอบกลับจากผู้ให้บริการไม่ถูกต้อง (HTTP ' . $response['status'] . ')');
        }
        if (isset($json['error'])) {
            $msg = (string) ($json['error_description'] ?? $json['error_message'] ?? $json['error'] ?? 'OAuth error');
            throw new \RuntimeException($msg);
        }
        if ($response['status'] >= 400) {
            throw new \RuntimeException('HTTP ' . $response['status'] . ' จากผู้ให้บริการ');
        }

        return $json;
    }
}
