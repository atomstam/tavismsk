<?php

declare(strict_types=1);

namespace App\Middleware;

class GuestMiddleware
{
    public function handle(?string $arg = null): mixed
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            auth()->attemptRemember();
        }
        if (auth()->check()) {
            header('Location: ' . url('/admin/dashboard'));
            exit;
        }
        return true;
    }
}
