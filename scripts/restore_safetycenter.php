<?php
/**
 * กู้ฐาน safetycenter เดิมจาก backup (ไม่ใช้ safetycenter-2.sql)
 *
 * แหล่งข้อมูล:
 *   /Applications/XAMPP/xamppfiles/htdocs/obecsafetycenter/db_safety_20231130.sql
 *
 * Usage:
 *   php scripts/restore_safetycenter.php
 */

declare(strict_types=1);

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';
$backup = '/Applications/XAMPP/xamppfiles/htdocs/obecsafetycenter/db_safety_20231130.sql';
$mysql = '/Applications/XAMPP/xamppfiles/bin/mysql';

if (!is_file($backup)) {
    fwrite(STDERR, "Missing backup: {$backup}\n");
    exit(1);
}
if (!is_executable($mysql)) {
    fwrite(STDERR, "Missing mysql binary: {$mysql}\n");
    exit(1);
}

echo "=== Restore original safetycenter DB ===\n";
echo "  from: {$backup}\n";
echo "  WARNING: will DROP and recreate database `safetycenter`\n";

$auth = sprintf('-h%s -P%s -u%s', $host, $port, escapeshellarg($user));
if ($pass !== '') {
    $auth .= ' -p' . escapeshellarg($pass);
}

$prep = sprintf(
    '%s %s -e %s',
    escapeshellarg($mysql),
    $auth,
    escapeshellarg('DROP DATABASE IF EXISTS `safetycenter`; CREATE DATABASE `safetycenter` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;')
);
echo "  > recreate empty safetycenter\n";
passthru($prep, $code);
if ($code !== 0) {
    fwrite(STDERR, "Failed to recreate database\n");
    exit(1);
}

$import = sprintf(
    '%s %s < %s',
    escapeshellarg($mysql),
    $auth,
    escapeshellarg($backup)
);
echo "  > importing backup (may take a minute)...\n";
passthru($import, $code);
if ($code !== 0) {
    fwrite(STDERR, "Import failed (code {$code})\n");
    exit(1);
}

$verify = sprintf(
    '%s %s -e %s',
    escapeshellarg($mysql),
    $auth,
    escapeshellarg(
        "SELECT COUNT(*) AS tables_cnt FROM information_schema.tables WHERE table_schema='safetycenter';"
        . " SELECT COUNT(*) AS users_cnt FROM safetycenter.users;"
        . " SHOW TABLES FROM safetycenter;"
    )
);
passthru($verify, $vcode);
echo "Done.\n";
exit($vcode === 0 ? 0 : 1);
