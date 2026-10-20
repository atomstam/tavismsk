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
        $section = null;
        $pendingLabel = null;
        $this->seenHrefs = [];

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

            if ($type === 'label' && ($def['audience'] ?? '') !== '') {
                $this->flushSection($section, $out);
                $pendingLabel = null;
                $section = [
                    'type' => 'section',
                    'label' => (string) ($def['label'] ?? ''),
                    'icon' => (string) ($def['icon'] ?? 'fa-layer-group'),
                    'audience' => (string) $def['audience'],
                    'items' => [],
                ];
                continue;
            }

            if ($type === 'label') {
                $pendingLabel = (string) ($def['label'] ?? '');
                continue;
            }

            $built = match ($type) {
                'link' => [$this->buildLink($def, $unreadNotifications)],
                'portfolio' => $this->buildPortfolioLinks(),
                'reports' => [$this->buildReportsGroup($def)],
                'catalog' => $this->buildCatalogLinks((string) ($def['group'] ?? '')),
                default => [],
            };

            $accepted = [];
            foreach ($built as $item) {
                if (!is_array($item) || !$this->acceptItem($item)) {
                    continue;
                }
                $accepted[] = $item;
            }
            if ($accepted === []) {
                continue;
            }

            if ($pendingLabel !== null) {
                $this->pushItem($section, $out, [
                    'type' => 'label',
                    'label' => $pendingLabel,
                ]);
                $pendingLabel = null;
            }
            foreach ($accepted as $item) {
                $this->pushItem($section, $out, $item);
            }
        }

        $this->flushSection($section, $out);
        return $out;
    }

    /** @var array<string, true> */
    private array $seenHrefs = [];

    /** @param array<string, mixed>|null $section @param list<array<string, mixed>> $out */
    private function flushSection(?array &$section, array &$out): void
    {
        if ($section === null || ($section['items'] ?? []) === []) {
            $section = null;
            return;
        }
        $out[] = $section;
        $section = null;
    }

    /** @param array<string, mixed>|null $section @param list<array<string, mixed>> $out @param array<string, mixed> $item */
    private function pushItem(?array &$section, array &$out, array $item): void
    {
        if ($section !== null) {
            $section['items'][] = $item;
            return;
        }
        $out[] = $item;
    }

    /** @param array<string, mixed> $item */
    private function acceptItem(array $item): bool
    {
        $href = (string) ($item['href'] ?? '');
        if ($href === '') {
            return true;
        }
        if (isset($this->seenHrefs[$href])) {
            return false;
        }
        $this->seenHrefs[$href] = true;
        return true;
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
        if (!empty($def['portal_only'])) {
            return is_array($roles) && (in_array($rawRole, $roles, true) || in_array($resolvedRole, $roles, true));
        }
        if ($rawRole === 'admin' || $resolvedRole === 'admin') {
            return true;
        }
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

    /** @return list<array<string, mixed>> */
    private function buildCatalogLinks(string $group = ''): array
    {
        $catalogs = config('catalogs');
        if (!is_array($catalogs)) {
            return [];
        }
        $links = [];
        foreach ($catalogs as $key => $cat) {
            if (!is_array($cat) || !can_perm((string) ($cat['module'] ?? ''), 'view')) {
                continue;
            }
            if ($group !== '' && (string) ($cat['group'] ?? '') !== $group) {
                continue;
            }
            $path = '/admin/catalog/' . $key;
            $links[] = [
                'type' => 'link',
                'label' => (string) ($cat['title'] ?? $key),
                'icon' => (string) ($cat['icon'] ?? 'fa-table'),
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
                'icon' => 'fa-sitemap',
                'href' => url('/admin/reports/org-stats'),
                'active' => active_menu('/admin/reports/org-stats') !== '',
            ],
        ];
        foreach (portfolio_modules() as $k => $m) {
            $path = '/admin/reports/' . $k;
            $children[] = [
                'label' => (string) ($m['title'] ?? $k),
                'icon' => (string) ($m['icon'] ?? 'fa-file-lines'),
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
