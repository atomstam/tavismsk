<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'username', 'email', 'password', 'title', 'first_name', 'last_name',
        'phone', 'province_id', 'amphur_id', 'tumbon_id', 'zipcode',
        'avatar', 'role', 'department_id', 'personnel_id', 'status',
        'email_verified_at', 'two_factor_enabled', 'two_factor_secret',
        'otp_code', 'otp_expires_at', 'remember_token',
        'password_reset_token', 'password_reset_expires', 'google_id',
        'last_login_at', 'last_login_ip', 'login_attempts', 'locked_until',
        'theme', 'locale', 'deleted_at', 'legacy_user_id',
        'org_unit_id', 'cluster_code', 'area_id', 'school_code', 'org_name', 'affiliation', 'id_card', 'org_level',
        'personal_type_id', 'position_text', 'member_type',
    ];

    public function findByLogin(string $login): ?array
    {
        $user = $this->db->fetch(
            'SELECT * FROM users WHERE username = ? AND deleted_at IS NULL LIMIT 1',
            [$login]
        );
        if ($user) {
            return $user;
        }
        if (str_contains($login, '@')) {
            return $this->db->fetch(
                'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
                [$login]
            );
        }

        return null;
    }

    public function incrementLoginAttempts(int $id): void
    {
        $max = (int) config('security', 'login_max_attempts', 5);
        $minutes = (int) config('security', 'login_lockout_minutes', 15);
        $lockUntil = date('Y-m-d H:i:s', time() + $minutes * 60);
        $this->db->query(
            'UPDATE users
             SET login_attempts = login_attempts + 1,
                 locked_until = IF(login_attempts + 1 >= ?, ?, locked_until)
             WHERE id = ?',
            [$max, $lockUntil, $id]
        );
    }

    public function markLoginSuccess(int $id, ?string $rehash = null): void
    {
        $data = [
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => request_ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
        ];
        if ($rehash !== null && $rehash !== '') {
            $data['password'] = $rehash;
        }
        $this->update($id, $data);
    }

    public function resetLoginAttempts(int $id): void
    {
        $this->markLoginSuccess($id);
    }
}
