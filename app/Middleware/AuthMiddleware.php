<?php

declare(strict_types=1);

namespace App\Middleware;

class AuthMiddleware
{
    public function handle(?string $arg = null): mixed
    {
        auth()->attemptRemember();

        if (!auth()->check()) {
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $uri = '/' . trim(str_replace('\\', '/', (string) $uri), '/');
            if ($uri !== '/') {
                $uri = rtrim($uri, '/');
            }
            if (function_exists('app_base_path')) {
                $base = app_base_path($uri);
                if ($base !== '' && ($uri === $base || str_starts_with($uri, $base . '/'))) {
                    $uri = substr($uri, strlen($base)) ?: '/';
                    $uri = '/' . ltrim((string) $uri, '/');
                }
            }
            if ($uri !== '/' && str_starts_with($uri, '/') && !str_starts_with($uri, '//')) {
                $_SESSION['intended_url'] = $uri;
            }
            flash('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
            header('Location: ' . url('/login'));
            exit;
        }

        if (!auth()->checkTimeout()) {
            flash('error', 'หมดเวลาการใช้งาน กรุณาเข้าสู่ระบบใหม่');
            header('Location: ' . url('/login'));
            exit;
        }

        return true;
    }
}
