<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\User;
use App\Services\DataTableService;
use App\Services\ExportService;

class UserController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        $this->requirePerm('users');
        $this->view('admin/users/index', [
            'title' => 'จัดการผู้ใช้งาน',
            'breadcrumb' => 'หน้าหลัก / ผู้ใช้งาน',
            'roles' => (new \App\Models\Role())->assignableBy(),
            'clusters' => (new \App\Services\OrgMasterService())->clusters(),
            'isAdmin' => is_admin_like(),
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('users');
        $role = (string) $this->input('role', '');
        $this->json(DataTableService::users(dt_params(), $role !== '' ? $role : null));
    }

    public function lookup(): void
    {
        $this->requirePerm('users');
        $username = trim((string) $this->input('username', ''));
        if ($username === '' || mb_strlen($username) < 2) {
            $this->json(['found' => false]);
            return;
        }
        $user = db()->fetch(
            'SELECT id, username, email, title, first_name, last_name, phone, role, department_id, personnel_id, status, two_factor_enabled
             FROM users WHERE username = ? AND deleted_at IS NULL LIMIT 1',
            [$username]
        );
        if (!$user) {
            $this->json(['found' => false, 'username' => $username]);
            return;
        }
        $this->json([
            'found' => true,
            'id' => (int) $user['id'],
            'edit_url' => url('/admin/users/' . (int) $user['id'] . '/edit'),
            'row' => $user,
        ]);
    }

    public function create(): void
    {
        $this->requirePerm('users', 'create');
        $this->formView(null);
    }

    public function store(): void
    {
        $this->requirePerm('users', 'create');
        $username = trim((string) $this->input('username', ''));
        $existing = $this->users->findBy('username', $username);
        if ($existing && empty($existing['deleted_at'])) {
            $this->flash('info', 'พบชื่อผู้ใช้นี้ในระบบแล้ว — เปิดโหมดแก้ไข');
            $this->redirect('/admin/users/' . (int) $existing['id'] . '/edit');
        }
        $errors = $this->validate($_POST, [
            'username' => 'required|min:3',
            'first_name' => 'required',
            'last_name' => 'required',
            'password' => 'required|min:8',
            'role' => 'required',
        ]);
        if ($errors) {
            $this->flash('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
            set_old_input($_POST);
            $this->redirect('/admin/users/create');
        }
        $payload = $this->payload(null);
        if ($payload === null) {
            set_old_input($_POST);
            $this->redirect('/admin/users/create');
        }
        $id = $this->users->create($payload);
        $this->syncPersonnelLink($id, (int) $this->input('personnel_id', 0) ?: null, null);
        try {
            $this->saveAvatar($id);
        } catch (\Throwable $e) {
            $this->flash('error', 'สร้างผู้ใช้แล้ว แต่รูปโปรไฟล์ไม่สำเร็จ: ' . $e->getMessage());
            $this->redirect('/admin/users/' . $id . '/edit');
        }
        activity_log('create', 'users', 'เพิ่มผู้ใช้', null, ['id' => $id]);
        $this->flash('success', 'เพิ่มผู้ใช้งานสำเร็จ');
        $this->redirect('/admin/users');
    }

    public function edit(string $id): void
    {
        $this->requirePerm('users', 'edit');
        $user = $this->users->find((int) $id);
        if (!$user) {
            $this->flash('error', 'ไม่พบผู้ใช้');
            $this->redirect('/admin/users');
        }
        $this->formView($user);
    }

    public function update(string $id): void
    {
        $this->requirePerm('users', 'edit');
        $user = $this->users->find((int) $id);
        if (!$user) {
            $this->redirect('/admin/users');
        }
        $payload = $this->payload($user);
        if ($payload === null) {
            set_old_input($_POST);
            $this->redirect('/admin/users/' . (int) $id . '/edit');
        }
        // ห้ามเปลี่ยน username ทับคนอื่น
        $dup = db()->fetch(
            'SELECT id FROM users WHERE username = ? AND id <> ? AND deleted_at IS NULL LIMIT 1',
            [$payload['username'], (int) $id]
        );
        if ($dup) {
            $this->flash('error', 'ชื่อผู้ใช้นี้ถูกใช้แล้ว');
            $this->redirect('/admin/users/' . (int) $id . '/edit');
        }
        $this->users->update((int) $id, $payload);
        $this->syncPersonnelLink(
            (int) $id,
            (int) ($payload['personnel_id'] ?? 0) ?: null,
            (int) ($user['personnel_id'] ?? 0) ?: null
        );
        try {
            $this->saveAvatar((int) $id, $user['avatar'] ?? null);
        } catch (\Throwable $e) {
            $this->flash('error', 'บันทึกข้อมูลแล้ว แต่รูปโปรไฟล์ไม่สำเร็จ: ' . $e->getMessage());
            $this->redirect('/admin/users/' . (int) $id . '/edit');
        }
        activity_log('edit', 'users', 'แก้ไขผู้ใช้', $user);
        $this->flash('success', 'บันทึกข้อมูลผู้ใช้แล้ว');
        $this->redirect('/admin/users/' . (int) $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $this->requirePerm('users', 'delete');
        if ((int) $id === (int) auth()->id()) {
            $this->flash('error', 'ไม่สามารถลบบัญชีของตนเองได้');
            $this->redirect('/admin/users');
        }
        $user = $this->users->find((int) $id);
        $this->users->update((int) $id, ['deleted_at' => date('Y-m-d H:i:s'), 'status' => 0]);
        if ($user && !empty($user['personnel_id'])) {
            db()->query(
                'UPDATE personnel SET user_id = NULL WHERE id = ? AND user_id = ?',
                [(int) $user['personnel_id'], (int) $id]
            );
        }
        activity_log('delete', 'users', 'ลบผู้ใช้', null, ['id' => (int) $id]);
        $this->flash('success', 'ลบผู้ใช้แล้ว');
        $this->redirect('/admin/users');
    }

    public function resetPassword(string $id): void
    {
        $this->requirePerm('users', 'edit');
        $pass = 'Cyp@' . date('Y');
        $this->users->update((int) $id, ['password' => hash_password($pass), 'login_attempts' => 0, 'locked_until' => null]);
        activity_log('reset_password', 'users', 'รีเซ็ตรหัสผ่านผู้ใช้', null, ['id' => (int) $id]);
        $this->flash('success', 'รีเซ็ตรหัสผ่านเป็น ' . $pass);
        $this->redirect('/admin/users');
    }

    public function export(): void
    {
        $this->requirePerm('users', 'export');
        $rows = db()->fetchAll(
            "SELECT u.username, u.email, u.title, u.first_name, u.last_name, u.role, u.org_level, u.phone, u.id_card, u.status,
                    u.cluster_code, u.school_code,
                    sch.name AS school_name, area.name AS area_name, c.name AS cluster_name
             FROM users u
             LEFT JOIN org_units sch ON sch.id = u.org_unit_id AND sch.org_type = 'school'
             LEFT JOIN org_units area ON area.id = u.area_id
             LEFT JOIN sc_clusters c ON c.id = CAST(NULLIF(u.cluster_code,'') AS UNSIGNED)
             WHERE u.deleted_at IS NULL ORDER BY u.id"
        );
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                $r['username'], $r['email'], trim(($r['title'] ?? '') . $r['first_name'] . ' ' . $r['last_name']),
                role_name($r['role']), $r['org_level'] ?? '', $r['cluster_name'] ?? $r['cluster_code'] ?? '',
                $r['area_name'] ?? '', $r['school_name'] ?? '', $r['school_code'] ?? '',
                $r['id_card'] ?? '', $r['phone'] ?? '',
                ((int) $r['status'] ? 'ใช้งาน' : 'ระงับ'),
            ];
        }
        (new ExportService())->spreadsheet(
            'ผู้ใช้งาน',
            ['ชื่อผู้ใช้', 'อีเมล', 'ชื่อ-สกุล', 'บทบาท', 'ระดับ', 'เขตตรวจ', 'สพม.', 'โรงเรียน', 'รหัสร.ร.', 'เลขบัตร', 'โทร', 'สถานะ'],
            $data
        );
    }

    private function formView(?array $user): void
    {
        $personnel = db()->fetchAll(
            "SELECT id, code, title, first_name, last_name, user_id
             FROM personnel
             WHERE deleted_at IS NULL AND status = 1
             ORDER BY first_name, last_name"
        );
        $org = new \App\Services\OrgMasterService();
        $orgUnitId = (int) ($user['org_unit_id'] ?? 0);
        $areaId = (int) ($user['area_id'] ?? 0);
        $cluster = (string) ($user['cluster_code'] ?? '');
        if ($cluster === '' || $areaId <= 0) {
            $unit = $orgUnitId ? $org->findUnit($orgUnitId) : null;
            if ($unit) {
                if (($unit['org_type'] ?? '') === 'school') {
                    if ($areaId <= 0) {
                        $areaId = (int) ($unit['parent_id'] ?? 0);
                    }
                    if ($cluster === '') {
                        $cluster = (string) ($unit['cluster_code'] ?? '');
                    }
                    $area = $areaId ? $org->findUnit($areaId) : null;
                    if ($area && $cluster === '') {
                        $cluster = (string) ($area['cluster_code'] ?? '');
                    }
                } elseif (($unit['org_type'] ?? '') === 'area') {
                    $areaId = (int) $unit['id'];
                    if ($cluster === '') {
                        $cluster = (string) ($unit['cluster_code'] ?? '');
                    }
                }
            }
        }
        $schoolSelectId = $orgUnitId;
        if ($user && ($user['org_level'] ?? '') === 'area' && $areaId > 0 && $orgUnitId === $areaId) {
            $schoolSelectId = 0;
        }
        $this->view('admin/users/form', [
            'title' => $user ? 'แก้ไขผู้ใช้งาน' : 'เพิ่มผู้ใช้งาน',
            'breadcrumb' => 'หน้าหลัก / ผู้ใช้งาน',
            'user' => $user,
            'personnelList' => $personnel,
            'roles' => (new \App\Models\Role())->assignableBy(),
            'departments' => (new \App\Models\Department())->active(),
            'lookupUrl' => url('/admin/users/lookup'),
            'clusters' => $org->clusters(),
            'prefixes' => $org->prefixes(),
            'personalTypes' => $org->personalTypes(),
            'memberTypes' => $org->memberTypeLabels(),
            'areas' => $cluster !== '' ? $org->areas($cluster, '', 300) : ($areaId ? $org->areas(null, '', 100) : []),
            'schools' => $areaId > 0 ? $org->schools($areaId, '', 300) : [],
            'orgSelected' => [
                'cluster' => $cluster,
                'area_id' => $areaId,
                'org_unit_id' => $schoolSelectId,
                'member_type' => (string) ($user['member_type'] ?? 'staff'),
                'personal_type_id' => (int) ($user['personal_type_id'] ?? 0),
                'position_text' => (string) ($user['position_text'] ?? ''),
            ],
        ]);
    }

    private function payload(?array $old): ?array
    {
        $personnelId = (int) $this->input('personnel_id', 0);
        $role = (string) $this->input('role', 'user');
        $assignable = (new \App\Models\Role())->assignableBy();
        if (!isset($assignable[$role])) {
            $this->flash('error', 'คุณไม่มีสิทธิ์กำหนดบทบาทนี้ — บัญชี Admin/วิทยากร จัดการโดย Admin เท่านั้น');
            return null;
        }
        // ผู้ใช้งานทั่วไปสมัครเองที่ /register — หลังบ้าน Admin สร้างได้ทั้ง admin / trainer / user
        $idCard = preg_replace('/\D+/', '', (string) $this->input('id_card', '')) ?: '';
        if ($idCard !== '' && strlen($idCard) !== 13) {
            $this->flash('error', 'เลขบัตรประชาชนต้องมี 13 หลัก');
            return null;
        }
        $membership = (new \App\Services\OrgMembershipService())->resolveFromInput(
            trim((string) $this->input('cluster_code', '')),
            (int) $this->input('area_id', 0),
            (int) $this->input('org_unit_id', 0),
            $role
        );
        if (!$membership['ok']) {
            $this->flash('error', $membership['error'] ?? 'กรุณาเลือกหน่วยงานให้ครบ');
            return null;
        }
        $data = [
            'username' => trim((string) $this->input('username', $old['username'] ?? '')),
            'email' => trim((string) $this->input('email', '')) ?: null,
            'title' => trim((string) $this->input('title', '')) ?: null,
            'first_name' => trim((string) $this->input('first_name', '')),
            'last_name' => trim((string) $this->input('last_name', '')),
            'phone' => trim((string) $this->input('phone', '')) ?: null,
            'id_card' => $idCard !== '' ? $idCard : null,
            'role' => $role,
            'department_id' => (int) $this->input('department_id') ?: null,
            'org_unit_id' => $membership['org_unit_id'],
            'area_id' => $membership['area_id'],
            'cluster_code' => $membership['cluster_code'],
            'school_code' => $membership['school_code'],
            'org_name' => $membership['org_name'] ?? null,
            'affiliation' => $membership['affiliation'] ?? null,
            'org_level' => $membership['org_level'],
            'member_type' => in_array((string) $this->input('member_type', 'staff'), ['staff', 'student', 'general'], true)
                ? (string) $this->input('member_type', 'staff')
                : 'staff',
            'personal_type_id' => null,
            'position_text' => null,
            'personnel_id' => $personnelId > 0 ? $personnelId : null,
            'status' => (int) $this->input('status', 1),
            'two_factor_enabled' => (int) $this->input('two_factor_enabled', 0),
        ];
        if (\App\Services\OrgMembershipService::requiresOrg($role)) {
            if ($data['member_type'] === 'general') {
                $positionText = trim((string) $this->input('position_text', ''));
                $data['position_text'] = $positionText !== '' ? mb_substr($positionText, 0, 120) : null;
                $data['personal_type_id'] = null;
            } else {
                $data['personal_type_id'] = (int) $this->input('personal_type_id') ?: null;
                $data['position_text'] = null;
            }
        }
        $pass = (string) $this->input('password', '');
        if ($pass !== '') {
            $data['password'] = hash_password($pass);
        } elseif (!$old) {
            $data['password'] = hash_password('Cyp@' . date('Y'));
        }
        return $data;
    }

    private function syncPersonnelLink(int $userId, ?int $personnelId, ?int $oldPersonnelId): void
    {
        if ($oldPersonnelId && $oldPersonnelId !== $personnelId) {
            db()->query(
                'UPDATE personnel SET user_id = NULL WHERE id = ? AND user_id = ?',
                [$oldPersonnelId, $userId]
            );
        }
        if ($personnelId) {
            db()->query(
                'UPDATE users SET personnel_id = NULL WHERE personnel_id = ? AND id <> ?',
                [$personnelId, $userId]
            );
            db()->query(
                'UPDATE personnel SET user_id = NULL WHERE user_id = ? AND id <> ?',
                [$userId, $personnelId]
            );
            db()->query('UPDATE personnel SET user_id = ? WHERE id = ?', [$userId, $personnelId]);
            db()->query('UPDATE users SET personnel_id = ? WHERE id = ?', [$personnelId, $userId]);
        } else {
            db()->query('UPDATE users SET personnel_id = NULL WHERE id = ?', [$userId]);
            db()->query('UPDATE personnel SET user_id = NULL WHERE user_id = ?', [$userId]);
        }
    }

    private function saveAvatar(int $id, ?string $old = null): void
    {
        $path = resolve_cropped_upload('avatar', 'avatar', 'avatar_crop_token', $old, [
            'user_id' => $id,
        ]);
        if ($path) {
            $this->users->update($id, ['avatar' => $path]);
        }
    }
}
