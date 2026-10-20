<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\UserRoleService;

class TrainerManageController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['admin']);
        $q = trim((string) $this->input('q', ''));
        $where = 'u.deleted_at IS NULL';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
            $s = '%' . $q . '%';
            $params = [$s, $s, $s, $s];
        }
        $rows = db()->fetchAll(
            "SELECT u.id, u.username, u.email, u.title, u.first_name, u.last_name, u.status, u.role,
                    (SELECT GROUP_CONCAT(r.role ORDER BY r.role) FROM user_roles r WHERE r.user_id = u.id) AS roles
             FROM users u
             WHERE {$where}
             ORDER BY u.first_name, u.last_name
             LIMIT 200",
            $params
        );
        $this->view('admin/trainers/index', [
            'title' => 'จัดการวิทยากร',
            'breadcrumb' => 'หน้าหลัก / จัดการวิทยากร',
            'rows' => $rows,
            'q' => $q,
        ]);
    }

    public function grant(): void
    {
        $this->requireRole(['admin']);
        $userId = (int) $this->input('user_id', 0);
        $user = db()->fetch('SELECT id FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]);
        if (!$user) {
            $this->flash('error', 'ไม่พบผู้ใช้');
            $this->redirect('/admin/trainers');
        }
        (new UserRoleService())->grant($userId, 'trainer');
        $this->flash('success', 'กำหนดบทบาทวิทยากรแล้ว ผู้ใช้นี้ยังเข้าโหมดผู้เรียนได้');
        $this->redirect('/admin/trainers');
    }

    public function revoke(): void
    {
        $this->requireRole(['admin']);
        $userId = (int) $this->input('user_id', 0);
        if ($userId === (int) auth()->id()) {
            $this->flash('error', 'ถอนบทบาทของบัญชีที่กำลังใช้งานเองไม่ได้');
            $this->redirect('/admin/trainers');
        }
        (new UserRoleService())->revoke($userId, 'trainer');
        $this->flash('success', 'ถอนบทบาทวิทยากรแล้ว');
        $this->redirect('/admin/trainers');
    }
}
