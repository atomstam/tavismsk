<?php

declare(strict_types=1);

namespace App\Services;

/**
 * สารสนเทศการลงทะเบียน/ความก้าวหน้าแบบรายหลักสูตร ตามบทบาท
 */
class EnrollmentManageService
{
    private PlanOwnerService $owners;

    public function __construct()
    {
        $this->owners = new PlanOwnerService();
    }

    /** @return list<int>|null null = ทุกหลักสูตร (admin) */
    public function visiblePlanIds(?string $role = null, ?int $userId = null): ?array
    {
        $role = $role ?? (string) (auth()->role() ?? 'user');
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        if ($role === 'admin') {
            return null;
        }
        if ($role === 'trainer') {
            return $this->owners->approvedPlanIds($userId);
        }
        // ผู้เรียน: หลักสูตรที่มี progress หรือหลักสูตรบังคับที่ผูกกับ enrollment
        $en = db()->fetch(
            "SELECT id FROM course_enrollments
             WHERE user_id = ? AND status IN ('pending','approved','in_progress','completed')
             ORDER BY id DESC LIMIT 1",
            [$userId]
        );
        if (!$en) {
            return [];
        }
        $rows = db()->fetchAll(
            'SELECT plan_id FROM training_module_progress WHERE enrollment_id = ?',
            [(int) $en['id']]
        );
        $ids = array_map(static fn (array $r): int => (int) $r['plan_id'], $rows);
        if ($ids === []) {
            // ยังไม่มี progress — แสดงหลักสูตรบังคับ 1–12
            $fallback = db()->fetchAll(
                'SELECT id FROM learning_plans WHERE status = 1 AND plan_no BETWEEN 1 AND 12 ORDER BY plan_no'
            );
            return array_map(static fn (array $r): int => (int) $r['id'], $fallback);
        }
        return $ids;
    }

    public function canViewPlan(int $planId, ?string $role = null, ?int $userId = null): bool
    {
        $role = $role ?? (string) (auth()->role() ?? 'user');
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        if ($role === 'admin') {
            return true;
        }
        if ($role === 'trainer') {
            return $this->owners->isApproved($userId, $planId);
        }
        $ids = $this->visiblePlanIds($role, $userId);
        return is_array($ids) && in_array($planId, $ids, true);
    }

    /** @return list<array<string,mixed>> */
    public function planSummaries(?string $role = null, ?int $userId = null): array
    {
        $role = $role ?? (string) (auth()->role() ?? 'user');
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $ids = $this->visiblePlanIds($role, $userId);

        $where = 'p.status = 1';
        $params = [];
        if (is_array($ids)) {
            if ($ids === []) {
                return [];
            }
            $in = implode(',', array_fill(0, count($ids), '?'));
            $where .= " AND p.id IN ({$in})";
            $params = $ids;
        }

        $rows = db()->fetchAll(
            "SELECT p.id, p.plan_no, p.title, p.hours, p.topic_group, p.instructor_label, p.is_required,
                    COUNT(DISTINCT tmp.id) AS learner_count,
                    SUM(CASE WHEN tmp.is_passed = 1 THEN 1 ELSE 0 END) AS passed_count,
                    SUM(CASE WHEN tmp.stage NOT IN ('completed') AND tmp.overall_percent > 0 THEN 1 ELSE 0 END) AS active_count,
                    ROUND(AVG(tmp.overall_percent), 1) AS avg_percent
             FROM learning_plans p
             LEFT JOIN training_module_progress tmp ON tmp.plan_id = p.id
             WHERE {$where}
             GROUP BY p.id
             ORDER BY p.plan_no, p.id",
            $params
        );

        // เจ้าของหลักสูตร (วิทยากรที่ได้รับมอบหมาย)
        $ownerMap = [];
        if ($rows !== []) {
            $planIds = array_map(static fn (array $r): int => (int) $r['id'], $rows);
            $in = implode(',', array_fill(0, count($planIds), '?'));
            $owners = db()->fetchAll(
                "SELECT o.plan_id, u.id AS user_id, u.title, u.first_name, u.last_name
                 FROM learning_plan_owners o
                 INNER JOIN users u ON u.id = o.user_id AND u.deleted_at IS NULL
                 WHERE o.status = 'approved' AND o.plan_id IN ({$in})
                 ORDER BY u.first_name, u.last_name",
                $planIds
            );
            foreach ($owners as $o) {
                $pid = (int) $o['plan_id'];
                $ownerMap[$pid][] = trim(($o['title'] ?? '') . ($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
            }
        }

        // ความก้าวหน้าของผู้เรียนเอง (+ คะแนนรวมสำหรับคอลัมน์ผล)
        $myProgress = [];
        if ($role === 'user') {
            $en = db()->fetch(
                "SELECT id FROM course_enrollments WHERE user_id = ? ORDER BY id DESC LIMIT 1",
                [$userId]
            );
            if ($en) {
                $lms = new ModuleLearningService();
                foreach (db()->fetchAll(
                    'SELECT plan_id, overall_percent, stage, is_passed,
                            pretest_percent, content_percent, task_percent,
                            posttest_percent, satisfaction_percent,
                            pretest_done, content_done, task_done, posttest_done, satisfaction_done
                     FROM training_module_progress WHERE enrollment_id = ?',
                    [(int) $en['id']]
                ) as $pr) {
                    $pid = (int) $pr['plan_id'];
                    $pr['score_percent'] = PlanProgress::scorePercent($lms->settings($pid), $pr);
                    $myProgress[$pid] = $pr;
                }
            }
        }

        foreach ($rows as &$r) {
            $pid = (int) $r['id'];
            $r['trainers'] = $ownerMap[$pid] ?? [];
            $r['my_progress'] = $myProgress[$pid] ?? null;
            $r['is_required'] = (int) ($r['is_required'] ?? ((int) $r['plan_no'] <= 12 ? 1 : 0));
        }
        unset($r);

        return $rows;
    }

    /** @return array<string,mixed>|null */
    public function plan(int $planId): ?array
    {
        $row = db()->fetch('SELECT * FROM learning_plans WHERE id = ?', [$planId]);
        return $row ?: null;
    }

    /** ผู้เรียนในหลักสูตร (สำหรับ admin/trainer) */
    /** @return list<array<string,mixed>> */
    public function learnersForPlan(int $planId): array
    {
        return db()->fetchAll(
            "SELECT e.id AS enrollment_id, e.enrollment_no, e.status AS enrollment_status,
                    e.total_hours, e.required_hours, e.final_score, e.is_passed AS enrollment_passed,
                    u.id AS user_id, u.title, u.first_name, u.last_name, u.username, u.avatar,
                    o.name AS org_name,
                    tmp.overall_percent, tmp.stage, tmp.is_passed AS plan_passed,
                    tmp.pretest_percent, tmp.content_percent, tmp.task_percent,
                    tmp.posttest_percent, tmp.satisfaction_percent, tmp.updated_at AS progress_updated_at
             FROM training_module_progress tmp
             INNER JOIN course_enrollments e ON e.id = tmp.enrollment_id
             INNER JOIN users u ON u.id = e.user_id AND u.deleted_at IS NULL
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE tmp.plan_id = ?
             ORDER BY tmp.overall_percent DESC, u.first_name, u.last_name",
            [$planId]
        );
    }

    /** @return array<string,mixed>|null ความก้าวหน้าของผู้เรียนในหลักสูตร */
    public function myPlanProgress(int $planId, int $userId): ?array
    {
        $en = db()->fetch(
            "SELECT * FROM course_enrollments WHERE user_id = ? ORDER BY id DESC LIMIT 1",
            [$userId]
        );
        if (!$en) {
            return null;
        }
        $tmp = db()->fetch(
            'SELECT * FROM training_module_progress WHERE enrollment_id = ? AND plan_id = ?',
            [(int) $en['id'], $planId]
        );
        if ($tmp) {
            $settings = (new ModuleLearningService())->settings($planId);
            $tmp['score_percent'] = PlanProgress::scorePercent($settings, $tmp);
        }
        return [
            'enrollment' => $en,
            'progress' => $tmp ?: null,
        ];
    }

    /** @return array<string,mixed>|null */
    public function myEnrollment(int $userId): ?array
    {
        $row = db()->fetch(
            'SELECT e.*, o.name AS org_name
             FROM course_enrollments e
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE e.user_id = ?
             ORDER BY e.id DESC LIMIT 1',
            [$userId]
        );
        return $row ?: null;
    }

    /** @return array{plans:int,learners:int,passed:int,pending_enrollments:int} */
    public function dashboardStats(?string $role = null, ?int $userId = null): array
    {
        $role = $role ?? (string) (auth()->role() ?? 'user');
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $plans = $this->planSummaries($role, $userId);
        $learners = 0;
        $passed = 0;
        foreach ($plans as $p) {
            $learners += (int) ($p['learner_count'] ?? 0);
            $passed += (int) ($p['passed_count'] ?? 0);
        }
        $pending = 0;
        if ($role === 'admin') {
            $pending = (int) (db()->fetch(
                "SELECT COUNT(*) AS c FROM course_enrollments WHERE status = 'pending'"
            )['c'] ?? 0);
        }
        return [
            'plans' => count($plans),
            'learners' => $learners,
            'passed' => $passed,
            'pending_enrollments' => $pending,
        ];
    }
}
