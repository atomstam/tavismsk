<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\OrgMasterService;

/** Public/authenticated JSON lookups for org cascade selects. */
class OrgLookupController extends Controller
{
    private OrgMasterService $org;

    public function __construct()
    {
        $this->org = new OrgMasterService();
    }

    public function clusters(): void
    {
        $this->json(['data' => $this->org->clusters()]);
    }

    public function areas(): void
    {
        $cluster = trim((string) $this->input('cluster', ''));
        $q = trim((string) $this->input('q', ''));
        $this->json(['data' => $this->org->areas($cluster !== '' ? $cluster : null, $q, 300)]);
    }

    public function schools(): void
    {
        $areaId = (int) $this->input('area_id', 0);
        $q = trim((string) $this->input('q', ''));
        if ($areaId <= 0 && $q === '') {
            $this->json(['data' => [], 'message' => 'ระบุ area_id หรือ q']);
            return;
        }
        $this->json(['data' => $this->org->schools($areaId > 0 ? $areaId : null, $q, 300)]);
    }

    public function meta(): void
    {
        $this->json([
            'prefixes' => $this->org->prefixes(),
            'personal_types' => $this->org->personalTypes(),
            'ready' => $this->org->isReady(),
            'import' => $this->org->importMeta(),
        ]);
    }
}
