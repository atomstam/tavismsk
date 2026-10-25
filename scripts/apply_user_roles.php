<?php

declare(strict_types=1);

/**
 * หนึ่งบัญชีมีได้หลายบทบาท และหนึ่งหลักสูตรมีเจ้าของได้หลายคน
 * php scripts/apply_user_roles.php
 */

$root = dirname(__DIR__);
require $root . '/config/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();
$pdo = $db->pdo();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS user_roles (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        role VARCHAR(30) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_user_role (user_id, role),
        KEY idx_user_roles_role (role),
        CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS learning_plan_owners (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        plan_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        note VARCHAR(255) DEFAULT NULL,
        reviewed_by INT UNSIGNED DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_plan_owner (plan_id, user_id),
        KEY idx_plan_owner_status (status),
        CONSTRAINT fk_plan_owner_plan FOREIGN KEY (plan_id) REFERENCES learning_plans (id) ON DELETE CASCADE,
        CONSTRAINT fk_plan_owner_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$copied = $pdo->exec(
    "INSERT IGNORE INTO user_roles (user_id, role)
     SELECT id, role FROM users WHERE deleted_at IS NULL AND role <> ''"
);
$learners = $pdo->exec(
    "INSERT IGNORE INTO user_roles (user_id, role)
     SELECT id, 'user' FROM users WHERE deleted_at IS NULL"
);

echo 'user_roles copied=' . (int) $copied . ' learner_rows=' . (int) $learners . PHP_EOL;
