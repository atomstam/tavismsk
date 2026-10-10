<?php

declare(strict_types=1);

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use RuntimeException;

/**
 * อัปโหลด / ลบ / อ่านรูปภาพระบบบน Google Drive
 */
class GoogleDriveService
{
    private ?Drive $drive = null;

    private function credentialsPath(): string
    {
        $path = (string) config('google_drive', 'credentials', '');
        if ($path === '') {
            return '';
        }
        if (!str_starts_with($path, '/') && !preg_match('#^[A-Za-z]:[/\\\\]#', $path)) {
            $path = BASE_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/');
        }
        return $path;
    }

    public function isConfigured(): bool
    {
        if (!config('google_drive', 'enabled', true)) {
            return false;
        }
        $cred = $this->credentialsPath();
        return $cred !== '' && is_readable($cred);
    }

    /**
     * โฟลเดอร์ปลายทาง:
     *   root / {บุคคล} / {Y-m-d} / {module} [/ {entityId}] / images|files
     */
    public function getOrCreateKindFolder(
        string $moduleKey,
        string $kind,
        ?string $dateKey = null,
        ?string $entityKey = null,
        ?string $personKey = null
    ): string {
        $moduleKey = $this->normalizeModuleKey($moduleKey);
        $kind = $kind === 'files' ? 'files' : 'images';
        $kindName = $kind === 'files'
            ? (string) config('google_drive', 'files_folder_name', 'files')
            : (string) config('google_drive', 'images_folder_name', 'images');

        $person = $this->normalizePersonKey($personKey);
        $personFolderId = $this->getOrCreateSubFolder($this->rootFolderId(), $person);
        $dateFolderId = $this->getOrCreateSubFolder(
            $personFolderId,
            $this->normalizeDutyDate($dateKey ?? '')
        );
        $moduleFolderId = $this->getOrCreateSubFolder($dateFolderId, $moduleKey);

        $parentId = $moduleFolderId;
        $entity = $this->normalizeEntityKey($entityKey);
        if ($entity !== '') {
            $parentId = $this->getOrCreateSubFolder($moduleFolderId, $entity);
        }

        return $this->getOrCreateSubFolder($parentId, $kindName !== '' ? $kindName : $kind);
    }

    public function rootFolderId(): string
    {
        $rootId = trim((string) config('google_drive', 'root_folder_id', ''));
        if ($rootId === '') {
            throw new RuntimeException('ไม่ได้ตั้งค่า GOOGLE_DRIVE_FOLDER_ID');
        }
        return $rootId;
    }

    public function normalizePersonKey(?string $personKey): string
    {
        $personKey = trim((string) $personKey);
        if ($personKey === '') {
            $personKey = (string) config('google_drive', 'system_folder_name', '_system');
        }
        $safe = preg_replace('/[\\\\\\/:*?"<>|]+/u', '_', $personKey) ?? $personKey;
        $safe = trim(preg_replace('/\s+/u', '_', $safe) ?? $safe, '._ ');
        $safe = preg_replace('/_+/u', '_', $safe) ?? $safe;
        if ($safe === '') {
            $safe = (string) config('google_drive', 'system_folder_name', '_system');
        }
        return mb_substr($safe, 0, 100);
    }

    private function normalizeEntityKey(?string $entityKey): string
    {
        $entityKey = trim((string) $entityKey);
        if ($entityKey === '') {
            return '';
        }
        $safe = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $entityKey) ?: '';
        return $safe;
    }

    /**
     * อัปโหลดรูปเต็ม + thumbnail
     * → บุคคล / วันที่ / module [/ entity] / images / (+ thb)
     *
     * @return array{full: string, thumb: string}
     */
    public function uploadModuleImagePair(
        string $localPath,
        string $moduleKey,
        ?string $dateKey = null,
        ?string $entityKey = null,
        ?string $personKey = null
    ): array {
        if (!is_file($localPath)) {
            throw new RuntimeException('ไม่พบไฟล์รูปภาพสำหรับอัปโหลด');
        }

        $moduleKey = $this->normalizeModuleKey($moduleKey);
        $imagesFolderId = $this->getOrCreateKindFolder($moduleKey, 'images', $dateKey, $entityKey, $personKey);

        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION)) ?: 'jpg';
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $baseName = $moduleKey . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));

        $fullId = $this->uploadFileToFolder($localPath, $imagesFolderId, $baseName . '.' . $ext);

        $thumbFolderId = $this->getOrCreateSubFolder(
            $imagesFolderId,
            (string) config('google_drive', 'thumb_folder_name', 'thb')
        );
        $thumbPath = $this->createThumbnailFile($localPath);
        try {
            $thumbId = $this->uploadFileToFolder($thumbPath, $thumbFolderId, $baseName . '_thb.jpg');
        } finally {
            if (is_file($thumbPath)) {
                @unlink($thumbPath);
            }
        }

        $person = $this->normalizePersonKey($personKey);
        $this->log("uploaded image {$person}/{$moduleKey}/{$baseName} full={$fullId} thumb={$thumbId}");
        return ['full' => $fullId, 'thumb' => $thumbId];
    }

    /**
     * อัปโหลดไฟล์เอกสาร → บุคคล / วันที่ / module [/ entity] / files
     *
     * @return array{full: string, thumb: string}
     */
    public function uploadModuleFile(
        string $localPath,
        string $moduleKey,
        ?string $originalName = null,
        ?string $dateKey = null,
        ?string $entityKey = null,
        ?string $personKey = null
    ): array {
        if (!is_file($localPath)) {
            throw new RuntimeException('ไม่พบไฟล์สำหรับอัปโหลด');
        }

        $moduleKey = $this->normalizeModuleKey($moduleKey);
        $filesFolderId = $this->getOrCreateKindFolder($moduleKey, 'files', $dateKey, $entityKey, $personKey);

        $ext = strtolower(pathinfo($originalName ?: $localPath, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION)) ?: 'bin';
        }
        $safeBase = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', pathinfo((string) ($originalName ?: 'file'), PATHINFO_FILENAME)) ?: 'file';
        $safeBase = substr($safeBase, 0, 40);
        $fileName = $moduleKey . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . '.' . $ext;

        $fullId = $this->uploadFileToFolder($localPath, $filesFolderId, $fileName);
        $person = $this->normalizePersonKey($personKey);
        $this->log("uploaded file {$person}/{$moduleKey}/{$fileName} id={$fullId}");

        return ['full' => $fullId, 'thumb' => ''];
    }

    /** เข้ากันได้กับ reportduty — อัปโหลดใต้โฟลเดอร์วันที่โดยตรง */
    public function uploadDutyPhotoPair(string $localPath, string $dutyDate): array
    {
        if (!is_file($localPath)) {
            throw new RuntimeException('ไม่พบไฟล์รูปภาพสำหรับอัปโหลด');
        }

        $dateFolderId = $this->getOrCreateDateFolder($dutyDate);
        $baseName = 'media_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));

        $fullId = $this->uploadFileToFolder($localPath, $dateFolderId, $baseName . '.jpg');

        $thumbFolderId = $this->getOrCreateSubFolder(
            $dateFolderId,
            (string) config('google_drive', 'thumb_folder_name', 'thb')
        );
        $thumbPath = $this->createThumbnailFile($localPath);
        try {
            $thumbId = $this->uploadFileToFolder($thumbPath, $thumbFolderId, $baseName . '_thb.jpg');
        } finally {
            if (is_file($thumbPath)) {
                @unlink($thumbPath);
            }
        }

        $this->log("uploaded pair {$baseName} full={$fullId} thumb={$thumbId}");
        return ['full' => $fullId, 'thumb' => $thumbId];
    }

    public function getOrCreateModuleFolder(string $moduleKey): string
    {
        $rootId = (string) config('google_drive', 'root_folder_id', '');
        if ($rootId === '') {
            throw new RuntimeException('ไม่ได้ตั้งค่า GOOGLE_DRIVE_FOLDER_ID');
        }
        return $this->getOrCreateSubFolder($rootId, $this->normalizeModuleKey($moduleKey));
    }

    private function normalizeModuleKey(string $moduleKey): string
    {
        $moduleKey = strtolower(trim($moduleKey));
        $moduleKey = preg_replace('/[^a-z0-9_\-]+/', '_', $moduleKey) ?: 'misc';
        return $moduleKey !== '' ? $moduleKey : 'misc';
    }

    /**
     * ส่งไฟล์ให้ browser (cache local → readfile, ไม่โหลดทั้งไฟล์ใน memory)
     */
    public function serveFile(string $fileId): bool
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            return false;
        }

        $cached = $this->cachedFilePath($fileId);
        if ($cached !== '' && is_file($cached)) {
            $this->emitFileResponse($cached);
            return true;
        }

        try {
            $file = $this->downloadFile($fileId);
        } catch (\Throwable) {
            return false;
        }

        if ($cached !== '' && is_file($cached)) {
            $this->emitFileResponse($cached);
            return true;
        }

        if ($file['content'] !== '') {
            $this->emitContentResponse($file['content'], $file['mime']);
            return true;
        }

        return false;
    }

    private function emitContentResponse(string $content, string $mime): void
    {
        $mime = $mime !== '' ? $mime : 'image/jpeg';
        $etag = '"' . md5($content) . '"';

        if (
            isset($_SERVER['HTTP_IF_NONE_MATCH'])
            && trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) === $etag
        ) {
            http_response_code(304);
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) strlen($content));
        header('Cache-Control: public, max-age=86400, must-revalidate');
        header('ETag: ' . $etag);
        header('X-Content-Type-Options: nosniff');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        echo $content;
    }

    private function emitFileResponse(string $path): void
    {
        $mime = mime_content_type($path) ?: 'image/jpeg';
        $size = filesize($path);
        $etag = '"' . md5_file($path) . '"';

        if (
            isset($_SERVER['HTTP_IF_NONE_MATCH'])
            && trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) === $etag
        ) {
            http_response_code(304);
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) $size);
        header('Cache-Control: public, max-age=86400, must-revalidate');
        header('ETag: ' . $etag);
        header('X-Content-Type-Options: nosniff');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $fp = fopen($path, 'rb');
        if ($fp === false) {
            throw new RuntimeException('อ่านไฟล์ cache ไม่สำเร็จ');
        }
        fpassthru($fp);
        fclose($fp);
    }

    /**
     * ดึงเนื้อหาไฟล์จาก Drive (สำหรับ proxy แสดงรูปในเว็บ)
     *
     * @return array{content: string, mime: string}
     */
    public function downloadFile(string $fileId): array
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            throw new RuntimeException('ไม่ระบุไฟล์ Google Drive');
        }

        $cached = $this->cachedFilePath($fileId);
        if ($cached !== '' && is_file($cached)) {
            $content = (string) file_get_contents($cached);
            $mime = mime_content_type($cached) ?: 'image/jpeg';
            return ['content' => $content, 'mime' => $mime];
        }

        $lockFp = null;
        $lockPath = ($cached !== '' ? $cached : STORAGE_PATH . '/cache/gdrive_media') . '.lock';
        $lockFp = @fopen($lockPath, 'c');
        if ($lockFp) {
            $deadline = time() + 20;
            while (!flock($lockFp, LOCK_EX | LOCK_NB)) {
                if (time() >= $deadline) {
                    fclose($lockFp);
                    throw new RuntimeException('รอโหลดรูปจาก Google Drive นานเกินไป');
                }
                usleep(100000);
                if ($cached !== '' && is_file($cached)) {
                    flock($lockFp, LOCK_UN);
                    fclose($lockFp);
                    $content = (string) file_get_contents($cached);
                    $mime = mime_content_type($cached) ?: 'image/jpeg';
                    return ['content' => $content, 'mime' => $mime];
                }
            }
        }

        try {
            if ($cached !== '' && is_file($cached)) {
                $content = (string) file_get_contents($cached);
                $mime = mime_content_type($cached) ?: 'image/jpeg';
                return ['content' => $content, 'mime' => $mime];
            }

            $drive = $this->client();

            /** @var \Psr\Http\Message\ResponseInterface $response */
            $response = $drive->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true,
            ]);
            $content = (string) $response->getBody()->getContents();
            if ($content === '') {
                throw new RuntimeException('อ่านไฟล์ Google Drive ไม่สำเร็จ');
            }

            $mime = 'image/jpeg';
            if ($response->hasHeader('Content-Type')) {
                $mime = trim(explode(';', (string) $response->getHeaderLine('Content-Type'))[0]);
            }
            if ($mime === '' || $mime === 'application/octet-stream') {
                $mime = 'image/jpeg';
            }

            $this->writeCachedFile($fileId, $content);
            return ['content' => $content, 'mime' => $mime];
        } finally {
            if ($lockFp) {
                flock($lockFp, LOCK_UN);
                fclose($lockFp);
                if (is_file($lockPath)) {
                    @unlink($lockPath);
                }
            }
        }
    }

    public function cachedFilePath(string $fileId): string
    {
        $fileId = trim($fileId);
        if ($fileId === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $fileId)) {
            return '';
        }
        return STORAGE_PATH . '/cache/gdrive_media/' . $fileId . '.bin';
    }

    public function fileWebUrl(string $fileId): string
    {
        $fileId = trim($fileId);
        return $fileId !== '' ? 'https://drive.google.com/file/d/' . $fileId . '/view' : '';
    }

    public function folderWebUrl(string $folderId): string
    {
        $folderId = trim($folderId);
        return $folderId !== '' ? 'https://drive.google.com/drive/folders/' . $folderId : '';
    }

    private function writeCachedFile(string $fileId, string $content): void
    {
        $path = $this->cachedFilePath($fileId);
        if ($path === '') {
            return;
        }
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (is_dir($dir) && !is_writable($dir)) {
            @chmod($dir, 0775);
        }
        if (@file_put_contents($path, $content, LOCK_EX) === false) {
            $this->log('cache write failed ' . $fileId . ' path=' . $path);
        }
    }

    public function deleteFile(string $fileId): bool
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            return false;
        }

        $this->purgeCachedMedia($fileId);

        if (!$this->isConfigured()) {
            $this->log('delete skipped (not configured) ' . $fileId);
            return false;
        }

        try {
            $this->client()->files->delete($fileId, ['supportsAllDrives' => true]);
            $this->log('deleted ' . $fileId);
            return true;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '404') || str_contains(strtolower($msg), 'not found')) {
                $this->log('delete already removed ' . $fileId);
                return true;
            }
            $this->log('delete failed ' . $fileId . ': ' . $msg);
            return false;
        }
    }

    /** ลบไฟล์ cache รูป proxy หลังลบจาก Drive */
    public function purgeCachedMedia(string $fileId): void
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            return;
        }
        $path = $this->cachedFilePath($fileId);
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
        $lock = ($path !== '' ? $path : STORAGE_PATH . '/cache/gdrive_media/' . $fileId . '.bin') . '.lock';
        if (is_file($lock)) {
            @unlink($lock);
        }
    }

    public function getOrCreateDateFolder(string $dutyDate): string
    {
        $rootId = (string) config('google_drive', 'root_folder_id', '');
        if ($rootId === '') {
            throw new RuntimeException('ไม่ได้ตั้งค่า GOOGLE_DRIVE_FOLDER_ID');
        }

        $dateKey = $this->normalizeDutyDate($dutyDate);
        return $this->getOrCreateSubFolder($rootId, $dateKey);
    }

    public function getOrCreateSubFolder(string $parentId, string $folderName): string
    {
        $folderName = trim($folderName);
        if ($parentId === '' || $folderName === '') {
            throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ Google Drive');
        }

        $cacheKey = $parentId . '/' . $folderName;
        $cached = $this->readFolderCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $drive = $this->client();
        $q = sprintf(
            "mimeType='application/vnd.google-apps.folder' and '%s' in parents and name='%s' and trashed=false",
            str_replace("'", "\\'", $parentId),
            str_replace("'", "\\'", $folderName)
        );

        $list = $drive->files->listFiles([
            'q' => $q,
            'fields' => 'files(id)',
            'pageSize' => 1,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
            'corpora' => 'allDrives',
        ]);

        $files = $list->getFiles();
        if ($files && count($files) > 0) {
            $folderId = (string) $files[0]->getId();
            $this->writeFolderCache($cacheKey, $folderId);
            return $folderId;
        }

        $folderMeta = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);
        $folder = $drive->files->create($folderMeta, [
            'fields' => 'id',
            'supportsAllDrives' => true,
        ]);
        $folderId = (string) ($folder->getId() ?? '');
        if ($folderId === '') {
            throw new RuntimeException('สร้างโฟลเดอร์ Google Drive ไม่สำเร็จ');
        }

        $this->writeFolderCache($cacheKey, $folderId);
        $this->log("created folder {$folderName} => {$folderId}");
        return $folderId;
    }

    private function uploadFileToFolder(string $localPath, string $folderId, string $filename): string
    {
        $drive = $this->client();
        $meta = new DriveFile([
            'name' => $filename,
            'parents' => [$folderId],
        ]);

        $mime = mime_content_type($localPath) ?: 'image/jpeg';
        $created = $drive->files->create($meta, [
            'data' => file_get_contents($localPath),
            'mimeType' => $mime,
            'uploadType' => 'multipart',
            'fields' => 'id',
            'supportsAllDrives' => true,
        ]);

        $fileId = (string) ($created->getId() ?? '');
        if ($fileId === '') {
            throw new RuntimeException('อัปโหลด Google Drive ไม่สำเร็จ');
        }

        $this->makePublicRead($fileId);
        return $fileId;
    }

    private function createThumbnailFile(string $sourcePath): string
    {
        $maxWidth = max(120, (int) config('google_drive', 'thumb_max_width', 400));
        $info = @getimagesize($sourcePath);
        if ($info === false) {
            throw new RuntimeException('อ่านไฟล์รูปภาพไม่สำเร็จ');
        }

        $srcW = (int) $info[0];
        $srcH = (int) $info[1];
        $mime = (string) ($info['mime'] ?? 'image/jpeg');

        $src = match ($mime) {
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            'image/gif' => @imagecreatefromgif($sourcePath),
            default => @imagecreatefromjpeg($sourcePath),
        };

        if (!$src) {
            throw new RuntimeException('สร้าง thumbnail ไม่สำเร็จ');
        }

        $newW = min($maxWidth, $srcW);
        $newH = (int) round($srcH * ($newW / $srcW));
        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        $dir = STORAGE_PATH . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $tmp = $dir . '/gdrive_thumb_' . uniqid('', true) . '.jpg';
        imagejpeg($dst, $tmp, 85);
        imagedestroy($src);
        imagedestroy($dst);

        return $tmp;
    }

    private function makePublicRead(string $fileId): void
    {
        try {
            $perm = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $this->client()->permissions->create($fileId, $perm, [
                'supportsAllDrives' => true,
            ]);
        } catch (\Throwable $e) {
            // โฟลเดอร์แชร์สาธารณะแล้วอาจไม่จำเป็น
        }
    }

    private function client(): Drive
    {
        if ($this->drive !== null) {
            return $this->drive;
        }

        $credPath = $this->credentialsPath();
        if ($credPath === '' || !is_readable($credPath)) {
            throw new RuntimeException(
                'ไม่พบไฟล์ credentials Google Drive — ตั้งค่า GOOGLE_DRIVE_CREDENTIALS ใน .env'
            );
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credPath);
        $client->setScopes([Drive::DRIVE]);
        $client->setApplicationName((string) config('app', 'short_name', 'TAVIS'));

        $impersonate = trim((string) config('google_drive', 'impersonate_user', ''));
        if ($impersonate !== '') {
            $client->setSubject($impersonate);
        }

        $this->drive = new Drive($client);
        return $this->drive;
    }

    private function normalizeDutyDate(string $dutyDate): string
    {
        $dutyDate = trim($dutyDate);
        if ($dutyDate === '') {
            return date((string) config('google_drive', 'date_folder_format', 'Y-m-d'));
        }
        $ts = strtotime($dutyDate);
        if ($ts === false) {
            throw new RuntimeException('วันที่รายงานหน้าที่ไม่ถูกต้อง');
        }
        return date((string) config('google_drive', 'date_folder_format', 'Y-m-d'), $ts);
    }

    /** @return array<string, string> */
    private function loadFolderCache(): array
    {
        $file = STORAGE_PATH . '/cache/gdrive_date_folders.json';
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    private function readFolderCache(string $cacheKey): ?string
    {
        $cache = $this->loadFolderCache();
        $id = $cache[$cacheKey] ?? null;
        return is_string($id) && $id !== '' ? $id : null;
    }

    private function writeFolderCache(string $cacheKey, string $folderId): void
    {
        $dir = STORAGE_PATH . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $cache = $this->loadFolderCache();
        $cache[$cacheKey] = $folderId;
        @file_put_contents(
            $dir . '/gdrive_date_folders.json',
            json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    private function log(string $message): void
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            return;
        }
        @file_put_contents(
            $dir . '/gdrive.log',
            date('c') . ' ' . $message . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
