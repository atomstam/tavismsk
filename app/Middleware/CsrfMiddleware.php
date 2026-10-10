<?php

declare(strict_types=1);

namespace App\Middleware;

class CsrfMiddleware
{
    public function handle(?string $arg = null): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf()) {
                http_response_code(419);
                if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                    || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
                    json_response(['success' => false, 'message' => 'CSRF token mismatch'], 419);
                }
                flash('error', 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('/')));
                exit;
            }
        }
        return true;
    }
}
