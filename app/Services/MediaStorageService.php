<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * จัดเก็บสื่อระบบทั้งหมดบน Google Drive (รูป + เอกสาร) หรือ local สำรอง
 *
 * โครงสร้าง Drive (สำรองรายบุคคล):
 *   root / {บุคคล} / {Y-m-d} / {module} [/ {entityId}] / images|files / ...
 *
 * ตัวอย่าง:
 *   U5_สมชาย_ใจดี / 2026-09-10 / portfolio / images / ...
 *   P00012_นายสมชาย_ใจดี / 2026-09-10 / gallery / 42 / images / ...
 *   _system / 2026-09-10 / logos / images / ...
 */
class MediaStorageService
{
    private GoogleDriveService $drive;

    /** @var array<string, string> crop type / folder → module folder บน Drive */
    private const MODULE_MAP = [
        'photo' => 'personnel',
        'avatar' => 'avatars',
        'logo' => 'logos',
        'site_logo' => 'logos',
        'favicon' => 'logos',
        'cover' => 'covers',
        'gallery' => 'gallery',
        'portfolio' => 'portfolio',
        'personnel' => 'personnel',
        'tickets' => 'tickets',
        'documents' => 'documents',
        'temp' => 'misc',
    ];

    /** โมดูลของระบบโรงเรียน (ไม่ผูกบุคลากร) */
    private const SYSTEM_MODULES = ['logos', 'logo', 'site_logo', 'favicon'];

    private const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct()
    {
        $this->drive = new GoogleDriveService();
    }

    public function usesDrive(): bool
    {
        if (!filter_var(config('google_drive', 'enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }
        return $this->drive->isConfigured();
    }

    public function moduleFor(string $typeOrFolder): string
    {
        $key = strtolower(trim($typeOrFolder));
        return self::MODULE_MAP[$key] ?? ($key !== '' ? $key : 'misc');
    }

    public function isImageExtension(string $ext): bool
    {
        return in_array(strtolower($ext), self::IMAGE_EXTS, true);
    }

    public function isImagePath(string $path): bool
    {
        if (media_is_gdrive($path)) {
            return gdrive_thumb_file_id($path) !== '' || gdrive_file_id($path) !== '';
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $this->isImageExtension($ext);
    }

    /**
     * สร้างชื่อโฟลเดอร์บุคคลสำหรับ Google Drive
     *
     * @param array{
     *   user_id?: int|null,
     *   personnel_id?: int|null,
     *   person_key?: string|null,
     *   system?: bool
     * } $options
     */
    public function resolvePersonKey(array $options = [], ?string $module = null): string
    {
        if (!empty($options['person_key'])) {
            return $this->drive->normalizePersonKey((string) $options['person_key']);
        }
        if (!empty($options['system']) || ($module !== null && $this->isSystemModule($module))) {
            return $this->drive->normalizePersonKey(
                (string) config('google_drive', 'system_folder_name', '_system')
            );
        }

        $personnelId = (int) ($options['personnel_id'] ?? 0);
        $userId = (int) ($options['user_id'] ?? 0);

        if ($personnelId <= 0 && $userId > 0) {
            $user = db()->fetch(
                'SELECT id, title, first_name, last_name, personnel_id FROM users WHERE id = ?',
                [$userId]
            );
            if ($user) {
                $personnelId = (int) ($user['personnel_id'] ?? 0);
                if ($personnelId <= 0) {
                    return $this->drive->normalizePersonKey($this->formatUserFolder($user));
                }
            }
        }

        if ($personnelId > 0) {
            $person = db()->fetch(
                'SELECT id, code, title, first_name, last_name, user_id FROM personnel WHERE id = ?',
                [$personnelId]
            );
            if ($person) {
                return $this->drive->normalizePersonKey($this->formatPersonnelFolder($person));
            }
        }

        if ($userId <= 0 && function_exists('auth') && auth()->check()) {
            $userId = (int) auth()->id();
            return $this->resolvePersonKey(['user_id' => $userId], $module);
        }

        return $this->drive->normalizePersonKey(
            (string) config('google_drive', 'system_folder_name', '_system')
        );
    }

    public function isSystemModule(string $module): bool
    {
        $key = strtolower(trim($module));
        $mapped = $this->moduleFor($key);
        return in_array($key, self::SYSTEM_MODULES, true)
            || in_array($mapped, self::SYSTEM_MODULES, true);
    }

    /** @param array<string,mixed> $person */
    private function formatPersonnelFolder(array $person): string
    {
        $code = trim((string) ($person['code'] ?? ''));
        if ($code === '') {
            $code = 'P' . str_pad((string) (int) ($person['id'] ?? 0), 4, '0', STR_PAD_LEFT);
        }
        $name = trim(
            (string) ($person['title'] ?? '')
            . (string) ($person['first_name'] ?? '')
            . '_'
            . (string) ($person['last_name'] ?? '')
        );
        $name = trim($name, '_');
        return $name !== '' ? ($code . '_' . $name) : $code;
    }

    /** @param array<string,mixed> $user */
    private function formatUserFolder(array $user): string
    {
        $id = (int) ($user['id'] ?? 0);
        $name = trim(
            (string) ($user['title'] ?? '')
            . (string) ($user['first_name'] ?? '')
            . '_'
            . (string) ($user['last_name'] ?? '')
        );
        $name = trim($name, '_');
        return $name !== '' ? ('U' . $id . '_' . $name) : ('U' . $id);
    }

    /**
     * บันทึกจาก crop token → คืน gdrive:full:thumb หรือ uploads/...
     *
     * @param array{user_id?:int|null,personnel_id?:int|null,person_key?:string|null,system?:bool,date?:string|null,entity_id?:int|string|null} $options
     */
    public function commitCropToken(string $type, string $token, ?string $oldPath = null, array $options = []): string
    {
        $token = trim($token);
        if ($token === '') {
            throw new RuntimeException('โทเคนรูปภาพไม่ถูกต้อง');
        }

        $crop = new ImageCropService($type);
        $module = $this->moduleFor($type);

        if ($this->usesDrive()) {
            $croppedPath = $crop->getCroppedOutputPath($token);
            try {
                $personKey = $this->resolvePersonKey($options, $module);
                $entityKey = isset($options['entity_id']) && $options['entity_id'] !== null && $options['entity_id'] !== ''
                    ? (string) $options['entity_id']
                    : null;
                $dateKey = isset($options['date']) ? (string) $options['date'] : null;
                $pair = $this->drive->uploadModuleImagePair(
                    $croppedPath,
                    $module,
                    $dateKey,
                    $entityKey,
                    $personKey
                );
                $crop->deleteTokenFiles($token);
                if ($oldPath) {
                    $this->delete($oldPath);
                }
                return gdrive_ref_from_ids($pair['full'], $pair['thumb']);
            } catch (\Throwable $e) {
                $this->logError('crop-upload', $token, $e);
                throw new RuntimeException(gdrive_user_error_message($e), 0, $e);
            }
        }

        return $crop->commitToken($token, $oldPath);
    }

    /**
     * อัปโหลดทุกประเภท (รูป → images, เอกสาร/วิดีโอ → files) เมื่อ Drive พร้อม
     *
     * @param array{user_id?:int|null,personnel_id?:int|null,person_key?:string|null,system?:bool,entity_id?:int|string|null,date?:string|null} $options
     * @return array{file_name:string,original_name:string,file_path:string,file_type:string,file_size:int,extension:string}
     */
    public function upload(array $file, string $folder = 'temp', array $options = []): array
    {
        $uploader = new UploadService();
        $saved = $uploader->upload($file, $folder);

        if (!$this->usesDrive()) {
            return $saved;
        }

        $localFull = $this->localFullPath($saved['file_path']);
        if (!is_file($localFull)) {
            return $saved;
        }

        $module = $this->moduleFor($folder);
        $ext = strtolower((string) ($saved['extension'] ?? pathinfo($saved['file_path'], PATHINFO_EXTENSION)));
        $entityKey = isset($options['entity_id']) && $options['entity_id'] !== null && $options['entity_id'] !== ''
            ? (string) $options['entity_id']
            : null;
        $dateKey = isset($options['date']) ? (string) $options['date'] : null;
        $personKey = $this->resolvePersonKey($options, $module);

        try {
            if ($this->isImageExtension($ext)) {
                $pair = $this->drive->uploadModuleImagePair(
                    $localFull,
                    $module,
                    $dateKey,
                    $entityKey,
                    $personKey
                );
            } else {
                $pair = $this->drive->uploadModuleFile(
                    $localFull,
                    $module,
                    (string) ($saved['original_name'] ?? $saved['file_name']),
                    $dateKey,
                    $entityKey,
                    $personKey
                );
            }
            $uploader->deleteLocalOnly($saved['file_path']);
            $ref = gdrive_ref_from_ids($pair['full'], $pair['thumb'] ?? '');

            return [
                'file_name' => $saved['file_name'],
                'original_name' => $saved['original_name'],
                'file_path' => $ref,
                'file_type' => $saved['file_type'],
                'file_size' => $saved['file_size'],
                'extension' => $saved['extension'],
            ];
        } catch (\Throwable $e) {
            $this->logError('file-upload', $saved['file_path'], $e);
            throw new RuntimeException(gdrive_user_error_message($e), 0, $e);
        }
    }

    /** ลบสื่อ (Drive + local) */
    public function delete(?string $ref): bool
    {
        $ref = trim((string) $ref);
        if ($ref === '') {
            return true;
        }

        if (media_is_gdrive($ref)) {
            if (!$this->usesDrive()) {
                $this->logDelete('skip not configured: ' . $ref);
                return false;
            }

            $fullId = gdrive_file_id($ref);
            $thumbId = gdrive_thumb_file_id($ref);
            $ok = true;

            if ($fullId !== '') {
                $ok = $this->drive->deleteFile($fullId) && $ok;
            }
            if ($thumbId !== '' && $thumbId !== $fullId) {
                $ok = $this->drive->deleteFile($thumbId) && $ok;
            }

            if (!$ok) {
                $this->logDelete('failed gdrive ref=' . $ref);
            }

            return $ok;
        }

        (new UploadService())->deleteLocalOnly($ref);
        return true;
    }

    private function localFullPath(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if (str_starts_with($relative, 'uploads/')) {
            return PUBLIC_PATH . '/' . $relative;
        }
        return rtrim((string) config('app', 'upload.path', PUBLIC_PATH . '/uploads'), '/') . '/' . $relative;
    }

    private function logError(string $ctx, string $ref, \Throwable $e): void
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $dir . '/gdrive.log',
            date('c') . " {$ctx} error ref={$ref}: " . $e->getMessage() . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    private function logDelete(string $message): void
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/gdrive.log',
            date('c') . ' media-delete ' . $message . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
