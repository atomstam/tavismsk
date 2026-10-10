<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PortfolioItem extends Model
{
    protected string $table = 'portfolio_items';
    protected array $fillable = [
        'user_id', 'type', 'title', 'description', 'organization', 'location',
        'role_name', 'category', 'status_name', 'level_name', 'group_name',
        'instance_no', 'start_date', 'end_date', 'hours', 'academic_year', 'year_be',
        'cover_image', 'is_featured', 'is_public', 'views', 'rating',
        'extra', 'created_by', 'deleted_at',
    ];

    public function scopedQuery(string $type, ?int $userId, bool $seeAll): array
    {
        $where = 'deleted_at IS NULL AND type = ?';
        $params = [$type];
        if (!$seeAll && $userId) {
            $where .= ' AND user_id = ?';
            $params[] = $userId;
        }
        return [$where, $params];
    }

    public function stats(string $type, ?int $userId, bool $seeAll, ?int $year = null): array
    {
        [$where, $params] = $this->scopedQuery($type, $userId, $seeAll);
        if ($year) {
            $where .= ' AND academic_year = ?';
            $params[] = $year;
        }
        $count = $this->count($where, $params);
        $hours = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(hours),0) AS h FROM portfolio_items WHERE {$where}",
            $params
        )['h'] ?? 0);
        $org = $this->db->fetch(
            "SELECT organization, COUNT(*) AS c FROM portfolio_items
             WHERE {$where} AND organization IS NOT NULL AND organization <> ''
             GROUP BY organization ORDER BY c DESC LIMIT 1",
            $params
        );
        $cat = $this->db->fetch(
            "SELECT category, COUNT(*) AS c FROM portfolio_items
             WHERE {$where} AND category IS NOT NULL AND category <> ''
             GROUP BY category ORDER BY c DESC LIMIT 1",
            $params
        );
        $st = $this->db->fetch(
            "SELECT status_name, COUNT(*) AS c FROM portfolio_items
             WHERE {$where} AND status_name IS NOT NULL AND status_name <> ''
             GROUP BY status_name ORDER BY c DESC LIMIT 1",
            $params
        );
        $grp = $this->db->fetch(
            "SELECT group_name, COUNT(*) AS c FROM portfolio_items
             WHERE {$where} AND group_name IS NOT NULL AND group_name <> ''
             GROUP BY group_name ORDER BY c DESC LIMIT 1",
            $params
        );
        $yearLatest = $this->db->fetch(
            "SELECT MAX(COALESCE(year_be, academic_year)) AS y FROM portfolio_items WHERE {$where}",
            $params
        );

        return [
            'count' => $count,
            'hours' => $hours,
            'org' => $org['organization'] ?? '—',
            'category' => $cat['category'] ?? '—',
            'status' => $st['status_name'] ?? '—',
            'group' => $grp['group_name'] ?? '—',
            'year' => $yearLatest['y'] ?? '—',
        ];
    }

    public function files(int $itemId, ?string $kind = null): array
    {
        if ($kind) {
            return $this->db->fetchAll(
                'SELECT * FROM portfolio_files WHERE item_id = ? AND kind = ? ORDER BY sort_order, id',
                [$itemId, $kind]
            );
        }
        return $this->db->fetchAll(
            'SELECT * FROM portfolio_files WHERE item_id = ? ORDER BY sort_order, id',
            [$itemId]
        );
    }

    public function withUser(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT i.*, u.title AS user_title, u.first_name, u.last_name, u.role AS user_role
             FROM portfolio_items i
             JOIN users u ON u.id = i.user_id
             WHERE i.id = ? AND i.deleted_at IS NULL',
            [$id]
        );
    }

    public function dashboardCounts(?int $userId, bool $seeAll): array
    {
        $out = [];
        foreach (array_keys(portfolio_modules()) as $type) {
            [$where, $params] = $this->scopedQuery($type, $userId, $seeAll);
            $out[$type] = $this->count($where, $params);
        }
        return $out;
    }
}
