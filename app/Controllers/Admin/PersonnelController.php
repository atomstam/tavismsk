<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Personnel;
use App\Services\DataTableService;
use App\Services\ExportService;

class PersonnelController extends Controller
{
    private Personnel $model;

    public function __construct()
    {
        $this->model = new Personnel();
    }

    public function index(): void
    {
        $this->requirePerm('personnel');
        $sources = \App\Services\RegistrationProfile::formSources(null);
        $this->view('admin/personnel/index', [
            'title' => 'บุคลากร',
            'breadcrumb' => 'หน้าหลัก / บุคลากร',
            'memberTypes' => $sources['memberTypes'],
            'personalTypes' => $sources['personalTypes'],
            'schools' => $sources['schools'],
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('personnel');
        $this->json(DataTableService::personnel(dt_params()));
    }

    public function lookup(): void
    {
        $this->requirePerm('personnel');
        $idCard = preg_replace('/\D/', '', (string) $this->input('id_card', ''));
        if ($idCard === '' || strlen($idCard) < 13) {
            $this->json(['found' => false]);
            return;
        }
        $row = db()->fetch(
            'SELECT * FROM personnel WHERE id_card = ? AND deleted_at IS NULL LIMIT 1',
            [$idCard]
        );
        if (!$row) {
            $this->json(['found' => false, 'id_card' => $idCard]);
            return;
        }
        $this->json([
            'found' => true,
            'id' => (int) $row['id'],
            'edit_url' => url('/admin/personnel/' . (int) $row['id'] . '/edit'),
            'row' => $row,
        ]);
    }

    public function nextCode(): void
    {
        $this->requirePerm('personnel');
        $this->json(['code' => $this->suggestCode()]);
    }

    public function create(): void
    {
        $this->requirePerm('personnel', 'create');
        $this->formView(null);
    }

    public function store(): void
    {
        $this->requirePerm('personnel', 'create');
        $idCard = preg_replace('/\D/', '', (string) $this->input('id_card', ''));
        if ($idCard !== '') {
            $existing = db()->fetch(
                'SELECT id FROM personnel WHERE id_card = ? AND deleted_at IS NULL LIMIT 1',
                [$idCard]
            );
            if ($existing) {
                $this->flash('info', 'พบเลขบัตรในระบบแล้ว — เปิดโหมดแก้ไข');
                $this->redirect('/admin/personnel/' . (int) $existing['id'] . '/edit');
            }
        }

        $payload = $this->payload(null);
        if ($payload === null) {
            set_old_input($_POST);
            $this->redirect('/admin/personnel/create');
        }
        $payload['code'] = $this->suggestCode();
        $id = $this->model->create($payload);
        $this->syncUserLink($id, (int) ($payload['user_id'] ?? 0) ?: null, null);
        try {
            $this->savePhoto($id);
        } catch (\Throwable $e) {
            $this->flash('error', 'บันทึกบุคลากรแล้ว แต่รูปภาพไม่สำเร็จ: ' . $e->getMessage());
            $this->redirect('/admin/personnel/' . $id . '/edit');
        }
        activity_log('create', 'personnel', 'เพิ่มบุคลากร', null, ['id' => $id]);
        $this->flash('success', 'บันทึกบุคลากรสำเร็จ');
        $this->redirect('/admin/personnel');
    }

    public function show(string $id): void
    {
        $this->requirePerm('personnel');
        $row = $this->model->withRelations((int) $id);
        if (!$row) {
            $this->redirect('/admin/personnel');
        }
        $this->view('admin/personnel/show', [
            'title' => $this->model->fullName($row),
            'breadcrumb' => 'หน้าหลัก / บุคลากร',
            'row' => $row,
            'profileView' => \App\Services\RegistrationProfile::present($row),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePerm('personnel', 'edit');
        $row = $this->model->find((int) $id);
        if (!$row) {
            $this->redirect('/admin/personnel');
        }
        if (!can_see_all_personnel() && (int) $row['user_id'] !== (int) auth()->id()) {
            $this->flash('error', 'ไม่มีสิทธิ์');
            $this->redirect('/admin/personnel');
        }
        $this->formView($row);
    }

    public function update(string $id): void
    {
        $this->requirePerm('personnel', 'edit');
        $row = $this->model->find((int) $id);
        if (!$row) {
            $this->redirect('/admin/personnel');
        }
        $payload = $this->payload($row);
        if ($payload === null) {
            set_old_input($_POST);
            $this->redirect('/admin/personnel/' . (int) $id . '/edit');
        }
        // รหัสไม่ให้แก้จากฟอร์ม
        unset($payload['code']);
        $this->model->update((int) $id, $payload);
        $this->syncUserLink(
            (int) $id,
            (int) ($payload['user_id'] ?? 0) ?: null,
            (int) ($row['user_id'] ?? 0) ?: null
        );
        try {
            $this->savePhoto((int) $id, $row['photo'] ?? null);
        } catch (\Throwable $e) {
            $this->flash('error', 'บันทึกข้อมูลแล้ว แต่รูปภาพไม่สำเร็จ: ' . $e->getMessage());
            $this->redirect('/admin/personnel/' . (int) $id . '/edit');
        }
        activity_log('edit', 'personnel', 'แก้ไขบุคลากร', $row);
        $this->flash('success', 'บันทึกข้อมูลบุคลากรแล้ว');
        $this->redirect('/admin/personnel/' . (int) $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $this->requirePerm('personnel', 'delete');
        $row = $this->model->find((int) $id);
        $this->model->update((int) $id, ['deleted_at' => date('Y-m-d H:i:s'), 'status' => 0]);
        if ($row && !empty($row['user_id'])) {
            db()->query(
                'UPDATE users SET personnel_id = NULL WHERE id = ? AND personnel_id = ?',
                [(int) $row['user_id'], (int) $id]
            );
        }
        activity_log('delete', 'personnel', 'ลบบุคลากร', null, ['id' => (int) $id]);
        $this->flash('success', 'ลบข้อมูลแล้ว');
        $this->redirect('/admin/personnel');
    }

    public function export(): void
    {
        $this->requirePerm('personnel', 'export');
        $rows = db()->fetchAll(
            "SELECT p.*, COALESCE(pt.title, p.position_text) AS position_name, r.name AS rank_name, d.name AS department_name
             FROM personnel p
             LEFT JOIN sc_personal_types pt ON pt.id = p.personal_type_id
             LEFT JOIN academic_ranks r ON r.id = p.academic_rank_id
             LEFT JOIN departments d ON d.id = p.department_id
             WHERE p.deleted_at IS NULL ORDER BY p.id"
        );
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                $r['code'], $r['id_card'], trim(($r['title'] ?? '') . $r['first_name'] . ' ' . $r['last_name']),
                $r['position_name'] ?? '', $r['rank_name'] ?? '', $r['department_name'] ?? '',
                $r['phone'] ?? '', $r['email'] ?? '',
            ];
        }
        (new ExportService())->spreadsheet('บุคลากร', ['รหัส', 'เลขบัตร', 'ชื่อ-สกุล', 'ตำแหน่ง', 'วิทยฐานะ', 'กลุ่มงาน', 'โทร', 'อีเมล'], $data);
    }

    public function importTemplate(): void
    {
        $this->requirePerm('personnel', 'create');
        $headers = [
            'เลขบัตรประชาชน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'เพศ(ชาย/หญิง)',
            'วันเกิด(YYYY-MM-DD)', 'ตำแหน่ง', 'วิทยฐานะ', 'กลุ่มงาน',
            'โทร', 'อีเมล', 'Line ID', 'Facebook', 'ที่อยู่', 'วุฒิการศึกษา',
        ];
        $sample = [[
            '1234567890123', 'นาย', 'สมชาย', 'ใจดี', 'ชาย',
            '1985-01-15', 'ครู', 'ชำนาญการ', 'กลุ่มบริหารวิชาการ',
            '0812345678', 'somchai@cyp.ac.th', '', '', '', '',
        ]];
        (new ExportService())->spreadsheet('เทมเพลตนำเข้าบุคลากร', $headers, $sample);
    }

    public function import(): void
    {
        $this->requirePerm('personnel', 'create');
        if (empty($_FILES['import_file']['tmp_name'])) {
            $this->flash('error', 'กรุณาเลือกไฟล์ .xlsx');
            $this->redirect('/admin/personnel');
        }
        $ext = strtolower(pathinfo((string) ($_FILES['import_file']['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            $this->flash('error', 'รองรับเฉพาะไฟล์ Excel (.xlsx)');
            $this->redirect('/admin/personnel');
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['import_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            $this->flash('error', 'อ่านไฟล์ไม่สำเร็จ');
            $this->redirect('/admin/personnel');
        }

        if (count($rows) < 2) {
            $this->flash('error', 'ไม่พบข้อมูลในไฟล์');
            $this->redirect('/admin/personnel');
        }

        $positions = [];
        foreach (db()->fetchAll("SELECT id, title FROM sc_personal_types WHERE kind = 'staff'") as $p) {
            $positions[mb_strtolower(trim((string) $p['title']))] = (int) $p['id'];
        }
        $ranks = [];
        foreach (db()->fetchAll('SELECT id, name FROM academic_ranks WHERE status = 1') as $p) {
            $ranks[mb_strtolower(trim((string) $p['name']))] = (int) $p['id'];
        }
        $depts = [];
        foreach (db()->fetchAll('SELECT id, name FROM departments WHERE status = 1') as $p) {
            $depts[mb_strtolower(trim((string) $p['name']))] = (int) $p['id'];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $row = $rows[$i];
            $idCard = preg_replace('/\D/', '', (string) ($row[0] ?? ''));
            $title = trim((string) ($row[1] ?? ''));
            $first = trim((string) ($row[2] ?? ''));
            $last = trim((string) ($row[3] ?? ''));
            if ($first === '' || $last === '') {
                $skipped++;
                continue;
            }
            $genderRaw = trim((string) ($row[4] ?? ''));
            $gender = match (true) {
                in_array($genderRaw, ['ชาย', 'male', 'ม'], true) => 'male',
                in_array($genderRaw, ['หญิง', 'female', 'ญ'], true) => 'female',
                default => null,
            };
            $birth = trim((string) ($row[5] ?? ''));
            if ($birth !== '' && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $birth)) {
                [$d, $m, $y] = array_map('intval', explode('/', $birth));
                if ($y > 2400) {
                    $y -= 543;
                }
                $birth = sprintf('%04d-%02d-%02d', $y, $m, $d);
            } elseif ($birth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) {
                $birth = '';
            }
            $posName = mb_strtolower(trim((string) ($row[6] ?? '')));
            $rankName = mb_strtolower(trim((string) ($row[7] ?? '')));
            $deptName = mb_strtolower(trim((string) ($row[8] ?? '')));

            $data = [
                'id_card' => $idCard !== '' ? $idCard : null,
                'title' => $title !== '' ? $title : null,
                'first_name' => $first,
                'last_name' => $last,
                'gender' => $gender,
                'birth_date' => $birth !== '' ? $birth : null,
                'member_type' => 'staff',
                'personal_type_id' => $positions[$posName] ?? null,
                'position_text' => $positions[$posName] ?? null ? null : (trim((string) ($row[6] ?? '')) ?: null),
                'academic_rank_id' => $ranks[$rankName] ?? null,
                'department_id' => $depts[$deptName] ?? null,
                'phone' => trim((string) ($row[9] ?? '')) ?: null,
                'email' => trim((string) ($row[10] ?? '')) ?: null,
                'line_id' => trim((string) ($row[11] ?? '')) ?: null,
                'facebook' => trim((string) ($row[12] ?? '')) ?: null,
                'address' => trim((string) ($row[13] ?? '')) ?: null,
                'education' => trim((string) ($row[14] ?? '')) ?: null,
                'status' => 1,
            ];

            $existing = null;
            if ($idCard !== '') {
                $existing = db()->fetch(
                    'SELECT id, code FROM personnel WHERE id_card = ? AND deleted_at IS NULL LIMIT 1',
                    [$idCard]
                );
            }
            if ($existing) {
                $this->model->update((int) $existing['id'], $data);
                $updated++;
            } else {
                $data['code'] = $this->suggestCode();
                $this->model->create($data);
                $created++;
            }
        }

        activity_log('import', 'personnel', "นำเข้าบุคลากร เพิ่ม {$created} อัปเดต {$updated} ข้าม {$skipped}");
        $this->flash('success', "นำเข้าสำเร็จ — เพิ่ม {$created} รายการ, อัปเดต {$updated} รายการ" . ($skipped ? ", ข้าม {$skipped}" : ''));
        $this->redirect('/admin/personnel');
    }

    private function formView(?array $row): void
    {
        $users = db()->fetchAll(
            "SELECT id, username, title, first_name, last_name, role, personnel_id
             FROM users
             WHERE deleted_at IS NULL AND status = 1
             ORDER BY first_name, last_name"
        );
        $sources = \App\Services\RegistrationProfile::formSources($row);
        $this->view('admin/personnel/form', [
            'title' => $row ? 'แก้ไขบุคลากร' : 'เพิ่มบุคลากร',
            'breadcrumb' => 'หน้าหลัก / บุคลากร',
            'row' => $row,
            'nextCode' => $row['code'] ?? $this->suggestCode(),
            'users' => $users,
            'prefixes' => $sources['prefixes'],
            'memberTypes' => $sources['memberTypes'],
            'personalTypes' => $sources['personalTypes'],
            'schools' => $sources['schools'],
            'provinces' => $sources['provinces'],
            'amphurs' => $sources['amphurs'],
            'tumbons' => $sources['tumbons'],
            'profile' => $sources['profile'],
            'lookupUrl' => url('/admin/personnel/lookup'),
        ]);
    }

    private function suggestCode(): string
    {
        $nextId = (int) (db()->fetch('SELECT COALESCE(MAX(id), 0) + 1 AS n FROM personnel')['n'] ?? 1);
        return 'P' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }

    private function payload(?array $old): ?array
    {
        $profile = (new \App\Services\RegistrationProfile())->collect(true);
        if (!$profile['ok']) {
            $this->flash('error', $profile['error'] ?? 'กรุณากรอกข้อมูลให้ครบถ้วน');
            return null;
        }
        $userId = (int) $this->input('user_id', 0);
        $first = trim((string) $this->input('first_name', ''));
        $last = trim((string) $this->input('last_name', ''));
        if ($first === '' || $last === '') {
            $this->flash('error', 'กรุณากรอกชื่อ และนามสกุลให้ครบ');
            return null;
        }
        return array_merge($profile['data'], [
            'user_id' => $userId > 0 ? $userId : null,
            'code' => $old['code'] ?? $this->suggestCode(),
            'id_card' => preg_replace('/\D/', '', (string) $this->input('id_card', '')) ?: null,
            'title' => trim((string) $this->input('title', '')) ?: null,
            'first_name' => $first,
            'last_name' => $last,
            'gender' => $this->input('gender') ?: null,
            'birth_date' => $this->input('birth_date') ?: null,
            'phone' => trim((string) $this->input('phone', '')) ?: null,
            'email' => trim((string) $this->input('email', '')) ?: null,
            'line_id' => trim((string) $this->input('line_id', '')) ?: null,
            'facebook' => trim((string) $this->input('facebook', '')) ?: null,
            'address' => trim((string) $this->input('address', '')) ?: null,
            'education' => trim((string) $this->input('education', '')) ?: null,
            'work_history' => trim((string) $this->input('work_history', '')) ?: null,
            'training_history' => trim((string) $this->input('training_history', '')) ?: null,
            'status' => (int) $this->input('status', 1),
        ]);
    }

    private function syncUserLink(int $personnelId, ?int $userId, ?int $oldUserId): void
    {
        if ($oldUserId && $oldUserId !== $userId) {
            db()->query(
                'UPDATE users SET personnel_id = NULL WHERE id = ? AND personnel_id = ?',
                [$oldUserId, $personnelId]
            );
        }
        if ($userId) {
            db()->query(
                'UPDATE personnel SET user_id = NULL WHERE user_id = ? AND id <> ? AND deleted_at IS NULL',
                [$userId, $personnelId]
            );
            db()->query('UPDATE users SET personnel_id = NULL WHERE personnel_id = ? AND id <> ?', [$personnelId, $userId]);
            db()->query('UPDATE users SET personnel_id = ? WHERE id = ?', [$personnelId, $userId]);
            db()->query('UPDATE personnel SET user_id = ? WHERE id = ?', [$userId, $personnelId]);
        } else {
            db()->query('UPDATE personnel SET user_id = NULL WHERE id = ?', [$personnelId]);
            db()->query('UPDATE users SET personnel_id = NULL WHERE personnel_id = ?', [$personnelId]);
        }
    }

    private function savePhoto(int $id, ?string $old = null): void
    {
        $path = resolve_cropped_upload('photo', 'photo', 'photo_crop_token', $old, [
            'personnel_id' => $id,
        ]);
        if ($path) {
            $this->model->update($id, ['photo' => $path]);
        }
    }
}