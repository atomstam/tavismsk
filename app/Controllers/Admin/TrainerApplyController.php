<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\PlanOwnerService;

class TrainerApplyController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['trainer']);
        $uid = (int) auth()->id();
        $plans = db()->fetchAll(
            "SELECT p.id, p.plan_no, p.title, p.hours, o.status, o.note
             FROM learning_plans p
             LEFT JOIN learning_plan_owners o ON o.plan_id = p.id AND o.user_id = ?
             WHERE p.status = 1
             ORDER BY p.plan_no",
            [$uid]
        );
        $this->view('admin/trainers/apply', [
            'title' => 'สมัครเป็นวิทยากร',
            'breadcrumb' => 'หน้าหลัก / สมัครเป็นวิทยากร',
            'plans' => $plans,
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['trainer']);
        $planId = (int) $this->input('plan_id', 0);
        $note = trim((string) $this->input('note', ''));
        $error = (new PlanOwnerService())->request((int) auth()->id(), $planId, $note);
        $this->flash($error === '' ? 'success' : 'error', $error === '' ? 'ส่งคำร้องขอเป็นเจ้าของหลักสูตรแล้ว' : $error);
        $this->redirect('/admin/trainer-apply');
    }
}
