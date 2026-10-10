<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\User;
use App\Services\SocialAuthService;

class SocialLoginController extends Controller
{
    public function start(string $provider): void
    {
        if (auth()->check()) {
            $this->redirect('/admin/dashboard');
        }

        try {
            (new SocialAuthService())->beginRedirect($provider);
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/login');
        }
    }

    public function callback(string $provider): void
    {
        $code = trim((string) ($_GET['code'] ?? ''));
        $state = trim((string) ($_GET['state'] ?? ''));

        try {
            $result = (new SocialAuthService())->finishCallback($provider, $code, $state);

            if (($result['mode'] ?? 'login') === 'link') {
                $this->flash('success', 'เชื่อมบัญชี ' . strtoupper($provider) . ' เรียบร้อยแล้ว');
                $this->redirect('/admin/profile');
            }

            if (auth()->check()) {
                $this->redirect('/admin/dashboard');
            }

            $user = $result['user'];
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                throw new \RuntimeException('ไม่พบบัญชีผู้ใช้');
            }

            // โหลด fresh row + บันทึก login
            $fresh = (new User())->find($userId);
            if (!$fresh || (int) ($fresh['status'] ?? 0) !== 1 || !empty($fresh['deleted_at'])) {
                throw new \RuntimeException('บัญชีถูกปิดใช้งาน');
            }

            (new User())->markLoginSuccess($userId);
            auth()->login($fresh, false);

            try {
                db()->insert('login_logs', [
                    'user_id' => $userId,
                    'username' => (string) ($fresh['username'] ?? ''),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                    'status' => 'success',
                    'message' => 'oauth:' . $provider,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                // ไม่บล็อก login
            }

            activity_log('login_oauth', 'auth', 'เข้าสู่ระบบด้วย ' . $provider, null, [
                'user_id' => $userId,
                'provider' => $provider,
            ]);

            $this->flash('success', 'ยินดีต้อนรับ ' . ($fresh['first_name'] ?? ''));

            $intended = (string) ($_SESSION['intended_url'] ?? '');
            unset($_SESSION['intended_url']);
            if (
                $intended !== ''
                && str_starts_with($intended, '/')
                && !str_starts_with($intended, '//')
                && !str_contains($intended, "\n")
            ) {
                $this->redirect($intended);
            }
            $this->redirect('/admin/dashboard');
        } catch (\Throwable $e) {
            error_log('OAuth callback [' . $provider . ']: ' . $e->getMessage());
            $return = SocialAuthService::consumeReturnRoute();
            if ($return === 'account_profile') {
                $this->flash('error', $e->getMessage());
                $this->redirect('/admin/profile');
            }
            $this->flash('error', $e->getMessage());
            $this->redirect('/login');
        }
    }
}
