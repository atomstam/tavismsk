<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\ModuleLearningService;
use App\Services\QuizImageService;

/**
 * แบบประเมินความพึงพอใจชุดเดียว ผู้ดูแลระบบเป็นคนแก้ แล้วใช้กับทุกหลักสูตร
 */
class SatisfactionController extends Controller
{
    private ModuleLearningService $lms;

    public function __construct()
    {
        $this->lms = new ModuleLearningService();
    }

    public function index(): void
    {
        $this->requireRole(['admin']);
        $quizId = $this->lms->centralSatisfactionId((int) auth()->id());
        $quiz = db()->fetch('SELECT * FROM training_quizzes WHERE id = ?', [$quizId]) ?: [];
        $questions = $quizId > 0 ? $this->lms->questions($quizId, true) : [];
        foreach ($questions as &$q) {
            $q['choices'] = db()->fetchAll(
                'SELECT * FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id',
                [(int) $q['id']]
            );
        }
        unset($q);
        $plans = (int) (db()->fetch('SELECT COUNT(*) AS c FROM learning_plans WHERE status = 1')['c'] ?? 0);

        $this->view('admin/satisfaction/index', [
            'title' => 'แบบประเมินความพึงพอใจ',
            'breadcrumb' => 'หน้าหลัก / แบบประเมินความพึงพอใจ',
            'quiz' => $quiz,
            'questions' => $questions,
            'planCount' => $plans,
        ]);
    }

    public function saveMeta(): void
    {
        $this->requireRole(['admin']);
        $quizId = $this->lms->centralSatisfactionId((int) auth()->id());
        $title = trim((string) $this->input('title', 'แบบประเมินความพึงพอใจ'));
        if ($title === '') {
            $title = 'แบบประเมินความพึงพอใจ';
        }
        $fields = [
            'title' => $title,
            'description' => trim((string) $this->input('description', '')) ?: null,
            'pass_score' => 0,
            'max_attempts' => max(1, (int) $this->input('max_attempts', 1)),
            'time_limit_minutes' => ($v = (int) $this->input('time_limit_minutes', 0)) > 0 ? $v : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        db()->update('training_quizzes', $fields, 'id = ?', [$quizId]);
        $this->copyMetaToEveryPlan($title, $fields);
        flash('success', 'บันทึกแบบประเมินความพึงพอใจแล้ว ใช้กับทุกหลักสูตร');
        $this->redirect('/admin/satisfaction');
    }

    public function storeQuestion(): void
    {
        $this->requireRole(['admin']);
        $quizId = $this->lms->centralSatisfactionId((int) auth()->id());
        $qText = trim((string) $this->input('question_text', ''));
        if ($qText === '' || $quizId < 1) {
            flash('error', 'กรุณากรอกคำถาม');
            $this->redirect('/admin/satisfaction');
        }
        $images = new QuizImageService();
        $editId = (int) $this->input('question_id', 0);
        if ($editId > 0) {
            $existing = db()->fetch(
                'SELECT * FROM training_quiz_questions WHERE id = ? AND quiz_id = ?',
                [$editId, $quizId]
            );
            if (!$existing) {
                flash('error', 'ไม่พบข้อที่จะแก้ไข');
                $this->redirect('/admin/satisfaction');
            }
            $this->saveChoices($editId, $images, $existing);
            flash('success', 'แก้ไขข้อประเมินแล้ว');
            $this->redirect('/admin/satisfaction');
        }

        $qId = (int) db()->insert('training_quiz_questions', [
            'quiz_id' => $quizId,
            'question_text' => $qText,
            'image_path' => $images->store($_FILES['question_image'] ?? null),
            'question_type' => 'single',
            'points' => 1,
            'sort_order' => (int) $this->input('sort_order', 0),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->insertChoices($qId, $images);
        flash('success', 'เพิ่มข้อประเมินแล้ว');
        $this->redirect('/admin/satisfaction');
    }

    public function deleteQuestion(string $id): void
    {
        $this->requireRole(['admin']);
        $quizId = $this->lms->centralSatisfactionId((int) auth()->id());
        $q = db()->fetch(
            'SELECT id FROM training_quiz_questions WHERE id = ? AND quiz_id = ?',
            [(int) $id, $quizId]
        );
        if ($q) {
            db()->query('DELETE FROM training_quiz_questions WHERE id = ?', [(int) $id]);
            flash('success', 'ลบข้อประเมินแล้ว');
        }
        $this->redirect('/admin/satisfaction');
    }

    /** @param array<string, mixed> $fields */
    private function copyMetaToEveryPlan(string $title, array $fields): void
    {
        $plans = db()->fetchAll('SELECT id FROM learning_plans');
        foreach ($plans as $plan) {
            $this->lms->ensureQuiz((int) $plan['id'], 'satisfaction', $title, (int) auth()->id());
        }
        db()->query(
            'UPDATE training_quizzes
             SET title = ?, description = ?, pass_score = 0, max_attempts = ?, time_limit_minutes = ?, updated_at = ?
             WHERE quiz_type = ?',
            [
                $title,
                $fields['description'],
                $fields['max_attempts'],
                $fields['time_limit_minutes'],
                $fields['updated_at'],
                'satisfaction',
            ]
        );
    }

    private function insertChoices(int $questionId, QuizImageService $images): void
    {
        $choices = $this->input('choices');
        if (!is_array($choices)) {
            $choices = [];
        }
        $choiceFiles = $_FILES['choice_images'] ?? [];
        $i = 0;
        foreach ($choices as $index => $text) {
            $text = trim((string) $text);
            $image = $images->storeAt(is_array($choiceFiles) ? $choiceFiles : [], (int) $index);
            if ($text === '' && $image === null) {
                continue;
            }
            db()->insert('training_quiz_choices', [
                'question_id' => $questionId,
                'choice_text' => $text !== '' ? $text : 'ตัวเลือก',
                'image_path' => $image,
                'is_correct' => 1,
                'sort_order' => $i,
            ]);
            $i++;
        }
    }

    /** @param array<string, mixed> $existing */
    private function saveChoices(int $questionId, QuizImageService $images, array $existing): void
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
                'choice_text' => $text !== '' ? $text : 'ตัวเลือก',
                'image_path' => $path !== '' ? $path : null,
                'is_correct' => 1,
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
}
