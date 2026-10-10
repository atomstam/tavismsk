<?php

declare(strict_types=1);

namespace App\Middleware;

class RoleMiddleware
{
    public function handle(?string $arg = null): mixed
    {
        if (!auth()->check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        if ($arg) {
            $roles = explode(',', $arg);
            if (!auth()->hasRole($roles)) {
                flash('error', 'สิทธิ์ไม่เพียงพอ');
                header('Location: ' . url('/admin/dashboard'));
                exit;
            }
        }
        return true;
    }
}
