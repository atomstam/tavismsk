<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Department extends Model
{
    protected string $table = 'departments';
    protected array $fillable = [
        'code', 'name', 'head_name', 'description', 'logo', 'color', 'sort_order', 'status', 'deleted_at',
    ];

    public function active(): array
    {
        return $this->where('deleted_at IS NULL AND status = 1', [], 'sort_order ASC, id ASC');
    }
}
