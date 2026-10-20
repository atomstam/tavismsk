<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Services\ModuleLearningService;
use App\Services\PlanProgress;
use App\Services\TrainingService;

/**
 * Frontend LMS — Pretest → Content → Task → Posttest → Satisfaction
 * ใช้ ModuleLearningService ชุดเดียวกับฝั่งจัดการบทเรียน
 */
class LearnController extends Controller
{
    private const BASE = '/learn';

    private ModuleLearningService $lms;

    public function __construct()
    {
        $this->lms = new ModuleLearningService();
    }

    public function index(): void
    {
        $this->requirePerm('learn', 'view');
        $userId = (int) auth()->id();
        $modules = $this->lms->moduleOverviewForUser($userId);
        $required = array_values(array_filter(
            $modules,
            static fn (array $m): bool => PlanProgress::isRequired($m['plan'] ?? [])
        ));
        $overall = 0.0;
        if ($required !== []) {
            $sum = 0.0;
            foreach ($required as $m) {
                $sum += (float) ($m['progress']['overall_percent'] ?? 0);
            }
            $overall = round($sum / count($required), 2);
        }
        $enrollId = (int) ($modules[0]['enrollment']['id'] ?? 0);
        $standing = $enrollId > 0 ? (new TrainingService())->requiredPlanStanding($enrollId) : ['total' => 0, 'passed' => 0, 'ready' => false];
        $this->webView('learn/index', [
            'title' => 'บทเรียนของฉัน',
            'modules' => $modules,
            'courseOverall' => $overall,
            'requiredStanding' => $standing,
            'learnBase' => self::BASE,
            'webShell' => true,
            'darkUi' => false,
        ]);
    }

    public function show(string $planId): void
    {
        $this->requirePerm('learn', 'view');
        $ctx = $this->context((int) $planId);
        $plan = $ctx['plan'];
        $enroll = $ctx['enrollment'];
        $progress = $this->lms->recalculate((int) $planId, (int) $enroll['id'], true);

        $preQuiz = $this->lms->quiz((int) $planId, 'pretest');
        $postQuiz = $this->lms->quiz((int) $planId, 'posttest');
        $satQuiz = $this->lms->quiz((int) $planId, 'satisfaction');
        $contents = $this->lms->contents((int) $planId);
        $tasks = $this->lms->tasks((int) $planId);

        $contentViews = [];
        foreach ($contents as $c) {
            $contentViews[(int) $c['id']] = db()->fetch(
                'SELECT * FROM training_content_views WHERE content_id = ? AND enrollment_id = ?',
                [(int) $c['id'], (int) $enroll['id']]
            );
        }
        $taskSubs = [];
        foreach ($tasks as $t) {
            $taskSubs[(int) $t['id']] = db()->fetch(
                'SELECT * FROM training_task_submissions WHERE task_id = ? AND enrollment_id = ?',
                [(int) $t['id'], (int) $enroll['id']]
            );
        }

        $this->webView('learn/show', [
            'title' => 'แผนที่ ' . ($plan['plan_no'] ?? '') . ' · เข้าเรียน',
            'plan' => $plan,
            'enrollment' => $enroll,
            'progress' => $progress,
            'settings' => $this->lms->settings((int) $planId),
            'preQuiz' => $preQuiz,
            'postQuiz' => $postQuiz,
            'satQuiz' => $satQuiz,
            'preAttempt' => $preQuiz ? $this->lms->bestAttempt((int) $preQuiz['id'], (int) $enroll['id']) : null,
            'postAttempt' => $postQuiz ? $this->lms->bestAttempt((int) $postQuiz['id'], (int) $enroll['id']) : null,
            'satAttempt' => $satQuiz ? $this->lms->bestAttempt((int) $satQuiz['id'], (int) $enroll['id']) : null,
            'contents' => $contents,
            'tasks' => $tasks,
            'contentViews' => $contentViews,
            'taskSubs' => $taskSubs,
            'canContent' => $this->lms->canAccessContent((int) $planId, (int) $enroll['id']),
            'canPosttest' => $this->lms->canAccessPosttest((int) $planId, (int) $enroll['id']),
            'canSatisfaction' => $this->lms->canAccessSatisfaction((int) $planId, (int) $enroll['id']),
            'planCertificate' => db()->fetch(
                "SELECT id, cert_no FROM certificates WHERE enrollment_id = ? AND plan_id = ? AND status = 'active'",
                [(int) $enroll['id'], (int) $planId]
            ),
            'learnBase' => self::BASE,
            'webShell' => true,
            'darkUi' => false,
        ]);
    }

    public function quiz(string $planId, string $type): void
    {
        $this->requirePerm('learn', 'view');
        if (!in_array($type, ['pretest', 'posttest', 'satisfaction'], true)) {
            flash('error', 'ประเภทแบบทดสอบไม่ถูกต้อง');
            $this->redirect(self::BASE . '/' . $planId);
        }
        $ctx = $this->context((int) $planId);
        $enrollId = (int) $ctx['enrollment']['id'];

        if ($type === 'posttest' && !$this->lms->canAccessPosttest((int) $planId, $enrollId)) {
            flash('error', 'กรุณาเรียนเนื้อหาและส่งภาระงานให้ครบก่อนทำ Posttest');
            $this->redirect(self::BASE . '/' . $planId);
        }
        if ($type === 'satisfaction' && !$this->lms->canAccessSatisfaction((int) $planId, $enrollId)) {
            flash('error', 'กรุณาทำ Posttest ให้ผ่านก่อนประเมินความพึงพอใจ');
            $this->redirect(self::BASE . '/' . $planId);
        }

        $quiz = $this->lms->quiz((int) $planId, $type);
        if (!$quiz) {
            flash('error', 'ยังไม่มีข้อสอบในโมดูลนี้');
            $this->redirect(self::BASE . '/' . $planId);
        }

        $used = $this->lms->attemptCount((int) $quiz['id'], $enrollId);
        $max = (int) $quiz['max_attempts'];
        if ($used >= $max) {
            flash('error', 'คุณทำแบบทดสอบครบจำนวนครั้งแล้ว');
            $this->redirect(self::BASE . '/' . $planId);
        }

        if (in_array($type, ['pretest', 'satisfaction'], true) && $this->lms->bestAttempt((int) $quiz['id'], $enrollId) && $max <= 1) {
            flash('info', $type === 'pretest' ? 'คุณทำ Pretest แล้ว' : 'คุณประเมินความพึงพอใจแล้ว');
            $this->redirect(self::BASE . '/' . $planId);
        }

        $typeLabel = match ($type) {
            'pretest' => 'ทดสอบก่อนเรียน',
            'posttest' => 'สอบหลังเรียน',
            default => 'ประเมินความพึงพอใจ',
        };

        $this->webView('learn/quiz', [
            'title' => $typeLabel . ' · แผนที่ ' . $ctx['plan']['plan_no'],
            'plan' => $ctx['plan'],
            'quiz' => $quiz,
            'type' => $type,
            'questions' => $this->lms->learnerQuestions((int) $planId, $type, $enrollId, (int) auth()->id()),
            'attemptsLeft' => max(0, $max - $used),
            'learnBase' => self::BASE,
            'webShell' => true,
            'darkUi' => false,
        ]);
    }

    public function submitQuiz(string $planId, string $type): void
    {
        $this->requirePerm('learn', 'view');
        if (!in_array($type, ['pretest', 'posttest', 'satisfaction'], true)) {
            $this->redirect(self::BASE . '/' . $planId);
        }
        $ctx = $this->context((int) $planId);
        $enrollId = (int) $ctx['enrollment']['id'];
        $userId = (int) auth()->id();

        if ($type === 'posttest' && !$this->lms->canAccessPosttest((int) $planId, $enrollId)) {
            flash('error', 'ยังไม่สามารถทำ Posttest ได้');
            $this->redirect(self::BASE . '/' . $planId);
        }
        if ($type === 'satisfaction' && !$this->lms->canAccessSatisfaction((int) $planId, $enrollId)) {
            flash('error', 'ยังไม่สามารถประเมินความพึงพอใจได้');
            $this->redirect(self::BASE . '/' . $planId);
        }

        $quiz = $this->lms->quiz((int) $planId, $type);
        if (!$quiz) {
            flash('error', 'ไม่พบแบบทดสอบ');
            $this->redirect(self::BASE . '/' . $planId);
        }

        $raw = $this->input('answers');
        $answers = [];
        if (is_array($raw)) {
            foreach ($raw as $qid => $cid) {
                $answers[(int) $qid] = (int) $cid;
            }
        }

        try {
            $limitIds = $type === 'satisfaction'
                ? null
                : $this->lms->learnerQuestionIds((int) $planId, $type, $enrollId, $userId);
            $result = $this->lms->submitQuiz((int) $quiz['id'], $enrollId, $userId, $answers, $limitIds);
            activity_log('submit', 'assessments', strtoupper($type) . ' plan#' . $planId . ' ' . $result['percent'] . '%');
            $typeLabel = match ($type) {
                'pretest' => 'ทดสอบก่อนเรียน',
                'posttest' => 'สอบหลังเรียน',
                default => 'แบบประเมินความพึงพอใจ',
            };
            if ($type === 'satisfaction') {
                flash('success', 'ส่ง' . $typeLabel . 'เรียบร้อยแล้ว ขอบคุณสำหรับความคิดเห็น');
            } else {
                $msg = sprintf(
                    'ส่ง%s แล้ว คะแนน %.1f/%.1f (%.0f%%) — %s',
                    $typeLabel,
                    $result['score'],
                    $result['max_score'],
                    $result['percent'],
                    $result['is_passed'] ? 'ผ่าน' : 'ยังไม่ผ่าน'
                );
                flash($result['is_passed'] || $type === 'pretest' ? 'success' : 'warning', $msg);
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect(self::BASE . '/' . $planId);
    }

    public function content(string $planId, string $contentId): void
    {
        $this->requirePerm('learn', 'view');
        $ctx = $this->context((int) $planId);
        $enrollId = (int) $ctx['enrollment']['id'];
        if (!$this->lms->canAccessContent((int) $planId, $enrollId)) {
            flash('error', 'กรุณาทำ Pretest ให้เสร็จก่อนเข้าเรียนเนื้อหา');
            $this->redirect(self::BASE . '/' . $planId);
        }
        $content = db()->fetch(
            'SELECT * FROM training_contents WHERE id = ? AND plan_id = ? AND status = 1',
            [(int) $contentId, (int) $planId]
        );
        if (!$content) {
            flash('error', 'ไม่พบเนื้อหา');
            $this->redirect(self::BASE . '/' . $planId);
        }
        $outside = trim((string) ($content['external_url'] ?? ''));
        if (in_array((string) $content['content_type'], ['gdrive', 'link'], true) && preg_match('#^https?://#i', $outside)) {
            $this->redirect($outside);
        }

        $ex = db()->fetch(
            'SELECT id FROM training_content_views WHERE content_id = ? AND enrollment_id = ?',
            [(int) $contentId, $enrollId]
        );
        if (!$ex) {
            db()->insert('training_content_views', [
                'content_id' => (int) $contentId,
                'enrollment_id' => $enrollId,
                'user_id' => (int) auth()->id(),
                'viewed_at' => date('Y-m-d H:i:s'),
                'is_completed' => 0,
            ]);
        }

        $this->webView('learn/content', [
            'title' => $content['title'],
            'plan' => $ctx['plan'],
            'content' => $content,
            'preview' => (new \App\Services\ContentPreviewService())->present($content),
            'learnBase' => self::BASE,
            'webShell' => true,
            'darkUi' => false,
        ]);
    }

    public function completeContent(string $planId, string $contentId): void
    {
        $this->requirePerm('learn', 'view');
        $ctx = $this->context((int) $planId);
        try {
            $this->lms->markContentComplete((int) $contentId, (int) $ctx['enrollment']['id'], (int) auth()->id());
            flash('success', 'บันทึกว่าเรียนเนื้อหาแล้ว');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect(self::BASE . '/' . $planId);
    }

    public function submitTask(string $planId, string $taskId): void
    {
        $this->requirePerm('learn', 'view');
        $ctx = $this->context((int) $planId);
        $filePath = null;
        if (!empty($_FILES['file']['name'])) {
            $filePath = upload_file($_FILES['file'], 'training-tasks', [
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'zip',
            ]);
        }
        try {
            $this->lms->submitTask((int) $taskId, (int) $ctx['enrollment']['id'], (int) auth()->id(), [
                'answer_text' => (string) $this->input('answer_text', ''),
                'link_url' => (string) $this->input('link_url', ''),
                'file_path' => $filePath,
            ]);
            flash('success', 'ส่งภาระงานแล้ว');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect(self::BASE . '/' . $planId);
    }

    public function planCertificate(string $planId): void
    {
        $this->requirePerm('learn', 'view');
        $ctx = $this->context((int) $planId);
        $cert = (new TrainingService())->issuePlanCertificate(
            (int) $ctx['enrollment']['id'],
            (int) $planId,
            (int) auth()->id()
        );
        if (!$cert) {
            flash('error', 'ยังไม่ผ่านเกณฑ์ของแผนนี้ จึงดาวน์โหลดเกียรติบัตรไม่ได้');
            $this->redirect(self::BASE . '/' . $planId);
        }
        $this->redirect('/admin/certificates/' . (int) $cert['id']);
    }

    public function diploma(): void
    {
        $this->requirePerm('learn', 'view');
        $enroll = $this->lms->ensureEnrollment((int) auth()->id());
        $cert = $enroll
            ? (new TrainingService())->issueCertificate((int) $enroll['id'], (int) auth()->id())
            : null;
        if (!$cert) {
            flash('error', 'ต้องเรียนแผนบังคับให้ครบและผ่านเกณฑ์ก่อน จึงจะดาวน์โหลดวุฒิบัตรได้');
            $this->redirect(self::BASE);
        }
        $this->redirect('/admin/certificates/' . (int) $cert['id']);
    }

    /** @return array{plan:array,enrollment:array} */
    private function context(int $planId): array
    {
        $plan = db()->fetch('SELECT * FROM learning_plans WHERE id = ? AND status = 1', [$planId]);
        if (!$plan) {
            flash('error', 'ไม่พบโมดูล');
            $this->redirect(self::BASE);
        }
        $enroll = $this->lms->ensureEnrollment((int) auth()->id());
        if (!$enroll) {
            flash('error', 'ไม่สามารถสร้างการลงทะเบียนได้');
            $this->redirect(self::BASE);
        }
        return ['plan' => $plan, 'enrollment' => $enroll];
    }
}
