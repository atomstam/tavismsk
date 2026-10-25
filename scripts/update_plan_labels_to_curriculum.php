#!/usr/bin/env php
<?php
/**
 * อัปเดตข้อความใน DB: แผน / แผนการเรียนรู้ → หลักสูตร
 * ใช้บน server จริงได้ (อ่านค่าจาก .env)
 *
 * วิธีรัน:
 *   php scripts/update_plan_labels_to_curriculum.php
 *
 * หรือใช้ SQL:
 *   mysql -u USER -p DB < database/migrations/20261025_rename_plan_labels_to_curriculum.sql
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . '/.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "ไม่พบ .env\n");
    exit(1);
}
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    putenv(trim($k) . '=' . trim($v, " \t\"'"));
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_DATABASE') ?: '';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
if ($db === '') {
    fwrite(STDERR, "DB_DATABASE ว่างใน .env\n");
    exit(1);
}

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$protect = [
    'วาดแผนที่เส้นทางบ้าน–โรงเรียนและระบุเครื่องหมาย' => '⟦P1⟧',
    'แผนที่ความเสี่ยงเส้นทางประจำวัน' => '⟦P2⟧',
    'แผนที่ขั้นตอนขอใบอนุญาต' => '⟦P3⟧',
    'แผนปรับทัศนคติส่วนบุคคล' => '⟦P4⟧',
    'แผนดูแลสุขภาพสำหรับผู้ใช้รถใช้ถนน' => '⟦P5⟧',
    'แผนที่และการติดต่อ' => '⟦P6⟧',
];

$repl = [
    'แผนการจัดการเรียนรู้' => 'หลักสูตร',
    'แผนการเรียนรู้' => 'หลักสูตร',
    'บริหารแผนอบรม' => 'บริหารหลักสูตร',
    'โมดูลการอบรม' => 'หลักสูตร',
    'การลงทะเบียนอบรม' => 'การลงทะเบียนหลักสูตร',
    'ลงทะเบียนอบรม' => 'ลงทะเบียนหลักสูตร',
    'เจ้าของแผน' => 'เจ้าของหลักสูตร',
    'เกียรติบัตรแผนที่' => 'เกียรติบัตรหลักสูตรที่',
    'พิมพ์เกียรติบัตรแผน' => 'พิมพ์เกียรติบัตรหลักสูตร',
    'แผนอบรม' => 'หลักสูตร',
    'แผนบังคับ' => 'หลักสูตรบังคับ',
    'แผนทั่วไป' => 'หลักสูตรทั่วไป',
    'แผนทั้งหมด' => 'หลักสูตรทั้งหมด',
    'รายละเอียดแผน' => 'รายละเอียดหลักสูตร',
    'ทุกแผน' => 'ทุกหลักสูตร',
    'แยกแผน' => 'แยกหลักสูตร',
    'รายแผน' => 'รายหลักสูตร',
    'ต่อแผน' => 'ต่อหลักสูตร',
    'ในแผน' => 'ในหลักสูตร',
    'ของแผน' => 'ของหลักสูตร',
    'แต่ละแผน' => 'แต่ละหลักสูตร',
    'หลายแผน' => 'หลายหลักสูตร',
    'ทั้งแผน' => 'ทั้งหลักสูตร',
    'เลือกแผน' => 'เลือกหลักสูตร',
    'ไม่พบแผน' => 'ไม่พบหลักสูตร',
    'ยังไม่มีแผน' => 'ยังไม่มีหลักสูตร',
    'มีแผน' => 'มีหลักสูตร',
    'เป็นแผน' => 'เป็นหลักสูตร',
    'ตามแผน' => 'ตามหลักสูตร',
    'จากแผน' => 'จากหลักสูตร',
    'และแผน' => 'และหลักสูตร',
    'หรือแผน' => 'หรือหลักสูตร',
    'แผนนี้' => 'หลักสูตรนี้',
    'แผนที่ ' => 'หลักสูตรที่ ',
    'แผน ' => 'หลักสูตร ',
];

$targets = [
    'faqs' => ['question', 'answer'],
    'announcements' => ['title', 'body'],
    'notifications' => ['title', 'message'],
    'certificates' => ['title'],
    'news' => ['title', 'excerpt', 'content'],
    'pages' => ['title', 'content', 'seo_title', 'seo_description'],
    'settings' => ['setting_value'],
    'training_contents' => ['title', 'description', 'body_text'],
    'training_quiz_questions' => ['question_text', 'explanation'],
    'training_tasks' => ['title', 'description', 'instructions'],
    'activity_logs' => ['description'],
    'worksheets' => ['title', 'description'],
];

function transform_plan_text(string $text, array $protect, array $repl): string
{
    foreach ($protect as $a => $b) {
        $text = str_replace($a, $b, $text);
    }
    foreach ($repl as $a => $b) {
        $text = str_replace($a, $b, $text);
    }
    $text = preg_replace('/แผนที่\s*(\d+)/u', 'หลักสูตรที่ $1', $text) ?? $text;
    $text = preg_replace('/(\d+)\s*แผน\b/u', '$1 หลักสูตร', $text) ?? $text;
    foreach ($protect as $a => $b) {
        $text = str_replace($b, $a, $text);
    }
    while (str_contains($text, 'หลักสูตรหลักสูตร')) {
        $text = str_replace('หลักสูตรหลักสูตร', 'หลักสูตร', $text);
    }
    return $text;
}

$pdo->beginTransaction();
$summary = [];

try {
    foreach ($targets as $table => $cols) {
        $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
        if (!$exists) {
            echo "[skip] ไม่มีตาราง {$table}\n";
            continue;
        }
        $realCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $realCols[$c['Field']] = true;
        }
        $cols = array_values(array_filter($cols, static fn ($c) => isset($realCols[$c])));
        if ($cols === []) {
            continue;
        }

        $pk = null;
        foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC) as $c) {
            if ($c['Key'] === 'PRI') {
                $pk = $c['Field'];
                break;
            }
        }
        if ($pk === null) {
            echo "[skip] {$table} ไม่มี PK\n";
            continue;
        }

        $ors = implode(' OR ', array_map(static fn ($c) => "`{$c}` LIKE '%แผน%'", $cols));
        $select = "`{$pk}`, " . implode(', ', array_map(static fn ($c) => "`{$c}`", $cols));
        $rows = $pdo->query("SELECT {$select} FROM `{$table}` WHERE {$ors}")->fetchAll(PDO::FETCH_ASSOC);
        $n = 0;
        foreach ($rows as $row) {
            $sets = [];
            $params = [];
            foreach ($cols as $col) {
                $old = (string) ($row[$col] ?? '');
                if ($old === '' || !str_contains($old, 'แผน')) {
                    continue;
                }
                $new = transform_plan_text($old, $protect, $repl);
                if ($new !== $old) {
                    $sets[] = "`{$col}` = ?";
                    $params[] = $new;
                }
            }
            if ($sets === []) {
                continue;
            }
            $params[] = $row[$pk];
            $pdo->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `' . $pk . '` = ?')
                ->execute($params);
            $n++;
        }
        $summary[$table] = $n;
        echo "[ok] {$table}: {$n} แถว\n";
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\nสรุป: อัปเดตข้อความแผน→หลักสูตร เสร็จแล้ว\n";
foreach ($summary as $t => $n) {
    echo "  - {$t}: {$n}\n";
}
exit(0);
