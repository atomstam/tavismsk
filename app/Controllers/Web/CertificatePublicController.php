<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;

class CertificatePublicController extends Controller
{
    public function verify(): void
    {
        $code = trim((string) ($_GET['code'] ?? ''));
        $cert = null;
        if ($code !== '') {
            $cert = db()->fetch(
                "SELECT * FROM certificates WHERE verify_code = ? AND status = 'active'",
                [$code]
            );
        }
        $this->webView('web/certificate-verify', [
            'title' => 'ตรวจสอบเกียรติบัตร',
            'cert' => $cert,
            'code' => $code,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    /** @return array{today:int,month:int,all:int} */
    private function visitorStats(): array
    {
        try {
            return [
                'today' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE DATE(visited_at)=CURDATE()")['c'] ?? 0),
                'month' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE YEAR(visited_at)=YEAR(CURDATE()) AND MONTH(visited_at)=MONTH(CURDATE())")['c'] ?? 0),
                'all' => (int) (db()->fetch('SELECT COUNT(*) AS c FROM visitors')['c'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['today' => 0, 'month' => 0, 'all' => 0];
        }
    }
}
