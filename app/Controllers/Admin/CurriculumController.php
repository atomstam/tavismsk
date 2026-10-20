<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\LearningPlan;
use App\Models\ClubMember;

class CurriculumController extends Controller
{
    private LearningPlan $plans;

    public function __construct()
    {
        $this->plans = new LearningPlan();
    }

    public function index(): void
    {
        $this->requirePerm('curriculum');
        $dbPlans = [];
        try {
            foreach ($this->plans->allActive() as $row) {
                $dbPlans[(int) $row['plan_no']] = $row;
            }
        } catch (\Throwable $e) {
            $dbPlans = [];
        }

        $plans = curriculum_plans();
        if (auth()->role() === 'trainer') {
            $owned = (new \App\Services\PlanOwnerService())->approvedPlanIds((int) auth()->id());
            $plans = array_values(array_filter($plans, static function (array $p) use ($dbPlans, $owned): bool {
                $row = $dbPlans[(int) ($p['plan_no'] ?? 0)] ?? null;
                return $row && in_array((int) $row['id'], $owned, true);
            }));
        }

        $this->view('admin/curriculum/index', [
            'title' => 'แผนการเรียนรู้',
            'breadcrumb' => 'หน้าหลัก / แผนการเรียนรู้',
            'plans' => $plans,
            'dbPlans' => $dbPlans,
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('curriculum');
        $this->json(\App\Services\DataTableService::curriculum(dt_params()));
    }

    public function show(string $id): void
    {
        $this->requirePerm('curriculum');
        $planId = (int) $id;
        $row = $this->plans->find($planId);
        $cfg = null;
        if ($row) {
            $cfg = curriculum_plan((int) $row['plan_no']);
        } else {
            $cfg = curriculum_plan($planId);
            if ($cfg) {
                $row = $this->plans->findByPlanNo((int) $cfg['plan_no']) ?: [
                    'id' => 0,
                    'plan_no' => $cfg['plan_no'],
                    'title' => $cfg['title'],
                    'hours' => $cfg['hours'],
                    'instructor_type' => $cfg['instructor_type'],
                    'instructor_label' => $cfg['instructor_label'] ?? '',
                    'topic_group' => $cfg['topic_group'] ?? '',
                    'description' => '',
                ];
            }
        }
        if (!$row) {
            $this->flash('error', 'ไม่พบแผนการเรียนรู้');
            $this->redirect('/admin/curriculum');
        }
        if (auth()->role() === 'trainer' && !(new \App\Services\PlanOwnerService())->isApproved((int) auth()->id(), (int) ($row['id'] ?? 0))) {
            $this->flash('error', 'ดูได้เฉพาะหลักสูตรที่คุณเป็นเจ้าของ');
            $this->redirect('/admin/curriculum');
        }

        $resources = [];
        $worksheets = [];
        $progress = [];
        $members = [];
        if (!empty($row['id'])) {
            try {
                $resources = $this->plans->resources((int) $row['id']);
                $worksheets = $this->plans->worksheets((int) $row['id']);
                $progress = db()->fetchAll(
                    "SELECT lp.*, cm.first_name, cm.last_name, cm.title AS member_title, cm.student_code, cm.grade_level, cm.room
                     FROM learning_progress lp
                     INNER JOIN club_members cm ON cm.id = lp.member_id AND cm.deleted_at IS NULL
                     WHERE lp.plan_id = ?
                     ORDER BY cm.first_name, cm.last_name",
                    [(int) $row['id']]
                );
                $members = (new ClubMember())->active();
            } catch (\Throwable $e) {
            }
        }

        $this->view('admin/curriculum/show', [
            'title' => 'แผนที่ ' . (int) ($row['plan_no'] ?? 0),
            'breadcrumb' => 'หน้าหลัก / แผนการเรียนรู้ / รายละเอียด',
            'row' => $row,
            'cfg' => $cfg,
            'resources' => $resources,
            'worksheets' => $worksheets,
            'progress' => $progress,
            'members' => $members,
        ]);
    }

    public function saveProgress(string $id): void
    {
        $this->requirePerm('curriculum', 'edit');
        $planId = (int) $id;
        $plan = $this->plans->find($planId);
        if (!$plan) {
            $this->flash('error', 'ไม่พบแผนการเรียนรู้');
            $this->redirect('/admin/curriculum');
        }

        $memberId = (int) $this->input('member_id', 0);
        $status = (string) $this->input('status', 'pending');
        $allowed = ['pending', 'in_progress', 'completed', 'absent'];
        if (!in_array($status, $allowed, true) || $memberId <= 0) {
            $this->flash('error', 'ข้อมูลไม่ถูกต้อง');
            $this->redirect('/admin/curriculum/' . $planId);
        }

        $scoreRaw = trim((string) $this->input('score', ''));
        $score = $scoreRaw === '' ? null : (float) $scoreRaw;
        $note = trim((string) $this->input('note', '')) ?: null;
        $attended = trim((string) $this->input('attended_at', '')) ?: null;
        $now = date('Y-m-d H:i:s');
        $userId = (int) (auth()->id() ?? 0);

        $existing = db()->fetch(
            'SELECT id FROM learning_progress WHERE member_id = ? AND plan_id = ?',
            [$memberId, $planId]
        );

        $payload = [
            'status' => $status,
            'score' => $score,
            'note' => $note,
            'attended_at' => $attended,
            'recorded_by' => $userId ?: null,
            'updated_at' => $now,
        ];

        if ($existing) {
            db()->update('learning_progress', $payload, 'id = ?', [(int) $existing['id']]);
        } else {
            $payload['member_id'] = $memberId;
            $payload['plan_id'] = $planId;
            $payload['created_at'] = $now;
            db()->insert('learning_progress', $payload);
        }

        activity_log('update', 'curriculum', 'บันทึกความก้าวหน้าแผนที่ ' . $plan['plan_no']);
        $this->flash('success', 'บันทึกความก้าวหน้าแล้ว');
        $this->redirect('/admin/curriculum/' . $planId);
    }

    public function export(): void
    {
        $this->requirePerm('curriculum', 'export');
        $rows = [];
        try {
            $rows = db()->fetchAll(
                "SELECT lp.plan_no, lp.title, lp.hours, lp.instructor_type, lp.instructor_label, lp.topic_group,
                        COUNT(pr.id) AS progress_count,
                        SUM(CASE WHEN pr.status = 'completed' THEN 1 ELSE 0 END) AS completed_count
                 FROM learning_plans lp
                 LEFT JOIN learning_progress pr ON pr.plan_id = lp.id
                 WHERE lp.status = 1
                 GROUP BY lp.id
                 ORDER BY lp.plan_no ASC"
            );
        } catch (\Throwable $e) {
            foreach (curriculum_plans() as $p) {
                $rows[] = [
                    'plan_no' => $p['plan_no'],
                    'title' => $p['title'],
                    'hours' => $p['hours'],
                    'instructor_type' => $p['instructor_type'],
                    'instructor_label' => $p['instructor_label'] ?? '',
                    'topic_group' => $p['topic_group'] ?? '',
                    'progress_count' => 0,
                    'completed_count' => 0,
                ];
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="tavis-curriculum-' . date('Ymd') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['plan_no', 'title', 'hours', 'instructor_type', 'instructor_label', 'topic_group', 'progress_count', 'completed_count']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['plan_no'] ?? '',
                $r['title'] ?? '',
                $r['hours'] ?? '',
                $r['instructor_type'] ?? '',
                $r['instructor_label'] ?? '',
                $r['topic_group'] ?? '',
                $r['progress_count'] ?? 0,
                $r['completed_count'] ?? 0,
            ]);
        }
        fclose($out);
        exit;
    }
}
