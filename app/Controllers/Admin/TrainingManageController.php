<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\ModuleLearningService;

/**
 * วิทยากร/แอดมิน — จัดการเนื้อหา Pretest / Content / Task / Posttest ต่อโมดูล
 */
class TrainingManageController extends Controller
{
    private ModuleLearningService $lms;

    public function __construct()
    {
        $this->lms = new ModuleLearningService();
    }

    public function index(): void
    {
        // ต้อง create — ผู้เรียนมี assessments.view แต่ไม่ควรเข้าจัดการ LMS
        $this->requirePerm('assessments', 'create');
        $this->view('admin/training/index', [
            'title' => 'จัดการบทเรียน LMS',
            'breadcrumb' => 'หน้าหลัก / บทเรียน LMS',
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('assessments', 'create');
        $this->json(\App\Services\DataTableService::trainingModules(dt_params()));
    }

    public function show(string $planId): void
    {
        $this->requirePerm('assessments', 'create');
        $plan = $this->findPlan((int) $planId);
        $settings = $this->lms->settings((int) $planId);
        $preId = $this->lms->ensureQuiz((int) $planId, 'pretest', 'ทดสอบก่อน: ' . $plan['title'], (int) auth()->id());
        $postId = $this->lms->ensureQuiz((int) $planId, 'posttest', 'สอบหลัง: ' . $plan['title'], (int) auth()->id());
        $satId = $this->lms->ensureQuiz((int) $planId, 'satisfaction', 'ประเมินความพึงพอใจ: ' . $plan['title'], (int) auth()->id());
        $preQuiz = db()->fetch('SELECT * FROM training_quizzes WHERE id = ?', [$preId]);
        $postQuiz = db()->fetch('SELECT * FROM training_quizzes WHERE id = ?', [$postId]);
        $satQuiz = db()->fetch('SELECT * FROM training_quizzes WHERE id = ?', [$satId]);
        $preQuestions = $this->lms->questions($preId, true);
        // reload with is_correct for admin
        foreach ($preQuestions as &$q) {
            $q['choices'] = db()->fetchAll(
                'SELECT * FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id',
                [(int) $q['id']]
            );
        }
        unset($q);
        $postQuestions = $this->lms->questions($postId, true);
        foreach ($postQuestions as &$q) {
            $q['choices'] = db()->fetchAll(
                'SELECT * FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id',
                [(int) $q['id']]
            );
        }
        unset($q);
        $satQuestions = $this->lms->questions($satId, true);
        foreach ($satQuestions as &$q) {
            $q['choices'] = db()->fetchAll(
                'SELECT * FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id',
                [(int) $q['id']]
            );
        }
        unset($q);

        $contents = db()->fetchAll(
            'SELECT * FROM training_contents WHERE plan_id = ? ORDER BY sort_order, id',
            [(int) $planId]
        );
        $tasks = db()->fetchAll(
            'SELECT * FROM training_tasks WHERE plan_id = ? ORDER BY sort_order, id',
            [(int) $planId]
        );
        $submissions = db()->fetchAll(
            "SELECT s.*, t.title AS task_title, u.first_name, u.last_name, u.username, e.enrollment_no
             FROM training_task_submissions s
             INNER JOIN training_tasks t ON t.id = s.task_id
             INNER JOIN users u ON u.id = s.user_id
             INNER JOIN course_enrollments e ON e.id = s.enrollment_id
             WHERE t.plan_id = ?
             ORDER BY FIELD(s.status,'submitted','revision','approved','rejected','draft'), s.id DESC
             LIMIT 100",
            [(int) $planId]
        );

        $this->view('admin/training/show', [
            'title' => 'โมดูล ' . ($plan['plan_no'] ?? '') . ' · จัดการบทเรียน',
            'breadcrumb' => 'หน้าหลัก / บทเรียน LMS / โมดูล',
            'plan' => $plan,
            'settings' => $settings,
            'preQuiz' => $preQuiz,
            'postQuiz' => $postQuiz,
            'satQuiz' => $satQuiz,
            'preQuestions' => $preQuestions,
            'postQuestions' => $postQuestions,
            'satQuestions' => $satQuestions,
            'contents' => $contents,
            'tasks' => $tasks,
            'submissions' => $submissions,
            'tab' => (string) ($this->input('tab') ?: 'content'),
        ]);
    }

    public function saveSettings(string $planId): void
    {
        $this->requirePerm('assessments', 'edit');
        $this->findPlan((int) $planId);
        $this->lms->settings((int) $planId);
        db()->update('training_plan_settings', [
            'weight_pretest' => (float) $this->input('weight_pretest', 10),
            'weight_content' => (float) $this->input('weight_content', 35),
            'weight_task' => (float) $this->input('weight_task', 15),
            'weight_posttest' => (float) $this->input('weight_posttest', 25),
            'weight_satisfaction' => (float) $this->input('weight_satisfaction', 15),
            'require_pretest' => $this->input('require_pretest') ? 1 : 0,
            'require_pass_posttest' => $this->input('require_pass_posttest') ? 1 : 0,
            'require_satisfaction' => $this->input('require_satisfaction') ? 1 : 0,
            'gate_content_after_pretest' => $this->input('gate_content_after_pretest') ? 1 : 0,
            'gate_posttest_after_content' => $this->input('gate_posttest_after_content') ? 1 : 0,
            'gate_satisfaction_after_posttest' => $this->input('gate_satisfaction_after_posttest') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'plan_id = ?', [(int) $planId]);
        flash('success', 'บันทึกน้ำหนักความก้าวหน้าแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=settings');
    }

    public function storeContent(string $planId): void
    {
        $this->requirePerm('assessments', 'create');
        $this->findPlan((int) $planId);
        $type = (string) $this->input('content_type', 'link');
        $allowedTypes = ['pdf', 'word', 'excel', 'gdrive', 'youtube', 'link', 'text'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'link';
        }

        $filePath = null;
        if (!empty($_FILES['file']['name'])) {
            $extMap = [
                'pdf' => ['pdf'],
                'word' => ['doc', 'docx'],
                'excel' => ['xls', 'xlsx', 'csv'],
            ];
            $allowed = $extMap[$type] ?? ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'];
            $filePath = upload_file($_FILES['file'], 'training', $allowed);
            if (!$filePath) {
                flash('error', 'อัปโหลดไฟล์ไม่สำเร็จ หรือนามสกุลไฟล์ไม่ถูกต้อง');
                $this->redirect('/admin/training/' . $planId . '?tab=content');
            }
            if (in_array($type, ['link', 'gdrive', 'youtube', 'text'], true)) {
                $ext = strtolower(pathinfo((string) $_FILES['file']['name'], PATHINFO_EXTENSION));
                $type = match (true) {
                    $ext === 'pdf' => 'pdf',
                    in_array($ext, ['doc', 'docx'], true) => 'word',
                    in_array($ext, ['xls', 'xlsx', 'csv'], true) => 'excel',
                    default => $type,
                };
            }
        }

        db()->insert('training_contents', [
            'plan_id' => (int) $planId,
            'title' => trim((string) $this->input('title', 'เนื้อหา')),
            'description' => trim((string) $this->input('description', '')) ?: null,
            'content_type' => $type,
            'file_path' => $filePath,
            'external_url' => trim((string) $this->input('external_url', '')) ?: null,
            'body_text' => trim((string) $this->input('body_text', '')) ?: null,
            'is_required' => $this->input('is_required') ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0),
            'status' => 1,
            'created_by' => (int) auth()->id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        activity_log('create', 'assessments', 'เพิ่มเนื้อหาโมดูล #' . $planId);
        flash('success', 'เพิ่มเนื้อหาแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=content');
    }

    public function deleteContent(string $planId, string $id): void
    {
        $this->requirePerm('assessments', 'delete');
        db()->query('DELETE FROM training_contents WHERE id = ? AND plan_id = ?', [(int) $id, (int) $planId]);
        flash('success', 'ลบเนื้อหาแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=content');
    }

    public function storeTask(string $planId): void
    {
        $this->requirePerm('assessments', 'create');
        $this->findPlan((int) $planId);
        db()->insert('training_tasks', [
            'plan_id' => (int) $planId,
            'title' => trim((string) $this->input('title', 'ภาระงาน')),
            'description' => trim((string) $this->input('description', '')) ?: null,
            'instructions' => trim((string) $this->input('instructions', '')) ?: null,
            'require_file' => $this->input('require_file') ? 1 : 0,
            'allow_link' => $this->input('allow_link') ? 1 : 0,
            'require_review' => $this->input('require_review') ? 1 : 0,
            'max_score' => (float) $this->input('max_score', 100),
            'pass_score' => (float) $this->input('pass_score', 60),
            'is_required' => $this->input('is_required') ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0),
            'status' => 1,
            'created_by' => (int) auth()->id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        activity_log('create', 'assessments', 'เพิ่มภาระงานโมดูล #' . $planId);
        flash('success', 'เพิ่มภาระงานแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=tasks');
    }

    public function deleteTask(string $planId, string $id): void
    {
        $this->requirePerm('assessments', 'delete');
        db()->query('DELETE FROM training_tasks WHERE id = ? AND plan_id = ?', [(int) $id, (int) $planId]);
        flash('success', 'ลบภาระงานแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=tasks');
    }

    public function reviewSubmission(string $planId, string $id): void
    {
        $this->requirePerm('assessments', 'edit');
        try {
            $this->lms->reviewTask(
                (int) $id,
                (string) $this->input('status', 'approved'),
                $this->input('score') !== null && $this->input('score') !== '' ? (float) $this->input('score') : null,
                trim((string) $this->input('feedback', '')) ?: null,
                (int) auth()->id()
            );
            flash('success', 'ตรวจภาระงานแล้ว');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('/admin/training/' . $planId . '?tab=submissions');
    }

    public function storeQuestion(string $planId): void
    {
        $this->requirePerm('assessments', 'create');
        $this->findPlan((int) $planId);
        $type = (string) $this->input('quiz_type', 'pretest');
        if (!in_array($type, ['pretest', 'posttest', 'satisfaction'], true)) {
            $type = 'pretest';
        }
        $quizId = $this->lms->ensureQuiz(
            (int) $planId,
            $type,
            ($type === 'pretest' ? 'Pretest' : 'Posttest') . ': plan ' . $planId,
            (int) auth()->id()
        );

        $qText = trim((string) $this->input('question_text', ''));
        if ($qText === '') {
            flash('error', 'กรุณากรอกคำถาม');
            $this->redirect('/admin/training/' . $planId . '?tab=' . $type);
        }

        $qId = (int) db()->insert('training_quiz_questions', [
            'quiz_id' => $quizId,
            'question_text' => $qText,
            'question_type' => 'single',
            'points' => (float) $this->input('points', 1),
            'explanation' => trim((string) $this->input('explanation', '')) ?: null,
            'sort_order' => (int) $this->input('sort_order', 0),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $choices = $this->input('choices');
        if (!is_array($choices)) {
            $choices = [];
        }
        $correct = (int) $this->input('correct_index', 0);
        $i = 0;
        foreach ($choices as $text) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }
            db()->insert('training_quiz_choices', [
                'question_id' => $qId,
                'choice_text' => $text,
                'is_correct' => $i === $correct ? 1 : 0,
                'sort_order' => $i,
            ]);
            $i++;
        }
        if ($i === 0) {
            // true/false defaults
            db()->insert('training_quiz_choices', [
                'question_id' => $qId, 'choice_text' => 'ถูก', 'is_correct' => $correct === 0 ? 1 : 0, 'sort_order' => 0,
            ]);
            db()->insert('training_quiz_choices', [
                'question_id' => $qId, 'choice_text' => 'ผิด', 'is_correct' => $correct === 1 ? 1 : 0, 'sort_order' => 1,
            ]);
        }

        flash('success', 'เพิ่มข้อสอบแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=' . $type);
    }

    public function deleteQuestion(string $planId, string $id): void
    {
        $this->requirePerm('assessments', 'delete');
        $q = db()->fetch(
            "SELECT q.id FROM training_quiz_questions q
             INNER JOIN training_quizzes z ON z.id = q.quiz_id
             WHERE q.id = ? AND z.plan_id = ?",
            [(int) $id, (int) $planId]
        );
        if ($q) {
            db()->query('DELETE FROM training_quiz_questions WHERE id = ?', [(int) $id]);
            flash('success', 'ลบข้อสอบแล้ว');
        }
        $tab = (string) ($this->input('tab') ?: 'pretest');
        $this->redirect('/admin/training/' . $planId . '?tab=' . $tab);
    }

    public function saveQuizMeta(string $planId): void
    {
        $this->requirePerm('assessments', 'edit');
        $type = (string) $this->input('quiz_type', 'pretest');
        if (!in_array($type, ['pretest', 'posttest', 'satisfaction'], true)) {
            $type = 'pretest';
        }
        $quizId = $this->lms->ensureQuiz((int) $planId, $type, ucfirst($type), (int) auth()->id());
        db()->update('training_quizzes', [
            'title' => trim((string) $this->input('title', ucfirst($type))),
            'description' => trim((string) $this->input('description', '')) ?: null,
            'pass_score' => (float) $this->input('pass_score', 60),
            'max_attempts' => max(1, (int) $this->input('max_attempts', 3)),
            'time_limit_minutes' => ($v = (int) $this->input('time_limit_minutes', 0)) > 0 ? $v : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$quizId]);
        flash('success', 'บันทึกการตั้งค่าแบบทดสอบแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=' . $type);
    }

    private function findPlan(int $id): array
    {
        $plan = db()->fetch('SELECT * FROM learning_plans WHERE id = ?', [$id]);
        if (!$plan) {
            flash('error', 'ไม่พบโมดูล');
            $this->redirect('/admin/training');
        }
        return $plan;
    }
}
