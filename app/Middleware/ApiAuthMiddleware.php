<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ApiAuthMiddleware
{
    public function handle(?string $arg = null): mixed
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(\S+)/', $header, $m)) {
            json_response(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $token = $m[1];
        try {
            if (class_exists(JWT::class)) {
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
                $_REQUEST['api_user_id'] = $decoded->sub ?? null;
            } else {
                // Fallback: simple token hash lookup
                $hash = hash('sha256', $token);
                $row = db()->fetch('SELECT user_id FROM api_tokens WHERE token_hash = ? AND (expires_at IS NULL OR expires_at > NOW())', [$hash]);
                if (!$row) {
                    json_response(['success' => false, 'message' => 'Invalid token'], 401);
                }
                $_REQUEST['api_user_id'] = $row['user_id'];
                db()->update('api_tokens', ['last_used_at' => date('Y-m-d H:i:s')], 'token_hash = ?', [$hash]);
            }
        } catch (\Throwable $e) {
            json_response(['success' => false, 'message' => 'Invalid or expired token'], 401);
        }

        return true;
    }
}
