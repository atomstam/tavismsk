<?php

declare(strict_types=1);

namespace App\Services;

/**
 * สถิติ Dashboard TAVIS — ตามบทบาท admin / trainer / user
 */
class DashboardStatsService
{
    /** การ์ดภาพรวมระบบอบรม */
    public const CARD_KEYS = [
        'learners', 'enrollments', 'pending', 'modules', 'certificates', 'plan_certificates',
        'diplomas', 'hours_total', 'org_units', 'task_pending', 'plans_owned', 'cert_ready',
        'my_progress', 'my_hours', 'my_certificates', 'my_diplomas', 'my_modules_done',
        // legacy eportfolio keys (ยังรองรับ)
        'personnel', 'awards', 'training', 'speaker', 'committee', 'innovation', 'research', 'plc',
    ];

    public const PUBLIC_CHART_KEYS = [
        'countBar', 'role', 'hours', 'share', 'radar', 'polar',
        'position', 'dept', 'coverage', 'year', 'month',
    ];

    public const ADMIN_CHART_KEYS = ['login', 'loginStatus'];

    /**
     * @return array{ok:bool,key:string,value?:int|float,message?:string}
     */
    public function card(string $key, int $filterUser = 0, bool $seeAll = true, ?int $selfUserId = null): array
    {
        if (!in_array($key, self::CARD_KEYS, true)) {
            return ['ok' => false, 'key' => $key, 'message' => 'ไม่พบการ์ดนี้'];
        }

        $uid = $selfUserId ?? (int) (auth()->id() ?? 0);
        $scopeUser = $seeAll ? ($filterUser > 0 ? $filterUser : null) : $uid;

        $value = match ($key) {
            'learners' => $this->countLearners($scopeUser, $seeAll),
            'enrollments' => $this->countEnrollments($scopeUser, $seeAll, null),
            'pending' => $this->countEnrollments($scopeUser, $seeAll, 'pending'),
            'modules' => (int) (db()->fetch('SELECT COUNT(*) AS c FROM learning_plans WHERE status = 1')['c'] ?? 0),
            'plans_owned' => $this->countPlansOwned($uid),
            'certificates', 'plan_certificates' => $this->countCertificates($scopeUser, $seeAll, 'plan'),
            'diplomas' => $this->countCertificates($scopeUser, $seeAll, 'diploma'),
            'cert_ready' => $this->countCertReady($scopeUser, $seeAll),
            'hours_total' => $this->sumHours($scopeUser, $seeAll),
            'org_units' => (int) (db()->fetch('SELECT COUNT(*) AS c FROM org_units WHERE deleted_at IS NULL')['c'] ?? 0),
            'task_pending' => $this->countTaskPending($scopeUser, $seeAll),
            'my_progress' => (float) number_format($this->avgProgress($uid), 1, '.', ''),
            'my_hours' => (float) number_format($this->sumHours($uid, false), 1, '.', ''),
            'my_certificates' => $this->countCertificates($uid, false, 'plan'),
            'my_diplomas' => $this->countCertificates($uid, false, 'diploma'),
            'my_modules_done' => $this->countModulesDone($uid),
            'personnel' => ($seeAll && $filterUser === 0)
                ? (int) db()->count('personnel', 'deleted_at IS NULL')
                : 1,
            'awards', 'training', 'speaker', 'committee', 'innovation', 'research', 'plc'
                => $this->portfolioTypeCount($key, $filterUser, $seeAll, $selfUserId),
            default => 0,
        };

        if (is_float($value)) {
            $value = (float) number_format($value, 1, '.', '');
        }

        return ['ok' => true, 'key' => $key, 'value' => $value];
    }

    /**
     * @return array<string,mixed>
     */
    public function chart(string $key, int $filterUser = 0, bool $seeAll = true, ?int $selfUserId = null, bool $allowLogin = false): array
    {
        $allowed = self::PUBLIC_CHART_KEYS;
        if ($allowLogin) {
            $allowed = array_merge($allowed, self::ADMIN_CHART_KEYS);
        }
        if (!in_array($key, $allowed, true)) {
            return ['ok' => false, 'key' => $key, 'message' => 'ไม่พบกราฟนี้'];
        }

        // ผู้เรียนทั่วไป: ซ่อนกราฟระดับระบบ
        if (!$seeAll && in_array($key, ['role', 'position', 'dept', 'coverage', 'login', 'loginStatus'], true)) {
            return ['ok' => true, 'key' => $key, 'hide' => true];
        }

        $payload = match ($key) {
            'countBar', 'radar' => $this->enrollmentStatusPayload($filterUser, $seeAll, $selfUserId),
            'share', 'polar' => $this->enrollmentSharePayload($filterUser, $seeAll, $selfUserId),
            'hours' => $this->trainingHoursPayload($filterUser, $seeAll, $selfUserId),
            'role' => $this->rolePayload(),
            'position' => $this->positionPayload($filterUser, $seeAll),
            'dept' => $this->deptPayload($filterUser, $seeAll),
            'coverage' => $this->coveragePayload($filterUser, $seeAll, $selfUserId),
            'year' => $this->yearPayload($filterUser, $seeAll, $selfUserId),
            'month' => $this->monthPayload($filterUser, $seeAll, $selfUserId),
            'login' => $this->loginPayload(),
            'loginStatus' => $this->loginStatusPayload(),
            default => [],
        };

        return ['ok' => true, 'key' => $key] + $payload;
    }

    private function portfolioTypeCount(string $key, int $filterUser, bool $seeAll, ?int $selfUserId): int
    {
        [$where, $params] = $this->portfolioScope($filterUser, $seeAll, $selfUserId);
        $where .= ' AND type = ?';
        $params[] = $key;
        return (int) db()->count('portfolio_items', $where, $params);
    }

    private function countLearners(?int $scopeUser, bool $seeAll): int
    {
        if ($scopeUser && !$seeAll) {
            return 1;
        }
        if ($scopeUser) {
            return 1;
        }
        return (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND status = 1 AND role IN ('user','teacher')"
        )['c'] ?? 0);
    }

    private function countEnrollments(?int $scopeUser, bool $seeAll, ?string $status): int
    {
        $where = '1=1';
        $params = [];
        if ($scopeUser !== null && (!$seeAll || $scopeUser > 0)) {
            $where .= ' AND user_id = ?';
            $params[] = $scopeUser;
        }
        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        return (int) (db()->fetch("SELECT COUNT(*) AS c FROM course_enrollments WHERE {$where}", $params)['c'] ?? 0);
    }

    /**
     * @param 'all'|'plan'|'diploma' $kind
     */
    private function countCertificates(?int $scopeUser, bool $seeAll, string $kind = 'all'): int
    {
        $where = "status = 'active'";
        $params = [];
        if ($kind === 'plan') {
            $where .= ' AND plan_id IS NOT NULL';
        } elseif ($kind === 'diploma') {
            $where .= ' AND plan_id IS NULL';
        }
        if ($scopeUser !== null && (!$seeAll || $scopeUser > 0)) {
            $where .= ' AND user_id = ?';
            $params[] = $scopeUser;
        } elseif (auth()->role() === 'trainer') {
            $planIds = (new PlanOwnerService())->approvedPlanIds((int) (auth()->id() ?? 0));
            if ($planIds === []) {
                return 0;
            }
            $ph = implode(',', array_map('intval', $planIds));
            if ($kind === 'diploma') {
                // วุฒิบัตรของผู้เรียนที่มีความก้าวหน้าในแผนที่ดูแล
                $where .= " AND enrollment_id IN (
                    SELECT DISTINCT tmp.enrollment_id FROM training_module_progress tmp
                    WHERE tmp.plan_id IN ({$ph})
                )";
            } else {
                $where .= " AND plan_id IN ({$ph})";
            }
        }
        return (int) (db()->fetch("SELECT COUNT(*) AS c FROM certificates WHERE {$where}", $params)['c'] ?? 0);
    }

    private function countPlansOwned(int $userId): int
    {
        if (auth()->role() === 'admin') {
            return (int) (db()->fetch('SELECT COUNT(*) AS c FROM learning_plans WHERE status = 1')['c'] ?? 0);
        }
        return count((new PlanOwnerService())->approvedPlanIds($userId));
    }

    private function countCertReady(?int $scopeUser, bool $seeAll): int
    {
        try {
            $planFilter = '';
            $params = [];
            if ($scopeUser !== null && (!$seeAll || $scopeUser > 0)) {
                $planFilter = ' AND e.user_id = ?';
                $params[] = $scopeUser;
            } elseif (auth()->role() === 'trainer') {
                $planIds = (new PlanOwnerService())->approvedPlanIds((int) (auth()->id() ?? 0));
                if ($planIds === []) {
                    return 0;
                }
                $ph = implode(',', array_map('intval', $planIds));
                $planFilter = " AND tmp.plan_id IN ({$ph})";
            }
            // นับ progress ที่ผ่านแผนแล้ว แต่ยังไม่มีเกียรติบัตรรายแผน
            return (int) (db()->fetch(
                "SELECT COUNT(*) AS c
                 FROM training_module_progress tmp
                 INNER JOIN course_enrollments e ON e.id = tmp.enrollment_id
                 LEFT JOIN certificates c ON c.enrollment_id = e.id AND c.plan_id = tmp.plan_id AND c.status = 'active'
                 WHERE tmp.is_passed = 1 AND c.id IS NULL{$planFilter}",
                $params
            )['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function sumHours(?int $scopeUser, bool $seeAll): float
    {
        $where = '1=1';
        $params = [];
        if ($scopeUser !== null && (!$seeAll || $scopeUser > 0)) {
            $where .= ' AND user_id = ?';
            $params[] = $scopeUser;
        }
        try {
            return round((float) (db()->fetch(
                "SELECT COALESCE(SUM(hours),0) AS h FROM training_hour_logs WHERE {$where}",
                $params
            )['h'] ?? 0), 1);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private function countTaskPending(?int $scopeUser, bool $seeAll): int
    {
        $where = "s.status IN ('submitted','revision')";
        $params = [];
        if ($scopeUser !== null && (!$seeAll || $scopeUser > 0)) {
            $where .= ' AND s.user_id = ?';
            $params[] = $scopeUser;
        } elseif (auth()->role() === 'trainer') {
            $planIds = (new PlanOwnerService())->approvedPlanIds((int) (auth()->id() ?? 0));
            if ($planIds === []) {
                return 0;
            }
            $ph = implode(',', array_map('intval', $planIds));
            $where .= " AND EXISTS (
                SELECT 1 FROM training_tasks t WHERE t.id = s.task_id AND t.plan_id IN ({$ph})
            )";
        }
        try {
            return (int) (db()->fetch(
                "SELECT COUNT(*) AS c FROM training_task_submissions s WHERE {$where}",
                $params
            )['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function avgProgress(int $userId): float
    {
        try {
            $row = db()->fetch(
                "SELECT AVG(overall_percent) AS a FROM training_module_progress WHERE user_id = ?",
                [$userId]
            );
            return round((float) ($row['a'] ?? 0), 1);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private function countModulesDone(int $userId): int
    {
        try {
            return (int) (db()->fetch(
                "SELECT COUNT(*) AS c FROM training_module_progress WHERE user_id = ? AND stage = 'completed'",
                [$userId]
            )['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** @return array<string,mixed> */
    private function enrollmentStatusPayload(int $filterUser, bool $seeAll, ?int $selfUserId): array
    {
        [$where, $params] = $this->enrollmentScope($filterUser, $seeAll, $selfUserId);
        $map = [
            'pending' => 0,
            'approved' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'failed' => 0,
            'cancelled' => 0,
        ];
        foreach (db()->fetchAll(
            "SELECT status, COUNT(*) AS c FROM course_enrollments WHERE {$where} GROUP BY status",
            $params
        ) as $row) {
            $st = (string) $row['status'];
            if (isset($map[$st])) {
                $map[$st] = (int) $row['c'];
            }
        }
        $labels = ['รออนุมัติ', 'อนุมัติแล้ว', 'กำลังเรียน', 'ผ่านหลักสูตร', 'ไม่ผ่าน', 'ยกเลิก'];
        $colors = ['#f59e0b', '#3b82f6', '#7c3aed', '#10b981', '#ef4444', '#94a3b8'];
        $counts = array_values($map);

        return [
            'moduleLabels' => $labels,
            'moduleColors' => $colors,
            'moduleCounts' => $counts,
        ];
    }

    /** @return array<string,mixed> */
    private function enrollmentSharePayload(int $filterUser, bool $seeAll, ?int $selfUserId): array
    {
        $base = $this->enrollmentStatusPayload($filterUser, $seeAll, $selfUserId);
        [$shareLabels, $shareData, $shareColors] = $this->dropZero(
            $base['moduleLabels'],
            $base['moduleCounts'],
            $base['moduleColors']
        );

        return $base + [
            'shareLabels' => $shareLabels,
            'shareData' => $shareData,
            'shareColors' => $shareColors,
        ];
    }

    /** @return array<string,mixed> */
    private function trainingHoursPayload(int $filterUser, bool $seeAll, ?int $selfUserId): array
    {
        [$where, $params] = $this->enrollmentScope($filterUser, $seeAll, $selfUserId);
        $labels = ['ชั่วโมงสะสม', 'ชั่วโมงที่กำหนด'];
        $colors = ['#0f766e', '#d97706'];
        $earned = (float) (db()->fetch(
            "SELECT COALESCE(SUM(total_hours),0) AS h FROM course_enrollments WHERE {$where}",
            $params
        )['h'] ?? 0);
        $required = (float) (db()->fetch(
            "SELECT COALESCE(SUM(required_hours),0) AS h FROM course_enrollments WHERE {$where}",
            $params
        )['h'] ?? 0);
        $hourData = [round($earned, 1), round($required, 1)];

        return [
            'hourLabels' => $labels,
            'hourBars' => $hourData,
            'hourColors' => $colors,
            'moduleLabels' => $labels,
            'moduleHours' => $hourData,
            'moduleColors' => $colors,
        ];
    }

    /** @return array{0:string,1:list<mixed>} */
    private function enrollmentScope(int $filterUser, bool $seeAll, ?int $selfUserId): array
    {
        $where = '1=1';
        $params = [];
        if ($filterUser > 0) {
            $where .= ' AND user_id = ?';
            $params[] = $filterUser;
        } elseif (!$seeAll) {
            $where .= ' AND user_id = ?';
            $params[] = $selfUserId ?? auth()->id();
        }
        return [$where, $params];
    }

    /** @return array<string,mixed> */
    private function rolePayload(): array
    {
        $roleLabels = [];
        $roleData = [];
        foreach (db()->fetchAll(
            'SELECT role, COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND status = 1 GROUP BY role'
        ) as $ur) {
            $roleLabels[] = role_name((string) $ur['role']);
            $roleData[] = (int) $ur['c'];
        }

        return ['roleLabels' => $roleLabels, 'roleData' => $roleData];
    }

    /** @return array<string,mixed> */
    private function positionPayload(int $filterUser, bool $seeAll): array
    {
        if (!$this->showPersonnelCharts($filterUser, $seeAll)) {
            return ['hide' => true, 'positionLabels' => [], 'positionData' => []];
        }
        $posLabels = [];
        $posData = [];
        foreach (db()->fetchAll(
            "SELECT COALESCE(pt.title, NULLIF(p.position_text,''), 'ไม่ระบุ') AS n, COUNT(*) AS c
             FROM personnel p LEFT JOIN sc_personal_types pt ON pt.id = p.personal_type_id
             WHERE p.deleted_at IS NULL
             GROUP BY COALESCE(pt.title, NULLIF(p.position_text,''), 'ไม่ระบุ') ORDER BY c DESC"
        ) as $row) {
            $posLabels[] = (string) $row['n'];
            $posData[] = (int) $row['c'];
        }
        [$posLabels, $posData] = $this->topN($posLabels, $posData, 8);

        return ['hide' => $posData === [], 'positionLabels' => $posLabels, 'positionData' => $posData];
    }

    /** @return array<string,mixed> */
    private function deptPayload(int $filterUser, bool $seeAll): array
    {
        if (!$this->showPersonnelCharts($filterUser, $seeAll)) {
            return ['hide' => true, 'deptLabels' => [], 'deptData' => []];
        }
        $deptLabels = [];
        $deptData = [];
        foreach (db()->fetchAll(
            "SELECT COALESCE(d.name,'ไม่ระบุ') AS n, COUNT(*) AS c
             FROM personnel p LEFT JOIN departments d ON d.id = p.department_id
             WHERE p.deleted_at IS NULL
             GROUP BY COALESCE(d.name,'ไม่ระบุ') ORDER BY c DESC"
        ) as $row) {
            $deptLabels[] = (string) $row['n'];
            $deptData[] = (int) $row['c'];
        }
        [$deptLabels, $deptData] = $this->topN($deptLabels, $deptData, 8);

        return ['hide' => $deptData === [], 'deptLabels' => $deptLabels, 'deptData' => $deptData];
    }

    /** @return array<string,mixed> */
    private function coveragePayload(int $filterUser, bool $seeAll, ?int $selfUserId): array
    {
        if (!$seeAll) {
            return ['hide' => true];
        }
        $withEnroll = (int) (db()->fetch(
            "SELECT COUNT(DISTINCT user_id) AS c FROM course_enrollments WHERE status IN ('approved','in_progress','completed')"
        )['c'] ?? 0);
        $learners = $this->countLearners(null, true);

        return [
            'hide' => false,
            'coverageLabels' => ['ลงทะเบียนแล้ว', 'ยังไม่ลงทะเบียน'],
            'coverageData' => [$withEnroll, max(0, $learners - $withEnroll)],
        ];
    }

    /** @return array<string,mixed> */
    private function yearPayload(int $filterUser, bool $seeAll, ?int $selfUserId): array
    {
        [$where, $params] = $this->enrollmentScope($filterUser, $seeAll, $selfUserId);
        $yearsMap = [];
        $pivot = [];
        foreach (db()->fetchAll(
            "SELECT academic_year, status, COUNT(*) AS c FROM course_enrollments
             WHERE {$where} AND academic_year IS NOT NULL
             GROUP BY academic_year, status",
            $params
        ) as $row) {
            $y = (string) $row['academic_year'];
            $yearsMap[$y] = true;
            $pivot[$y][(string) $row['status']] = (int) $row['c'];
        }
        $years = array_keys($yearsMap);
        sort($years);
        if (!$years) {
            $years[] = (string) current_academic_year();
        }
        $statusMeta = [
            'pending' => ['รออนุมัติ', '#f59e0b'],
            'approved' => ['อนุมัติ', '#3b82f6'],
            'in_progress' => ['กำลังเรียน', '#7c3aed'],
            'completed' => ['ผ่านหลักสูตร', '#10b981'],
        ];
        $yearSets = [];
        foreach ($statusMeta as $st => [$label, $color]) {
            $series = [];
            foreach ($years as $y) {
                $series[] = $pivot[$y][$st] ?? 0;
            }
            if (array_sum($series) === 0) {
                continue;
            }
            $yearSets[] = [
                'label' => $label,
                'data' => $series,
                'backgroundColor' => $color,
            ];
        }

        return ['yearLabels' => $years, 'yearSets' => $yearSets];
    }

    /** @return array<string,mixed> */
    private function monthPayload(int $filterUser, bool $seeAll, ?int $selfUserId): array
    {
        [$where, $params] = $this->enrollmentScope($filterUser, $seeAll, $selfUserId);
        $from = date('Y-m-01', strtotime('-5 months'));
        $map = [];
        foreach (db()->fetchAll(
            "SELECT DATE_FORMAT(registered_at, '%Y-%m') AS ym, COUNT(*) AS c
             FROM course_enrollments WHERE {$where} AND registered_at >= ?
             GROUP BY ym",
            array_merge($params, [$from . ' 00:00:00'])
        ) as $row) {
            $map[(string) $row['ym']] = (int) $row['c'];
        }
        $monthLabels = [];
        $monthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("-{$i} months"));
            $ym = date('Y-m', strtotime($start));
            $monthLabels[] = thai_chart_month($start);
            $monthData[] = $map[$ym] ?? 0;
        }

        return ['monthLabels' => $monthLabels, 'monthData' => $monthData];
    }

    /** @return array<string,mixed> */
    private function loginPayload(): array
    {
        $okMap = [];
        $failMap = [];
        foreach (db()->fetchAll(
            "SELECT DATE(created_at) AS d, status, COUNT(*) AS c
             FROM login_logs
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
               AND status IN ('success','2fa','failed','locked','attempt')
             GROUP BY DATE(created_at), status"
        ) as $row) {
            $d = (string) $row['d'];
            $st = (string) $row['status'];
            $c = (int) $row['c'];
            if (in_array($st, ['success', '2fa'], true)) {
                $okMap[$d] = ($okMap[$d] ?? 0) + $c;
            } elseif (in_array($st, ['failed', 'locked', 'attempt'], true)) {
                $failMap[$d] = ($failMap[$d] ?? 0) + $c;
            }
        }
        $loginLabels = [];
        $loginData = [];
        $loginFail = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $loginLabels[] = thai_chart_day($d);
            $loginData[] = $okMap[$d] ?? 0;
            $loginFail[] = $failMap[$d] ?? 0;
        }

        return [
            'loginLabels' => $loginLabels,
            'loginData' => $loginData,
            'loginFail' => $loginFail,
        ];
    }

    /** @return array<string,mixed> */
    private function loginStatusPayload(): array
    {
        $ok = 0;
        $fail = 0;
        foreach (db()->fetchAll(
            "SELECT status, COUNT(*) AS c FROM login_logs
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
               AND status IN ('success','2fa','failed','locked','attempt')
             GROUP BY status"
        ) as $row) {
            $st = (string) $row['status'];
            $c = (int) $row['c'];
            if (in_array($st, ['success', '2fa'], true)) {
                $ok += $c;
            } else {
                $fail += $c;
            }
        }

        $loginStatusLabels = [];
        $loginStatusData = [];
        $loginStatusColors = [];
        if ($ok > 0 || $fail > 0) {
            if ($ok > 0) {
                $loginStatusLabels[] = 'สำเร็จ';
                $loginStatusData[] = $ok;
                $loginStatusColors[] = '#10b981';
            }
            if ($fail > 0) {
                $loginStatusLabels[] = 'ไม่สำเร็จ';
                $loginStatusData[] = $fail;
                $loginStatusColors[] = '#ef4444';
            }
        } else {
            $loginStatusLabels = ['ยังไม่มีข้อมูล'];
            $loginStatusData = [1];
            $loginStatusColors = ['#e2e8f0'];
        }

        return [
            'loginStatusLabels' => $loginStatusLabels,
            'loginStatusData' => $loginStatusData,
            'loginStatusColors' => $loginStatusColors,
        ];
    }

    private function showPersonnelCharts(int $filterUser, bool $seeAll): bool
    {
        return $seeAll && $filterUser === 0 && can_perm('personnel', 'view');
    }

    /**
     * @param list<string> $labels
     * @param list<int|float> $data
     * @param list<string>|null $colors
     * @return array{0:list<string>,1:list<int|float>,2:list<string>}
     */
    private function dropZero(array $labels, array $data, ?array $colors = null): array
    {
        $outL = [];
        $outD = [];
        $outC = [];
        foreach ($data as $i => $v) {
            if ((float) $v <= 0) {
                continue;
            }
            $outL[] = $labels[$i] ?? '';
            $outD[] = $v;
            $outC[] = $colors[$i] ?? '#94a3b8';
        }

        return [$outL, $outD, $outC];
    }

    /**
     * @param list<string> $labels
     * @param list<int> $data
     * @return array{0:list<string>,1:list<int>}
     */
    private function topN(array $labels, array $data, int $limit = 8): array
    {
        $pairs = [];
        foreach ($labels as $i => $label) {
            $pairs[] = [$label, (int) ($data[$i] ?? 0)];
        }
        usort($pairs, static fn ($a, $b) => $b[1] <=> $a[1]);
        $outL = [];
        $outD = [];
        $other = 0;
        foreach ($pairs as $i => $pair) {
            if ($i < $limit) {
                $outL[] = $pair[0];
                $outD[] = $pair[1];
            } else {
                $other += $pair[1];
            }
        }
        if ($other > 0) {
            $outL[] = 'อื่นๆ';
            $outD[] = $other;
        }

        return [$outL, $outD];
    }

    /** @return array{0:string,1:list<mixed>} */
    private function portfolioScope(int $filterUser, bool $seeAll, ?int $selfUserId = null): array
    {
        $where = 'deleted_at IS NULL';
        $params = [];
        if ($filterUser > 0) {
            $where .= ' AND user_id = ?';
            $params[] = $filterUser;
        } elseif (!$seeAll) {
            $where .= ' AND user_id = ?';
            $params[] = $selfUserId ?? auth()->id();
        }

        return [$where, $params];
    }
}
