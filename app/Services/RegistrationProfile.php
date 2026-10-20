<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Shared registration profile: member type, position, สพม.มหาสารคาม / อื่นๆ, and address.
 * Used by public register and by admin CRUD for users and personnel.
 */
class RegistrationProfile
{
    /** @return array<string, mixed> */
    public static function blank(): array
    {
        return [
            'member_type' => 'staff',
            'personal_type_id' => null,
            'position_text' => null,
            'org_unit_id' => null,
            'area_id' => null,
            'cluster_code' => null,
            'school_code' => null,
            'org_name' => null,
            'affiliation' => null,
            'org_level' => 'user',
            'province_id' => null,
            'amphur_id' => null,
            'tumbon_id' => null,
            'zipcode' => null,
        ];
    }

    public static function areaChoice(?array $row): string
    {
        if (!$row) {
            return '';
        }
        if ((int) ($row['org_unit_id'] ?? 0) > 0) {
            return 'spmmsk';
        }
        if (trim((string) ($row['org_name'] ?? '')) !== '' || trim((string) ($row['affiliation'] ?? '')) !== '') {
            return 'other';
        }

        return '';
    }

    /**
     * @return array{ok:bool, error?:string, data:array<string, mixed>}
     */
    public function collect(bool $required): array
    {
        $memberType = trim((string) ($_POST['member_type'] ?? ''));
        $personalTypeId = (int) ($_POST['personal_type_id'] ?? 0);
        $positionText = trim((string) ($_POST['position_text'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $areaChoice = trim((string) ($_POST['area_choice'] ?? ''));
        $schoolId = (int) ($_POST['org_unit_id'] ?? 0);
        $orgName = trim((string) ($_POST['org_name'] ?? ''));
        $affiliation = trim((string) ($_POST['affiliation'] ?? ''));
        $provinceId = (int) ($_POST['province_id'] ?? 0);
        $amphurId = (int) ($_POST['amphur_id'] ?? 0);
        $tumbonId = (int) ($_POST['tumbon_id'] ?? 0);

        $empty = $memberType === '' && $areaChoice === '' && $provinceId === 0
            && $personalTypeId === 0 && $positionText === '';
        if (!$required && $empty) {
            return ['ok' => true, 'data' => self::blank()];
        }

        $org = new OrgMasterService();
        if (!$org->isMemberType($memberType)) {
            return $this->fail('กรุณาเลือกประเภทผู้ใช้งาน');
        }
        if ($memberType === 'general') {
            $personalTypeId = 0;
            if ($positionText === '' || mb_strlen($positionText) > 120) {
                return $this->fail('กรุณากรอกตำแหน่ง');
            }
        } else {
            $positionText = '';
            if (!$org->personalTypeBelongs($personalTypeId, $memberType)) {
                return $this->fail($memberType === 'student' ? 'กรุณาเลือกระดับชั้น' : 'กรุณาเลือกตำแหน่ง');
            }
        }
        if ($title === '' || !$org->prefixAllowed($title, $memberType)) {
            return $this->fail('กรุณาเลือกคำนำหน้าให้ถูกต้องตามประเภทผู้ใช้งาน');
        }

        $geo = new GeoService();
        $geoError = $geo->validateSelection($provinceId, $amphurId, $tumbonId);
        if ($geoError !== null) {
            return $this->fail($geoError);
        }
        $tumbon = $geo->tumbon($tumbonId);
        $zipcode = ($tumbon['zipcode'] ?? '') !== ''
            ? (string) $tumbon['zipcode']
            : trim((string) ($_POST['zipcode'] ?? ''));

        $membership = (new OrgMembershipService())->resolvePublic($areaChoice, $schoolId, $orgName, $affiliation);
        if (!$membership['ok']) {
            return $this->fail($membership['error'] ?? 'กรุณาเลือกหน่วยงานให้ครบ');
        }

        return [
            'ok' => true,
            'data' => [
                'member_type' => $memberType,
                'personal_type_id' => $personalTypeId > 0 ? $personalTypeId : null,
                'position_text' => $positionText !== '' ? mb_substr($positionText, 0, 120) : null,
                'org_unit_id' => $membership['org_unit_id'],
                'area_id' => $membership['area_id'],
                'cluster_code' => $membership['cluster_code'],
                'school_code' => $membership['school_code'],
                'org_name' => $membership['org_name'],
                'affiliation' => $membership['affiliation'],
                'org_level' => $membership['org_level'],
                'province_id' => $provinceId,
                'amphur_id' => $amphurId,
                'tumbon_id' => $tumbonId,
                'zipcode' => $zipcode !== '' ? $zipcode : null,
            ],
        ];
    }

    /**
     * Dropdown data for the registration profile form and list filters.
     *
     * @return array<string, mixed>
     */
    public static function formSources(?array $row): array
    {
        $org = new OrgMasterService();
        $geo = new GeoService();
        $area = $org->spmmskArea();
        $areaId = (int) ($area['id'] ?? 0);
        $memberType = (string) ($row['member_type'] ?? 'staff');
        if (!$org->isMemberType($memberType)) {
            $memberType = 'staff';
        }
        $provinceId = (int) ($row['province_id'] ?? 0);
        $amphurId = (int) ($row['amphur_id'] ?? 0);
        $schoolId = (int) ($row['org_unit_id'] ?? 0);
        $schools = $areaId > 0 ? $org->schools($areaId, '', 300) : [];
        if ($schoolId > 0 && !self::listHasId($schools, $schoolId)) {
            $unit = $org->findUnit($schoolId);
            if ($unit && ($unit['org_type'] ?? '') === 'school') {
                array_unshift($schools, [
                    'id' => (int) $unit['id'],
                    'name' => (string) ($unit['name'] ?? ''),
                ]);
            }
        }

        return [
            'memberTypes' => $org->memberTypeLabels(),
            'personalTypes' => $org->personalTypes(),
            'prefixes' => $org->prefixes(),
            'schools' => $schools,
            'provinces' => $geo->provinces(),
            'amphurs' => $provinceId > 0 ? $geo->amphursByProvinceId($provinceId) : [],
            'tumbons' => $amphurId > 0 ? $geo->tumbonsByAmphurId($amphurId) : [],
            'profile' => [
                'member_type' => $memberType,
                'personal_type_id' => (int) ($row['personal_type_id'] ?? 0),
                'position_text' => (string) ($row['position_text'] ?? ''),
                'area_choice' => self::areaChoice($row),
                'org_unit_id' => $schoolId,
                'org_name' => (string) ($row['org_name'] ?? ''),
                'affiliation' => (string) ($row['affiliation'] ?? ''),
                'province_id' => $provinceId,
                'amphur_id' => $amphurId,
                'tumbon_id' => (int) ($row['tumbon_id'] ?? 0),
                'zipcode' => (string) ($row['zipcode'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'first_name' => (string) ($row['first_name'] ?? ''),
                'last_name' => (string) ($row['last_name'] ?? ''),
                'phone' => (string) ($row['phone'] ?? ''),
            ],
        ];
    }

    /**
     * Read-only registration fields, in the same order as the public register form.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function present(array $row, bool $unbound = false): array
    {
        $labels = (new OrgMasterService())->memberTypeLabels();
        $text = static function ($value): string {
            $value = trim((string) $value);
            return $value !== '' ? $value : '—';
        };
        $member = (string) ($row['member_type'] ?? '');
        $position = (string) (($row['position_name'] ?? '') !== '' ? $row['position_name'] : ($row['position_text'] ?? ''));
        $area = self::areaChoice($row);
        $areaLabel = match ($area) {
            'spmmsk' => 'สพม.มหาสารคาม',
            'other' => 'อื่นๆ',
            default => '—',
        };
        $school = (string) (($row['school_name'] ?? '') !== '' ? $row['school_name'] : ($row['org_name'] ?? ''));

        return [
            'unbound' => $unbound,
            'member_type' => $text($labels[$member] ?? $member),
            'position_label' => $member === 'student' ? 'ระดับชั้น' : 'ตำแหน่ง',
            'position' => $text($position),
            'title' => $text($row['title'] ?? ''),
            'first_name' => $text($row['first_name'] ?? ''),
            'last_name' => $text($row['last_name'] ?? ''),
            'phone' => $text($row['phone'] ?? ''),
            'area' => $unbound ? 'ไม่สังกัด' : $areaLabel,
            'school_label' => $area === 'other' ? 'หน่วยงาน' : 'โรงเรียน',
            'school' => $unbound ? '—' : $text($school),
            'affiliation' => $text($row['affiliation'] ?? ''),
            'show_affiliation' => !$unbound && $area === 'other',
            'province' => $text($row['province_name'] ?? ''),
            'amphur' => $text($row['amphur_name'] ?? ''),
            'tumbon' => $text($row['tumbon_name'] ?? ''),
            'zipcode' => $text($row['zipcode'] ?? ''),
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private static function listHasId(array $rows, int $id): bool
    {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{where:string, params:list<mixed>}
     */
    public static function listFilter(string $alias): array
    {
        $where = '';
        $params = [];
        $member = trim((string) ($_GET['member_type'] ?? ''));
        if (in_array($member, ['staff', 'student', 'general'], true)) {
            $where .= " AND {$alias}.member_type = ?";
            $params[] = $member;
        }
        $personalTypeId = (int) ($_GET['personal_type_id'] ?? 0);
        if ($personalTypeId > 0) {
            $where .= " AND {$alias}.personal_type_id = ?";
            $params[] = $personalTypeId;
        }
        $areaChoice = trim((string) ($_GET['area_choice'] ?? ''));
        $schoolId = (int) ($_GET['school_id'] ?? $_GET['org_unit_id'] ?? 0);
        if ($areaChoice === 'other') {
            $where .= " AND {$alias}.org_unit_id IS NULL AND NULLIF({$alias}.org_name, '') IS NOT NULL";
        } elseif ($schoolId > 0) {
            $where .= " AND {$alias}.org_unit_id = ?";
            $params[] = $schoolId;
        } elseif ($areaChoice === 'spmmsk') {
            $area = (new OrgMasterService())->spmmskArea();
            if ($area) {
                $where .= " AND {$alias}.area_id = ?";
                $params[] = (int) $area['id'];
            }
        }
        return ['where' => $where, 'params' => $params];
    }
}
