<?php

declare(strict_types=1);

namespace App\Core;

class Security
{
    public static function generateToken(int $bytes = 16): string
    {
        return bin2hex(random_bytes(max(1, $bytes)));
    }

    public static function verifyCsrf(?string $token = null): bool
    {
        return verify_csrf($token);
    }
}
