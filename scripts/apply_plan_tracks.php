<?php

declare(strict_types=1);

/** หลักสูตร 1–12 บังคับ หลักสูตร 13–14 ทั่วไป และเกียรติบัตรรายหลักสูตร */
require dirname(__DIR__) . '/config/bootstrap.php';

function columnExists(string $table, string $column): bool
{
    $row = db()->fetch(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column]
    );
    return (int) ($row['c'] ?? 0) > 0;
}

if (!columnExists('learning_plans', 'is_required')) {
    db()->query('ALTER TABLE learning_plans ADD COLUMN is_required TINYINT(1) NOT NULL DEFAULT 1 AFTER status');
}
db()->query('UPDATE learning_plans SET is_required = 1 WHERE plan_no BETWEEN 1 AND 12');
db()->query('UPDATE learning_plans SET is_required = 0 WHERE plan_no >= 13');

if (!columnExists('certificates', 'plan_id')) {
    db()->query('ALTER TABLE certificates ADD COLUMN plan_id INT UNSIGNED NULL AFTER enrollment_id');
    db()->query('ALTER TABLE certificates ADD KEY idx_cert_plan (plan_id)');
    db()->query('ALTER TABLE certificates ADD CONSTRAINT fk_cert_plan FOREIGN KEY (plan_id) REFERENCES learning_plans (id) ON DELETE CASCADE');
}

$counts = db()->fetch(
    'SELECT SUM(is_required = 1) AS required_plans, SUM(is_required = 0) AS elective_plans FROM learning_plans'
);
echo 'required=' . (int) ($counts['required_plans'] ?? 0) . ' elective=' . (int) ($counts['elective_plans'] ?? 0) . PHP_EOL;
