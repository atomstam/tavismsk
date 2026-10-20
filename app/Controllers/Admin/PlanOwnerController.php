<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\PlanOwnerService;

class PlanOwnerController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['admin']);
        $pending = db()->fetchAll(
            "SELECT o.*, p.plan_no, p.title AS plan_title, u.username, u.title, u.first_name, u.last_name
             FROM learning_plan_owners o
             INNER JOIN learning_plans p ON p.id = o.plan_id
             INNER JOIN users u ON u.id = o.user_id
             WHERE o.status = 'pending'
             ORDER BY o.created_at"
        );
        $owners = db()->fetchAll(
            "SELECT o.*, p.plan_no, p.title AS plan_title, u.username, u.title, u.first_name, u.last_name
             FROM learning_plan_owners o
             INNER JOIN learning_plans p ON p.id = o.plan_id
             INNER JOIN users u ON u.id = o.user_id
             WHERE o.status = 'approved'
             ORDER BY p.plan_no, u.first_name"
        );
        $plans = db()->fetchAll('SELECT id, plan_no, title FROM learning_plans WHERE status = 1 ORDER BY plan_no');
        $trainers = db()->fetchAll(
            "SELECT u.id, u.username, u.title, u.first_name, u.last_name
             FROM users u
             INNER JOIN user_roles r ON r.user_id = u.id AND r.role = 'trainer'
             WHERE u.deleted_at IS NULL AND u.status = 1
             ORDER BY u.first_name, u.last_name"
        );
        $this->view('admin/plan_owners/index', [
            'title' => 'อนุมัติเจ้าของหลักสูตร',
            'breadcrumb' => 'หน้าหลัก / อนุมัติเจ้าของหลักสูตร',
            'pending' => $pending,
            'owners' => $owners,
            'plans' => $plans,
            'trainers' => $trainers,
        ]);
    }

    public function review(): void
    {
        $this->requireRole(['admin']);
        $id = (int) $this->input('id', 0);
        $status = (string) $this->input('status', '');
        $ok = (new PlanOwnerService())->review($id, $status, (int) auth()->id());
        $this->flash($ok ? 'success' : 'error', $ok ? 'บันทึกผลการอนุมัติแล้ว' : 'ไม่พบคำร้อง');
        $this->redirect('/admin/plan-owners');
    }

    public function assign(): void
    {
        $this->requireRole(['admin']);
        $error = (new PlanOwnerService())->assign(
            (int) $this->input('plan_id', 0),
            (int) $this->input('user_id', 0),
            (int) auth()->id()
        );
        $this->flash($error === '' ? 'success' : 'error', $error === '' ? 'มอบหมายเจ้าของหลักสูตรแล้ว' : $error);
        $this->redirect('/admin/plan-owners');
    }

    public function remove(): void
    {
        $this->requireRole(['admin']);
        (new PlanOwnerService())->remove((int) $this->input('id', 0));
        $this->flash('success', 'นำเจ้าของหลักสูตรออกแล้ว');
        $this->redirect('/admin/plan-owners');
    }
}
