<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Auth Session Manager
 */
class Auth
{
    private static ?Auth $instance = null;
    private ?array $user = null;

    private function __construct()
    {
        if (isset($_SESSION['user_id'])) {
            $this->loadUser((int) $_SESSION['user_id']);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user['id'] ?? null;
    }

    public function role(): ?string
    {
        $active = isset($_SESSION['active_role']) ? (string) $_SESSION['active_role'] : '';
        $owned = $this->roles();
        if ($active !== '' && in_array($active, $owned, true)) {
            return $active;
        }
        return $this->user['role'] ?? null;
    }

    /** @return list<string> */
    public function roles(): array
    {
        $roles = $this->user['roles'] ?? null;
        if (is_array($roles) && $roles !== []) {
            return array_values($roles);
        }
        $role = (string) ($this->user['role'] ?? '');
        return $role !== '' ? [$role] : [];
    }

    /** Primary role after resolving config/roles.php alias_of (e.g. director → admin). */
    public function resolvedRole(): ?string
    {
        $role = $this->role();
        if ($role === null || $role === '') {
            return $role;
        }
        $cfg = config('roles', $role);
        if (is_array($cfg) && !empty($cfg['alias_of'])) {
            return (string) $cfg['alias_of'];
        }
        return $role;
    }

    public function hasRole(array|string $roles): bool
    {
        if (!$this->check()) {
            return false;
        }
        $roles = (array) $roles;
        $raw = $this->role();
        $resolved = $this->resolvedRole();
        return in_array($raw, $roles, true) || in_array($resolved, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['admin']);
    }

    public function isTrainer(): bool
    {
        return $this->hasRole(['trainer']) && !$this->isAdmin();
    }

    public function isLearner(): bool
    {
        return $this->hasRole(['user']) && !$this->hasRole(['admin', 'trainer']);
    }

    public function login(array $user, bool $remember = false, ?string $portal = null): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_at'] = time();
        $_SESSION['last_activity'] = time();
        $this->user = $user;
        $this->attachRoles();
        $roles = $this->roles();
        if ($portal !== null && in_array($portal, $roles, true)) {
            $_SESSION['active_role'] = $portal;
        } elseif (empty($_SESSION['active_role']) || !in_array((string) $_SESSION['active_role'], $roles, true)) {
            $_SESSION['active_role'] = (string) ($user['role'] ?? ($roles[0] ?? 'user'));
        }
        $_SESSION['user_role'] = (string) $_SESSION['active_role'];

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $db = Database::getInstance();
            $db->update('users', [
                'remember_token' => $hash,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$user['id']]);
            setcookie('remember_token', $token, $this->cookieParams(time() + (86400 * 30)));
        }
    }

    public function logout(): void
    {
        if ($this->check()) {
            $db = Database::getInstance();
            $db->update('users', ['remember_token' => null], 'id = ?', [$this->id()]);
        }
        $_SESSION = [];
        $clear = $this->cookieParams(time() - 3600);
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', $clear);
        }
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', $clear);
        }
        session_destroy();
        $this->user = null;
    }

    /** @return array{expires:int,path:string,secure:bool,httponly:bool,samesite:string} */
    private function cookieParams(int $expires): array
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
            || str_starts_with(strtolower((string) env('APP_URL', '')), 'https://')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

        return [
            'expires' => $expires,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    public function attemptRemember(): bool
    {
        if ($this->check() || empty($_COOKIE['remember_token'])) {
            return false;
        }
        $hash = hash('sha256', $_COOKIE['remember_token']);
        $db = Database::getInstance();
        $user = $db->fetch('SELECT * FROM users WHERE remember_token = ? AND status = 1', [$hash]);
        if ($user) {
            $this->login($user, true);
            return true;
        }
        return false;
    }

    public function checkTimeout(): bool
    {
        $timeout = (int) env('SESSION_TIMEOUT', 3600);
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            $this->logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    private function loadUser(int $id): void
    {
        try {
            $db = Database::getInstance();
            $user = $db->fetch('SELECT * FROM users WHERE id = ? AND status = 1', [$id]);
            $this->user = $user ?: null;
            if (!$user) {
                unset($_SESSION['user_id'], $_SESSION['active_role']);
                return;
            }
            $this->attachRoles();
        } catch (\Throwable $e) {
            $this->user = null;
        }
    }

    private function attachRoles(): void
    {
        if (!$this->user) {
            return;
        }
        try {
            $this->user['roles'] = (new \App\Services\UserRoleService())->of((int) $this->user['id']);
        } catch (\Throwable $e) {
            $role = (string) ($this->user['role'] ?? 'user');
            $this->user['roles'] = $role !== '' ? [$role] : ['user'];
        }
    }
}
