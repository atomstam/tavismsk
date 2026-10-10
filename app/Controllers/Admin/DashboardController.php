<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\DashboardStatsService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requirePerm('dashboard');
        $seeAll = can_see_all_personnel();
        $filterUser = $seeAll ? (int) $this->input('user_id', 0) : 0;

        $this->view('admin/dashboard/index', [
            'title' => 'ภาพรวมระบบ',
            'breadcrumb' => 'หน้าหลัก / ภาพรวมระบบ',
            'people' => $this->peopleOptions(),
            'filterUser' => $filterUser,
        ]);
    }

    public function card(string $key): void
    {
        $this->requirePerm('dashboard');
        [$filterUser, $seeAll] = $this->statsScope();
        $selfId = auth()->id();
        $this->releaseSession();
        $data = (new DashboardStatsService())->card($key, $filterUser, $seeAll, $selfId);
        $this->json($data, !empty($data['ok']) ? 200 : 404);
    }

    public function chart(string $key): void
    {
        $this->requirePerm('dashboard');
        [$filterUser, $seeAll] = $this->statsScope();
        $selfId = auth()->id();
        $this->releaseSession();
        $data = (new DashboardStatsService())->chart($key, $filterUser, $seeAll, $selfId, true);
        $this->json($data, !empty($data['ok']) ? 200 : 404);
    }

    public function widgets(): void
    {
        $this->releaseSession();
        $this->json([
            'clock' => date('H:i:s'),
            'date' => thai_date(date('Y-m-d H:i:s'), true),
            'visitors_today' => (int) db()->count('visitors', 'DATE(visited_at) = CURDATE()'),
        ]);
    }

    public function search(): void
    {
        $q = trim((string) $this->input('q', ''));
        $results = ['personnel' => [], 'portfolio' => [], 'users' => []];
        if (mb_strlen($q) < 2) {
            $this->json(['results' => $results]);
        }
        $s = '%' . $q . '%';
        $results['personnel'] = db()->fetchAll(
            'SELECT id, title, first_name, last_name FROM personnel WHERE deleted_at IS NULL AND (first_name LIKE ? OR last_name LIKE ? OR code LIKE ?) LIMIT 8',
            [$s, $s, $s]
        );
        $results['portfolio'] = db()->fetchAll(
            'SELECT id, type, title FROM portfolio_items WHERE deleted_at IS NULL AND title LIKE ? LIMIT 8',
            [$s]
        );
        if (can_perm('users', 'view')) {
            $results['users'] = db()->fetchAll(
                'SELECT id, username, first_name, last_name FROM users WHERE deleted_at IS NULL AND (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?) LIMIT 8',
                [$s, $s, $s]
            );
        }
        $this->json(['results' => $results]);
    }

    /** @return array{0:int,1:bool} */
    private function statsScope(): array
    {
        $seeAll = can_see_all_personnel();
        $filterUser = $seeAll ? (int) $this->input('user_id', 0) : 0;

        return [$filterUser, $seeAll];
    }

    /** @return list<array> */
    private function peopleOptions(): array
    {
        if (!is_admin_like()) {
            return [];
        }
        return db()->fetchAll(
            "SELECT id, title, first_name, last_name FROM users WHERE deleted_at IS NULL AND status = 1 ORDER BY first_name"
        );
    }
}
