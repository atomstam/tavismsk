<?php

declare(strict_types=1);

namespace App\Services;

class UserRoleService
{
    /** @var list<string> */
    public const PORTALS = ['admin', 'trainer', 'user'];

    /** @return list<string> */
    public function of(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        try {
            $rows = db()->fetchAll('SELECT role FROM user_roles WHERE user_id = ? ORDER BY role', [$userId]);
        } catch (\Throwable $e) {
            $row = db()->fetch('SELECT role FROM users WHERE id = ?', [$userId]);
            $role = (string) ($row['role'] ?? 'user');
            return $role !== '' ? [$role] : ['user'];
        }
        $roles = [];
        foreach ($rows as $row) {
            $role = (string) $row['role'];
            if (in_array($role, self::PORTALS, true)) {
                $roles[] = $role;
            }
        }
        if ($roles === []) {
            $this->grant($userId, 'user');
            return ['user'];
        }
        return $roles;
    }

    public function has(int $userId, string $role): bool
    {
        return in_array($role, $this->of($userId), true);
    }

    public function grant(int $userId, string $role): void
    {
        if ($userId <= 0 || !in_array($role, self::PORTALS, true)) {
            return;
        }
        $exists = db()->fetch('SELECT id FROM user_roles WHERE user_id = ? AND role = ?', [$userId, $role]);
        if (!$exists) {
            db()->insert('user_roles', [
                'user_id' => $userId,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        if ($role !== 'user') {
            $this->grant($userId, 'user');
            return;
        }
        $this->syncPrimary($userId);
    }

    public function revoke(int $userId, string $role): void
    {
        if ($role === 'user' || $userId <= 0) {
            return;
        }
        db()->delete('user_roles', 'user_id = ? AND role = ?', [$userId, $role]);
        $this->syncPrimary($userId);
    }

    public function syncPrimary(int $userId): void
    {
        $roles = [];
        foreach (db()->fetchAll('SELECT role FROM user_roles WHERE user_id = ?', [$userId]) as $row) {
            $roles[] = (string) $row['role'];
        }
        $primary = 'user';
        if (in_array('admin', $roles, true)) {
            $primary = 'admin';
        } elseif (in_array('trainer', $roles, true)) {
            $primary = 'trainer';
        }
        db()->update('users', ['role' => $primary, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
    }

    public static function label(string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'trainer' => 'Trainer',
            default => 'User',
        };
    }
}
