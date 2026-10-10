<?php

declare(strict_types=1);

namespace App\Services;

class PermissionService
{
    /** @return array<string, string> */
    public function modules(): array
    {
        $mods = config('permissions', 'modules');
        return is_array($mods) ? $mods : [];
    }

    /** @return array<string, string> */
    public function actions(): array
    {
        $acts = config('permissions', 'actions');
        return is_array($acts) ? $acts : [];
    }

    /** @return array<string, list<string>> */
    public function forRole(?string $role = null): array
    {
        $role = $role ?: (auth()->role() ?? '');
        $role = $this->resolveRoleKey($role);
        $all = config('permissions') ?: [];
        foreach (['modules', 'actions'] as $k) {
            unset($all[$k]);
        }
        if (!isset($all[$role]) || !is_array($all[$role])) {
            return [];
        }
        return $all[$role];
    }

    public function can(?string $role, string $module, string $action = 'view'): bool
    {
        $role = $role ?: (auth()->role() ?? '');
        if ($role === '') {
            return false;
        }
        $perms = $this->forRole($role);
        if (isset($perms['*']) && in_array($action, (array) $perms['*'], true)) {
            return true;
        }
        $actions = (array) ($perms[$module] ?? []);
        return in_array($action, $actions, true);
    }

    public function resolveRoleKey(string $role): string
    {
        if ($role === '') {
            return '';
        }
        $cfg = config('roles', $role);
        if (is_array($cfg) && !empty($cfg['alias_of'])) {
            return (string) $cfg['alias_of'];
        }
        return $role;
    }
}
