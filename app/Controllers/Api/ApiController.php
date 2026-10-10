<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\User;
use Firebase\JWT\JWT;

class ApiController extends Controller
{
    public function token(): void
    {
        $username = trim((string) $this->input('username', ''));
        $password = (string) $this->input('password', '');
        $user = (new User())->findByLogin($username);
        if (!$user || !verify_password($password, $user['password']) || (int) $user['status'] !== 1) {
            $this->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
        $secret = (string) env('JWT_SECRET');
        $ttl = (int) env('JWT_EXPIRE', 86400);
        $payload = [
            'sub' => $user['id'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + $ttl,
        ];
        $jwt = class_exists(JWT::class)
            ? JWT::encode($payload, $secret, 'HS256')
            : bin2hex(random_bytes(32));
        if (!class_exists(JWT::class)) {
            db()->insert('api_tokens', [
                'user_id' => $user['id'],
                'token_hash' => hash('sha256', $jwt),
                'name' => 'api',
                'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->json(['success' => true, 'token' => $jwt, 'expires_in' => $ttl, 'token_type' => 'Bearer']);
    }

    public function me(): void
    {
        $uid = (int) ($_REQUEST['api_user_id'] ?? 0);
        $user = (new User())->find($uid);
        if (!$user) {
            $this->json(['success' => false], 404);
        }
        $this->json(['success' => true, 'data' => (new User())->hide($user)]);
    }

    public function portfolio(): void
    {
        $uid = (int) ($_REQUEST['api_user_id'] ?? 0);
        $type = (string) $this->input('type', '');
        $where = 'deleted_at IS NULL AND user_id = ?';
        $params = [$uid];
        if ($type !== '') {
            $where .= ' AND type = ?';
            $params[] = $type;
        }
        $rows = db()->fetchAll("SELECT * FROM portfolio_items WHERE {$where} ORDER BY start_date DESC LIMIT 200", $params);
        $this->json(['success' => true, 'data' => $rows]);
    }

    public function docs(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'TAVIS API',
                'version' => '1.0.0',
                'description' => 'REST API TAVIS – Traffic Awareness Vaccine Intelligent System สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม',
            ],
            'servers' => [['url' => url('/api')]],
            'paths' => [
                '/token' => ['post' => ['summary' => 'ขอ JWT Token', 'requestBody' => ['content' => ['application/json' => ['schema' => ['properties' => ['username' => ['type' => 'string'], 'password' => ['type' => 'string']]]]]]]],
                '/me' => ['get' => ['summary' => 'ข้อมูลผู้ใช้', 'security' => [['bearerAuth' => []]]]],
                '/portfolio' => ['get' => ['summary' => 'รายการพอร์ตโฟลิโอ', 'security' => [['bearerAuth' => []]]]],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
