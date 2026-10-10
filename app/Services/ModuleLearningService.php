<?php

declare(strict_types=1);

namespace App\Services;

/**
 * LMS ต่อโมดูล: Pretest → Content → Task → Posttest → Satisfaction และคำนวณความก้าวหน้า 0–100%
 */
class ModuleLearningService
{
    public const WEIGHT_PRETEST = 10.0;
    public const WEIGHT_CONTENT = 35.0;
    public const WEIGHT_TASK = 15.0;
    public const WEIGHT_POSTTEST = 25.0;
    public const WEIGHT_SATISFACTION = 15.0;

    public function settings(int $planId): array
    {
        $row = db()->fetch('SELECT * FROM training_plan_settings WHERE plan_id = ?', [$planId]);
        if (!$row) {
            db()->insert('training_plan_settings', [
                'plan_id' => $planId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $row = db()->fetch('SELECT * FROM training_plan_settings WHERE plan_id = ?', [$planId]);
        }
        return $row ?: [
            'plan_id' => $planId,
            'weight_pretest' => self::WEIGHT_PRETEST,
            'weight_content' => self::WEIGHT_CONTENT,
            'weight_task' => self::WEIGHT_TASK,
            'weight_posttest' => self::WEIGHT_POSTTEST,
            'weight_satisfaction' => self::WEIGHT_SATISFACTION,
            'require_pretest' => 1,
            'require_pass_posttest' => 1,
            'require_satisfaction' => 1,
            'gate_content_after_pretest' => 1,
            'gate_posttest_after_content' => 1,
            'gate_satisfaction_after_posttest' => 1,
        ];
    }

    public function activeEnrollment(int $userId): ?array
    {
        return db()->fetch(
            "SELECT * FROM course_enrollments
             WHERE user_id = ? AND status IN ('approved','in_progress','completed')
             ORDER BY FIELD(status,'in_progress','approved','completed'), id DESC
             LIMIT 1",
            [$userId]
        );
    }

    public function ensureEnrollment(int $userId): array
    {
        $en = $this->activeEnrollment($userId);
        if ($en) {
            return $en;
        }
        $training = new TrainingService();
        $id = $training->enroll(['user_id' => $userId]);
        $training->approve($id, $userId);
        $en = db()->fetch('SELECT * FROM course_enrollments WHERE id = ?', [$id]);
        return $en ?: [];
    }

    public function quiz(int $planId, string $type): ?array
    {
        return db()->fetch(
            'SELECT * FROM training_quizzes WHERE plan_id = ? AND quiz_type = ? AND status = 1',
            [$planId, $type]
        );
    }

    public function ensureQuiz(int $planId, string $type, string $title, int $userId): int
    {
        $existing = db()->fetch(
            'SELECT id FROM training_quizzes WHERE plan_id = ? AND quiz_type = ?',
            [$planId, $type]
        );
        if ($existing) {
            return (int) $existing['id'];
        }
        return (int) db()->insert('training_quizzes', [
            'plan_id' => $planId,
            'quiz_type' => $type,
            'title' => $title,
            'pass_score' => 60,
            'max_attempts' => $type === 'pretest' || $type === 'satisfaction' ? 1 : 3,
            'status' => 1,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return list<array> */
    public function questions(int $quizId, bool $withChoices = true): array
    {
        $qs = db()->fetchAll(
            'SELECT * FROM training_quiz_questions WHERE quiz_id = ? AND status = 1 ORDER BY sort_order, id',
            [$quizId]
        );
        if (!$withChoices) {
            return $qs;
        }
        foreach ($qs as &$q) {
            $q['choices'] = db()->fetchAll(
                'SELECT id, choice_text, sort_order FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id',
                [(int) $q['id']]
            );
        }
        unset($q);
        return $qs;
    }

    /** @return list<array> */
    public function contents(int $planId): array
    {
        return db()->fetchAll(
            'SELECT * FROM training_contents WHERE plan_id = ? AND status = 1 ORDER BY sort_order, id',
            [$planId]
        );
    }

    /** @return list<array> */
    public function tasks(int $planId): array
    {
        return db()->fetchAll(
            'SELECT * FROM training_tasks WHERE plan_id = ? AND status = 1 ORDER BY sort_order, id',
            [$planId]
        );
    }

    public function bestAttempt(int $quizId, int $enrollmentId): ?array
    {
        return db()->fetch(
            "SELECT * FROM training_quiz_attempts
             WHERE quiz_id = ? AND enrollment_id = ? AND status = 'submitted'
             ORDER BY is_passed DESC, percent DESC, id DESC LIMIT 1",
            [$quizId, $enrollmentId]
        );
    }

    public function attemptCount(int $quizId, int $enrollmentId): int
    {
        $row = db()->fetch(
            "SELECT COUNT(*) AS c FROM training_quiz_attempts
             WHERE quiz_id = ? AND enrollment_id = ? AND status = 'submitted'",
            [$quizId, $enrollmentId]
        );
        return (int) ($row['c'] ?? 0);
    }

    public function canAccessContent(int $planId, int $enrollmentId): bool
    {
        $s = $this->settings($planId);
        if (!(int) ($s['gate_content_after_pretest'] ?? 1)) {
            return true;
        }
        if (!(int) ($s['require_pretest'] ?? 1)) {
            return true;
        }
        $quiz = $this->quiz($planId, 'pretest');
        if (!$quiz) {
            return true;
        }
        $best = $this->bestAttempt((int) $quiz['id'], $enrollmentId);
        return $best !== null;
    }

    public function canAccessPosttest(int $planId, int $enrollmentId): bool
    {
        $s = $this->settings($planId);
        if (!(int) ($s['gate_posttest_after_content'] ?? 1)) {
            return true;
        }
        $p = $this->recalculate($planId, $enrollmentId, false);
        return (bool) ($p['content_done'] ?? false) && (bool) ($p['task_done'] ?? false);
    }

    public function canAccessSatisfaction(int $planId, int $enrollmentId): bool
    {
        $s = $this->settings($planId);
        if (!(int) ($s['gate_satisfaction_after_posttest'] ?? 1)) {
            return true;
        }
        $p = $this->recalculate($planId, $enrollmentId, false);
        return (bool) ($p['posttest_done'] ?? false);
    }

    /**
     * คำนวณและบันทึก progress ต่อโมดูล (enrollment × plan)
     * @return array{overall_percent:float,pretest_percent:float,content_percent:float,task_percent:float,posttest_percent:float,satisfaction_percent:float,stage:string,...}
     */
    public function recalculate(int $planId, int $enrollmentId, bool $persist = true): array
    {
        $enroll = db()->fetch('SELECT * FROM course_enrollments WHERE id = ?', [$enrollmentId]);
        if (!$enroll) {
            return ['overall_percent' => 0.0, 'stage' => 'pretest'];
        }
        $userId = (int) $enroll['user_id'];
        $s = $this->settings($planId);
        $wPre = (float) ($s['weight_pretest'] ?? self::WEIGHT_PRETEST);
        $wCon = (float) ($s['weight_content'] ?? self::WEIGHT_CONTENT);
        $wTask = (float) ($s['weight_task'] ?? self::WEIGHT_TASK);
        $wPost = (float) ($s['weight_posttest'] ?? self::WEIGHT_POSTTEST);
        $wSat = (float) ($s['weight_satisfaction'] ?? self::WEIGHT_SATISFACTION);
        $totalW = max(0.01, $wPre + $wCon + $wTask + $wPost + $wSat);

        $preQuiz = $this->quiz($planId, 'pretest');
        $postQuiz = $this->quiz($planId, 'posttest');
        $satQuiz = $this->quiz($planId, 'satisfaction');
        $contents = $this->contents($planId);
        $tasks = $this->tasks($planId);
        $hasMaterial = $preQuiz || $postQuiz || $satQuiz || $contents !== [] || $tasks !== [];

        // —— Pretest ——
        $pretestPct = 0.0;
        $pretestDone = 0;
        if (!$hasMaterial) {
            // โมดูลยังไม่เปิดบทเรียน LMS
            $payload = [
                'enrollment_id' => $enrollmentId,
                'plan_id' => $planId,
                'user_id' => $userId,
                'pretest_percent' => 0,
                'content_percent' => 0,
                'task_percent' => 0,
                'posttest_percent' => 0,
                'satisfaction_percent' => 0,
                'overall_percent' => 0,
                'pretest_done' => 0,
                'content_done' => 0,
                'task_done' => 0,
                'posttest_done' => 0,
                'satisfaction_done' => 0,
                'stage' => 'pretest',
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($persist) {
                $ex = db()->fetch(
                    'SELECT id FROM training_module_progress WHERE enrollment_id = ? AND plan_id = ?',
                    [$enrollmentId, $planId]
                );
                if ($ex) {
                    db()->update('training_module_progress', $payload, 'id = ?', [(int) $ex['id']]);
                } else {
                    $payload['created_at'] = date('Y-m-d H:i:s');
                    db()->insert('training_module_progress', $payload);
                }
            }
            return $payload;
        }

        if (!$preQuiz || !(int) ($s['require_pretest'] ?? 1)) {
            $pretestPct = 100.0;
            $pretestDone = 1;
        } else {
            $best = $this->bestAttempt((int) $preQuiz['id'], $enrollmentId);
            if ($best) {
                $pretestPct = 100.0;
                $pretestDone = 1;
            }
        }

        // —— Content ——
        $requiredContents = array_values(array_filter($contents, static fn ($c) => (int) ($c['is_required'] ?? 1) === 1));
        $contentPct = 100.0;
        $contentDone = 1;
        if ($requiredContents !== []) {
            $done = 0;
            foreach ($requiredContents as $c) {
                $v = db()->fetch(
                    'SELECT is_completed FROM training_content_views WHERE content_id = ? AND enrollment_id = ?',
                    [(int) $c['id'], $enrollmentId]
                );
                if ($v && (int) $v['is_completed'] === 1) {
                    $done++;
                }
            }
            $contentPct = round(($done / count($requiredContents)) * 100, 2);
            $contentDone = $done >= count($requiredContents) ? 1 : 0;
        }

        // —— Tasks ——
        $requiredTasks = array_values(array_filter($tasks, static fn ($t) => (int) ($t['is_required'] ?? 1) === 1));
        $taskPct = 100.0;
        $taskDone = 1;
        if ($requiredTasks !== []) {
            $scoreSum = 0.0;
            $doneCount = 0;
            foreach ($requiredTasks as $t) {
                $sub = db()->fetch(
                    'SELECT * FROM training_task_submissions WHERE task_id = ? AND enrollment_id = ?',
                    [(int) $t['id'], $enrollmentId]
                );
                $credit = $this->taskCredit($t, $sub);
                $scoreSum += $credit;
                if ($credit >= 1.0) {
                    $doneCount++;
                }
            }
            $taskPct = round(($scoreSum / count($requiredTasks)) * 100, 2);
            $taskDone = $doneCount >= count($requiredTasks) ? 1 : 0;
        }

        // —— Posttest ——
        $posttestPct = 0.0;
        $posttestDone = 0;
        if (!$postQuiz) {
            $posttestPct = 100.0;
            $posttestDone = 1;
        } else {
            $best = $this->bestAttempt((int) $postQuiz['id'], $enrollmentId);
            if ($best) {
                if ((int) ($s['require_pass_posttest'] ?? 1) === 1) {
                    if ((int) $best['is_passed'] === 1) {
                        $posttestPct = 100.0;
                        $posttestDone = 1;
                    } else {
                        $posttestPct = min(99.0, (float) $best['percent']);
                        $posttestDone = 0;
                    }
                } else {
                    $posttestPct = 100.0;
                    $posttestDone = 1;
                }
            }
        }

        // —— Satisfaction ——
        $satisfactionPct = 0.0;
        $satisfactionDone = 0;
        if (!$satQuiz || !(int) ($s['require_satisfaction'] ?? 1)) {
            $satisfactionPct = 100.0;
            $satisfactionDone = 1;
        } else {
            $best = $this->bestAttempt((int) $satQuiz['id'], $enrollmentId);
            if ($best) {
                $satisfactionPct = 100.0;
                $satisfactionDone = 1;
            }
        }

        $overall = round(
            (($pretestPct / 100) * $wPre
                + ($contentPct / 100) * $wCon
                + ($taskPct / 100) * $wTask
                + ($posttestPct / 100) * $wPost
                + ($satisfactionPct / 100) * $wSat)
            / $totalW * 100,
            2
        );

        $stage = 'pretest';
        if (!$pretestDone) {
            $stage = 'pretest';
        } elseif (!$contentDone) {
            $stage = 'content';
        } elseif (!$taskDone) {
            $stage = 'task';
        } elseif (!$posttestDone) {
            $stage = 'posttest';
        } elseif (!$satisfactionDone) {
            $stage = 'satisfaction';
        } else {
            $stage = 'completed';
            $overall = 100.0;
        }

        $payload = [
            'enrollment_id' => $enrollmentId,
            'plan_id' => $planId,
            'user_id' => $userId,
            'pretest_percent' => $pretestPct,
            'content_percent' => $contentPct,
            'task_percent' => $taskPct,
            'posttest_percent' => $posttestPct,
            'satisfaction_percent' => $satisfactionPct,
            'overall_percent' => $overall,
            'pretest_done' => $pretestDone,
            'content_done' => $contentDone,
            'task_done' => $taskDone,
            'posttest_done' => $posttestDone,
            'satisfaction_done' => $satisfactionDone,
            'stage' => $stage,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($persist) {
            $ex = db()->fetch(
                'SELECT id FROM training_module_progress WHERE enrollment_id = ? AND plan_id = ?',
                [$enrollmentId, $planId]
            );
            if ($ex) {
                db()->update('training_module_progress', $payload, 'id = ?', [(int) $ex['id']]);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                db()->insert('training_module_progress', $payload);
            }

            if ($stage === 'completed') {
                $this->syncModuleAssessment($enrollmentId, $planId, $overall, $userId);
            }
        }

        return $payload;
    }

    /** @param array|null $sub */
    private function taskCredit(array $task, ?array $sub): float
    {
        if (!$sub) {
            return 0.0;
        }
        $st = (string) ($sub['status'] ?? '');
        if ($st === 'rejected' || $st === 'draft') {
            return 0.0;
        }
        if ((int) ($task['require_review'] ?? 1) === 1) {
            if ($st === 'approved') {
                return 1.0;
            }
            if ($st === 'submitted' || $st === 'revision') {
                return 0.5; // ส่งแล้วรอตรวจ = ครึ่งหนึ่งของน้ำหนักภาระงาน
            }
            return 0.0;
        }
        return in_array($st, ['submitted', 'approved'], true) ? 1.0 : 0.0;
    }

    private function syncModuleAssessment(int $enrollmentId, int $planId, float $overall, int $userId): void
    {
        try {
            (new TrainingService())->saveAssessment($enrollmentId, $planId, [
                'score' => $overall,
                'max_score' => 100,
                'hours_earned' => null,
                'note' => 'auto from LMS progress',
            ], $userId);
        } catch (\Throwable $e) {
            // ignore if assessor sync fails
        }
    }

    /**
     * @param array<int,int> $answers question_id => choice_id
     */
    public function submitQuiz(int $quizId, int $enrollmentId, int $userId, array $answers): array
    {
        $quiz = db()->fetch('SELECT * FROM training_quizzes WHERE id = ? AND status = 1', [$quizId]);
        if (!$quiz) {
            throw new \RuntimeException('ไม่พบแบบทดสอบ');
        }
        $maxAttempts = (int) ($quiz['max_attempts'] ?? 3);
        $used = $this->attemptCount($quizId, $enrollmentId);
        if ($used >= $maxAttempts) {
            throw new \RuntimeException('ครบจำนวนครั้งที่อนุญาตแล้ว');
        }

        $questions = db()->fetchAll(
            'SELECT * FROM training_quiz_questions WHERE quiz_id = ? AND status = 1',
            [$quizId]
        );
        $score = 0.0;
        $maxScore = 0.0;
        $graded = [];

        foreach ($questions as $q) {
            $qid = (int) $q['id'];
            $pts = (float) $q['points'];
            $maxScore += $pts;
            $choiceId = isset($answers[$qid]) ? (int) $answers[$qid] : 0;
            $correct = false;
            if ($choiceId > 0) {
                $ch = db()->fetch(
                    'SELECT * FROM training_quiz_choices WHERE id = ? AND question_id = ?',
                    [$choiceId, $qid]
                );
                $correct = $ch && (int) $ch['is_correct'] === 1;
            }
            $earn = $correct ? $pts : 0.0;
            $score += $earn;
            $graded[] = [
                'question_id' => $qid,
                'choice_id' => $choiceId ?: null,
                'is_correct' => $correct ? 1 : 0,
                'points_earned' => $earn,
            ];
        }

        $percent = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0.0;
        $passScore = (float) $quiz['pass_score'];
        $passed = $percent >= $passScore ? 1 : 0;

        $attemptId = (int) db()->insert('training_quiz_attempts', [
            'quiz_id' => $quizId,
            'enrollment_id' => $enrollmentId,
            'user_id' => $userId,
            'attempt_no' => $used + 1,
            'score' => $score,
            'max_score' => $maxScore,
            'percent' => $percent,
            'is_passed' => $passed,
            'status' => 'submitted',
            'started_at' => date('Y-m-d H:i:s'),
            'submitted_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($graded as $g) {
            db()->insert('training_quiz_answers', [
                'attempt_id' => $attemptId,
                'question_id' => $g['question_id'],
                'choice_id' => $g['choice_id'],
                'is_correct' => $g['is_correct'],
                'points_earned' => $g['points_earned'],
            ]);
        }

        $progress = $this->recalculate((int) $quiz['plan_id'], $enrollmentId, true);

        return [
            'attempt_id' => $attemptId,
            'score' => $score,
            'max_score' => $maxScore,
            'percent' => $percent,
            'is_passed' => $passed,
            'progress' => $progress,
        ];
    }

    public function markContentComplete(int $contentId, int $enrollmentId, int $userId): array
    {
        $content = db()->fetch('SELECT * FROM training_contents WHERE id = ? AND status = 1', [$contentId]);
        if (!$content) {
            throw new \RuntimeException('ไม่พบเนื้อหา');
        }
        $planId = (int) $content['plan_id'];
        if (!$this->canAccessContent($planId, $enrollmentId)) {
            throw new \RuntimeException('กรุณาทำ Pretest ให้เสร็จก่อน');
        }

        $ex = db()->fetch(
            'SELECT id FROM training_content_views WHERE content_id = ? AND enrollment_id = ?',
            [$contentId, $enrollmentId]
        );
        $now = date('Y-m-d H:i:s');
        if ($ex) {
            db()->update('training_content_views', [
                'is_completed' => 1,
                'completed_at' => $now,
                'viewed_at' => $now,
            ], 'id = ?', [(int) $ex['id']]);
        } else {
            db()->insert('training_content_views', [
                'content_id' => $contentId,
                'enrollment_id' => $enrollmentId,
                'user_id' => $userId,
                'viewed_at' => $now,
                'completed_at' => $now,
                'is_completed' => 1,
            ]);
        }

        return $this->recalculate($planId, $enrollmentId, true);
    }

    public function submitTask(int $taskId, int $enrollmentId, int $userId, array $data): array
    {
        $task = db()->fetch('SELECT * FROM training_tasks WHERE id = ? AND status = 1', [$taskId]);
        if (!$task) {
            throw new \RuntimeException('ไม่พบภาระงาน');
        }
        $planId = (int) $task['plan_id'];
        if (!$this->canAccessContent($planId, $enrollmentId)) {
            throw new \RuntimeException('กรุณาทำ Pretest ให้เสร็จก่อน');
        }

        $filePath = $data['file_path'] ?? null;
        $link = trim((string) ($data['link_url'] ?? ''));
        $text = trim((string) ($data['answer_text'] ?? ''));

        if ((int) ($task['require_file'] ?? 0) === 1 && !$filePath && $link === '') {
            throw new \RuntimeException('กรุณาแนบไฟล์หรือลิงก์');
        }

        $status = (int) ($task['require_review'] ?? 1) === 1 ? 'submitted' : 'approved';
        $now = date('Y-m-d H:i:s');
        $payload = [
            'answer_text' => $text !== '' ? $text : null,
            'file_path' => $filePath,
            'link_url' => $link !== '' ? $link : null,
            'status' => $status,
            'submitted_at' => $now,
            'updated_at' => $now,
            'score' => $status === 'approved' ? (float) $task['max_score'] : null,
            'reviewed_at' => $status === 'approved' ? $now : null,
            'reviewed_by' => $status === 'approved' ? $userId : null,
        ];

        $ex = db()->fetch(
            'SELECT id, status FROM training_task_submissions WHERE task_id = ? AND enrollment_id = ?',
            [$taskId, $enrollmentId]
        );
        if ($ex) {
            if (($ex['status'] ?? '') === 'approved') {
                throw new \RuntimeException('ภาระงานนี้ตรวจผ่านแล้ว ไม่สามารถส่งใหม่ได้');
            }
            db()->update('training_task_submissions', $payload, 'id = ?', [(int) $ex['id']]);
        } else {
            $payload['task_id'] = $taskId;
            $payload['enrollment_id'] = $enrollmentId;
            $payload['user_id'] = $userId;
            $payload['created_at'] = $now;
            db()->insert('training_task_submissions', $payload);
        }

        return $this->recalculate($planId, $enrollmentId, true);
    }

    public function reviewTask(int $submissionId, string $status, ?float $score, ?string $feedback, int $reviewerId): array
    {
        $sub = db()->fetch('SELECT * FROM training_task_submissions WHERE id = ?', [$submissionId]);
        if (!$sub) {
            throw new \RuntimeException('ไม่พบการส่งงาน');
        }
        if (!in_array($status, ['approved', 'rejected', 'revision'], true)) {
            throw new \RuntimeException('สถานะไม่ถูกต้อง');
        }
        $task = db()->fetch('SELECT * FROM training_tasks WHERE id = ?', [(int) $sub['task_id']]);
        db()->update('training_task_submissions', [
            'status' => $status,
            'score' => $score,
            'feedback' => $feedback,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$submissionId]);

        return $this->recalculate((int) $task['plan_id'], (int) $sub['enrollment_id'], true);
    }

    /** @return list<array> */
    public function moduleOverviewForUser(int $userId): array
    {
        $en = $this->ensureEnrollment($userId);
        if (!$en) {
            return [];
        }
        $plans = db()->fetchAll('SELECT * FROM learning_plans WHERE status = 1 ORDER BY plan_no, id');
        $out = [];
        foreach ($plans as $p) {
            $prog = $this->recalculate((int) $p['id'], (int) $en['id'], true);
            $out[] = [
                'plan' => $p,
                'progress' => $prog,
                'enrollment' => $en,
            ];
        }
        return $out;
    }

    public function youtubeEmbedUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        return null;
    }
}
