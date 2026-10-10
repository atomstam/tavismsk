<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * อนุญาตเฉพาะบทบาทที่เข้าหลังบ้านได้ (ไม่รวม guest)
 */
class AdminMiddleware
{
    public function handle(?string $arg = null): mixed
    {
        if (!auth()->check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        // admin / trainer / user (+ legacy aliases via Auth::hasRole)
        $allowed = ['admin', 'trainer', 'user'];
        if ($arg) {
            $allowed = explode(',', $arg);
        }
        if (!auth()->hasRole($allowed)) {
            flash('error', 'คุณไม่มีสิทธิ์เข้าถึงส่วนผู้ดูแลระบบ');
            header('Location: ' . url('/'));
            exit;
        }
        return true;
    }
}
