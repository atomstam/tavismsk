<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class LearningPlan extends Model
{
    protected string $table = 'learning_plans';
    protected array $fillable = [
        'plan_no', 'title', 'description', 'hours', 'instructor_type', 'instructor_label',
        'topic_group', 'objectives', 'sort_order', 'status',
    ];

    public function findByPlanNo(int $planNo): ?array
    {
        return $this->findBy('plan_no', $planNo);
    }

    public function allActive(): array
    {
        return $this->where('status = 1', [], 'plan_no ASC');
    }

    public function resources(int $planId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM learning_resources WHERE plan_id = ? AND status = 1 ORDER BY sort_order ASC, id ASC',
            [$planId]
        );
    }

    public function worksheets(int $planId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM worksheets WHERE plan_id = ? AND status = 1 ORDER BY sort_order ASC, id ASC',
            [$planId]
        );
    }
}
