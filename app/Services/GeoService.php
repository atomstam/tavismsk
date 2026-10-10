<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Thailand address cascade: province → amphur → tumbon → zipcode.
 * Geo tables use amphur.provinceID = province.code and tumbon.amphurID = amphur.amphur_code.
 */
class GeoService
{
    /** @return list<array{id:int,code:int,name:string}> */
    public function provinces(): array
    {
        $rows = db()->fetchAll(
            'SELECT id, code, TRIM(name) AS name
             FROM province
             WHERE TRIM(name) <> \'\'
             ORDER BY name ASC'
        );
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'code' => (int) $r['code'],
            'name' => (string) $r['name'],
        ], $rows);
    }

    /** @return list<array{id:int,code:string,name:string}> */
    public function amphursByProvinceId(int $provinceId): array
    {
        if ($provinceId <= 0) {
            return [];
        }
        $rows = db()->fetchAll(
            'SELECT a.id, a.amphur_code AS code, TRIM(a.name) AS name
             FROM amphur a
             INNER JOIN province p ON a.provinceID = p.code
             WHERE p.id = ?
               AND TRIM(a.name) <> \'\'
               AND TRIM(a.name) NOT LIKE \'*%\'
             ORDER BY a.amphur_code ASC, a.name ASC',
            [$provinceId]
        );
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'code' => (string) $r['code'],
            'name' => (string) $r['name'],
        ], $rows);
    }

    /** @return list<array{id:int,name:string,zipcode:string}> */
    public function tumbonsByAmphurId(int $amphurId): array
    {
        if ($amphurId <= 0) {
            return [];
        }
        $rows = db()->fetchAll(
            'SELECT t.id, TRIM(t.name) AS name, TRIM(t.zipcode) AS zipcode
             FROM tumbon t
             INNER JOIN amphur a ON t.amphurID = CAST(a.amphur_code AS UNSIGNED)
             WHERE a.id = ?
               AND TRIM(t.name) <> \'\'
             ORDER BY t.name ASC',
            [$amphurId]
        );
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'zipcode' => (string) $r['zipcode'],
        ], $rows);
    }

    /** @return array{id:int,name:string,zipcode:string,amphur_id:int,province_id:int}|null */
    public function tumbon(int $tumbonId): ?array
    {
        if ($tumbonId <= 0) {
            return null;
        }
        $row = db()->fetch(
            'SELECT t.id, TRIM(t.name) AS name, TRIM(t.zipcode) AS zipcode,
                    a.id AS amphur_id, p.id AS province_id
             FROM tumbon t
             INNER JOIN amphur a ON t.amphurID = CAST(a.amphur_code AS UNSIGNED)
             INNER JOIN province p ON a.provinceID = p.code
             WHERE t.id = ?
             LIMIT 1',
            [$tumbonId]
        );
        if (!$row) {
            return null;
        }
        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'zipcode' => (string) $row['zipcode'],
            'amphur_id' => (int) $row['amphur_id'],
            'province_id' => (int) $row['province_id'],
        ];
    }

    public function validateSelection(int $provinceId, int $amphurId, int $tumbonId): ?string
    {
        if ($provinceId <= 0 || $amphurId <= 0 || $tumbonId <= 0) {
            return 'กรุณาเลือกจังหวัด อำเภอ และตำบล';
        }
        $t = $this->tumbon($tumbonId);
        if (!$t || $t['amphur_id'] !== $amphurId || $t['province_id'] !== $provinceId) {
            return 'ข้อมูลจังหวัด อำเภอ ตำบล ไม่สัมพันธ์กัน';
        }
        return null;
    }
}
