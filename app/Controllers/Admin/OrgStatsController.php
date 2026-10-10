<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\ExportService;
use App\Services\OrgMasterService;

class OrgStatsController extends Controller
{
    private OrgMasterService $org;

    public function __construct()
    {
        $this->org = new OrgMasterService();
    }

    public function index(): void
    {
        $this->requirePerm('reports');
        $cluster = trim((string) $this->input('cluster', ''));
        $areaId = (int) $this->input('area_id', 0);
        $schoolId = (int) $this->input('school_id', 0);
        $role = trim((string) $this->input('role', ''));

        $summary = $this->summaryRows($cluster, $areaId, $schoolId, $role);
        $byRole = $this->roleBreakdown($cluster, $areaId, $schoolId);
        $byCluster = $this->clusterBreakdown();
        $byAreaTop = $this->topAreas(15);
        $totals = [
            'users' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL")['c'] ?? 0),
            'active' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND status=1")['c'] ?? 0),
            'areas' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM org_units WHERE org_type='area' AND deleted_at IS NULL")['c'] ?? 0),
            'schools' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM org_units WHERE org_type='school' AND deleted_at IS NULL")['c'] ?? 0),
            'with_org' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND org_unit_id IS NOT NULL")['c'] ?? 0),
        ];

        $this->view('admin/reports/org_stats', [
            'title' => 'สารสนเทศผู้ใช้ตามระดับหน่วยงาน',
            'breadcrumb' => 'หน้าหลัก / รายงาน / สารสนเทศหน่วยงาน',
            'clusters' => $this->org->clusters(),
            'areas' => $this->org->areas($cluster !== '' ? $cluster : null, '', 300),
            'schools' => $areaId > 0 ? $this->org->schools($areaId, '', 300) : [],
            'roles' => config('app', 'roles') ?: [],
            'filters' => compact('cluster', 'areaId', 'schoolId', 'role'),
            'summary' => $summary,
            'byRole' => $byRole,
            'byCluster' => $byCluster,
            'byAreaTop' => $byAreaTop,
            'totals' => $totals,
            'importMeta' => $this->org->importMeta(),
            'ready' => $this->org->isReady(),
        ]);
    }

    public function export(): void
    {
        $this->requirePerm('reports', 'export');
        $cluster = trim((string) $this->input('cluster', ''));
        $areaId = (int) $this->input('area_id', 0);
        $schoolId = (int) $this->input('school_id', 0);
        $role = trim((string) $this->input('role', ''));
        $rows = $this->summaryRows($cluster, $areaId, $schoolId, $role);
        $data = [];
        $n = 1;
        foreach ($rows as $r) {
            $data[] = [
                $n++,
                $r['full_name'],
                $r['username'],
                role_name((string) $r['role']),
                $r['school_name'] ?? '-',
                $r['area_name'] ?? '-',
                $r['cluster_name'] ?? '-',
                $r['province'] ?? '-',
                ((int) $r['status'] === 1) ? 'ใช้งาน' : 'ระงับ',
            ];
        }
        (new ExportService())->spreadsheet(
            'สารสนเทศผู้ใช้ตามหน่วยงาน',
            ['ลำดับ', 'ชื่อ-สกุล', 'ชื่อผู้ใช้', 'บทบาท', 'โรงเรียน', 'สพม.', 'เขตตรวจ', 'จังหวัด', 'สถานะ'],
            $data
        );
    }

    /** @return list<array<string,mixed>> */
    private function summaryRows(string $cluster, int $areaId, int $schoolId, string $role): array
    {
        $where = 'u.deleted_at IS NULL';
        $params = [];
        if ($role !== '') {
            $where .= ' AND u.role = ?';
            $params[] = $role;
        }
        $flt = \App\Services\OrgMembershipService::filterSql($cluster, $areaId, $schoolId, 'u');
        $where .= $flt['where'];
        $params = array_merge($params, $flt['params']);

        return db()->fetchAll(
            "SELECT u.id, u.username, u.role, u.org_level, u.status, u.cluster_code,
                    TRIM(CONCAT(COALESCE(u.title,''), u.first_name, ' ', u.last_name)) AS full_name,
                    COALESCE(sch.name, u.org_name) AS school_name, sch.province,
                    COALESCE(area.name, u.affiliation) AS area_name,
                    c.name AS cluster_name
             FROM users u
             LEFT JOIN org_units sch ON sch.id = u.org_unit_id AND sch.org_type = 'school' AND sch.deleted_at IS NULL
             LEFT JOIN org_units area ON area.id = u.area_id AND area.deleted_at IS NULL
             LEFT JOIN sc_clusters c ON c.id = CAST(NULLIF(u.cluster_code,'') AS UNSIGNED)
             WHERE {$where}
             ORDER BY area.name, sch.name, u.first_name
             LIMIT 5000",
            $params
        );
    }

    /** @return list<array{role:string,c:int}> */
    private function roleBreakdown(string $cluster, int $areaId, int $schoolId): array
    {
        $where = 'u.deleted_at IS NULL';
        $params = [];
        $flt = \App\Services\OrgMembershipService::filterSql($cluster, $areaId, $schoolId, 'u');
        $where .= $flt['where'];
        $params = array_merge($params, $flt['params']);
        return db()->fetchAll(
            "SELECT u.role, COUNT(*) AS c FROM users u WHERE {$where} GROUP BY u.role ORDER BY c DESC",
            $params
        );
    }

    /** @return list<array{cluster_code:?string,cluster_name:?string,schools:int,users:int}> */
    private function clusterBreakdown(): array
    {
        return db()->fetchAll(
            "SELECT u.cluster_code,
                    MAX(c.name) AS cluster_name,
                    COUNT(DISTINCT CASE WHEN sch.org_type='school' THEN sch.id END) AS schools,
                    COUNT(DISTINCT u.id) AS users
             FROM users u
             LEFT JOIN sc_clusters c ON c.id = CAST(NULLIF(u.cluster_code,'') AS UNSIGNED)
             LEFT JOIN org_units sch ON sch.id = u.org_unit_id AND sch.deleted_at IS NULL
             WHERE u.deleted_at IS NULL AND u.cluster_code IS NOT NULL AND u.cluster_code <> ''
             GROUP BY u.cluster_code
             ORDER BY users DESC"
        );
    }

    /** @return list<array{id:int,name:string,province:?string,users:int}> */
    private function topAreas(int $limit = 15): array
    {
        $limit = max(5, min(50, $limit));
        return db()->fetchAll(
            "SELECT a.id, a.name, a.province, COUNT(u.id) AS users
             FROM org_units a
             LEFT JOIN users u ON u.area_id = a.id AND u.deleted_at IS NULL
             WHERE a.org_type = 'area' AND a.deleted_at IS NULL
             GROUP BY a.id, a.name, a.province
             ORDER BY users DESC, a.name
             LIMIT {$limit}"
        );
    }
}
