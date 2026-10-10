<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Security;

class UploadService
{
    /** @var array<string,list<string>> */
    private const MIME_MAP = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'zip'  => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
        'mp4'  => ['video/mp4', 'application/octet-stream'],
    ];

    public function upload(array $file, string $folder = 'temp'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ');
        }

        $max = (int) config('app', 'upload.max_size', 10 * 1024 * 1024);
        if (($file['size'] ?? 0) > $max) {
            throw new \RuntimeException('ไฟล์มีขนาดเกินกำหนด (' . format_bytes($max) . ')');
        }

        $original = $file['name'] ?? 'file';
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = config('app', 'upload.allowed', config('app', 'allowed_extensions', []));

        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('ไม่อนุญาตประเภทไฟล์ .' . $ext);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        $this->assertMimeMatchesExtension($ext, $mime);

        $folder = $this->safeFolder($folder);
        $newName = date('YmdHis') . '_' . Security::generateToken(8) . '.' . $ext;
        $dir = $this->ensureDir($folder);

        $dest = $dir . DIRECTORY_SEPARATOR . $newName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('ไม่สามารถบันทึกไฟล์ได้');
        }

        return [
            'file_name'     => $newName,
            'original_name' => basename($original),
            'file_path'     => 'uploads/' . $folder . '/' . $newName,
            'file_type'     => $mime,
            'file_size'     => (int) $file['size'],
            'extension'     => $ext,
        ];
    }

    /**
     * @return array{file_name:string,original_name:string,file_path:string,file_type:string,file_size:int,extension:string}
     */
    public function saveBinary(string $contents, string $folder = 'temp', string $extension = 'png', string $mime = 'image/png'): array
    {
        if ($contents === '') {
            throw new \RuntimeException('ไม่มีข้อมูลไฟล์');
        }

        $extension = strtolower(ltrim($extension, '.'));
        $allowed = config('app', 'upload.allowed', config('app', 'allowed_extensions', []));
        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('ไม่อนุญาตประเภทไฟล์ .' . $extension);
        }

        $this->assertMimeMatchesExtension($extension, $mime);

        $folder = $this->safeFolder($folder);
        $newName = date('YmdHis') . '_' . Security::generateToken(8) . '.' . $extension;
        $dir = $this->ensureDir($folder);

        $dest = $dir . DIRECTORY_SEPARATOR . $newName;
        if (file_put_contents($dest, $contents) === false) {
            throw new \RuntimeException('ไม่สามารถบันทึกไฟล์ได้');
        }

        return [
            'file_name'     => $newName,
            'original_name' => $newName,
            'file_path'     => 'uploads/' . $folder . '/' . $newName,
            'file_type'     => $mime,
            'file_size'     => strlen($contents),
            'extension'     => $extension,
        ];
    }

    public function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        if (function_exists('media_is_gdrive') && media_is_gdrive($relativePath)) {
            (new MediaStorageService())->delete($relativePath);
            return;
        }
        $this->deleteLocalOnly($relativePath);
    }

    /** ลบเฉพาะไฟล์บนดิสก์ (ไม่แตะ Google Drive) */
    public function deleteLocalOnly(?string $relativePath): void
    {
        if (!$relativePath || (function_exists('media_is_gdrive') && media_is_gdrive($relativePath))) {
            return;
        }
        $full = $this->resolveSafePath($relativePath);
        if ($full !== null && is_file($full)) {
            unlink($full);
        }
    }

    private function assertMimeMatchesExtension(string $ext, string $mime): void
    {
        $allowedMimes = self::MIME_MAP[$ext] ?? null;
        if ($allowedMimes === null) {
            throw new \RuntimeException('ไม่อนุญาตประเภทไฟล์ .' . $ext);
        }
        if (!in_array($mime, $allowedMimes, true)) {
            throw new \RuntimeException('ชนิดไฟล์ไม่ตรงกับนามสกุล (MIME: ' . $mime . ')');
        }
    }

    private function safeFolder(string $folder): string
    {
        $folder = trim(str_replace(['..', '\\'], '', $folder), '/');
        if (str_starts_with($folder, 'uploads/')) {
            $folder = substr($folder, 8);
        }
        if ($folder === '' || !preg_match('/^[a-zA-Z0-9_\-\/]+$/', $folder)) {
            throw new \RuntimeException('โฟลเดอร์อัปโหลดไม่ถูกต้อง');
        }
        return $folder;
    }

    private function ensureDir(string $folder): string
    {
        $root = $this->uploadsRoot();
        if (!is_dir($root) && !mkdir($root, 0775, true) && !is_dir($root)) {
            throw new \RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้');
        }
        @chmod($root, 0775);
        $root = realpath($root) ?: $root;
        $dir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้');
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }
        if (!is_writable($dir)) {
            throw new \RuntimeException('โฟลเดอร์อัปโหลดไม่มีสิทธิ์เขียนไฟล์');
        }
        $resolved = realpath($dir);
        if ($resolved === false || !str_starts_with($resolved, $root)) {
            throw new \RuntimeException('พาธอัปโหลดไม่ปลอดภัย');
        }
        return $resolved;
    }

    private function uploadsRoot(): string
    {
        return rtrim((string) config('app', 'upload.path', PUBLIC_PATH . '/uploads'), '/');
    }

    private function resolveSafePath(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace(['..', '\\'], '', $relativePath), '/');
        if (str_starts_with($relativePath, 'uploads/')) {
            $relativePath = substr($relativePath, 8);
        }
        if ($relativePath === '') {
            return null;
        }
        $root = realpath($this->uploadsRoot());
        if ($root === false) {
            return null;
        }
        $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $resolved = realpath($full);
        if ($resolved === false || !str_starts_with($resolved, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $resolved;
    }
}
