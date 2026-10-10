<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\TrainingService;

class EnrollmentController extends Controller
{
    private TrainingService $training;

    public function __construct()
    {
        $this->training = new TrainingService();
    }

    public function index(): void
    {
        $this->requirePerm('enrollments');
        $this->view('admin/enrollments/index', [
            'title' => 'การลงทะเบียนอบรม',
            'breadcrumb' => 'หน้าหลัก / ลงทะเบียนอบรม',
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('enrollments');
        $this->json(\App\Services\DataTableService::enrollments(dt_params()));
    }

    public function approve(string $id): void
    {
        $this->requirePerm('enrollments', 'edit');
        if ($this->training->approve((int) $id, (int) auth()->id())) {
            activity_log('approve', 'enrollments', 'อนุมัติการลงทะเบียน #' . $id);
            flash('success', 'อนุมัติการลงทะเบียนแล้ว');
        } else {
            flash('error', 'ไม่สามารถอนุมัติรายการนี้ได้');
        }
        $this->redirect('/admin/enrollments');
    }

    public function store(): void
    {
        $this->requirePerm('enrollments', 'create');
        $userId = (int) $this->input('user_id', auth()->id());
        if (!is_trainer_like() && $userId !== (int) auth()->id()) {
            flash('error', 'ไม่มีสิทธิ์ลงทะเบียนแทนผู้อื่น');
            $this->redirect('/admin/enrollments');
        }
        $orgId = (int) $this->input('org_unit_id', 0) ?: null;
        if (!$orgId) {
            $me = auth()->user();
            $orgId = isset($me['org_unit_id']) ? (int) $me['org_unit_id'] : null;
        }
        $id = $this->training->enroll([
            'user_id' => $userId,
            'org_unit_id' => $orgId,
            'note' => (string) $this->input('note', ''),
        ]);
        activity_log('create', 'enrollments', 'ลงทะเบียนอบรม #' . $id);
        flash('success', 'ลงทะเบียนอบรมสำเร็จ รอการอนุมัติ');
        $this->redirect('/admin/enrollments');
    }
}
