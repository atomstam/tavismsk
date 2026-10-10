<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;

class NotificationController extends Controller
{
    public function index(): void
    {
        $this->requirePerm('notifications');
        $this->view('admin/notifications/index', [
            'title' => 'การแจ้งเตือน',
            'breadcrumb' => 'หน้าหลัก / แจ้งเตือน',
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('notifications');
        $this->json(\App\Services\DataTableService::notifications(dt_params()));
    }

    public function read(string $id): void
    {
        db()->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [(int) $id, auth()->id()]);
        $row = db()->fetch('SELECT * FROM notifications WHERE id = ?', [(int) $id]);
        if (!empty($row['link'])) {
            $this->redirect($row['link']);
        }
        $this->redirect('/admin/notifications');
    }

    public function readAll(): void
    {
        db()->query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [auth()->id()]);
        $this->flash('success', 'อ่านทั้งหมดแล้ว');
        $this->redirect('/admin/notifications');
    }
}
