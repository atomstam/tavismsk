<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\ModuleLearningService;
use App\Services\PlanOwnerService;
use App\Services\PlanProgress;
use App\Services\TrainingService;

class DiplomaController extends Controller
{
    private TrainingService $training;
    private ModuleLearningService $lms;

    public function __construct()
    {
        $this->training = new TrainingService();
        $this->lms = new ModuleLearningService();
    }

    public function index(): void
    {
        $this->requirePerm('certificates', 'view');
        if (is_trainer_like()) {
            $this->manageIndex();
            return;
        }
        $this->learnerIndex();
    }

    /** มุมมองผู้เรียน — ความก้าวหน้าวุฒิบัตรของตนเอง */
    private function learnerIndex(): void
    {
        $userId = (int) auth()->id();
        $enroll = $this->lms->ensureEnrollment($userId);
        $enrollId = (int) ($enroll['id'] ?? 0);
        $standing = $enrollId > 0
            ? $this->training->diplomaStanding($enrollId)
            : ['total' => 0, 'passed' => 0, 'ready' => false, 'hours' => 0.0, 'percent' => 0.0];
        $diplomaCert = $enrollId > 0
            ? db()->fetch(
                "SELECT id, cert_no, issued_at FROM certificates
                 WHERE enrollment_id = ? AND plan_id IS NULL AND status = 'active'",
                [$enrollId]
            )
            : null;

        $requiredPlans = db()->fetchAll(
            'SELECT * FROM learning_plans WHERE status = 1 AND is_required = 1 ORDER BY plan_no, id'
        );
        $progressMap = [];
        if ($enrollId > 0 && $requiredPlans !== []) {
            $ids = implode(',', array_map(static fn (array $p): int => (int) $p['id'], $requiredPlans));
            foreach (db()->fetchAll(
                "SELECT * FROM training_module_progress
                 WHERE enrollment_id = ? AND plan_id IN ({$ids})",
                [$enrollId]
            ) as $row) {
                $progressMap[(int) $row['plan_id']] = $row;
            }
        }

        $planRows = [];
        foreach ($requiredPlans as $plan) {
            $pid = (int) $plan['id'];
            $progress = $progressMap[$pid] ?? [];
            $settings = $this->lms->settings($pid);
            $passed = $progress !== [] && PlanProgress::certificateReady($progress, [], $settings);
            $planRows[] = [
                'plan' => $plan,
                'progress' => $progress,
                'passed' => $passed,
            ];
        }

        $rows = $this->rowsFromEnrollments(
            $enrollId > 0 ? [$this->enrollmentDisplayRow($enrollId)] : [],
            [$enrollId => $standing],
            $enrollId > 0 && $diplomaCert ? [$enrollId => $diplomaCert] : []
        );

        $requiredTotal = (int) ($standing['total'] ?? count($requiredPlans));
        $passedPlans = (int) ($standing['passed'] ?? 0);

        $this->view('admin/diplomas/index', [
            'title' => 'วุฒิบัตรของฉัน',
            'breadcrumb' => 'หน้าหลัก / วุฒิบัตร',
            'rows' => $rows,
            'planRows' => $planRows,
            'stats' => [
                'learners' => $requiredTotal,
                'not_passed' => max(0, $requiredTotal - $passedPlans),
                'passed' => $passedPlans,
                'issued' => $diplomaCert ? 1 : 0,
            ],
            'requiredTotal' => $requiredTotal,
            'isLearner' => true,
            'canIssue' => false,
        ]);
    }

    /** มุมมองวิทยากร/แอดมิน — รายชื่อผู้เข้ารับการอบรมและความก้าวหน้าวุฒิบัตร */
    private function manageIndex(): void
    {
        $role = (string) (auth()->role() ?? 'admin');
        $uid = (int) auth()->id();
        $enrollments = $this->rosterEnrollments($role, $uid);
        $enrollIds = array_map(static fn (array $r): int => (int) $r['id'], $enrollments);
        $standings = $this->training->diplomaStandingsForEnrollments($enrollIds);
        $certs = [];
        if ($enrollIds !== []) {
            $ph = implode(',', array_map('intval', $enrollIds));
            foreach (db()->fetchAll(
                "SELECT id, enrollment_id, cert_no, issued_at FROM certificates
                 WHERE status = 'active' AND plan_id IS NULL AND enrollment_id IN ({$ph})"
            ) as $c) {
                $certs[(int) $c['enrollment_id']] = $c;
            }
        }

        $rows = $this->rowsFromEnrollments($enrollments, $standings, $certs);
        $requiredTotal = (int) (db()->fetch(
            'SELECT COUNT(*) AS c FROM learning_plans WHERE status = 1 AND is_required = 1'
        )['c'] ?? 0);

        $this->view('admin/diplomas/index', [
            'title' => 'วุฒิบัตร',
            'breadcrumb' => 'หน้าหลัก / วุฒิบัตร',
            'rows' => $rows,
            'planRows' => [],
            'stats' => $this->statsFromRows($rows),
            'requiredTotal' => $requiredTotal,
            'isLearner' => false,
            'canIssue' => can_perm('certificates', 'issue_cert') || can_perm('certificates', 'print'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rosterEnrollments(string $role, int $uid): array
    {
        $sql = "SELECT e.id, e.enrollment_no, e.is_passed, e.total_hours,
                       u.title, u.first_name, u.last_name, u.username,
                       o.name AS org_name
                FROM course_enrollments e
                INNER JOIN users u ON u.id = e.user_id AND u.deleted_at IS NULL
                LEFT JOIN org_units o ON o.id = e.org_unit_id";
        $params = [];
        if ($role === 'trainer') {
            $owned = (new PlanOwnerService())->approvedPlanIds($uid);
            if ($owned === []) {
                return [];
            }
            $ph = implode(',', array_map('intval', $owned));
            $sql .= " WHERE e.id IN (
                        SELECT DISTINCT tmp.enrollment_id
                        FROM training_module_progress tmp
                        WHERE tmp.plan_id IN ({$ph})
                      )";
        }
        $sql .= ' ORDER BY u.first_name ASC, u.last_name ASC, e.id ASC';

        return db()->fetchAll($sql, $params);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentDisplayRow(int $enrollmentId): array
    {
        return db()->fetch(
            "SELECT e.id, e.enrollment_no, e.is_passed, e.total_hours,
                    u.title, u.first_name, u.last_name, u.username,
                    o.name AS org_name
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id AND u.deleted_at IS NULL
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE e.id = ?",
            [$enrollmentId]
        ) ?: [
            'id' => $enrollmentId,
            'enrollment_no' => '',
            'is_passed' => 0,
            'total_hours' => 0,
            'title' => '',
            'first_name' => '',
            'last_name' => '',
            'username' => '',
            'org_name' => '',
        ];
    }

    /**
     * @param list<array<string, mixed>> $enrollments
     * @param array<int, array{total:int,passed:int,ready:bool,hours:float,percent?:float}> $standings
     * @param array<int, array<string, mixed>> $certs
     * @return list<array<string, mixed>>
     */
    private function rowsFromEnrollments(array $enrollments, array $standings, array $certs): array
    {
        $rows = [];
        foreach ($enrollments as $enroll) {
            $eid = (int) ($enroll['id'] ?? 0);
            if ($eid <= 0) {
                continue;
            }
            $standing = $standings[$eid] ?? [
                'total' => 0,
                'passed' => 0,
                'ready' => false,
                'hours' => 0.0,
                'percent' => 0.0,
            ];
            $total = (int) ($standing['total'] ?? 0);
            $passed = (int) ($standing['passed'] ?? 0);
            $percent = isset($standing['percent'])
                ? (float) $standing['percent']
                : ($total > 0 ? round(($passed / $total) * 100, 1) : 0.0);
            $cert = $certs[$eid] ?? null;
            $ready = !empty($standing['ready']) || $cert !== null;
            $rows[] = [
                'enrollment_id' => $eid,
                'enrollment_no' => (string) ($enroll['enrollment_no'] ?? ''),
                'name' => trim(($enroll['title'] ?? '') . ($enroll['first_name'] ?? '') . ' ' . ($enroll['last_name'] ?? '')),
                'username' => (string) ($enroll['username'] ?? ''),
                'org_name' => (string) ($enroll['org_name'] ?? ''),
                'passed' => $passed,
                'total' => $total,
                'percent' => $percent,
                'hours' => (float) ($standing['hours'] ?? 0),
                'ready' => $ready,
                'issued' => $cert !== null,
                'cert' => $cert,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{learners:int,not_passed:int,passed:int,issued:int}
     */
    private function statsFromRows(array $rows): array
    {
        $stats = ['learners' => count($rows), 'not_passed' => 0, 'passed' => 0, 'issued' => 0];
        foreach ($rows as $row) {
            if (!empty($row['issued'])) {
                $stats['issued']++;
            } elseif (!empty($row['ready'])) {
                $stats['passed']++;
            } else {
                $stats['not_passed']++;
            }
        }

        return $stats;
    }
}
