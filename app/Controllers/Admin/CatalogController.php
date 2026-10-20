<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\CatalogService;

class CatalogController extends Controller
{
    private CatalogService $catalogs;

    public function __construct()
    {
        $this->catalogs = new CatalogService();
    }

    public function index(string $key): void
    {
        $cat = $this->gate($key);
        $columns = [];
        foreach ((array) $cat['columns'] as $col) {
            if (!is_array($col)) {
                continue;
            }
            $columns[] = [
                'key' => (string) $col['key'],
                'raw' => !empty($col['raw']),
            ];
        }
        $this->view('admin/catalog/index', [
            'title' => (string) $cat['title'],
            'breadcrumb' => 'หน้าหลัก / ' . $cat['title'],
            'catalog' => $cat,
            'key' => $key,
            'jsColumns' => $columns,
        ]);
    }

    public function datatable(string $key): void
    {
        $this->gate($key);
        $this->json($this->catalogs->datatable($key, dt_params()));
    }

    public function create(string $key): void
    {
        $cat = $this->gate($key, 'create');
        if (array_key_exists('creatable', $cat) && empty($cat['creatable'])) {
            $this->redirect('/admin/catalog/' . $key);
        }
        $this->form($key, $cat, null);
    }

    public function store(string $key): void
    {
        $this->gate($key, 'create');
        $this->persist($key, null);
    }

    public function edit(string $key, string $id): void
    {
        $cat = $this->gate($key, 'edit');
        $row = $this->catalogs->find($key, (int) $id);
        if (!$row) {
            $this->redirect('/admin/catalog/' . $key);
        }
        $this->form($key, $cat, $row);
    }

    public function update(string $key, string $id): void
    {
        $this->gate($key, 'edit');
        $this->persist($key, (int) $id);
    }

    public function destroy(string $key, string $id): void
    {
        $this->gate($key, 'delete');
        try {
            $this->catalogs->delete($key, (int) $id);
            $this->flash('success', 'ลบรายการแล้ว');
        } catch (\InvalidArgumentException $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect('/admin/catalog/' . $key);
    }

    /** @return array<string, mixed> */
    private function gate(string $key, string $action = 'view'): array
    {
        $cat = $this->catalogs->get($key);
        if ($cat === []) {
            $this->flash('error', 'ไม่พบชุดข้อมูล');
            $this->redirect('/admin/dashboard');
        }
        $this->requirePerm((string) $cat['module'], $action);
        return $cat;
    }

    /** @param array<string, mixed> $cat @param array<string, mixed>|null $row */
    private function form(string $key, array $cat, ?array $row): void
    {
        $this->view('admin/catalog/form', [
            'title' => ($row ? 'แก้ไข' : 'เพิ่ม') . (string) $cat['title'],
            'breadcrumb' => 'หน้าหลัก / ' . $cat['title'],
            'catalog' => $cat,
            'key' => $key,
            'row' => $row,
        ]);
    }

    private function persist(string $key, ?int $id): void
    {
        try {
            $this->catalogs->save($key, $id, $_POST);
            $this->flash('success', 'บันทึกแล้ว');
            $this->redirect('/admin/catalog/' . $key);
        } catch (\InvalidArgumentException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect($id ? '/admin/catalog/' . $key . '/' . $id . '/edit' : '/admin/catalog/' . $key . '/create');
        }
    }
}
