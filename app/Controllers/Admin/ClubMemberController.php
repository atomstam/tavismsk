<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\ClubMember;
use App\Services\EnrollmentManageService;

class ClubMemberController extends Controller
{
    private ClubMember $model;
    private EnrollmentManageService $manage;

    public function __construct()
    {
        $this->model = new ClubMember();
        $this->manage = new EnrollmentManageService();
    }

    /** ผู้เข้ารับการพัฒนา — รายการแยกหลักสูตร + จำนวน/% progress */
    public function index(): void
    {
        $this->requirePerm('members');
        $role = (string) (auth()->role() ?? 'user');
        if ($role !== 'admin' && $role !== 'trainer') {
            $this->flash('error', 'หน้านี้สำหรับแอดมินและวิทยากร');
            $this->redirect('/admin/dashboard');
        }
        $uid = (int) auth()->id();
        $this->view('admin/members/index', [
            'title' => 'ผู้เข้ารับการพัฒนา',
            'breadcrumb' => 'หน้าหลัก / ผู้เข้ารับการพัฒนา',
            'role' => $role,
            'plans' => $this->manage->planSummaries($role, $uid),
            'stats' => $this->manage->dashboardStats($role, $uid),
        ]);
    }

    public function plan(string $planId): void
    {
        $this->requirePerm('members', 'view');
        $role = (string) (auth()->role() ?? 'user');
        $uid = (int) auth()->id();
        $pid = (int) $planId;
        $plan = $this->manage->plan($pid);
        if (!$plan || !$this->manage->canViewPlan($pid, $role, $uid)) {
            $this->flash('error', 'ไม่พบหลักสูตร หรือไม่มีสิทธิ์เข้าดู');
            $this->redirect('/admin/members');
        }

        $orgs = db()->fetchAll(
            "SELECT DISTINCT o.id, o.name
             FROM training_module_progress tmp
             INNER JOIN course_enrollments e ON e.id = tmp.enrollment_id
             INNER JOIN org_units o ON o.id = e.org_unit_id
             WHERE tmp.plan_id = ?
             ORDER BY o.name",
            [$pid]
        );

        $this->view('admin/members/plan', [
            'title' => 'ผู้เรียน · หลักสูตรที่ ' . (int) ($plan['plan_no'] ?? 0),
            'breadcrumb' => 'หน้าหลัก / ผู้เข้ารับการพัฒนา / รายละเอียดหลักสูตร',
            'role' => $role,
            'plan' => $plan,
            'orgs' => $orgs,
            'summary' => $this->planSummaryRow($pid, $role, $uid),
        ]);
    }

    public function planDatatable(string $planId): void
    {
        $this->requirePerm('members', 'view');
        $role = (string) (auth()->role() ?? 'user');
        $uid = (int) auth()->id();
        $pid = (int) $planId;
        if (!$this->manage->canViewPlan($pid, $role, $uid)) {
            $this->json(['error' => 'forbidden'], 403);
        }
        $this->json(\App\Services\DataTableService::planLearners($pid, dt_params()));
    }

    /** สมาชิกชมรม (ระบบเดิม) */
    public function clubIndex(): void
    {
        $this->requirePerm('members');
        $this->view('admin/members/club', [
            'title' => 'สมาชิกชมรม',
            'breadcrumb' => 'หน้าหลัก / ผู้เข้ารับการพัฒนา / สมาชิกชมรม',
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('members');
        $this->json(\App\Services\DataTableService::clubMembers(dt_params()));
    }

    public function create(): void
    {
        $this->requirePerm('members', 'create');
        $this->formView(null);
    }

    public function store(): void
    {
        $this->requirePerm('members', 'create');
        $id = $this->model->create($this->payload());
        activity_log('create', 'members', 'เพิ่มสมาชิกชมรม #' . $id);
        $this->flash('success', 'เพิ่มสมาชิกชมรมแล้ว');
        $this->redirect('/admin/members/club');
    }

    public function edit(string $id): void
    {
        $this->requirePerm('members', 'edit');
        $row = $this->model->find((int) $id);
        if (!$row || !empty($row['deleted_at'])) {
            $this->flash('error', 'ไม่พบสมาชิก');
            $this->redirect('/admin/members/club');
        }
        $this->formView($row);
    }

    public function update(string $id): void
    {
        $this->requirePerm('members', 'edit');
        $this->model->update((int) $id, $this->payload());
        activity_log('update', 'members', 'แก้ไขสมาชิกชมรม #' . $id);
        $this->flash('success', 'บันทึกสมาชิกแล้ว');
        $this->redirect('/admin/members/club');
    }

    public function destroy(string $id): void
    {
        $this->requirePerm('members', 'delete');
        $this->model->update((int) $id, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'status' => 0,
        ]);
        activity_log('delete', 'members', 'ลบสมาชิกชมรม #' . $id);
        $this->flash('success', 'ลบสมาชิกแล้ว');
        $this->redirect('/admin/members/club');
    }

    /** @return array<string,mixed>|null */
    private function planSummaryRow(int $planId, string $role, int $userId): ?array
    {
        foreach ($this->manage->planSummaries($role, $userId) as $row) {
            if ((int) ($row['id'] ?? 0) === $planId) {
                return $row;
            }
        }
        return null;
    }

    private function formView(?array $row): void
    {
        $years = [];
        try {
            $years = academic_years_list();
        } catch (\Throwable $e) {
            $years = [current_academic_year()];
        }
        $this->view('admin/members/form', [
            'title' => $row ? 'แก้ไขสมาชิกชมรม' : 'เพิ่มสมาชิกชมรม',
            'breadcrumb' => 'หน้าหลัก / ผู้เข้ารับการพัฒนา / สมาชิกชมรม',
            'row' => $row,
            'years' => $years,
            'prefixes' => (new \App\Services\OrgMasterService())->prefixes(),
        ]);
    }

    private function payload(): array
    {
        $personnelId = (int) $this->input('personnel_id', 0);
        return [
            'personnel_id' => $personnelId > 0 ? $personnelId : null,
            'student_code' => trim((string) $this->input('student_code', '')) ?: null,
            'id_card' => trim((string) $this->input('id_card', '')) ?: null,
            'title' => trim((string) $this->input('title', '')) ?: null,
            'first_name' => trim((string) $this->input('first_name', '')),
            'last_name' => trim((string) $this->input('last_name', '')),
            'gender' => trim((string) $this->input('gender', '')) ?: null,
            'birth_date' => trim((string) $this->input('birth_date', '')) ?: null,
            'grade_level' => trim((string) $this->input('grade_level', '')) ?: null,
            'room' => trim((string) $this->input('room', '')) ?: null,
            'phone' => trim((string) $this->input('phone', '')) ?: null,
            'email' => trim((string) $this->input('email', '')) ?: null,
            'academic_year' => (int) $this->input('academic_year', current_academic_year()) ?: null,
            'status' => (int) $this->input('status', 1),
        ];
    }
}
