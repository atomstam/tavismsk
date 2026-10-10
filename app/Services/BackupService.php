<?php

declare(strict_types=1);

namespace App\Services;

class BackupService
{
    public function create(?int $userId = null): array
    {
        $dir = BASE_PATH . '/' . ltrim((string) env('BACKUP_PATH', 'storage/backups'), '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'tavis_' . date('Ymd_His') . '.sql';
        $path = $dir . '/' . $name;

        $cfg = require CONFIG_PATH . '/database.php';
        $db = $cfg['connections']['mysql'];
        $tables = db()->fetchAll('SHOW TABLES');
        $key = 'Tables_in_' . $db['database'];
        $sql = "-- TAVIS backup " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $t) {
            $table = (string) ($t[$key] ?? reset($t));
            $create = db()->fetch("SHOW CREATE TABLE `{$table}`");
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n" . ($create['Create Table'] ?? '') . ";\n\n";
            $rows = db()->fetchAll("SELECT * FROM `{$table}`");
            foreach ($rows as $row) {
                $cols = implode('`,`', array_keys($row));
                $vals = array_map(function ($v) {
                    if ($v === null) {
                        return 'NULL';
                    }
                    return "'" . addslashes((string) $v) . "'";
                }, array_values($row));
                $sql .= "INSERT INTO `{$table}` (`{$cols}`) VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql .= "\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($path, $sql);

        $id = db()->insert('backups', [
            'file_name' => $name,
            'file_path' => 'storage/backups/' . $name,
            'file_size' => filesize($path) ?: 0,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['id' => $id, 'file_name' => $name, 'file_path' => $path, 'file_size' => filesize($path)];
    }

    public function restore(string $absolutePath): void
    {
        if (!is_file($absolutePath)) {
            throw new \RuntimeException('ไม่พบไฟล์สำรอง');
        }
        $sql = file_get_contents($absolutePath);
        if ($sql === false) {
            throw new \RuntimeException('อ่านไฟล์ไม่สำเร็จ');
        }
        db()->pdo()->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            db()->pdo()->exec($stmt);
        }
        db()->pdo()->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
