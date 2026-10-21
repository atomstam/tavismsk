<?php

declare(strict_types=1);

/**
 * กระดานสนทนาต่อแผน — ห้องรวม + ตัวต่อตัว
 * php scripts/apply_plan_chat.php
 */

$root = dirname(__DIR__);
require $root . '/config/bootstrap.php';

use App\Core\Database;

$pdo = Database::getInstance()->pdo();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS plan_chat_threads (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        plan_id INT UNSIGNED NOT NULL,
        thread_type ENUM('group','direct') NOT NULL DEFAULT 'direct',
        learner_id INT UNSIGNED DEFAULT NULL,
        trainer_id INT UNSIGNED DEFAULT NULL,
        last_message_at DATETIME DEFAULT NULL,
        learner_last_read_at DATETIME DEFAULT NULL,
        trainer_last_read_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_plan_chat_learner (learner_id),
        KEY idx_plan_chat_trainer (trainer_id),
        KEY idx_plan_chat_last (last_message_at),
        KEY idx_plan_chat_type (plan_id, thread_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS plan_chat_messages (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        thread_id INT UNSIGNED NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        body TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_plan_chat_msg_thread (thread_id, id),
        KEY idx_plan_chat_msg_sender (sender_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS plan_chat_reads (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        thread_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        last_read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_plan_chat_read (thread_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$cols = $pdo->query('SHOW COLUMNS FROM plan_chat_threads')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('thread_type', $cols, true)) {
    $pdo->exec(
        "ALTER TABLE plan_chat_threads
         ADD COLUMN thread_type ENUM('group','direct') NOT NULL DEFAULT 'direct' AFTER plan_id"
    );
    echo "added thread_type\n";
}

$fkRows = $pdo->query(
    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plan_chat_threads'
       AND CONSTRAINT_NAME LIKE 'fk_plan_chat_%'
     GROUP BY CONSTRAINT_NAME"
)->fetchAll(PDO::FETCH_COLUMN);

foreach ($fkRows as $fk) {
    try {
        $pdo->exec('ALTER TABLE plan_chat_threads DROP FOREIGN KEY `' . str_replace('`', '', (string) $fk) . '`');
        echo "dropped FK {$fk}\n";
    } catch (Throwable $e) {
        // ignore
    }
}

try {
    $pdo->exec('ALTER TABLE plan_chat_threads DROP INDEX uk_plan_chat_pair');
    echo "dropped uk_plan_chat_pair\n";
} catch (Throwable $e) {
    // ignore
}

$pdo->exec('ALTER TABLE plan_chat_threads MODIFY learner_id INT UNSIGNED NULL');
$pdo->exec('ALTER TABLE plan_chat_threads MODIFY trainer_id INT UNSIGNED NULL');
$pdo->exec("UPDATE plan_chat_threads SET thread_type = 'direct' WHERE thread_type = '' OR thread_type IS NULL");

$colCheck = $pdo->query("SHOW COLUMNS FROM plan_chat_threads LIKE 'group_slot'")->fetch();
if (!$colCheck) {
    $pdo->exec(
        "ALTER TABLE plan_chat_threads
         ADD COLUMN group_slot TINYINT UNSIGNED
           GENERATED ALWAYS AS (IF(thread_type = 'group', 1, NULL)) STORED"
    );
    echo "added group_slot\n";
}

$indexes = $pdo->query('SHOW INDEX FROM plan_chat_threads')->fetchAll(PDO::FETCH_ASSOC);
$names = array_unique(array_column($indexes, 'Key_name'));
if (!in_array('uk_plan_chat_group', $names, true)) {
    $pdo->exec('ALTER TABLE plan_chat_threads ADD UNIQUE KEY uk_plan_chat_group (plan_id, group_slot)');
    echo "added uk_plan_chat_group\n";
}
if (!in_array('uk_plan_chat_direct', $names, true)) {
    $pdo->exec('ALTER TABLE plan_chat_threads ADD UNIQUE KEY uk_plan_chat_direct (plan_id, learner_id, trainer_id)');
    echo "added uk_plan_chat_direct\n";
}

// Re-add FKs if missing
$ensureFk = static function (PDO $pdo, string $name, string $col, string $refTable) use (&$fkRows): void {
    $exists = $pdo->query(
        "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plan_chat_threads'
           AND CONSTRAINT_NAME = " . $pdo->quote($name)
    )->fetch();
    if ($exists) {
        return;
    }
    $pdo->exec(
        "ALTER TABLE plan_chat_threads
         ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$col}`) REFERENCES `{$refTable}` (`id`) ON DELETE CASCADE"
    );
    echo "added FK {$name}\n";
};

$ensureFk($pdo, 'fk_plan_chat_plan', 'plan_id', 'learning_plans');
$ensureFk($pdo, 'fk_plan_chat_learner', 'learner_id', 'users');
$ensureFk($pdo, 'fk_plan_chat_trainer', 'trainer_id', 'users');

$msgFk = $pdo->query(
    "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plan_chat_messages'
       AND CONSTRAINT_NAME = 'fk_plan_chat_msg_thread'"
)->fetch();
if (!$msgFk) {
    try {
        $pdo->exec(
            'ALTER TABLE plan_chat_messages
             ADD CONSTRAINT fk_plan_chat_msg_thread FOREIGN KEY (thread_id) REFERENCES plan_chat_threads (id) ON DELETE CASCADE'
        );
        $pdo->exec(
            'ALTER TABLE plan_chat_messages
             ADD CONSTRAINT fk_plan_chat_msg_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE'
        );
    } catch (Throwable $e) {
        // may already exist under different state
    }
}

$readFk = $pdo->query(
    "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plan_chat_reads'
       AND CONSTRAINT_NAME = 'fk_plan_chat_read_thread'"
)->fetch();
if (!$readFk) {
    try {
        $pdo->exec(
            'ALTER TABLE plan_chat_reads
             ADD CONSTRAINT fk_plan_chat_read_thread FOREIGN KEY (thread_id) REFERENCES plan_chat_threads (id) ON DELETE CASCADE'
        );
        $pdo->exec(
            'ALTER TABLE plan_chat_reads
             ADD CONSTRAINT fk_plan_chat_read_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE'
        );
    } catch (Throwable $e) {
    }
}

echo "plan_chat ready (group + direct)\n";
