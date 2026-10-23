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
            'quiz_draw_count' => 10,
            'quiz_shuffle' => 1,
            'pass_percent' => 80,
        ];
    }

    /** แบบประเมินความพึงพอใจชุดเดียว ใช้ร่วมทุกแผน */
    public function centralSatisfactionId(int $userId = 0): int
    {
        $row = db()->fetch(
            "SELECT id FROM training_quizzes WHERE quiz_type = 'satisfaction' AND is_central = 1 ORDER BY id LIMIT 1"
        );
        if ($row) {
            return (int) $row['id'];
        }
        $plan = db()->fetch('SELECT id FROM learning_plans ORDER BY plan_no, id LIMIT 1');
        $planId = (int) ($plan['id'] ?? 0);
        if ($planId < 1) {
            return 0;
        }
        $id = $this->ensureQuiz($planId, 'satisfaction', 'แบบประเมินความพึงพอใจ (กลาง)', $userId);
        db()->update('training_quizzes', [
            'is_central' => 1,
            'title' => 'แบบประเมินความพึงพอใจ (กลาง)',
            'pass_score' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        return $id;
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

    /** ลงทะเบียนแผนบังคับ 1–12 ให้ผู้เรียนใหม่ */
    public function enrollRequiredPlans(int $userId): void
    {
        $en = $this->ensureEnrollment($userId);
        $enrollmentId = (int) ($en['id'] ?? 0);
        if ($enrollmentId <= 0) {
            return;
        }
        $plans = db()->fetchAll(
            'SELECT id FROM learning_plans WHERE status = 1 AND plan_no BETWEEN 1 AND 12 ORDER BY plan_no'
        );
        foreach ($plans as $plan) {
            $this->recalculate($enrollmentId, (int) $plan['id'], $userId);
        }
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
            'pass_score' => $type === 'posttest' ? 70 : 60,
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
                'SELECT id, choice_text, image_path, sort_order FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id',
                [(int) $q['id']]
            );
        }
        unset($q);
        return $qs;
    }

    /**
     * ข้อที่ผู้เรียนคนนี้ต้องทำ
     * ทดสอบก่อน/หลังใช้คลังเดียวกัน คนละชุด และสอบหลังสลับลำดับข้อกับตัวเลือก
     *
     * @return list<array>
     */
    public function learnerQuestions(int $planId, string $type, int $enrollmentId, int $userId): array
    {
        if ($type === 'satisfaction') {
            $centralId = $this->centralSatisfactionId($userId);
            return $centralId > 0 ? $this->questions($centralId) : [];
        }
        $paper = $this->ensurePaper($planId, $enrollmentId, $userId);
        $ids = $type === 'posttest' ? $paper['post_ids'] : $paper['pre_ids'];
        $choiceMap = $paper['choices'][$type === 'posttest' ? 'post' : 'pre'] ?? [];
        return $this->hydratePaper($ids, $choiceMap);
    }

    /** @return list<int> */
    public function learnerQuestionIds(int $planId, string $type, int $enrollmentId, int $userId): array
    {
        $rows = $this->learnerQuestions($planId, $type, $enrollmentId, $userId);
        return array_map(static fn (array $q): int => (int) $q['id'], $rows);
    }

    /**
     * @return array{pre_ids:list<int>,post_ids:list<int>,choices:array{pre:array<string,list<int>>,post:array<string,list<int>>}}
     */
    private function ensurePaper(int $planId, int $enrollmentId, int $userId): array
    {
        $row = db()->fetch(
            'SELECT * FROM training_quiz_papers WHERE plan_id = ? AND enrollment_id = ?',
            [$planId, $enrollmentId]
        );
        if ($row) {
            $paper = $this->decodePaper($row);
            if ($paper['pre_ids'] !== []) {
                return $paper;
            }
            db()->query('DELETE FROM training_quiz_papers WHERE id = ?', [(int) $row['id']]);
        }

        $settings = $this->settings($planId);
        $draw = (int) ($settings['quiz_draw_count'] ?? 0);
        if ($draw <= 0) {
            $lms = config('app', 'lms');
            $draw = (int) (is_array($lms) ? ($lms['questions_per_learner'] ?? 10) : 10);
        }
        if ($draw < 1) {
            $draw = 10;
        }
        $shuffle = (int) ($settings['quiz_shuffle'] ?? 1) === 1;
        // สุ่มจากคลังแผนก่อน ถ้าไม่ครบจำนวนที่ตั้งไว้ ให้เติมจากคลังรวม (ไม่ซ้ำข้อความ)
        $pre = $this->drawUniqueQuestionIds($planId, $draw, $shuffle);
        $post = $shuffle ? $this->differentOrder($pre) : $pre;
        $post = array_values(array_unique(array_map('intval', $post)));
        $choices = ['pre' => [], 'post' => []];
        foreach ($pre as $qid) {
            $cids = array_map(
                static fn (array $c): int => (int) $c['id'],
                db()->fetchAll(
                    'SELECT id FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id',
                    [$qid]
                )
            );
            $preChoices = $cids;
            if ($shuffle) {
                shuffle($preChoices);
            }
            $postChoices = $shuffle ? $this->differentOrder($preChoices) : $preChoices;
            $choices['pre'][(string) $qid] = array_values($preChoices);
            $choices['post'][(string) $qid] = array_values($postChoices);
        }

        try {
            db()->insert('training_quiz_papers', [
                'plan_id' => $planId,
                'enrollment_id' => $enrollmentId,
                'user_id' => $userId,
                'question_ids' => json_encode($pre, JSON_UNESCAPED_UNICODE),
                'post_question_ids' => json_encode($post, JSON_UNESCAPED_UNICODE),
                'choice_orders' => json_encode($choices, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $row = db()->fetch(
                'SELECT * FROM training_quiz_papers WHERE plan_id = ? AND enrollment_id = ?',
                [$planId, $enrollmentId]
            );
            if ($row) {
                return $this->decodePaper($row);
            }
            throw $e;
        }

        return ['pre_ids' => $pre, 'post_ids' => $post, 'choices' => $choices];
    }

    /** @param array<string, mixed> $row */
    private function decodePaper(array $row): array
    {
        $choices = json_decode((string) ($row['choice_orders'] ?? ''), true);
        if (!is_array($choices)) {
            $choices = ['pre' => [], 'post' => []];
        }
        return [
            'pre_ids' => $this->idList($row['question_ids'] ?? '[]'),
            'post_ids' => $this->idList($row['post_question_ids'] ?? '[]'),
            'choices' => [
                'pre' => is_array($choices['pre'] ?? null) ? $choices['pre'] : [],
                'post' => is_array($choices['post'] ?? null) ? $choices['post'] : [],
            ],
        ];
    }

    /** @return list<int> */
    private function idList(mixed $json): array
    {
        $data = is_string($json) ? json_decode($json, true) : $json;
        if (!is_array($data)) {
            return [];
        }
        // กัน id ซ้ำในชุดข้อสอบ
        $ids = [];
        $seen = [];
        foreach ($data as $id) {
            $id = (int) $id;
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $ids[] = $id;
        }
        return $ids;
    }

    /**
     * ดึง id จากคลังแบบไม่ซ้ำข้อความคำถาม (เก็บ id แรกของแต่ละข้อความ)
     * @return list<int>
     */
    private function uniqueBankQuestionIds(int $quizId): array
    {
        $rows = db()->fetchAll(
            'SELECT id, question_text FROM training_quiz_questions WHERE quiz_id = ? AND status = 1 ORDER BY sort_order, id',
            [$quizId]
        );
        $ids = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = $this->questionDedupeKey((string) ($row['question_text'] ?? ''));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $ids[] = (int) $row['id'];
        }
        return $ids;
    }

    /**
     * สุ่มข้อไม่ซ้ำข้อความ ให้ได้จำนวนตามที่ตั้ง (เติมจากคลังรวมถ้าคลังแผนไม่พอ)
     * @return list<int>
     */
    private function drawUniqueQuestionIds(int $planId, int $draw, bool $shuffle): array
    {
        $quiz = $this->quiz($planId, 'pretest');
        $local = $quiz ? $this->uniqueBankQuestionIds((int) $quiz['id']) : [];
        if ($shuffle) {
            shuffle($local);
        }

        $picked = [];
        $seenKeys = [];
        foreach ($local as $id) {
            if (count($picked) >= $draw) {
                break;
            }
            $row = db()->fetch('SELECT question_text FROM training_quiz_questions WHERE id = ?', [$id]);
            $key = $this->questionDedupeKey((string) ($row['question_text'] ?? ''));
            if ($key === '' || isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;
            $picked[] = (int) $id;
        }

        if (count($picked) < $draw) {
            $pool = db()->fetchAll(
                "SELECT q.id, q.question_text
                 FROM training_quiz_questions q
                 INNER JOIN training_quizzes z ON z.id = q.quiz_id
                 WHERE q.status = 1 AND z.status = 1 AND z.quiz_type = 'pretest'
                 ORDER BY q.sort_order, q.id"
            );
            $extras = [];
            foreach ($pool as $row) {
                $key = $this->questionDedupeKey((string) ($row['question_text'] ?? ''));
                if ($key === '' || isset($seenKeys[$key])) {
                    continue;
                }
                $seenKeys[$key] = true;
                $extras[] = (int) $row['id'];
            }
            if ($shuffle) {
                shuffle($extras);
            }
            foreach ($extras as $id) {
                if (count($picked) >= $draw) {
                    break;
                }
                $picked[] = $id;
            }
        }

        if ($shuffle) {
            shuffle($picked);
        }
        return array_values(array_unique(array_map('intval', $picked)));
    }

    private function questionDedupeKey(string $text): string
    {
        $normalized = $this->learnerFacingQuestionText($text);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        return mb_strtolower(trim($normalized), 'UTF-8');
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function differentOrder(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $n = count($ids);
        if ($n < 2) {
            return $ids;
        }
        $next = $ids;
        for ($i = 0; $i < 8; $i++) {
            shuffle($next);
            if ($next !== $ids) {
                return array_values($next);
            }
        }
        $first = array_shift($next);
        $next[] = $first;
        return array_values($next);
    }

    /**
     * @param list<int> $ids
     * @param array<string, list<int>> $choiceMap
     * @return list<array>
     */
    private function hydratePaper(array $ids, array $choiceMap): array
    {
        if ($ids === []) {
            return [];
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = db()->fetchAll(
            "SELECT * FROM training_quiz_questions WHERE id IN ({$placeholders}) AND status = 1",
            $ids
        );
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }
        $out = [];
        $seenText = [];
        foreach ($ids as $qid) {
            if (!isset($byId[$qid])) {
                continue;
            }
            $q = $byId[$qid];
            unset($q['explanation']);
            // ผู้เรียนเห็นเฉพาะคำถาม ไม่โชว์ที่มาจากคลัง (เช่น "แผนที่ 1 ข้อ 8: …")
            $q['question_text'] = $this->learnerFacingQuestionText((string) ($q['question_text'] ?? ''));
            $textKey = $this->questionDedupeKey((string) $q['question_text']);
            if ($textKey !== '' && isset($seenText[$textKey])) {
                continue; // ข้ามข้อข้อความซ้ำในชุดเดิม
            }
            if ($textKey !== '') {
                $seenText[$textKey] = true;
            }
            $choiceRows = db()->fetchAll(
                'SELECT id, choice_text, image_path, sort_order FROM training_quiz_choices WHERE question_id = ?',
                [$qid]
            );
            $byChoice = [];
            foreach ($choiceRows as $ch) {
                $byChoice[(int) $ch['id']] = $ch;
            }
            $order = $choiceMap[(string) $qid] ?? array_keys($byChoice);
            $choices = [];
            foreach ($order as $cid) {
                $cid = (int) $cid;
                if (isset($byChoice[$cid])) {
                    $choices[] = $byChoice[$cid];
                    unset($byChoice[$cid]);
                }
            }
            foreach ($byChoice as $ch) {
                $choices[] = $ch;
            }
            $q['choices'] = $choices;
            $out[] = $q;
        }
        return $out;
    }

    /** ตัดคำนำที่บอกแหล่งสุ่มจากคลัง ออกจากข้อความข้อสอบฝั่งผู้เรียน */
    private function learnerFacingQuestionText(string $text): string
    {
        $clean = preg_replace('/^แผนที่\s*\d+\s*ข้อ\s*\d+\s*:\s*/u', '', trim($text));
        $clean = is_string($clean) ? trim($clean) : trim($text);
        return $clean !== '' ? $clean : trim($text);
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

    /** ภาระงาน: ต้องเรียนเนื้อหาที่บังคับครบก่อน (รอบเรียนซ้ำยังเห็นงานที่ส่งแล้วได้) */
    public function canAccessTask(int $planId, int $enrollmentId): bool
    {
        if (!$this->canAccessContent($planId, $enrollmentId)) {
            return false;
        }
        $p = $this->recalculate($planId, $enrollmentId, false);
        if ((bool) ($p['content_done'] ?? false)) {
            return true;
        }
        return $this->hasTaskSubmissions($planId, $enrollmentId);
    }

    public function canAccessPosttest(int $planId, int $enrollmentId): bool
    {
        $s = $this->settings($planId);
        if (!(int) ($s['gate_posttest_after_content'] ?? 1)) {
            return true;
        }
        $p = $this->recalculate($planId, $enrollmentId, false);
        // ปลดล็อกสอบหลังเมื่อเปิดเนื้อหาครบ + ส่งภาระงานครบ (คะแนนรอวิทยากรตรวจแยกต่างหาก)
        return (bool) ($p['content_done'] ?? false) && $this->allRequiredTasksSubmitted($planId, $enrollmentId);
    }

    public function canAccessSatisfaction(int $planId, int $enrollmentId): bool
    {
        $s = $this->settings($planId);
        if (!(int) ($s['gate_satisfaction_after_posttest'] ?? 1)) {
            return true;
        }
        // ส่งสอบหลังแล้วปลดล็อกได้ (ไม่ต้องรอผ่านเกณฑ์)
        $quiz = $this->quiz($planId, 'posttest');
        if (!$quiz) {
            return true;
        }
        return $this->bestAttempt((int) $quiz['id'], $enrollmentId) !== null;
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
                'is_passed' => 0,
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
                $pretestPct = round((float) $best['percent'], 2);
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

        // —— Tasks —— คะแนนนับหลังวิทยากรตรวจ · ความก้าวหน้านับเมื่อส่งงานแล้ว
        $requiredTasks = array_values(array_filter($tasks, static fn ($t) => (int) ($t['is_required'] ?? 1) === 1));
        $taskPct = 100.0;
        $taskDone = 1;
        $taskProgressPct = 100.0;
        $taskScoreEarned = 0.0;
        $taskScoreMax = 0.0;
        if ($requiredTasks !== []) {
            $approvedCount = 0;
            $submittedCount = 0;
            foreach ($requiredTasks as $t) {
                $max = max(0.01, (float) ($t['max_score'] ?? 100));
                $taskScoreMax += $max;
                $sub = db()->fetch(
                    'SELECT * FROM training_task_submissions WHERE task_id = ? AND enrollment_id = ?',
                    [(int) $t['id'], $enrollmentId]
                );
                $st = (string) ($sub['status'] ?? '');
                if (in_array($st, ['submitted', 'approved', 'revision'], true)) {
                    $submittedCount++;
                }
                if ($sub && $st === 'approved') {
                    $earned = $sub['score'] !== null && $sub['score'] !== ''
                        ? (float) $sub['score']
                        : $max;
                    $earned = max(0.0, min($max, $earned));
                    $taskScoreEarned += $earned;
                    $approvedCount++;
                }
            }
            $taskPct = round(($taskScoreEarned / $taskScoreMax) * 100, 2);
            $taskDone = $approvedCount >= count($requiredTasks) ? 1 : 0;
            $taskProgressPct = round(($submittedCount / count($requiredTasks)) * 100, 2);
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
                $posttestPct = round((float) $best['percent'], 2);
                if ((int) ($s['require_pass_posttest'] ?? 1) === 1) {
                    $posttestDone = (int) $best['is_passed'] === 1 ? 1 : 0;
                } else {
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

        $scorePercents = [
            'pretest' => $pretestPct,
            'content' => $contentPct,
            'task' => $taskPct,
            'posttest' => $posttestPct,
            'satisfaction' => $satisfactionPct,
        ];
        $doneFlags = [
            'pretest_done' => $pretestDone,
            'content_done' => $contentDone,
            'task_done' => $taskDone,
            'posttest_done' => $posttestDone,
            'satisfaction_done' => $satisfactionDone,
        ];
        // ความก้าวหน้า = การดำเนินการครบองค์ประกอบ (ภาระงานนับเมื่อส่งแล้ว)
        $completionPercents = [
            'pretest' => $pretestDone ? 100.0 : 0.0,
            'content' => $contentPct,
            'task' => $taskProgressPct,
            'posttest' => $posttestDone ? 100.0 : 0.0,
            'satisfaction' => $satisfactionDone ? 100.0 : 0.0,
        ];
        $decision = PlanProgress::decide($s, $scorePercents, $doneFlags, $completionPercents);
        $overall = (float) $decision['overall_percent'];
        $scoreOverall = (float) ($decision['score_percent'] ?? 0);

        $tasksSubmitted = $this->allRequiredTasksSubmitted($planId, $enrollmentId);
        $stage = 'pretest';
        if (!$pretestDone) {
            $stage = 'pretest';
        } elseif (!$contentDone) {
            $stage = 'content';
        } elseif (!$tasksSubmitted) {
            $stage = 'task';
        } elseif (!$posttestDone) {
            $stage = 'posttest';
        } elseif (!$satisfactionDone) {
            $stage = 'satisfaction';
        } else {
            // ขั้นเรียนครบ — คะแนนภาระงานอาจยังรอวิทยากรตรวจ (ใช้ is_passed / certificateReady แยก)
            $stage = 'completed';
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
            'score_percent' => $scoreOverall,
            'pretest_done' => $pretestDone,
            'content_done' => $contentDone,
            'task_done' => $taskDone,
            'posttest_done' => $posttestDone,
            'satisfaction_done' => $satisfactionDone,
            'is_passed' => (int) $decision['is_passed'],
            'stage' => $stage,
            'task_score_earned' => round($taskScoreEarned, 2),
            'task_score_max' => round($taskScoreMax, 2),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        // คอลัมน์พิเศษไม่บันทึกลง DB
        $persistPayload = $payload;
        unset($persistPayload['task_score_earned'], $persistPayload['task_score_max'], $persistPayload['score_percent']);

        if ($persist) {
            $ex = db()->fetch(
                'SELECT id FROM training_module_progress WHERE enrollment_id = ? AND plan_id = ?',
                [$enrollmentId, $planId]
            );
            if ($ex) {
                db()->update('training_module_progress', $persistPayload, 'id = ?', [(int) $ex['id']]);
            } else {
                $persistPayload['created_at'] = date('Y-m-d H:i:s');
                db()->insert('training_module_progress', $persistPayload);
            }

            if ((int) $decision['is_passed'] === 1) {
                // บันทึกคะแนนรวม (ไม่ใช่ความก้าวหน้า) สำหรับประเมิน/เกียรติบัตร
                $this->syncModuleAssessment($enrollmentId, $planId, $scoreOverall, $userId);
            }
        }

        return $payload;
    }

    /** ส่งภาระงานบังคับครบหรือยัง (ยังไม่ต้องรอตรวจ — ใช้ปลดล็อกสอบหลัง) */
    public function allRequiredTasksSubmitted(int $planId, int $enrollmentId): bool
    {
        $tasks = array_values(array_filter(
            $this->tasks($planId),
            static fn (array $t): bool => (int) ($t['is_required'] ?? 1) === 1
        ));
        if ($tasks === []) {
            return true;
        }
        foreach ($tasks as $t) {
            $sub = db()->fetch(
                'SELECT status FROM training_task_submissions WHERE task_id = ? AND enrollment_id = ?',
                [(int) $t['id'], $enrollmentId]
            );
            $st = (string) ($sub['status'] ?? '');
            if (!in_array($st, ['submitted', 'approved'], true)) {
                return false;
            }
        }
        return true;
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
     * @param list<int>|null $limitIds ข้อที่อยู่ในชุดของผู้เรียนคนนี้ ถ้าว่างจะตรวจทั้งคลัง
     */
    public function submitQuiz(int $quizId, int $enrollmentId, int $userId, array $answers, ?array $limitIds = null): array
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

        if ($limitIds !== null) {
            $limitIds = array_values(array_filter(array_map('intval', $limitIds)));
            if ($limitIds === []) {
                throw new \RuntimeException('ยังไม่มีข้อสอบในชุดนี้');
            }
            $placeholders = implode(',', array_fill(0, count($limitIds), '?'));
            $found = db()->fetchAll(
                "SELECT * FROM training_quiz_questions WHERE id IN ({$placeholders}) AND status = 1",
                $limitIds
            );
            $byId = [];
            foreach ($found as $row) {
                $byId[(int) $row['id']] = $row;
            }
            $questions = [];
            foreach ($limitIds as $qid) {
                if (isset($byId[$qid])) {
                    $questions[] = $byId[$qid];
                }
            }
        } else {
            $questions = db()->fetchAll(
                'SELECT * FROM training_quiz_questions WHERE quiz_id = ? AND status = 1',
                [$quizId]
            );
        }
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

        $planId = (int) $quiz['plan_id'];
        $contentReset = false;
        $attemptsUsed = $used + 1;

        // สอบหลัง: ครบจำนวนครั้งแล้วยังไม่ผ่าน → ล้างสถานะเปิดดูเนื้อหา + รีเซ็ตรอบสอบ เพื่อเรียนเนื้อหาใหม่ก่อนสอบใหม่
        if ((string) ($quiz['quiz_type'] ?? '') === 'posttest'
            && $passed === 0
            && $attemptsUsed >= $maxAttempts
        ) {
            $this->resetPosttestForRetake($planId, $quizId, $enrollmentId);
            $contentReset = true;
        }

        $progress = $this->recalculate($planId, $enrollmentId, true);

        return [
            'attempt_id' => $attemptId,
            'score' => $score,
            'max_score' => $maxScore,
            'percent' => $percent,
            'is_passed' => $passed,
            'attempts_used' => $attemptsUsed,
            'max_attempts' => $maxAttempts,
            'content_reset' => $contentReset,
            'progress' => $progress,
        ];
    }

    /** มีการส่งภาระงานอย่างน้อย 1 รายการในแผนนี้หรือไม่ */
    private function hasTaskSubmissions(int $planId, int $enrollmentId): bool
    {
        $row = db()->fetch(
            "SELECT COUNT(*) AS c
             FROM training_task_submissions s
             INNER JOIN training_tasks t ON t.id = s.task_id
             WHERE t.plan_id = ? AND t.status = 1 AND s.enrollment_id = ?
               AND s.status IN ('submitted','approved','rejected','revision')",
            [$planId, $enrollmentId]
        );
        return (int) ($row['c'] ?? 0) > 0;
    }

    /**
     * หลังสอบหลังครบครั้งแล้วยังไม่ผ่าน:
     * - ล้างการเปิดดูเนื้อหาของแผนนี้
     * - ปิดรอบ attempt เดิม (expired) เพื่อให้เริ่มนับใหม่หลังเรียนเนื้อหาครบ
     */
    private function resetPosttestForRetake(int $planId, int $quizId, int $enrollmentId): void
    {
        $contentIds = db()->fetchAll(
            'SELECT id FROM training_contents WHERE plan_id = ? AND status = 1',
            [$planId]
        );
        foreach ($contentIds as $c) {
            db()->query(
                'DELETE FROM training_content_views WHERE content_id = ? AND enrollment_id = ?',
                [(int) $c['id'], $enrollmentId]
            );
        }

        db()->query(
            "UPDATE training_quiz_attempts
             SET status = 'expired'
             WHERE quiz_id = ? AND enrollment_id = ? AND status = 'submitted'",
            [$quizId, $enrollmentId]
        );
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
        if (!$this->canAccessTask($planId, $enrollmentId)) {
            throw new \RuntimeException('กรุณาเปิดดูเนื้อหาให้ครบก่อนส่งภาระงาน');
        }

        $filePath = $data['file_path'] ?? null;
        $link = trim((string) ($data['link_url'] ?? ''));
        $text = trim((string) ($data['answer_text'] ?? ''));

        if ((int) ($task['require_file'] ?? 0) === 1 && !$filePath && $link === '') {
            throw new \RuntimeException('กรุณาแนบไฟล์หรือลิงก์');
        }

        // ภาระงานต้องรอวิทยากรตรวจให้คะแนนก่อนจึงนับร้อยละ
        $status = 'submitted';
        $now = date('Y-m-d H:i:s');
        $payload = [
            'answer_text' => $text !== '' ? $text : null,
            'file_path' => $filePath,
            'link_url' => $link !== '' ? $link : null,
            'status' => $status,
            'submitted_at' => $now,
            'updated_at' => $now,
            'score' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
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

        try {
            (new NotificationService())->taskSubmitted($planId, $taskId, $userId);
        } catch (\Throwable $e) {
            // ไม่บล็อกการส่งงานหากแจ้งเตือนล้มเหลว
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
        if (!$task) {
            throw new \RuntimeException('ไม่พบภาระงาน');
        }
        $max = max(0.0, (float) ($task['max_score'] ?? 100));
        // ค่าเริ่มต้น = คะแนนเต็มของภาระงานนั้น
        if ($status === 'approved') {
            if ($score === null) {
                $score = $max;
            }
            $score = max(0.0, min($max, $score));
        } elseif ($status === 'rejected') {
            $score = $score !== null ? max(0.0, min($max, $score)) : 0.0;
        } else {
            $score = null;
        }

        db()->update('training_task_submissions', [
            'status' => $status,
            'score' => $score,
            'feedback' => $feedback,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$submissionId]);

        try {
            (new NotificationService())->taskReviewed($submissionId, $status);
        } catch (\Throwable $e) {
            // ไม่บล็อกการตรวจงานหากแจ้งเตือนล้มเหลว
        }

        return $this->recalculate((int) $task['plan_id'], (int) $sub['enrollment_id'], true);
    }

    /**
     * ตรวจผ่านหลายรายการพร้อมกัน (ค่าเริ่มต้น = คะแนนเต็มของแต่ละภาระงาน)
     * @param list<int>|null $submissionIds ถ้าว่าง = ทุกงานที่รอตรวจในแผน
     * @return int จำนวนที่ตรวจ
     */
    public function bulkApproveSubmissions(int $planId, int $reviewerId, ?array $submissionIds = null, ?int $enrollmentId = null): int
    {
        $params = [$planId];
        $sql = "SELECT s.id, s.enrollment_id, t.max_score
                FROM training_task_submissions s
                INNER JOIN training_tasks t ON t.id = s.task_id
                WHERE t.plan_id = ? AND t.status = 1
                  AND s.status IN ('submitted','revision')";
        if ($enrollmentId !== null && $enrollmentId > 0) {
            $sql .= ' AND s.enrollment_id = ?';
            $params[] = $enrollmentId;
        }
        if ($submissionIds !== null) {
            $submissionIds = array_values(array_filter(array_map('intval', $submissionIds)));
            if ($submissionIds === []) {
                return 0;
            }
            $ph = implode(',', array_fill(0, count($submissionIds), '?'));
            $sql .= " AND s.id IN ({$ph})";
            foreach ($submissionIds as $sid) {
                $params[] = $sid;
            }
        }
        $rows = db()->fetchAll($sql, $params);
        $touchedEnroll = [];
        foreach ($rows as $row) {
            $this->reviewTask((int) $row['id'], 'approved', (float) ($row['max_score'] ?? 100), null, $reviewerId);
            $touchedEnroll[(int) $row['enrollment_id']] = true;
        }
        foreach (array_keys($touchedEnroll) as $eid) {
            $this->recalculate($planId, (int) $eid, true);
        }
        return count($rows);
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
