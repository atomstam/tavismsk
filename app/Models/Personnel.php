<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Personnel extends Model
{
    protected string $table = 'personnel';
    protected array $fillable = [
        'user_id', 'code', 'id_card', 'title', 'first_name', 'last_name', 'gender',
        'birth_date', 'position_id', 'academic_rank_id', 'salary_step', 'salary',
        'level_name', 'department_id', 'work_group', 'phone', 'email', 'line_id',
        'facebook', 'address', 'education', 'work_history', 'training_history',
        'photo', 'attachment', 'sort_order', 'status', 'deleted_at',
    ];

    public function fullName(array $row): string
    {
        return trim(($row['title'] ?? '') . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    }

    public function withRelations(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT p.*, d.name AS department_name, pos.name AS position_name, r.name AS rank_name
             FROM personnel p
             LEFT JOIN departments d ON d.id = p.department_id
             LEFT JOIN positions pos ON pos.id = p.position_id
             LEFT JOIN academic_ranks r ON r.id = p.academic_rank_id
             WHERE p.id = ? AND p.deleted_at IS NULL',
            [$id]
        );
    }

    public function findByUser(int $userId): ?array
    {
        return $this->findBy('user_id', $userId);
    }
}
