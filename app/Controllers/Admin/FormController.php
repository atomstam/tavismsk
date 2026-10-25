<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\ExportService;

class FormController extends Controller
{
    public function index(): void
    {
        $this->requirePerm('forms');
        $this->view('admin/forms/index', [
            'title' => 'แบบฟอร์มออนไลน์',
            'breadcrumb' => 'หน้าหลัก / แบบฟอร์ม',
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('forms');
        $this->json(\App\Services\DataTableService::forms(dt_params()));
    }

    public function create(): void
    {
        $this->requirePerm('forms', 'create');
        $this->view('admin/forms/form', [
            'title' => 'สร้างแบบฟอร์ม',
            'breadcrumb' => 'หน้าหลัก / แบบฟอร์ม',
            'row' => null,
            'fields' => [],
        ]);
    }

    public function store(): void
    {
        $this->requirePerm('forms', 'create');
        $title = trim((string) $this->input('title', ''));
        $id = db()->insert('forms', [
            'title' => $title,
            'slug' => slugify($title),
            'description' => trim((string) $this->input('description', '')),
            'status' => (int) $this->input('status', 1) === 1 ? 1 : 0,
            'created_by' => auth()->id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->saveFields($id);
        $this->flash('success', 'สร้างแบบฟอร์มแล้ว — เปิดสถานะเพื่อแสดงในเมนูหน้าเว็บ');
        $this->redirect('/admin/forms');
    }

    public function edit(string $id): void
    {
        $this->requirePerm('forms', 'edit');
        $row = db()->fetch('SELECT * FROM forms WHERE id = ?', [(int) $id]);
        $fields = db()->fetchAll('SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order', [(int) $id]);
        $this->view('admin/forms/form', [
            'title' => 'แก้ไขแบบฟอร์ม',
            'breadcrumb' => 'หน้าหลัก / แบบฟอร์ม',
            'row' => $row,
            'fields' => $fields,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePerm('forms', 'edit');
        db()->update('forms', [
            'title' => trim((string) $this->input('title', '')),
            'description' => trim((string) $this->input('description', '')),
            'status' => (int) $this->input('status', 1),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $id]);
        db()->delete('form_fields', 'form_id = ?', [(int) $id]);
        $this->saveFields((int) $id);
        $this->flash('success', 'บันทึกแบบฟอร์มแล้ว');
        $this->redirect('/admin/forms');
    }

    public function submissions(string $id): void
    {
        $this->requirePerm('forms');
        $form = db()->fetch('SELECT * FROM forms WHERE id = ?', [(int) $id]);
        $rows = db()->fetchAll('SELECT * FROM form_submissions WHERE form_id = ? ORDER BY id DESC', [(int) $id]);
        $this->view('admin/forms/submissions', [
            'title' => 'คำตอบ: ' . ($form['title'] ?? ''),
            'breadcrumb' => 'หน้าหลัก / แบบฟอร์ม',
            'form' => $form,
            'rows' => $rows,
        ]);
    }

    public function export(string $id): void
    {
        $this->requirePerm('forms', 'export');
        $form = db()->fetch('SELECT * FROM forms WHERE id = ?', [(int) $id]);
        $rows = db()->fetchAll('SELECT * FROM form_submissions WHERE form_id = ? ORDER BY id', [(int) $id]);
        $data = [];
        foreach ($rows as $r) {
            $payload = json_decode((string) $r['payload'], true) ?: [];
            $data[] = array_merge([$r['id'], $r['created_at']], array_values($payload));
        }
        (new ExportService())->spreadsheet($form['title'] ?? 'form', ['ID', 'วันที่', 'ข้อมูล'], $data);
    }

    private function saveFields(int $formId): void
    {
        $labels = $_POST['field_label'] ?? [];
        $types = $_POST['field_type'] ?? [];
        $opts = $_POST['field_options'] ?? [];
        $req = $_POST['field_required'] ?? [];
        if (!is_array($labels)) {
            return;
        }
        $sort = 0;
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            db()->insert('form_fields', [
                'form_id' => $formId,
                'label' => $label,
                'name' => 'f' . ($sort + 1),
                'type' => (string) ($types[$i] ?? 'text'),
                'options' => (string) ($opts[$i] ?? ''),
                'required' => !empty($req[$i]) ? 1 : 0,
                'sort_order' => $sort,
            ]);
            $sort++;
        }
    }
}
