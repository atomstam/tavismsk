<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Department;
use App\Services\MediaStorageService;

class DepartmentController extends Controller
{
    private Department $model;

    public function __construct()
    {
        $this->model = new Department();
    }

    public function index(): void
    {
        $this->requirePerm('departments');
        $this->view('admin/departments/index', [
            'title' => 'หน่วยงาน / กลุ่มงาน',
            'breadcrumb' => 'หน้าหลัก / หน่วยงาน',
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('departments');
        $this->json(\App\Services\DataTableService::departments(dt_params()));
    }

    public function create(): void
    {
        $this->requirePerm('departments', 'create');
        $this->formView(null);
    }

    public function store(): void
    {
        $this->requirePerm('departments', 'create');
        $id = $this->model->create($this->payload());
        $this->logo($id);
        $this->flash('success', 'เพิ่มหน่วยงานแล้ว');
        $this->redirect('/admin/departments');
    }

    public function edit(string $id): void
    {
        $this->requirePerm('departments', 'edit');
        $row = $this->model->find((int) $id);
        if (!$row) {
            $this->redirect('/admin/departments');
        }
        $this->formView($row);
    }

    public function update(string $id): void
    {
        $this->requirePerm('departments', 'edit');
        $this->model->update((int) $id, $this->payload());
        $this->logo((int) $id);
        $this->flash('success', 'บันทึกหน่วยงานแล้ว');
        $this->redirect('/admin/departments');
    }

    public function destroy(string $id): void
    {
        $this->requirePerm('departments', 'delete');
        $this->model->update((int) $id, ['deleted_at' => date('Y-m-d H:i:s')]);
        $this->flash('success', 'ลบหน่วยงานแล้ว');
        $this->redirect('/admin/departments');
    }

    private function formView(?array $row): void
    {
        $this->view('admin/departments/form', [
            'title' => $row ? 'แก้ไขหน่วยงาน' : 'เพิ่มหน่วยงาน',
            'breadcrumb' => 'หน้าหลัก / หน่วยงาน',
            'row' => $row,
        ]);
    }

    private function payload(): array
    {
        return [
            'code' => trim((string) $this->input('code', '')) ?: null,
            'name' => trim((string) $this->input('name', '')),
            'head_name' => trim((string) $this->input('head_name', '')) ?: null,
            'description' => trim((string) $this->input('description', '')) ?: null,
            'color' => trim((string) $this->input('color', '#2563eb')),
            'sort_order' => (int) $this->input('sort_order', 0),
            'status' => (int) $this->input('status', 1),
        ];
    }

    private function logo(int $id): void
    {
        if (empty($_FILES['logo']['name'])) {
            return;
        }
        try {
            $saved = (new MediaStorageService())->upload($_FILES['logo'], 'logo', [
                'system' => true,
            ]);
            $this->model->update($id, ['logo' => $saved['file_path']]);
        } catch (\Throwable $e) {
        }
    }
}
