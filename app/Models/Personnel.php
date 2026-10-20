<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Personnel extends Model
{
    protected string $table = 'personnel';
    protected array $fillable = [
        'user_id', 'code', 'id_card', 'title', 'first_name', 'last_name', 'gender',
        'birth_date', 'academic_rank_id', 'salary_step', 'salary',
        'level_name', 'department_id', 'work_group', 'phone', 'email', 'line_id',
        'facebook', 'address', 'education', 'work_history', 'training_history',
        'photo', 'attachment', 'sort_order', 'status', 'deleted_at',
        'member_type', 'personal_type_id', 'position_text',
        'org_unit_id', 'area_id', 'cluster_code', 'school_code', 'org_name', 'affiliation',
        'province_id', 'amphur_id', 'tumbon_id', 'zipcode',
    ];

    public function fullName(array $row): string
    {
        return trim(($row['title'] ?? '') . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    }

    public function withRelations(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT p.*, d.name AS department_name, r.name AS rank_name,
                    sch.name AS school_name, pt.title AS position_name, pv.name AS province_name,
                    am.name AS amphur_name, tb.name AS tumbon_name
             FROM personnel p
             LEFT JOIN departments d ON d.id = p.department_id
             LEFT JOIN academic_ranks r ON r.id = p.academic_rank_id
             LEFT JOIN org_units sch ON sch.id = p.org_unit_id
             LEFT JOIN sc_personal_types pt ON pt.id = p.personal_type_id
             LEFT JOIN province pv ON pv.id = p.province_id
             LEFT JOIN amphur am ON am.id = p.amphur_id
             LEFT JOIN tumbon tb ON tb.id = p.tumbon_id
             WHERE p.id = ? AND p.deleted_at IS NULL',
            [$id]
        );
    }

    public function findByUser(int $userId): ?array
    {
        return $this->findBy('user_id', $userId);
    }
}
