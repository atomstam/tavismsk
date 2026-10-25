<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\LearningPlan;
use App\Services\ModuleLearningService;

class CurriculumPublicController extends Controller
{
    public function index(): void
    {
        $plans = curriculum_plans();
        $dbByNo = [];
        try {
            foreach ((new LearningPlan())->allActive() as $row) {
                $dbByNo[(int) $row['plan_no']] = $row;
            }
        } catch (\Throwable $e) {
        }

        $progressByPlanId = [];
        if (auth()->check()) {
            try {
                $lms = new ModuleLearningService();
                foreach ($lms->moduleOverviewForUser((int) auth()->id()) as $m) {
                    $pid = (int) ($m['plan']['id'] ?? 0);
                    if ($pid > 0) {
                        $progressByPlanId[$pid] = $m['progress'] ?? [];
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        $this->webView('web/curriculum', [
            'title' => 'หลักสูตร 14 ชั่วโมง',
            'plans' => $plans,
            'dbByNo' => $dbByNo,
            'progressByPlanId' => $progressByPlanId,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function show(string $planNo): void
    {
        $no = (int) $planNo;
        $cfg = curriculum_plan($no);
        if (!$cfg) {
            http_response_code(404);
            $this->webView('errors/404', ['title' => 'ไม่พบหลักสูตร']);
            return;
        }

        $row = null;
        $resources = [];
        $worksheets = [];
        $lmsContents = [];
        $lmsTasks = [];
        $progress = null;
        try {
            $model = new LearningPlan();
            $row = $model->findByPlanNo($no);
            if ($row) {
                $resources = $model->resources((int) $row['id']);
                $worksheets = $model->worksheets((int) $row['id']);
                $lms = new ModuleLearningService();
                $lmsContents = $lms->contents((int) $row['id']);
                $lmsTasks = $lms->tasks((int) $row['id']);
                if (auth()->check()) {
                    $enroll = $lms->ensureEnrollment((int) auth()->id());
                    if ($enroll) {
                        $progress = $lms->recalculate((int) $row['id'], (int) $enroll['id'], true);
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        $sections = curriculum_plan_sections($cfg, $lmsContents, $lmsTasks, $resources, $worksheets);

        $this->webView('web/curriculum-show', [
            'title' => 'หลักสูตรที่ ' . $no . ' — ' . $cfg['title'],
            'cfg' => $cfg,
            'row' => $row,
            'sections' => $sections,
            'progress' => $progress,
            'plans' => curriculum_plans(),
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    /** @return array{today:int,month:int,all:int} */
    private function visitorStats(): array
    {
        try {
            $today = (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE DATE(visited_at) = CURDATE()")['c'] ?? 0);
            $month = (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE YEAR(visited_at)=YEAR(CURDATE()) AND MONTH(visited_at)=MONTH(CURDATE())")['c'] ?? 0);
            $all = (int) (db()->fetch('SELECT COUNT(*) AS c FROM visitors')['c'] ?? 0);
            return compact('today', 'month', 'all');
        } catch (\Throwable $e) {
            return ['today' => 0, 'month' => 0, 'all' => 0];
        }
    }
}
