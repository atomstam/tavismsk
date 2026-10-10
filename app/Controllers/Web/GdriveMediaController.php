<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Services\GoogleDriveService;

/**
 * Proxy รูปภาพจาก Google Drive — stream จาก cache, ไม่ lock session
 */
class GdriveMediaController extends Controller
{
    public function show(string $fileId): void
    {
        ini_set('display_errors', '0');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $fileId = trim($fileId);
        if ($fileId === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $fileId)) {
            http_response_code(400);
            exit;
        }

        $drive = new GoogleDriveService();
        if (!$drive->isConfigured()) {
            http_response_code(503);
            exit;
        }

        if ($drive->serveFile($fileId)) {
            exit;
        }

        http_response_code(404);
        exit;
    }
}
