<?php

declare(strict_types=1);

/**
 * คลังข้อสอบร่วมของทดสอบก่อน/หลัง, รูปคำถาม-ตัวเลือก, และชุดข้อที่สุ่มต่อผู้เรียน
 * รันซ้ำได้
 */
require dirname(__DIR__) . '/config/bootstrap.php';

$col = db()->fetch("SHOW COLUMNS FROM training_quiz_questions LIKE 'image_path'");
if (!$col) {
    db()->query('ALTER TABLE training_quiz_questions ADD COLUMN image_path VARCHAR(500) NULL DEFAULT NULL AFTER question_text');
}
$col = db()->fetch("SHOW COLUMNS FROM training_quiz_choices LIKE 'image_path'");
if (!$col) {
    db()->query('ALTER TABLE training_quiz_choices ADD COLUMN image_path VARCHAR(500) NULL DEFAULT NULL AFTER choice_text');
}
$col = db()->fetch("SHOW COLUMNS FROM training_quizzes LIKE 'draw_count'");
if (!$col) {
    db()->query('ALTER TABLE training_quizzes ADD COLUMN draw_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER max_attempts');
}

db()->query(
    "CREATE TABLE IF NOT EXISTS training_quiz_papers (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        plan_id INT UNSIGNED NOT NULL,
        enrollment_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        question_ids JSON NOT NULL,
        post_question_ids JSON NOT NULL,
        choice_orders JSON NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_quiz_paper (plan_id, enrollment_id),
        CONSTRAINT fk_paper_plan FOREIGN KEY (plan_id) REFERENCES learning_plans (id) ON DELETE CASCADE,
        CONSTRAINT fk_paper_enroll FOREIGN KEY (enrollment_id) REFERENCES course_enrollments (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$posts = db()->fetchAll(
    "SELECT q.*, z.plan_id
     FROM training_quiz_questions q
     INNER JOIN training_quizzes z ON z.id = q.quiz_id
     WHERE z.quiz_type = 'posttest'"
);
$copied = 0;
foreach ($posts as $q) {
    $pre = db()->fetch(
        "SELECT id FROM training_quizzes WHERE plan_id = ? AND quiz_type = 'pretest'",
        [(int) $q['plan_id']]
    );
    if (!$pre) {
        continue;
    }
    $dup = db()->fetch(
        'SELECT id FROM training_quiz_questions WHERE quiz_id = ? AND question_text = ? LIMIT 1',
        [(int) $pre['id'], $q['question_text']]
    );
    if ($dup) {
        continue;
    }
    $newId = (int) db()->insert('training_quiz_questions', [
        'quiz_id' => (int) $pre['id'],
        'question_text' => $q['question_text'],
        'image_path' => $q['image_path'] ?? null,
        'question_type' => $q['question_type'] ?? 'single',
        'points' => $q['points'],
        'explanation' => $q['explanation'] ?? null,
        'sort_order' => $q['sort_order'] ?? 0,
        'status' => $q['status'] ?? 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $choices = db()->fetchAll('SELECT * FROM training_quiz_choices WHERE question_id = ? ORDER BY sort_order, id', [(int) $q['id']]);
    foreach ($choices as $ch) {
        db()->insert('training_quiz_choices', [
            'question_id' => $newId,
            'choice_text' => $ch['choice_text'],
            'image_path' => $ch['image_path'] ?? null,
            'is_correct' => $ch['is_correct'],
            'sort_order' => $ch['sort_order'],
        ]);
    }
    $copied++;
}

echo "quiz bank ready, copied {$copied} posttest questions into the shared bank\n";
