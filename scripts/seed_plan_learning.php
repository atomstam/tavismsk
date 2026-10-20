<?php

declare(strict_types=1);

/**
 * เติมข้อสอบ เนื้อหา ภาระงานทุกแผน และแบบประเมินความพึงพอใจกลาง 15 ข้อ
 * รันซ้ำได้: ไม่สร้างแถวที่ชื่อเดิมมีอยู่แล้ว
 */
require dirname(__DIR__) . '/config/bootstrap.php';

use App\Services\ModuleLearningService;

function columnExists(string $table, string $column): bool
{
    $row = db()->fetch(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column]
    );
    return (int) ($row['c'] ?? 0) > 0;
}

if (!columnExists('training_plan_settings', 'quiz_draw_count')) {
    db()->query('ALTER TABLE training_plan_settings ADD COLUMN quiz_draw_count INT UNSIGNED NOT NULL DEFAULT 10 AFTER gate_posttest_after_content');
}
if (!columnExists('training_plan_settings', 'quiz_shuffle')) {
    db()->query('ALTER TABLE training_plan_settings ADD COLUMN quiz_shuffle TINYINT(1) NOT NULL DEFAULT 1 AFTER quiz_draw_count');
}
if (!columnExists('training_plan_settings', 'pass_percent')) {
    db()->query('ALTER TABLE training_plan_settings ADD COLUMN pass_percent DECIMAL(5,2) NOT NULL DEFAULT 80.00 AFTER quiz_shuffle');
}
if (!columnExists('training_quizzes', 'is_central')) {
    db()->query('ALTER TABLE training_quizzes ADD COLUMN is_central TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
}
if (!columnExists('training_module_progress', 'is_passed')) {
    db()->query('ALTER TABLE training_module_progress ADD COLUMN is_passed TINYINT(1) NOT NULL DEFAULT 0 AFTER overall_percent');
}
db()->query("ALTER TABLE training_quizzes MODIFY quiz_type ENUM('pretest','posttest','satisfaction') NOT NULL");

$lms = new ModuleLearningService();
$actor = db()->fetch('SELECT id FROM users ORDER BY id LIMIT 1');
$actorId = (int) ($actor['id'] ?? 0);
$now = date('Y-m-d H:i:s');
$curriculum = config('curriculum');
if (!is_array($curriculum)) {
    $curriculum = [];
}
$byNo = [];
foreach ($curriculum as $row) {
    if (is_array($row) && isset($row['plan_no'])) {
        $byNo[(int) $row['plan_no']] = $row;
    }
}

$plans = db()->fetchAll('SELECT * FROM learning_plans ORDER BY plan_no, id');
$addedQ = 0;
$addedC = 0;
$addedT = 0;

$wrongs = [
    'เร่งความเร็วในเขตชุมชนโดยไม่ดูสภาพถนน',
    'ฝ่าสัญญาณไฟแดงเมื่อเห็นว่าทางว่าง',
    'ไม่สวมหมวกนิรภัยเพราะเดินทางระยะสั้น',
    'ใช้โทรศัพท์ขณะขับขี่',
    'ขับขี่ทั้งที่ง่วงหรือได้รับสารที่มีผลต่อสติ',
    'ไม่ตรวจสภาพรถก่อนออกเดินทาง',
];

foreach ($plans as $plan) {
    $planId = (int) $plan['id'];
    $meta = $byNo[(int) $plan['plan_no']] ?? [];
    $sheets = array_values(array_filter((array) ($meta['knowledge_sheets'] ?? [])));
    if ($sheets === []) {
        $sheets = [(string) $plan['title']];
    }
    $lms->settings($planId);
    $preId = $lms->ensureQuiz($planId, 'pretest', 'ทดสอบก่อน: ' . $plan['title'], $actorId);
    $lms->ensureQuiz($planId, 'posttest', 'สอบหลัง: ' . $plan['title'], $actorId);
    $lms->ensureQuiz($planId, 'satisfaction', 'ประเมินความพึงพอใจ: ' . $plan['title'], $actorId);

    $have = (int) (db()->fetch(
        'SELECT COUNT(*) AS c FROM training_quiz_questions WHERE quiz_id = ? AND status = 1',
        [$preId]
    )['c'] ?? 0);
    $need = 12 - $have;
    for ($i = 0; $i < $need; $i++) {
        $sheet = $sheets[$i % count($sheets)];
        $n = $have + $i + 1;
        $text = 'แผนที่ ' . (int) $plan['plan_no'] . ' ข้อ ' . $n . ': สาระ «' . $sheet . '» ข้อใดถูกต้อง';
        $exists = db()->fetch(
            'SELECT id FROM training_quiz_questions WHERE quiz_id = ? AND question_text = ?',
            [$preId, $text]
        );
        if ($exists) {
            continue;
        }
        $qid = (int) db()->insert('training_quiz_questions', [
            'quiz_id' => $preId,
            'question_text' => $text,
            'question_type' => 'single',
            'points' => 1,
            'explanation' => 'สอดคล้องกับสาระของแผน: ' . $sheet,
            'sort_order' => $n,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $choices = array_merge(['ปฏิบัติตามสาระ: ' . $sheet], $wrongs);
        foreach (array_slice($choices, 0, 4) as $ci => $choice) {
            db()->insert('training_quiz_choices', [
                'question_id' => $qid,
                'choice_text' => $choice,
                'is_correct' => $ci === 0 ? 1 : 0,
                'sort_order' => $ci,
            ]);
        }
        $addedQ++;
    }

    $mainTitle = 'เนื้อหาหลักของแผนนี้';
    $main = db()->fetch(
        'SELECT id FROM training_contents WHERE plan_id = ? AND title = ?',
        [$planId, $mainTitle]
    );
    if (!$main) {
        $body = "แผนการเรียนรู้: " . $plan['title'] . "\n\nสาระสำคัญ\n- " . implode("\n- ", $sheets);
        db()->insert('training_contents', [
            'plan_id' => $planId,
            'title' => $mainTitle,
            'description' => 'อ่านสาระสำคัญของแผนนี้ก่อนทำภาระงานสรุปองค์ความรู้',
            'content_type' => 'text',
            'body_text' => $body,
            'is_required' => 1,
            'sort_order' => 0,
            'status' => 1,
            'created_by' => $actorId ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $addedC++;
    }
    $sort = 1;
    foreach ((array) ($meta['resources'] ?? []) as $res) {
        if (!is_array($res)) {
            continue;
        }
        $url = trim((string) ($res['url'] ?? ''));
        $title = trim((string) ($res['title'] ?? ''));
        if ($url === '' || $title === '') {
            continue;
        }
        $dup = db()->fetch(
            'SELECT id FROM training_contents WHERE plan_id = ? AND external_url = ?',
            [$planId, $url]
        );
        if ($dup) {
            $sort++;
            continue;
        }
        $type = (string) ($res['type'] ?? 'link');
        if (!in_array($type, ['pdf', 'word', 'excel', 'gdrive', 'youtube', 'link', 'text'], true)) {
            $type = 'link';
        }
        db()->insert('training_contents', [
            'plan_id' => $planId,
            'title' => $title,
            'description' => 'สื่อประกอบแผนนี้',
            'content_type' => $type,
            'external_url' => $url,
            'is_required' => 0,
            'sort_order' => $sort,
            'status' => 1,
            'created_by' => $actorId ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $addedC++;
        $sort++;
    }

    $summaryTitle = 'เขียนสรุปองค์ความรู้ที่ได้จากการเรียน';
    $summary = db()->fetch(
        'SELECT id FROM training_tasks WHERE plan_id = ? AND title = ?',
        [$planId, $summaryTitle]
    );
    if (!$summary) {
        db()->insert('training_tasks', [
            'plan_id' => $planId,
            'title' => $summaryTitle,
            'description' => 'ภาระงานแรกของแผน: สรุปสิ่งที่ได้จากเนื้อหาของแผนนี้',
            'instructions' => 'เขียนข้อความสรุปองค์ความรู้ที่ได้จากการเรียนเนื้อหาของแผน «' . $plan['title'] . '» ความยาวพอให้เห็นความเข้าใจ ไม่ต้องแนบไฟล์',
            'require_file' => 0,
            'allow_link' => 0,
            'require_review' => 1,
            'max_score' => 100,
            'pass_score' => 60,
            'is_required' => 1,
            'sort_order' => 0,
            'status' => 1,
            'created_by' => $actorId ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $addedT++;
    }
    $tSort = 1;
    foreach ((array) ($meta['worksheets'] ?? []) as $sheetTitle) {
        $sheetTitle = trim((string) $sheetTitle);
        if ($sheetTitle === '' || $sheetTitle === $summaryTitle) {
            continue;
        }
        $dup = db()->fetch(
            'SELECT id FROM training_tasks WHERE plan_id = ? AND title = ?',
            [$planId, $sheetTitle]
        );
        if ($dup) {
            $tSort++;
            continue;
        }
        db()->insert('training_tasks', [
            'plan_id' => $planId,
            'title' => $sheetTitle,
            'description' => 'ภาระงานต่อจากสรุปองค์ความรู้',
            'instructions' => 'ทำภาระงาน «' . $sheetTitle . '» แล้วเขียนคำตอบเป็นข้อความ',
            'require_file' => 0,
            'allow_link' => 1,
            'require_review' => 1,
            'max_score' => 100,
            'pass_score' => 60,
            'is_required' => 1,
            'sort_order' => $tSort,
            'status' => 1,
            'created_by' => $actorId ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $addedT++;
        $tSort++;
    }
    $firstTask = db()->fetch(
        'SELECT id, title FROM training_tasks WHERE plan_id = ? AND status = 1 ORDER BY sort_order, id LIMIT 1',
        [$planId]
    );
    if ($firstTask && (string) $firstTask['title'] !== $summaryTitle) {
        db()->query(
            'UPDATE training_tasks SET sort_order = sort_order + 1 WHERE plan_id = ? AND title <> ?',
            [$planId, $summaryTitle]
        );
        db()->query(
            'UPDATE training_tasks SET sort_order = 0 WHERE plan_id = ? AND title = ?',
            [$planId, $summaryTitle]
        );
    }
}

$centralId = $lms->centralSatisfactionId($actorId);
$survey = [
    'เนื้อหาของแผนนี้เข้าใจง่ายและตรงกับชื่อแผน',
    'แบบทดสอบก่อนเรียนช่วยให้สำรวจความรู้เดิมได้',
    'จำนวนข้อและการสุ่มข้อสอบจากคลังมีความเหมาะสม',
    'แบบทดสอบหลังเรียนสอดคล้องกับเนื้อหาที่เรียน',
    'สื่อการเรียนทั้งข้อความ วิดีโอ และไฟล์เปิดใช้งานได้',
    'ภาระงานเขียนสรุปองค์ความรู้ช่วยทบทวนสิ่งที่เรียน',
    'ลำดับขั้นทดสอบก่อน เนื้อหา ภาระงาน สอบหลัง และประเมินชัดเจน',
    'น้ำหนักร้อยละความก้าวหน้าและเกณฑ์การผ่านเข้าใจได้',
    'ระบบ TAVIS ใช้งานได้สะดวก',
    'ข้อมูลการลงทะเบียนและโปรไฟล์ผู้เรียนเพียงพอต่อการเรียน',
    'หลังเรียนแล้วความรู้ด้านความปลอดภัยทางถนนเพิ่มขึ้น',
    'สามารถนำความรู้ไปใช้ในการใช้รถใช้ถนนได้จริง',
    'ระยะเวลาของแผนนี้เหมาะสมกับเนื้อหา',
    'อยากแนะนำชมรมวัคซีนจราจรและระบบนี้ให้ผู้อื่น',
    'โดยรวมพึงพอใจกับการเรียนแผนนี้',
];
$likert = ['มากที่สุด', 'มาก', 'ปานกลาง', 'น้อย', 'น้อยที่สุด'];
$addedS = 0;
foreach ($survey as $i => $text) {
    $exists = db()->fetch(
        'SELECT id FROM training_quiz_questions WHERE quiz_id = ? AND question_text = ?',
        [$centralId, $text]
    );
    if ($exists) {
        continue;
    }
    $qid = (int) db()->insert('training_quiz_questions', [
        'quiz_id' => $centralId,
        'question_text' => $text,
        'question_type' => 'single',
        'points' => 1,
        'sort_order' => $i + 1,
        'status' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    foreach ($likert as $ci => $choice) {
        db()->insert('training_quiz_choices', [
            'question_id' => $qid,
            'choice_text' => $choice,
            'is_correct' => 1,
            'sort_order' => $ci,
        ]);
    }
    $addedS++;
}

$satCount = (int) (db()->fetch(
    'SELECT COUNT(*) AS c FROM training_quiz_questions WHERE quiz_id = ? AND status = 1',
    [$centralId]
)['c'] ?? 0);
echo "plans=" . count($plans) . " questions+{$addedQ} contents+{$addedC} tasks+{$addedT} satisfaction+{$addedS} central_questions={$satCount}\n";
