<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\GeoService;

/** Public JSON lookups for province → amphur → tumbon cascade. */
class GeoLookupController extends Controller
{
    private GeoService $geo;

    public function __construct()
    {
        $this->geo = new GeoService();
    }

    public function provinces(): void
    {
        $this->json(['data' => $this->geo->provinces()]);
    }

    public function amphurs(): void
    {
        $provinceId = (int) $this->input('province_id', 0);
        if ($provinceId <= 0) {
            $this->json(['data' => [], 'message' => 'ระบุ province_id']);
            return;
        }
        $this->json(['data' => $this->geo->amphursByProvinceId($provinceId)]);
    }

    public function tumbons(): void
    {
        $amphurId = (int) $this->input('amphur_id', 0);
        if ($amphurId <= 0) {
            $this->json(['data' => [], 'message' => 'ระบุ amphur_id']);
            return;
        }
        $this->json(['data' => $this->geo->tumbonsByAmphurId($amphurId)]);
    }
}
