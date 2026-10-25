<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\EnrollmentManageService;
use App\Services\TrainingService;

class EnrollmentController extends Controller
{
    private TrainingService $training;
    private EnrollmentManageService $manage;

    public function __construct()
    {
        $this->training = new TrainingService();
        $this->manage = new EnrollmentManageService();
    }

    /** ผู้เรียน: ลงทะเบียนของตัวเอง + หลักสูตรที่เรียนอยู่ */
    public function index(): void
    {
        $this->requirePerm('enrollments');
        $role = (string) (auth()->role() ?? 'user');
        if ($role === 'admin' || $role === 'trainer') {
            $this->redirect('/admin/plan-enrollments');
        }

        $uid = (int) auth()->id();
        $this->view('admin/enrollments/index_user', [
            'title' => 'ลงทะเบียน/เข้าเรียน',
            'breadcrumb' => 'หน้าหลัก / ลงทะเบียน/เข้าเรียน',
            'role' => 'user',
            'plans' => $this->manage->planSummaries('user', $uid),
            'stats' => $this->manage->dashboardStats('user', $uid),
            'myEnrollment' => $this->manage->myEnrollment($uid),
        ]);
    }

    /** Admin / Trainer: บริหารจัดการหลักสูตรและสารสนเทศผู้เรียน */
    public function manage(): void
    {
        $this->requirePerm('enrollments');
        $role = (string) (auth()->role() ?? 'user');
        if ($role !== 'admin' && $role !== 'trainer') {
            $this->redirect('/admin/enrollments');
        }

        $uid = (int) auth()->id();
        $this->view('admin/enrollments/index_manage', [
            'title' => 'บริหารหลักสูตร',
            'breadcrumb' => 'หน้าหลัก / บริหารหลักสูตร',
            'role' => $role,
            'plans' => $this->manage->planSummaries($role, $uid),
            'stats' => $this->manage->dashboardStats($role, $uid),
        ]);
    }

    public function plan(string $planId): void
    {
        $this->requirePerm('enrollments', 'view');
        $pid = (int) $planId;
        $role = (string) (auth()->role() ?? 'user');
        $uid = (int) auth()->id();
        $plan = $this->manage->plan($pid);
        $back = ($role === 'admin' || $role === 'trainer')
            ? '/admin/plan-enrollments'
            : '/admin/enrollments';

        if (!$plan || !$this->manage->canViewPlan($pid, $role, $uid)) {
            $this->flash('error', 'ไม่พบหลักสูตร หรือไม่มีสิทธิ์เข้าดู');
            $this->redirect($back);
        }

        $learners = [];
        $mine = null;
        if ($role === 'admin' || $role === 'trainer') {
            $learners = $this->manage->learnersForPlan($pid);
        } else {
            $mine = $this->manage->myPlanProgress($pid, $uid);
        }

        $this->view('admin/enrollments/plan', [
            'title' => 'หลักสูตรที่ ' . (int) ($plan['plan_no'] ?? 0),
            'breadcrumb' => ($role === 'admin' || $role === 'trainer')
                ? 'หน้าหลัก / บริหารหลักสูตร / รายละเอียดหลักสูตร'
                : 'หน้าหลัก / ลงทะเบียน/เข้าเรียน / รายละเอียดหลักสูตร',
            'role' => $role,
            'plan' => $plan,
            'learners' => $learners,
            'mine' => $mine,
            'backUrl' => $back,
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('enrollments');
        if (auth()->role() !== 'admin') {
            $this->json(['error' => 'forbidden'], 403);
        }
        $this->json(\App\Services\DataTableService::enrollments(dt_params()));
    }

    public function approve(string $id): void
    {
        $this->requirePerm('enrollments', 'edit');
        if (auth()->role() !== 'admin') {
            flash('error', 'เฉพาะแอดมินที่อนุมัติการลงทะเบียนหลักสูตรได้');
            $this->redirect('/admin/plan-enrollments');
        }
        if ($this->training->approve((int) $id, (int) auth()->id())) {
            activity_log('approve', 'enrollments', 'อนุมัติการลงทะเบียน #' . $id);
            flash('success', 'อนุมัติการลงทะเบียนแล้ว');
        } else {
            flash('error', 'ไม่สามารถอนุมัติรายการนี้ได้');
        }
        $this->redirect('/admin/plan-enrollments');
    }

    public function store(): void
    {
        $this->requirePerm('enrollments', 'create');
        $role = (string) (auth()->role() ?? 'user');
        $userId = (int) $this->input('user_id', auth()->id());

        if ($role === 'trainer') {
            flash('error', 'วิทยากรไม่ได้ลงทะเบียนหลักสูตรแทนผู้เรียนจากหน้านี้');
            $this->redirect('/admin/plan-enrollments');
        }
        if ($role === 'user' && $userId !== (int) auth()->id()) {
            flash('error', 'ไม่มีสิทธิ์ลงทะเบียนแทนผู้อื่น');
            $this->redirect('/admin/enrollments');
        }
        if ($role !== 'admin' && $role !== 'user') {
            flash('error', 'ไม่มีสิทธิ์ลงทะเบียน');
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
        activity_log('create', 'enrollments', 'ลงทะเบียนหลักสูตร #' . $id);
        flash('success', $role === 'admin' ? 'ลงทะเบียนหลักสูตรสำเร็จ' : 'ลงทะเบียนหลักสูตรสำเร็จ รอการอนุมัติ');
        $this->redirect($role === 'admin' ? '/admin/plan-enrollments' : '/admin/enrollments');
    }
}
