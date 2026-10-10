<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database Singleton with PDO Prepared Statements
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config = require CONFIG_PATH . '/database.php';
        $db = $config['connections']['mysql'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['database'],
            $db['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $db['username'], $db['password'], $db['options']);
        } catch (PDOException $e) {
            if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
                throw $e;
            }
            die('ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "`{$col}` = ?", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";
        $stmt = $this->query($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM `{$table}` WHERE {$where}";
        $row = $this->fetch($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    private function __clone() {}
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton');
    }
}
