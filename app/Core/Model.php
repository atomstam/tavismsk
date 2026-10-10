<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base Model with CRUD helpers
 */
abstract class Model
{
    protected Database $db;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = ['password', 'remember_token', 'two_factor_secret'];
    protected bool $timestamps = true;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->fetchAll("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    public function findBy(string $column, mixed $value): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$column}` = ? LIMIT 1",
            [$value]
        );
    }

    public function where(string $where, array $params = [], string $orderBy = 'id DESC'): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY {$orderBy}",
            $params
        );
    }

    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        if ($this->timestamps) {
            $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
            $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        }
        return $this->db->insert($this->table, $data);
    }

    public function update(int $id, array $data): int
    {
        $data = $this->filterFillable($data);
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return $this->db->update($this->table, $data, "`{$this->primaryKey}` = ?", [$id]);
    }

    public function delete(int $id): int
    {
        return $this->db->delete($this->table, "`{$this->primaryKey}` = ?", [$id]);
    }

    public function softDelete(int $id): int
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s'), 'status' => 0]);
    }

    public function count(string $where = '1=1', array $params = []): int
    {
        return $this->db->count($this->table, $where, $params);
    }

    public function paginate(int $page = 1, int $perPage = 15, string $where = '1=1', array $params = [], string $orderBy = 'id DESC'): array
    {
        $total = $this->count($where, $params);
        $offset = ($page - 1) * $perPage;
        $items = $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public function search(string $keyword, array $columns, int $page = 1, int $perPage = 15): array
    {
        $likes = [];
        $params = [];
        foreach ($columns as $col) {
            $likes[] = "`{$col}` LIKE ?";
            $params[] = "%{$keyword}%";
        }
        $where = '(' . implode(' OR ', $likes) . ')';
        return $this->paginate($page, $perPage, $where, $params);
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    public function hide(array $row): array
    {
        foreach ($this->hidden as $field) {
            unset($row[$field]);
        }
        return $row;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function db(): Database
    {
        return $this->db;
    }
}
