<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ClubMember extends Model
{
    protected string $table = 'club_members';
    protected array $fillable = [
        'personnel_id', 'student_code', 'id_card', 'title', 'first_name', 'last_name',
        'gender', 'birth_date', 'grade_level', 'room', 'phone', 'email', 'photo',
        'academic_year', 'status', 'deleted_at',
    ];

    public function fullName(array $row): string
    {
        return trim(($row['title'] ?? '') . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    }

    public function active(string $orderBy = 'first_name ASC, last_name ASC'): array
    {
        return $this->where('deleted_at IS NULL', [], $orderBy);
    }
}
