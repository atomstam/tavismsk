<?php

declare(strict_types=1);

/**
 * ทดสอบอัปโหลดแบบแยกโฟลเดอร์รายบุคคล
 * ใช้: php scripts/test_gdrive_upload.php
 */

require dirname(__DIR__) . '/config/bootstrap.php';

echo "=== E-Portfolio Google Drive (per-person) ===\n";

$drive = new App\Services\GoogleDriveService();
$media = new App\Services\MediaStorageService();
if (!$drive->isConfigured()) {
    echo "❌ Google Drive ยังไม่พร้อม\n";
    exit(1);
}

$personKey = $media->resolvePersonKey([
    'person_key' => 'U_DEMO_ทดสอบระบบ',
]);
echo "Person folder: {$personKey}\n";
echo "Path pattern: {person}/{date}/{module}/images|files\n";

$imgTmp = STORAGE_PATH . '/cache/gdrive_test_' . uniqid('', true) . '.jpg';
$img = imagecreatetruecolor(200, 120);
imagejpeg($img, $imgTmp, 85);
imagedestroy($img);

$pdfTmp = STORAGE_PATH . '/cache/gdrive_test_' . uniqid('', true) . '.pdf';
file_put_contents($pdfTmp, "%PDF-1.4\n%%EOF\n");

$ok = true;
try {
    $pair = $drive->uploadModuleImagePair($imgTmp, 'portfolio', null, null, $personKey);
    echo "✅ Image → {$personKey}/{date}/portfolio/images\n";
    echo "  " . gdrive_ref_from_ids($pair['full'], $pair['thumb']) . "\n";
} catch (Throwable $e) {
    $ok = false;
    echo "❌ Image: " . gdrive_user_error_message($e) . "\n";
} finally {
    @unlink($imgTmp);
}

try {
    $pair = $drive->uploadModuleFile($pdfTmp, 'portfolio', 'demo.pdf', null, null, $personKey);
    echo "✅ File  → {$personKey}/{date}/portfolio/files\n";
    echo "  " . gdrive_ref_from_ids($pair['full'], $pair['thumb'] ?? '') . "\n";
} catch (Throwable $e) {
    $ok = false;
    echo "❌ File: " . gdrive_user_error_message($e) . "\n";
} finally {
    @unlink($pdfTmp);
}

exit($ok ? 0 : 1);
