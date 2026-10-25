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
        if ((string) $this->input('tab') === 'satisfaction') {
            $this->redirect('/admin/training/' . (int) $planId . '?tab=content');
        }
        $plan = $this->findPlan((int) $planId);
        $settings = $this->lms->settings((int) $planId);
        $preId = $this->lms->ensureQuiz((int) $planId, 'pretest', 'ทดสอบก่อน: ' . $plan['title'], (int) auth()->id());
        $postId = $this->lms->ensureQuiz((int) $planId, 'posttest', 'สอบหลัง: ' . $plan['title'], (int) auth()->id());
        $this->lms->ensureQuiz((int) $planId, 'satisfaction', 'แบบประเมินความพึงพอใจ (กลาง)', (int) auth()->id());
        $preQuiz = db()->fetch('SELECT * FROM training_quizzes WHERE id = ?', [$preId]);
        $postQuiz = db()->fetch('SELECT * FROM training_quizzes WHERE id = ?', [$postId]);
        $preQuestions = $this->lms->questions($preId, true);
        foreach ($preQuestions as &$q) {
            $q['choices'] = db()->fetchAll(
                'SELECT * FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id',
                [(int) $q['id']]
            );
        }
        unset($q);
        $postQuestions = $preQuestions;

        $contents = db()->fetchAll(
            'SELECT * FROM training_contents WHERE plan_id = ? ORDER BY sort_order, id',
            [(int) $planId]
        );
        $tasks = db()->fetchAll(
            'SELECT * FROM training_tasks WHERE plan_id = ? ORDER BY sort_order, id',
            [(int) $planId]
        );
        $submissions = db()->fetchAll(
            "SELECT s.*, t.title AS task_title, t.max_score AS task_max_score,
                    u.first_name, u.last_name, u.username, e.enrollment_no
             FROM training_task_submissions s
             INNER JOIN training_tasks t ON t.id = s.task_id
             INNER JOIN users u ON u.id = s.user_id
             INNER JOIN course_enrollments e ON e.id = s.enrollment_id
             WHERE t.plan_id = ?
             ORDER BY FIELD(s.status,'submitted','revision','approved','rejected','draft'), s.id DESC
             LIMIT 300",
            [(int) $planId]
        );
        $pendingCount = (int) (db()->fetch(
            "SELECT COUNT(*) AS c
             FROM training_task_submissions s
             INNER JOIN training_tasks t ON t.id = s.task_id
             WHERE t.plan_id = ? AND t.status = 1 AND s.status IN ('submitted','revision')",
            [(int) $planId]
        )['c'] ?? 0);

        $satId = $this->lms->centralSatisfactionId((int) auth()->id());
        $satCount = $satId > 0
            ? (int) (db()->fetch(
                'SELECT COUNT(*) AS c FROM training_quiz_questions WHERE quiz_id = ? AND status = 1',
                [$satId]
            )['c'] ?? 0)
            : 0;
        $requiredContents = count(array_filter($contents, static fn (array $c): bool => (int) ($c['is_required'] ?? 0) === 1 && (int) ($c['status'] ?? 1) === 1));
        $requiredTasks = count(array_filter($tasks, static fn (array $t): bool => (int) ($t['is_required'] ?? 0) === 1 && (int) ($t['status'] ?? 1) === 1));
        $shares = \App\Services\PlanProgress::shares(
            $settings,
            (int) ($settings['quiz_draw_count'] ?? 10),
            count($preQuestions),
            $requiredContents,
            $requiredTasks,
            $satCount
        );

        $this->view('admin/training/show', [
            'title' => 'โมดูล ' . ($plan['plan_no'] ?? '') . ' · จัดการบทเรียน',
            'breadcrumb' => 'หน้าหลัก / บทเรียน LMS / โมดูล',
            'plan' => $plan,
            'settings' => $settings,
            'preQuiz' => $preQuiz,
            'postQuiz' => $postQuiz,
            'preQuestions' => $preQuestions,
            'postQuestions' => $postQuestions,
            'contents' => $contents,
            'tasks' => $tasks,
            'submissions' => $submissions,
            'pendingCount' => $pendingCount,
            'shares' => $shares,
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
            'quiz_draw_count' => max(1, (int) $this->input('quiz_draw_count', 10)),
            'quiz_shuffle' => $this->input('quiz_shuffle') ? 1 : 0,
            'pass_percent' => max(1, min(100, (float) $this->input('pass_percent', 80))),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'plan_id = ?', [(int) $planId]);
        flash('success', 'บันทึกน้ำหนักความก้าวหน้าแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=settings');
    }

    public function storeContent(string $planId): void
    {
        $editId = (int) $this->input('content_id', 0);
        $this->requirePerm('assessments', $editId > 0 ? 'edit' : 'create');
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

        $payload = [
            'title' => trim((string) $this->input('title', 'เนื้อหา')),
            'description' => trim((string) $this->input('description', '')) ?: null,
            'content_type' => $type,
            'external_url' => trim((string) $this->input('external_url', '')) ?: null,
            'body_text' => trim((string) $this->input('body_text', '')) ?: null,
            'is_required' => $this->input('is_required') ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($filePath) {
            $payload['file_path'] = $filePath;
        }
        if ($editId > 0) {
            $existing = db()->fetch(
                'SELECT id FROM training_contents WHERE id = ? AND plan_id = ?',
                [$editId, (int) $planId]
            );
            if (!$existing) {
                flash('error', 'ไม่พบเนื้อหาที่จะแก้ไข');
                $this->redirect('/admin/training/' . $planId . '?tab=content');
            }
            db()->update('training_contents', $payload, 'id = ? AND plan_id = ?', [$editId, (int) $planId]);
            activity_log('update', 'assessments', 'แก้ไขเนื้อหาโมดูล #' . $planId);
            flash('success', 'แก้ไขเนื้อหาแล้ว');
            $this->redirect('/admin/training/' . $planId . '?tab=content');
        }

        $payload['plan_id'] = (int) $planId;
        $payload['file_path'] = $filePath;
        $payload['status'] = 1;
        $payload['created_by'] = (int) auth()->id();
        $payload['created_at'] = date('Y-m-d H:i:s');
        db()->insert('training_contents', $payload);
        activity_log('create', 'assessments', 'เพิ่มเนื้อหาโมดูล #' . $planId);
        flash('success', 'เพิ่มเนื้อหาแล้ว');
        $this->redirect('/admin/training/' . $planId . '?tab=content');
    }

    public function viewContent(string $planId, string $id): void
    {
        $this->requirePerm('assessments', 'view');
        $plan = $this->findPlan((int) $planId);
        $content = db()->fetch(
            'SELECT * FROM training_contents WHERE id = ? AND plan_id = ?',
            [(int) $id, (int) $planId]
        );
        if (!$content) {
            flash('error', 'ไม่พบเนื้อหา');
            $this->redirect('/admin/training/' . $planId . '?tab=content');
        }
        $outside = trim((string) ($content['external_url'] ?? ''));
        if (in_array((string) $content['content_type'], ['gdrive', 'link'], true) && preg_match('#^https?://#i', $outside)) {
            $this->redirect($outside);
        }
        $preview = (new \App\Services\ContentPreviewService())->present($content);
        $preview['title'] = (string) ($content['title'] ?? '');
        $preview['back_url'] = url('/admin/training/' . (int) $planId . '?tab=content');
        if (($preview['kind'] ?? '') === 'pdf' && ($preview['frame_src'] ?? '') !== '') {
            $path = (string) (parse_url((string) $preview['frame_src'], PHP_URL_PATH) ?: '');
            $preview['file_name'] = basename($path) ?: 'content.pdf';
            $preview['info_text'] = 'ไฟล์เนื้อหา: ' . (string) ($content['title'] ?? '')
                . ' — หลักสูตรที่ ' . (int) ($plan['plan_no'] ?? 0)
                . ' — ' . $preview['file_name'];
        }
        $this->view('admin/training/content_view', [
            'title' => (string) $content['title'],
            'breadcrumb' => 'หน้าหลัก / บทเรียน LMS / เนื้อหา',
            'plan' => $plan,
            'content' => $content,
            'preview' => $preview,
        ]);
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
        $editId = (int) $this->input('task_id', 0);
        $this->requirePerm('assessments', $editId > 0 ? 'edit' : 'create');
        $this->findPlan((int) $planId);
        $payload = [
            'title' => trim((string) $this->input('title', 'ภาระงาน')),
            'description' => trim((string) $this->input('description', '')) ?: null,
            'instructions' => trim((string) $this->input('instructions', '')) ?: null,
            'require_file' => $this->input('require_file') ? 1 : 0,
            'allow_link' => $this->input('allow_link') ? 1 : 0,
            'require_review' => 1, // ภาระงานต้องรอวิทยากรตรวจให้คะแนนเสมอ
            'max_score' => (float) $this->input('max_score', 100),
            'pass_score' => (float) $this->input('pass_score', 60),
            'is_required' => $this->input('is_required') ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($editId > 0) {
            $existing = db()->fetch(
                'SELECT id FROM training_tasks WHERE id = ? AND plan_id = ?',
                [$editId, (int) $planId]
            );
            if (!$existing) {
                flash('error', 'ไม่พบภาระงานที่จะแก้ไข');
                $this->redirect('/admin/training/' . $planId . '?tab=tasks');
            }
            db()->update('training_tasks', $payload, 'id = ? AND plan_id = ?', [$editId, (int) $planId]);
            flash('success', 'แก้ไขภาระงานแล้ว');
            $this->redirect('/admin/training/' . $planId . '?tab=tasks');
        }
        $payload['plan_id'] = (int) $planId;
        $payload['status'] = 1;
        $payload['created_by'] = (int) auth()->id();
        $payload['created_at'] = date('Y-m-d H:i:s');
        db()->insert('training_tasks', $payload);
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
        $this->findPlan((int) $planId);
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

    /** ตรวจผ่านหลายรายการ / ทั้งหลักสูตร / รายผู้เรียน — คะแนนเริ่มต้น = คะแนนเต็ม */
    public function bulkReview(string $planId): void
    {
        $this->requirePerm('assessments', 'edit');
        $this->findPlan((int) $planId);
        $scope = (string) $this->input('scope', 'selected');
        $enrollmentId = (int) $this->input('enrollment_id', 0);
        $ids = $this->input('submission_ids');
        $idList = is_array($ids) ? array_map('intval', $ids) : [];

        try {
            if ($scope === 'all') {
                $n = $this->lms->bulkApproveSubmissions((int) $planId, (int) auth()->id(), null, null);
            } elseif ($scope === 'enrollment' && $enrollmentId > 0) {
                $n = $this->lms->bulkApproveSubmissions((int) $planId, (int) auth()->id(), null, $enrollmentId);
            } else {
                $n = $this->lms->bulkApproveSubmissions((int) $planId, (int) auth()->id(), $idList, null);
            }
            flash('success', 'ตรวจผ่านแล้ว ' . $n . ' รายการ (คะแนนเริ่มต้น = คะแนนเต็มของแต่ละภาระงาน)');
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
        if (!in_array($type, ['pretest', 'posttest'], true)) {
            flash('error', 'แบบประเมินความพึงพอใจจัดการโดยผู้ดูแลระบบ');
            $this->redirect('/admin/training/' . $planId . '?tab=content');
        }
        $bankType = 'pretest';
        $quizId = $this->lms->ensureQuiz(
            (int) $planId,
            $bankType,
            'Pretest: plan ' . $planId,
            (int) auth()->id()
        );

        $returnTab = (string) $this->input('return_tab', $type);
        if (!in_array($returnTab, ['pretest', 'posttest'], true)) {
            $returnTab = $type;
        }
        $qText = trim((string) $this->input('question_text', ''));
        if ($qText === '') {
            flash('error', 'กรุณากรอกคำถาม');
            $this->redirect('/admin/training/' . $planId . '?tab=' . $returnTab);
        }

        $images = new \App\Services\QuizImageService();
        $editId = (int) $this->input('question_id', 0);
        if ($editId > 0) {
            $this->requirePerm('assessments', 'edit');
            $existing = db()->fetch(
                "SELECT q.* FROM training_quiz_questions q
                 INNER JOIN training_quizzes z ON z.id = q.quiz_id
                 WHERE q.id = ? AND (z.plan_id = ? OR z.id = ?)",
                [$editId, (int) $planId, $quizId]
            );
            if (!$existing) {
                flash('error', 'ไม่พบข้อสอบที่จะแก้ไข');
                $this->redirect('/admin/training/' . $planId . '?tab=' . $returnTab);
            }
            $this->saveQuestionChoices($editId, $images, $existing);
            flash('success', 'แก้ไขข้อสอบแล้ว');
            $this->redirect('/admin/training/' . $planId . '?tab=' . $returnTab);
        }

        $qId = (int) db()->insert('training_quiz_questions', [
            'quiz_id' => $quizId,
            'question_text' => $qText,
            'image_path' => $images->store($_FILES['question_image'] ?? null),
            'question_type' => 'single',
            'points' => 1,
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
        $choiceFiles = $_FILES['choice_images'] ?? [];
        $i = 0;
        foreach ($choices as $index => $text) {
            $text = trim((string) $text);
            $image = $images->storeAt(is_array($choiceFiles) ? $choiceFiles : [], (int) $index);
            if ($text === '' && $image === null) {
                continue;
            }
            db()->insert('training_quiz_choices', [
                'question_id' => $qId,
                'choice_text' => $text,
                'image_path' => $image,
                'is_correct' => (int) $index === $correct ? 1 : 0,
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
        $this->redirect('/admin/training/' . $planId . '?tab=' . $returnTab);
    }

    /** @param array<string, mixed> $existing */
    private function saveQuestionChoices(int $questionId, \App\Services\QuizImageService $images, array $existing): void
    {
        $newImage = $images->store($_FILES['question_image'] ?? null);
        $imagePath = $newImage ?: (string) ($existing['image_path'] ?? '');
        if ($this->input('remove_question_image') && $newImage === null) {
            $imagePath = '';
        }
        db()->update('training_quiz_questions', [
            'question_text' => trim((string) $this->input('question_text', '')),
            'image_path' => $imagePath !== '' ? $imagePath : null,
            'points' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$questionId]);

        $choices = $this->input('choices');
        $choiceIds = $this->input('choice_ids');
        $keepPaths = $this->input('choice_image_paths');
        $remove = $this->input('remove_choice_image');
        if (!is_array($choices)) {
            $choices = [];
        }
        if (!is_array($choiceIds)) {
            $choiceIds = [];
        }
        if (!is_array($keepPaths)) {
            $keepPaths = [];
        }
        if (!is_array($remove)) {
            $remove = [];
        }
        $correct = (int) $this->input('correct_index', 0);
        $files = $_FILES['choice_images'] ?? [];
        $kept = [];
        $order = 0;
        foreach ($choices as $index => $text) {
            $index = (int) $index;
            $text = trim((string) $text);
            $choiceId = (int) ($choiceIds[$index] ?? 0);
            $uploaded = $images->storeAt(is_array($files) ? $files : [], $index);
            $path = $uploaded ?: trim((string) ($keepPaths[$index] ?? ''));
            if (!empty($remove[$index]) && $uploaded === null) {
                $path = '';
            }
            if ($text === '' && $path === '') {
                if ($choiceId > 0) {
                    db()->query('DELETE FROM training_quiz_choices WHERE id = ? AND question_id = ?', [$choiceId, $questionId]);
                }
                continue;
            }
            $row = [
                'choice_text' => $text,
                'image_path' => $path !== '' ? $path : null,
                'is_correct' => $index === $correct ? 1 : 0,
                'sort_order' => $order,
            ];
            if ($choiceId > 0) {
                $own = db()->fetch(
                    'SELECT id FROM training_quiz_choices WHERE id = ? AND question_id = ?',
                    [$choiceId, $questionId]
                );
                if (!$own) {
                    $choiceId = 0;
                }
            }
            if ($choiceId > 0) {
                db()->update('training_quiz_choices', $row, 'id = ? AND question_id = ?', [$choiceId, $questionId]);
                $kept[] = $choiceId;
            } else {
                $row['question_id'] = $questionId;
                $kept[] = (int) db()->insert('training_quiz_choices', $row);
            }
            $order++;
        }
        if ($kept) {
            $placeholders = implode(',', array_fill(0, count($kept), '?'));
            db()->query(
                "DELETE FROM training_quiz_choices WHERE question_id = ? AND id NOT IN ({$placeholders})",
                array_merge([$questionId], $kept)
            );
        } else {
            db()->query('DELETE FROM training_quiz_choices WHERE question_id = ?', [$questionId]);
        }
    }

    public function deleteQuestion(string $planId, string $id): void
    {
        $this->requirePerm('assessments', 'delete');
        $q = db()->fetch(
            "SELECT q.id FROM training_quiz_questions q
             INNER JOIN training_quizzes z ON z.id = q.quiz_id
             WHERE q.id = ? AND z.plan_id = ? AND z.quiz_type = 'pretest'",
            [(int) $id, (int) $planId]
        );
        if ($q) {
            db()->query('DELETE FROM training_quiz_questions WHERE id = ?', [(int) $id]);
            flash('success', 'ลบข้อสอบแล้ว');
        }
        $tab = (string) ($this->input('tab') ?: 'pretest');
        if (!in_array($tab, ['pretest', 'posttest'], true)) {
            $tab = 'pretest';
        }
        $this->redirect('/admin/training/' . $planId . '?tab=' . $tab);
    }

    public function saveQuizMeta(string $planId): void
    {
        $this->requirePerm('assessments', 'edit');
        $type = (string) $this->input('quiz_type', 'pretest');
        if (!in_array($type, ['pretest', 'posttest'], true)) {
            flash('error', 'แบบประเมินความพึงพอใจจัดการโดยผู้ดูแลระบบ');
            $this->redirect('/admin/training/' . $planId . '?tab=content');
        }
        $quizId = $this->lms->ensureQuiz((int) $planId, $type, ucfirst($type), (int) auth()->id());
        db()->update('training_quizzes', [
            'title' => trim((string) $this->input('title', ucfirst($type))),
            'description' => trim((string) $this->input('description', '')) ?: null,
            'pass_score' => (float) $this->input('pass_score', $type === 'posttest' ? 70 : 60),
            'max_attempts' => max(1, (int) $this->input('max_attempts', $type === 'posttest' ? 3 : 1)),
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
        if (!(new \App\Services\PlanOwnerService())->canManage((int) $plan['id'])) {
            flash('error', 'จัดการได้เฉพาะหลักสูตรที่คุณเป็นเจ้าของ');
            $this->redirect('/admin/training');
        }
        return $plan;
    }
}
