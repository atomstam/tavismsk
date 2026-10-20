<?php

declare(strict_types=1);

/**
 * Drop legacy positions / prefixes once sc_personal_types and sc_prefixes are in use.
 * Safe to run more than once.
 */
require dirname(__DIR__) . '/config/bootstrap.php';

$fk = db()->fetch(
    "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'personnel' AND CONSTRAINT_NAME = 'fk_personnel_pos'"
);
if ($fk) {
    db()->query('ALTER TABLE personnel DROP FOREIGN KEY fk_personnel_pos');
}

$col = db()->fetch("SHOW COLUMNS FROM personnel LIKE 'position_id'");
if ($col) {
    db()->query('ALTER TABLE personnel DROP COLUMN position_id');
}

db()->query('DROP TABLE IF EXISTS positions');
db()->query('DROP TABLE IF EXISTS prefixes');

echo "legacy positions and prefixes removed\n";
