<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Org binding for role=user only (cluster → area → school).
 * admin / trainer ไม่ต้องสังกัดหน่วยงาน
 */
class OrgMembershipService
{
    private OrgMasterService $org;

    /** Roles that must not be tied to cluster/area/school. */
    public const STAFF_ROLES = ['admin', 'trainer'];

    public function __construct(?OrgMasterService $org = null)
    {
        $this->org = $org ?? new OrgMasterService();
    }

    public static function requiresOrg(string $role): bool
    {
        return !in_array($role, self::STAFF_ROLES, true);
    }

    /**
     * @return array{
     *   ok:bool,
     *   error?:string,
     *   org_unit_id:?int,
     *   area_id:?int,
     *   cluster_code:?string,
     *   school_code:?string,
     *   org_level:string
     * }
     */
    public function resolveFromInput(
        string $clusterCode,
        int $areaId,
        int $schoolId,
        string $role = 'user',
        string $orgLevelHint = ''
    ): array {
        $role = trim($role) !== '' ? trim($role) : 'user';
        $clusterCode = trim($clusterCode);

        // admin / วิทยากร — ไม่ผูก cluster / area / school
        if (!self::requiresOrg($role)) {
            return [
                'ok' => true,
                'org_unit_id' => null,
                'area_id' => null,
                'cluster_code' => null,
                'school_code' => null,
                'org_name' => null,
                'affiliation' => null,
                'org_level' => 'admin',
            ];
        }

        // ผู้ใช้งาน — ต้องมีครบ cluster + area + school
        if ($clusterCode === '') {
            return $this->fail('กรุณาเลือกเขตตรวจราชการ');
        }
        if ($areaId <= 0) {
            return $this->fail('กรุณาเลือก สพม. / เขตพื้นที่');
        }
        if ($schoolId <= 0) {
            return $this->fail('กรุณาเลือกโรงเรียน / หน่วยงาน');
        }

        $area = $this->org->findUnit($areaId);
        if (!$area || ($area['org_type'] ?? '') !== 'area' || (int) ($area['status'] ?? 0) !== 1) {
            return $this->fail('ไม่พบเขตพื้นที่ที่เลือก');
        }
        $areaCluster = (string) ($area['cluster_code'] ?? '');
        if ($areaCluster !== '' && $areaCluster !== $clusterCode) {
            return $this->fail('เขตพื้นที่ไม่อยู่ในเขตตรวจราชการที่เลือก');
        }

        $school = $this->org->findUnit($schoolId);
        if (!$school || ($school['org_type'] ?? '') !== 'school' || (int) ($school['status'] ?? 0) !== 1) {
            return $this->fail('ไม่พบโรงเรียนที่เลือก');
        }
        $parentAreaId = (int) ($school['parent_id'] ?? 0);
        if ($parentAreaId > 0 && $parentAreaId !== $areaId) {
            return $this->fail('โรงเรียนไม่อยู่ในเขตพื้นที่ที่เลือก');
        }

        $clus = $clusterCode !== ''
            ? $clusterCode
            : (string) ($school['cluster_code'] ?? $areaCluster);

        return [
            'ok' => true,
            'org_unit_id' => $schoolId,
            'area_id' => $areaId,
            'cluster_code' => $clus !== '' ? $clus : null,
            'school_code' => trim((string) ($school['code'] ?? $school['external_id'] ?? '')) ?: null,
            'org_name' => null,
            'affiliation' => null,
            'org_level' => 'user',
        ];
    }

    /**
     * Public registration: สพม.มหาสารคาม + school, or free-text หน่วยงาน/สังกัด.
     *
     * @return array{
     *   ok:bool,
     *   error?:string,
     *   org_unit_id:?int,
     *   area_id:?int,
     *   cluster_code:?string,
     *   school_code:?string,
     *   org_name:?string,
     *   affiliation:?string,
     *   org_level:string
     * }
     */
    public function resolvePublic(string $areaChoice, int $schoolId, string $orgName, string $affiliation): array
    {
        $areaChoice = trim($areaChoice);
        $orgName = trim($orgName);
        $affiliation = trim($affiliation);

        if ($areaChoice === 'other') {
            if ($orgName === '' || $affiliation === '') {
                return $this->fail('กรุณากรอกหน่วยงานและสังกัด');
            }
            if (mb_strlen($orgName) > 255 || mb_strlen($affiliation) > 255) {
                return $this->fail('หน่วยงานหรือสังกัดยาวเกินไป');
            }

            return [
                'ok' => true,
                'org_unit_id' => null,
                'area_id' => null,
                'cluster_code' => null,
                'school_code' => null,
                'org_name' => $orgName,
                'affiliation' => $affiliation,
                'org_level' => 'user',
            ];
        }

        if ($areaChoice !== 'spmmsk') {
            return $this->fail('กรุณาเลือกเขตพื้นที่');
        }

        $area = $this->org->spmmskArea();
        if (!$area) {
            return $this->fail('ไม่พบข้อมูล สพม.มหาสารคาม');
        }
        if ($schoolId <= 0) {
            return $this->fail('กรุณาเลือกโรงเรียน');
        }

        $school = $this->org->findUnit($schoolId);
        $areaId = (int) $area['id'];
        if (!$school || ($school['org_type'] ?? '') !== 'school' || (int) ($school['status'] ?? 0) !== 1) {
            return $this->fail('ไม่พบโรงเรียนที่เลือก');
        }
        if ((int) ($school['parent_id'] ?? 0) !== $areaId) {
            return $this->fail('โรงเรียนไม่อยู่ใน สพม.มหาสารคาม');
        }

        $clus = trim((string) ($area['cluster_code'] ?? ''));

        return [
            'ok' => true,
            'org_unit_id' => $schoolId,
            'area_id' => $areaId,
            'cluster_code' => $clus !== '' ? $clus : null,
            'school_code' => trim((string) ($school['code'] ?? $school['external_id'] ?? '')) ?: null,
            'org_name' => null,
            'affiliation' => null,
            'org_level' => 'user',
        ];
    }

    /** @return array{ok:bool,error:string,org_unit_id:null,area_id:null,cluster_code:null,school_code:null,org_level:string} */
    private function fail(string $error): array
    {
        return [
            'ok' => false,
            'error' => $error,
            'org_unit_id' => null,
            'area_id' => null,
            'cluster_code' => null,
            'school_code' => null,
            'org_name' => null,
            'affiliation' => null,
            'org_level' => 'user',
        ];
    }

    public function levelFromRole(string $role): string
    {
        return match ($role) {
            'admin', 'trainer' => 'admin',
            default => 'user',
        };
    }

    public function normalizeLevel(string $level): string
    {
        $level = strtolower(trim($level));
        return in_array($level, ['admin', 'cluster', 'area', 'school', 'user'], true) ? $level : 'user';
    }

    /** @deprecated org_level UI — kept for reports labels */
    public static function levelLabels(): array
    {
        return [
            'admin' => 'ไม่สังกัด (Admin/วิทยากร)',
            'user' => 'ผู้ใช้งาน (สังกัดโรงเรียน)',
            'cluster' => 'ระดับเขตตรวจราชการ',
            'area' => 'ระดับ สพม./เขตพื้นที่',
            'school' => 'ระดับโรงเรียน',
        ];
    }

    /**
     * @return array{where:string,params:list<mixed>}
     */
    public static function filterSql(string $cluster, int $areaId, int $schoolId, string $alias = 'u'): array
    {
        $where = '';
        $params = [];
        if ($schoolId > 0) {
            $where .= " AND {$alias}.org_unit_id = ?";
            $params[] = $schoolId;
        } elseif ($areaId > 0) {
            $where .= " AND {$alias}.area_id = ?";
            $params[] = $areaId;
        } elseif ($cluster !== '') {
            $where .= " AND {$alias}.cluster_code = ?";
            $params[] = $cluster;
        }
        return ['where' => $where, 'params' => $params];
    }
}
