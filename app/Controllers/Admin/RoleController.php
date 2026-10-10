<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Role;
use App\Services\PermissionService;

class RoleController extends Controller
{
    public function index(): void
    {
        $this->requirePerm('roles');
        $this->view('admin/roles/index', [
            'title' => 'บทบาทและสิทธิ์',
            'breadcrumb' => 'หน้าหลัก / บทบาท',
            'roles' => (new Role())->all(),
            'matrix' => (new PermissionService())->modules(),
        ]);
    }

    public function show(string $key): void
    {
        $this->requirePerm('roles');
        $roles = (new Role())->all();
        if (!isset($roles[$key])) {
            $this->redirect('/admin/roles');
        }
        $this->view('admin/roles/show', [
            'title' => $roles[$key]['label'] ?? $key,
            'breadcrumb' => 'หน้าหลัก / บทบาท',
            'roleKey' => $key,
            'role' => $roles[$key],
            'perms' => (new PermissionService())->forRole($key),
            'modules' => (new PermissionService())->modules(),
            'actions' => (new PermissionService())->actions(),
        ]);
    }
}
