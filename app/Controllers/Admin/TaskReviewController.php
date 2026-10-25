<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\PlanOwnerService;

/**
 * วิทยากร — ตรวจให้คะแนนภาระงานของหลักสูตรที่รับผิดชอบ
 */
class TaskReviewController extends Controller
{
    public function index(): void
    {
        $this->requirePerm('assessments', 'edit');
        $owners = new PlanOwnerService();
        $role = auth()->role();
        $plans = [];

        if ($role === 'admin') {
            $plans = db()->fetchAll(
                'SELECT id, plan_no, title, hours, is_required, topic_group
                 FROM learning_plans WHERE status = 1 ORDER BY plan_no, id'
            );
        } else {
            $ids = $owners->approvedPlanIds((int) auth()->id());
            if ($ids !== []) {
                $ph = implode(',', array_map('intval', $ids));
                $plans = db()->fetchAll(
                    "SELECT id, plan_no, title, hours, is_required, topic_group
                     FROM learning_plans WHERE status = 1 AND id IN ({$ph})
                     ORDER BY plan_no, id"
                );
            }
        }

        $rows = [];
        $totalPending = 0;
        foreach ($plans as $plan) {
            $planId = (int) $plan['id'];
            $pending = (int) (db()->fetch(
                "SELECT COUNT(*) AS c
                 FROM training_task_submissions s
                 INNER JOIN training_tasks t ON t.id = s.task_id
                 WHERE t.plan_id = ? AND t.status = 1 AND s.status IN ('submitted','revision')",
                [$planId]
            )['c'] ?? 0);
            $graded = (int) (db()->fetch(
                "SELECT COUNT(*) AS c
                 FROM training_task_submissions s
                 INNER JOIN training_tasks t ON t.id = s.task_id
                 WHERE t.plan_id = ? AND t.status = 1 AND s.status = 'approved'",
                [$planId]
            )['c'] ?? 0);
            $taskCount = (int) (db()->fetch(
                'SELECT COUNT(*) AS c FROM training_tasks WHERE plan_id = ? AND status = 1',
                [$planId]
            )['c'] ?? 0);
            $totalPending += $pending;
            $rows[] = [
                'plan' => $plan,
                'pending' => $pending,
                'graded' => $graded,
                'task_count' => $taskCount,
            ];
        }

        // หลักสูตรที่รอตรวจขึ้นก่อน
        usort($rows, static function (array $a, array $b): int {
            if ($a['pending'] === $b['pending']) {
                return ((int) ($a['plan']['plan_no'] ?? 0)) <=> ((int) ($b['plan']['plan_no'] ?? 0));
            }
            return $b['pending'] <=> $a['pending'];
        });

        $this->view('admin/task_reviews/index', [
            'title' => 'ตรวจภาระงาน',
            'breadcrumb' => 'หน้าหลัก / ตรวจภาระงาน',
            'rows' => $rows,
            'totalPending' => $totalPending,
        ]);
    }
}
