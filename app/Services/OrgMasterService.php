<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Master data from Safety Center (clusters / areas / schools / prefixes).
 */
class OrgMasterService
{
    /** @return list<array{id:int,name:string,short_name:?string}> */
    public function clusters(): array
    {
        return db()->fetchAll('SELECT id, name, short_name FROM sc_clusters ORDER BY id');
    }

    /** @return array{staff:string,student:string,general:string} */
    public function memberTypeLabels(): array
    {
        return [
            'staff' => 'ครูและบุคลากรทางการศึกษา',
            'student' => 'นักเรียน',
            'general' => 'ทั่วไป',
        ];
    }

    /** @return list<string> */
    public function memberTypeKeys(): array
    {
        return ['staff', 'student', 'general'];
    }

    public function isMemberType(string $type): bool
    {
        return in_array($type, $this->memberTypeKeys(), true);
    }

    /** @return list<array{id:int,title:string,audience:string}> */
    public function prefixes(?string $memberType = null): array
    {
        if ($memberType !== null && $this->isMemberType($memberType)) {
            return db()->fetchAll(
                'SELECT id, title, audience FROM sc_prefixes
                 WHERE audience = ? OR audience = \'both\'
                 ORDER BY id',
                [$memberType]
            );
        }
        return db()->fetchAll('SELECT id, title, audience FROM sc_prefixes ORDER BY id');
    }

    /** @return list<array{id:int,title:string,kind:string}> */
    public function personalTypes(?string $kind = null): array
    {
        if ($kind === 'staff' || $kind === 'student') {
            return db()->fetchAll(
                'SELECT id, title, kind FROM sc_personal_types WHERE kind = ? ORDER BY id',
                [$kind]
            );
        }
        return db()->fetchAll('SELECT id, title, kind FROM sc_personal_types ORDER BY id');
    }

    public function personalTypeBelongs(int $id, string $kind): bool
    {
        if ($id <= 0 || ($kind !== 'staff' && $kind !== 'student')) {
            return false;
        }
        $row = db()->fetch(
            'SELECT id FROM sc_personal_types WHERE id = ? AND kind = ? LIMIT 1',
            [$id, $kind]
        );
        return $row !== null;
    }

    public function prefixAllowed(string $title, string $memberType): bool
    {
        if ($title === '' || !$this->isMemberType($memberType)) {
            return false;
        }
        $row = db()->fetch(
            'SELECT id FROM sc_prefixes
             WHERE title = ? AND (audience = ? OR audience = \'both\')
             LIMIT 1',
            [$title, $memberType]
        );
        return $row !== null;
    }

    /**
     * @return list<array{id:int,code:?string,name:string,cluster_code:?string,province:?string}>
     */
    public function areas(?string $clusterCode = null, string $q = '', int $limit = 200): array
    {
        $where = "org_type = 'area' AND deleted_at IS NULL AND status = 1";
        $params = [];
        if ($clusterCode !== null && $clusterCode !== '') {
            $where .= ' AND cluster_code = ?';
            $params[] = $clusterCode;
        }
        if ($q !== '') {
            $where .= ' AND (name LIKE ? OR code LIKE ? OR province LIKE ?)';
            $s = '%' . $q . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        $limit = max(1, min(500, $limit));
        return db()->fetchAll(
            "SELECT id, code, name, cluster_code, province FROM org_units WHERE {$where} ORDER BY name LIMIT {$limit}",
            $params
        );
    }

    /**
     * @return list<array{id:int,code:?string,name:string,parent_id:?int,province:?string}>
     */
    public function schools(?int $areaId = null, string $q = '', int $limit = 200): array
    {
        $where = "org_type = 'school' AND deleted_at IS NULL AND status = 1";
        $params = [];
        if ($areaId !== null && $areaId > 0) {
            $where .= ' AND parent_id = ?';
            $params[] = $areaId;
        }
        if ($q !== '') {
            $where .= ' AND (name LIKE ? OR code LIKE ? OR province LIKE ?)';
            $s = '%' . $q . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        $limit = max(1, min(500, $limit));
        return db()->fetchAll(
            "SELECT id, code, name, parent_id, province FROM org_units WHERE {$where} ORDER BY name LIMIT {$limit}",
            $params
        );
    }

    public function findUnit(int $id): ?array
    {
        return db()->fetch(
            'SELECT * FROM org_units WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$id]
        );
    }

    /** Breadcrumb labels for a school/area unit. */
    public function unitPath(int $orgUnitId): array
    {
        $unit = $this->findUnit($orgUnitId);
        if (!$unit) {
            return [];
        }
        $path = [$unit];
        $pid = (int) ($unit['parent_id'] ?? 0);
        $guard = 0;
        while ($pid > 0 && $guard++ < 6) {
            $p = $this->findUnit($pid);
            if (!$p) {
                break;
            }
            array_unshift($path, $p);
            $pid = (int) ($p['parent_id'] ?? 0);
        }
        return $path;
    }

    public function importMeta(): array
    {
        $rows = db()->fetchAll('SELECT meta_key, meta_value FROM sc_import_meta');
        $out = [];
        foreach ($rows as $r) {
            $out[$r['meta_key']] = $r['meta_value'];
        }
        return $out;
    }

    /** สพม.มหาสารคาม — the only area offered on public registration. */
    public function spmmskArea(): ?array
    {
        return db()->fetch(
            "SELECT id, code, name, cluster_code, province
             FROM org_units
             WHERE org_type = 'area' AND deleted_at IS NULL AND status = 1
               AND (code = '1000440001' OR external_id = '27' OR name = 'สพม.มหาสารคาม')
             ORDER BY id DESC
             LIMIT 1"
        );
    }

    public function isReady(): bool
    {
        try {
            $area = $this->spmmskArea();
            if (!$area) {
                return false;
            }
            $n = (int) (db()->fetch(
                "SELECT COUNT(*) AS c FROM org_units WHERE org_type='school' AND parent_id = ? AND deleted_at IS NULL AND status = 1",
                [(int) $area['id']]
            )['c'] ?? 0);

            return $n > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
