<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Builds visible admin sidebar items for the current (or given) role.
 */
class SidebarMenuService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function items(?string $role = null, int $unreadNotifications = 0): array
    {
        $role = $role ?: (auth()->role() ?? '');
        $resolved = $this->resolveRole($role);
        $defs = config('menu');
        if (!is_array($defs)) {
            return [];
        }

        $out = [];
        $pendingLabel = null;

        foreach ($defs as $def) {
            if (!is_array($def)) {
                continue;
            }
            $type = (string) ($def['type'] ?? 'link');

            if (!$this->roleAllowed($def, $role, $resolved)) {
                continue;
            }

            $module = (string) ($def['module'] ?? '');
            $action = (string) ($def['action'] ?? 'view');
            if ($module !== '' && !can_perm($module, $action)) {
                continue;
            }

            if ($type === 'label') {
                $pendingLabel = [
                    'type' => 'label',
                    'label' => (string) ($def['label'] ?? ''),
                ];
                continue;
            }

            $built = match ($type) {
                'link' => $this->buildLink($def, $unreadNotifications),
                'portfolio' => $this->buildPortfolioLinks(),
                'reports' => $this->buildReportsGroup($def),
                default => null,
            };

            if ($built === null || $built === []) {
                continue;
            }

            if ($pendingLabel !== null) {
                $out[] = $pendingLabel;
                $pendingLabel = null;
            }

            if ($type === 'portfolio') {
                foreach ($built as $link) {
                    $out[] = $link;
                }
            } else {
                $out[] = $built;
            }
        }

        return $out;
    }

    public function resolveRole(string $role): string
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

    /** @param array<string, mixed> $def */
    private function roleAllowed(array $def, string $rawRole, string $resolvedRole): bool
    {
        $roles = $def['roles'] ?? null;
        if (!is_array($roles) || $roles === []) {
            return true;
        }
        return in_array($rawRole, $roles, true) || in_array($resolvedRole, $roles, true);
    }

    /**
     * @param array<string, mixed> $def
     * @return array<string, mixed>
     */
    private function buildLink(array $def, int $unread): array
    {
        $path = (string) ($def['path'] ?? '/');
        $exact = !empty($def['exact']);
        $item = [
            'type' => 'link',
            'label' => (string) ($def['label'] ?? ''),
            'icon' => (string) ($def['icon'] ?? 'fa-circle'),
            'href' => url($path),
            'active' => active_menu($path, $exact) !== '',
            'exact' => $exact,
        ];
        if (($def['badge'] ?? '') === 'notifications' && $unread > 0) {
            $item['badge'] = $unread;
        }
        return $item;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildPortfolioLinks(): array
    {
        $links = [];
        foreach (portfolio_modules() as $k => $m) {
            $path = '/admin/portfolio/' . $k;
            $links[] = [
                'type' => 'link',
                'label' => (string) ($m['title'] ?? $k),
                'icon' => (string) ($m['icon'] ?? 'fa-folder'),
                'href' => url($path),
                'active' => active_menu($path) !== '',
            ];
        }
        return $links;
    }

    /**
     * @param array<string, mixed> $def
     * @return array<string, mixed>|null
     */
    private function buildReportsGroup(array $def): ?array
    {
        if (!can_perm('reports', 'view')) {
            return null;
        }
        $currentPath = current_path();
        $open = str_starts_with($currentPath, '/admin/reports');
        $children = [
            [
                'label' => 'สารสนเทศตามหน่วยงาน',
                'href' => url('/admin/reports/org-stats'),
                'active' => active_menu('/admin/reports/org-stats') !== '',
            ],
        ];
        foreach (portfolio_modules() as $k => $m) {
            $path = '/admin/reports/' . $k;
            $children[] = [
                'label' => (string) ($m['title'] ?? $k),
                'href' => url($path),
                'active' => active_menu($path) !== '',
            ];
        }
        return [
            'type' => 'group',
            'label' => (string) ($def['label'] ?? 'รายงาน'),
            'icon' => (string) ($def['icon'] ?? 'fa-file-lines'),
            'open' => $open,
            'children' => $children,
        ];
    }
}
