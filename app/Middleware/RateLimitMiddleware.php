<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Simple file-based rate limiter (IP + action)
 * Usage in routes: ['csrf', 'rate:login:5:60'] = 5 requests / 60 seconds
 */
class RateLimitMiddleware
{
    public function handle(?string $arg = null): mixed
    {
        $arg = (string) $arg;
        // format: action:max:seconds  e.g. login:5:60
        $parts = array_values(array_filter(explode(':', $arg)));
        $action = $parts[0] ?? 'default';
        $max = max(1, (int) ($parts[1] ?? 10));
        $seconds = max(1, (int) ($parts[2] ?? 60));

        $ip = $this->clientIp();
        $key = hash('sha256', $action . '|' . $ip);
        $dir = STORAGE_PATH . '/cache/rate';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/' . $key . '.json';
        $now = time();
        $data = ['start' => $now, 'count' => 0];

        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded) && isset($decoded['start'], $decoded['count'])) {
                $data = $decoded;
            }
        }

        if (($now - (int) $data['start']) >= $seconds) {
            $data = ['start' => $now, 'count' => 0];
        }

        $data['count'] = (int) $data['count'] + 1;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        if ((int) $data['count'] > $max) {
            $retry = max(1, $seconds - ($now - (int) $data['start']));
            http_response_code(429);
            header('Retry-After: ' . $retry);
            header('Content-Type: application/json; charset=utf-8');
            $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                || str_contains($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest')
                || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api')
                || str_contains($_SERVER['REQUEST_URI'] ?? '', '/stats/')
                || str_contains($_SERVER['REQUEST_URI'] ?? '', '/dashboard/card/')
                || str_contains($_SERVER['REQUEST_URI'] ?? '', '/dashboard/chart/');
            if ($wantsJson) {
                echo json_encode([
                    'success' => false,
                    'message' => 'คำขอบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่',
                    'retry_after' => $retry,
                ], JSON_UNESCAPED_UNICODE);
            } else {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'พยายามบ่อยเกินไป กรุณารอ ' . $retry . ' วินาที'];
                }
                $ref = $_SERVER['HTTP_REFERER'] ?? '/';
                header('Location: ' . $ref);
            }
            exit;
        }

        return true;
    }

    private function clientIp(): string
    {
        // Prefer direct REMOTE_ADDR; only trust proxy headers when configured
        $trustProxy = filter_var(env('TRUST_PROXY', false), FILTER_VALIDATE_BOOLEAN);
        if ($trustProxy) {
            $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if (is_string($xff) && $xff !== '') {
                $first = trim(explode(',', $xff)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
