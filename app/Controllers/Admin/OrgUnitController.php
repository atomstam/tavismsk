<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\DataTableService;

class OrgUnitController extends Controller
{
    public function index(): void
    {
        $this->requirePerm('org_units');
        $types = config('app', 'org_types') ?: [];
        $this->view('admin/org_units/index', [
            'title' => 'โครงสร้างหน่วยงาน',
            'breadcrumb' => 'หน้าหลัก / โครงสร้างหน่วยงาน',
            'types' => $types,
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('org_units');
        $this->json(DataTableService::orgUnits(dt_params()));
    }
}
