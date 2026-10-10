<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\DashboardStatsService;
use App\Services\DataTableService;

class LogController extends Controller
{
    public function activity(): void
    {
        $this->requirePerm('logs');
        $recent = db()->fetchAll(
            "SELECT a.*, CONCAT(u.first_name,' ',u.last_name) AS user_name
             FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT 8"
        );
        $this->view('admin/logs/activity', [
            'title' => 'Activity / Audit Log',
            'breadcrumb' => 'หน้าหลัก / Log',
            'recent' => $recent,
        ]);
    }

    public function login(): void
    {
        $this->requirePerm('logs');
        $this->view('admin/logs/login', [
            'title' => 'Log การเข้าสู่ระบบ',
            'breadcrumb' => 'หน้าหลัก / Log Login',
        ]);
    }

    public function chart(string $key): void
    {
        $this->requirePerm('logs');
        if (!in_array($key, ['login', 'loginStatus'], true)) {
            $this->json(['ok' => false, 'key' => $key, 'message' => 'ไม่พบกราฟนี้'], 404);
        }
        $this->releaseSession();
        $data = (new DashboardStatsService())->chart($key, 0, true, null, true);
        $this->json($data, !empty($data['ok']) ? 200 : 404);
    }

    public function activityDatatable(): void
    {
        $this->requirePerm('logs');
        $this->json(DataTableService::activityLogs(dt_params()));
    }

    public function loginDatatable(): void
    {
        $this->requirePerm('logs');
        $this->json(DataTableService::loginLogs(dt_params()));
    }
}
