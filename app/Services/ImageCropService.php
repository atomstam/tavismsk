<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Security;
use InvalidArgumentException;

/**
 * Crop รูปภาพแบบกำหนดขนาดตามประเภท (ใช้ร่วมทุกฟอร์ม)
 *
 * @phpstan-type CropPreset array{
 *   label:string, width:int, height:int, folder:string, shape:string,
 *   accept_sizes?: list<array{0:int,1:int}>
 * }
 */
class ImageCropService
{
    private const TEMP_SUBDIR = 'tmp/image_crop';

    private const MAX_PREVIEW_WIDTH = 1200;

    /** @var array<string, string> */
    private const ALLOWED_EXT = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    /** @var array<string, CropPreset>|null */
    private static ?array $presetCache = null;

    /** Fallback เมื่อยังไม่มีใน config */
    private const DEFAULT_PRESETS = [
        'avatar' => [
            'label' => 'รูปโปรไฟล์',
            'width' => 400,
            'height' => 400,
            'folder' => 'avatars',
            'shape' => 'circle',
        ],
        'photo' => [
            'label' => 'รูปบุคลากร',
            'width' => 400,
            'height' => 400,
            'folder' => 'personnel',
            'shape' => 'circle',
        ],
        'logo' => [
            'label' => 'โลโก้หน่วยงาน',
            'width' => 400,
            'height' => 400,
            'folder' => 'logo',
            'shape' => 'square',
        ],
        'site_logo' => [
            'label' => 'โลโก้เว็บไซต์',
            'width' => 600,
            'height' => 200,
            'folder' => 'logo',
            'shape' => 'rect',
        ],
        'favicon' => [
            'label' => 'Favicon',
            'width' => 180,
            'height' => 180,
            'folder' => 'logo',
            'shape' => 'square',
        ],
        'cover' => [
            'label' => 'รูปปก',
            'width' => 1280,
            'height' => 720,
            'folder' => 'portfolio',
            'shape' => 'rect',
            'accept_sizes' => [[1280, 720], [1920, 1080]],
        ],
    ];

    /** @deprecated ใช้ presets() แทน — คงไว้เพื่อโค้ดเก่า */
    public const PRESETS = [];

    private string $type;

    /** @var CropPreset */
    private array $preset;

    public function __construct(string $type)
    {
        $type = strtolower(trim($type));
        $presets = self::presets();
        if (!isset($presets[$type])) {
            throw new InvalidArgumentException('ประเภทการตัดรูปไม่ถูกต้อง');
        }
        $this->type = $type;
        $this->preset = $presets[$type];
    }

    /** @return array<string, CropPreset> */
    public static function presets(): array
    {
        if (self::$presetCache !== null) {
            return self::$presetCache;
        }

        $out = self::DEFAULT_PRESETS;
        $cfg = config('app', 'upload.image_sizes', []);
        if (is_array($cfg)) {
            foreach ($cfg as $key => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $key = strtolower((string) $key);
                $base = $out[$key] ?? [
                    'label' => (string) ($row['label'] ?? $key),
                    'width' => 400,
                    'height' => 400,
                    'folder' => $key,
                    'shape' => 'square',
                ];
                $w = (int) ($row['w'] ?? $row['width'] ?? $base['width']);
                $h = (int) ($row['h'] ?? $row['height'] ?? $base['height']);
                $accept = $row['accept'] ?? $row['accept_sizes'] ?? ($base['accept_sizes'] ?? null);
                $out[$key] = [
                    'label' => (string) ($row['label'] ?? $base['label']),
                    'width' => $w,
                    'height' => $h,
                    'folder' => (string) ($row['folder'] ?? $base['folder']),
                    'shape' => (string) ($row['shape'] ?? $base['shape']),
                ];
                if (is_array($accept) && $accept !== []) {
                    $out[$key]['accept_sizes'] = $accept;
                }
            }
        }

        self::$presetCache = $out;
        return self::$presetCache;
    }

    public static function types(): array
    {
        return array_keys(self::presets());
    }

    /** @return CropPreset */
    public function preset(): array
    {
        return $this->preset;
    }

    public function outputWidth(): int
    {
        return (int) $this->preset['width'];
    }

    public function outputHeight(): int
    {
        return (int) $this->preset['height'];
    }

    /**
     * @param array<string, mixed> $upload
     * @return array{token: string, preview_url: string, width: int, height: int, needs_crop: bool, type: string, output_width: int, output_height: int, shape: string, label: string}
     */
    public function uploadTemp(array $upload): array
    {
        $err = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('อัปโหลดรูปภาพไม่สำเร็จ');
        }

        $max = (int) config('app', 'upload.max_size', 10 * 1024 * 1024);
        $size = (int) ($upload['size'] ?? 0);
        if ($size < 1 || $size > $max) {
            throw new InvalidArgumentException('ขนาดรูปภาพต้องไม่เกิน ' . format_bytes($max));
        }

        $origName = (string) ($upload['name'] ?? 'image.jpg');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED_EXT[$ext])) {
            throw new InvalidArgumentException('ชนิดไฟล์ไม่รองรับ (ใช้ jpg, png, gif หรือ webp)');
        }

        $tmp = (string) ($upload['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('ไฟล์อัปโหลดไม่ถูกต้อง');
        }

        if (@getimagesize($tmp) === false) {
            throw new InvalidArgumentException('ไฟล์ไม่ใช่รูปภาพที่ถูกต้อง');
        }

        $this->ensureTempDir();
        $token = $this->newToken();
        $relative = self::TEMP_SUBDIR . '/' . $token . '_src.' . $ext;
        $dest = $this->uploadsRoot() . '/' . $relative;

        if (!@move_uploaded_file($tmp, $dest) && !(@copy($tmp, $dest) && @unlink($tmp))) {
            throw new InvalidArgumentException('บันทึกไฟล์ชั่วคราวไม่สำเร็จ');
        }
        @chmod($dest, 0644);

        $origRelative = self::TEMP_SUBDIR . '/' . $token . '_orig.' . $ext;
        $origPath = $this->uploadsRoot() . '/' . $origRelative;
        if (!@copy($dest, $origPath)) {
            $this->deleteTokenFiles($token);
            throw new InvalidArgumentException('บันทึกไฟล์ต้นฉบับไม่สำเร็จ');
        }
        @chmod($origPath, 0644);

        $info = @getimagesize($dest);
        if ($info === false) {
            $this->deleteTokenFiles($token);
            throw new InvalidArgumentException('ไม่สามารถอ่านรูปภาพได้');
        }

        $origWidth = (int) ($info[0] ?? 0);
        $origHeight = (int) ($info[1] ?? 0);
        $needsCrop = !$this->isDefaultSize($origWidth, $origHeight);

        $this->scaleDownIfNeeded($dest, self::MAX_PREVIEW_WIDTH);
        $this->scaleDownIfNeeded($origPath, self::MAX_PREVIEW_WIDTH);

        $previewInfo = @getimagesize($dest);
        $width = $previewInfo ? (int) ($previewInfo[0] ?? 0) : $origWidth;
        $height = $previewInfo ? (int) ($previewInfo[1] ?? 0) : $origHeight;

        return [
            'token' => $token,
            'preview_url' => upload_url('uploads/' . $relative) . '?t=' . time(),
            'width' => $width,
            'height' => $height,
            'needs_crop' => $needsCrop,
            'type' => $this->type,
            'output_width' => $this->outputWidth(),
            'output_height' => $this->outputHeight(),
            'shape' => (string) $this->preset['shape'],
            'label' => (string) $this->preset['label'],
        ];
    }

    /**
     * @return array{token: string, preview_url: string, type: string}
     */
    public function applyCrop(string $token, int $x1, int $y1, int $w, int $h): array
    {
        $token = $this->normalizeToken($token);
        if ($w < 1 || $h < 1) {
            throw new InvalidArgumentException('กรุณาเลือกพื้นที่ตัดรูป');
        }

        $srcPath = $this->findSourcePath($token);
        if ($srcPath === null) {
            throw new InvalidArgumentException('ไม่พบรูปชั่วคราว — กรุณาเลือกรูปใหม่');
        }

        $src = $this->loadImage($srcPath);
        if ($src === null) {
            throw new InvalidArgumentException('ไม่สามารถเปิดรูปภาพได้');
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $x1 = max(0, min($x1, $srcW - 1));
        $y1 = max(0, min($y1, $srcH - 1));
        $w = max(1, min($w, $srcW - $x1));
        $h = max(1, min($h, $srcH - $y1));

        $outW = $this->outputWidth();
        $outH = $this->outputHeight();
        $dst = imagecreatetruecolor($outW, $outH);
        if ($dst === false) {
            imagedestroy($src);
            throw new InvalidArgumentException('สร้างรูปภาพไม่สำเร็จ');
        }

        $this->fillWhite($dst);
        imagecopyresampled($dst, $src, 0, 0, $x1, $y1, $outW, $outH, $w, $h);
        imagedestroy($src);

        $this->ensureTempDir();
        $relative = self::TEMP_SUBDIR . '/' . $token . '_out.jpg';
        $outPath = $this->uploadsRoot() . '/' . $relative;

        if (!imagejpeg($dst, $outPath, 90)) {
            imagedestroy($dst);
            throw new InvalidArgumentException('บันทึกรูปที่ตัดแล้วไม่สำเร็จ');
        }
        imagedestroy($dst);
        @chmod($outPath, 0644);

        return [
            'token' => $token,
            'preview_url' => upload_url('uploads/' . $relative) . '?t=' . time(),
            'type' => $this->type,
        ];
    }

    /**
     * @return array{token: string, preview_url: string, width: int, height: int, type: string}
     */
    public function transformTemp(string $token, string $action): array
    {
        $token = $this->normalizeToken($token);
        $action = strtolower(trim($action));
        $allowed = ['rotate_left', 'rotate_right', 'flip_h', 'flip_v', 'reset'];
        if (!in_array($action, $allowed, true)) {
            throw new InvalidArgumentException('การปรับรูปภาพไม่ถูกต้อง');
        }

        $srcPath = $this->findSourcePath($token);
        if ($srcPath === null) {
            throw new InvalidArgumentException('ไม่พบรูปชั่วคราว — กรุณาเลือกรูปใหม่');
        }

        if ($action === 'reset') {
            $origPath = $this->findOrigPath($token);
            if ($origPath === null) {
                throw new InvalidArgumentException('ไม่พบรูปต้นฉบับ');
            }
            if (!@copy($origPath, $srcPath)) {
                throw new InvalidArgumentException('รีเซ็ตรูปภาพไม่สำเร็จ');
            }
        } else {
            $src = $this->loadImage($srcPath);
            if ($src === null) {
                throw new InvalidArgumentException('ไม่สามารถเปิดรูปภาพได้');
            }

            $dst = match ($action) {
                'rotate_left' => @imagerotate($src, 90, 0),
                'rotate_right' => @imagerotate($src, -90, 0),
                'flip_h' => $this->flipImage($src, true),
                'flip_v' => $this->flipImage($src, false),
                default => null,
            };
            imagedestroy($src);

            if ($dst === false || $dst === null) {
                throw new InvalidArgumentException('ปรับรูปภาพไม่สำเร็จ');
            }

            if (!imagejpeg($dst, $srcPath, 90)) {
                imagedestroy($dst);
                throw new InvalidArgumentException('บันทึกรูปภาพไม่สำเร็จ');
            }
            imagedestroy($dst);
            @chmod($srcPath, 0644);
        }

        $this->deleteCroppedPreview($token);
        $this->scaleDownIfNeeded($srcPath, self::MAX_PREVIEW_WIDTH);

        $info = @getimagesize($srcPath);
        if ($info === false) {
            throw new InvalidArgumentException('ไม่สามารถอ่านรูปภาพได้');
        }

        return [
            'token' => $token,
            'preview_url' => upload_url('uploads/' . $this->relativeFromAbsolute($srcPath)) . '?t=' . time(),
            'width' => (int) ($info[0] ?? 0),
            'height' => (int) ($info[1] ?? 0),
            'type' => $this->type,
        ];
    }

    public function getCroppedOutputPath(string $token): string
    {
        $token = $this->normalizeToken($token);
        $croppedPath = $this->uploadsRoot() . '/' . self::TEMP_SUBDIR . '/' . $token . '_out.jpg';
        if (!is_file($croppedPath)) {
            throw new InvalidArgumentException('ไม่พบรูปที่ตัดแล้ว — กรุณาตัดรูปก่อนบันทึก');
        }
        return $croppedPath;
    }

    public function commitToken(string $token, ?string $oldRelativePath = null): string
    {
        $token = $this->normalizeToken($token);
        $croppedPath = $this->getCroppedOutputPath($token);

        $folder = (string) $this->preset['folder'];
        $dir = $this->ensureWritableDir($folder);

        $newName = date('YmdHis') . '_' . Security::generateToken(8) . '.jpg';
        $finalRelative = 'uploads/' . $folder . '/' . $newName;
        $finalPath = $dir . '/' . $newName;

        if (!@copy($croppedPath, $finalPath)) {
            @chmod($dir, 0775);
            if (!@copy($croppedPath, $finalPath)) {
                throw new InvalidArgumentException('บันทึกรูปภาพไม่สำเร็จ — ตรวจสอบสิทธิ์โฟลเดอร์อัปโหลด');
            }
        }
        @chmod($finalPath, 0644);

        if ($oldRelativePath) {
            (new MediaStorageService())->delete($oldRelativePath);
        }
        $this->deleteTokenFiles($token);

        return $finalRelative;
    }

    public function deleteTokenFiles(string $token): void
    {
        $token = $this->normalizeToken($token);
        $dir = $this->uploadsRoot() . '/' . self::TEMP_SUBDIR;
        foreach (glob($dir . '/' . $token . '_*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function isDefaultSize(int $width, int $height): bool
    {
        if ($width === $this->outputWidth() && $height === $this->outputHeight()) {
            return true;
        }
        foreach ($this->preset['accept_sizes'] ?? [] as $pair) {
            if ((int) ($pair[0] ?? 0) === $width && (int) ($pair[1] ?? 0) === $height) {
                return true;
            }
        }
        return false;
    }

    private function uploadsRoot(): string
    {
        return rtrim((string) config('app', 'upload.path', PUBLIC_PATH . '/uploads'), '/');
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function normalizeToken(string $token): string
    {
        $token = strtolower(trim($token));
        if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new InvalidArgumentException('โทเคนรูปภาพไม่ถูกต้อง');
        }
        return $token;
    }

    private function ensureTempDir(): void
    {
        $this->ensureWritableDir(self::TEMP_SUBDIR);
    }

    private function ensureWritableDir(string $relativeFolder): string
    {
        $relativeFolder = trim(str_replace(['..', '\\'], '', $relativeFolder), '/');
        $dir = $this->uploadsRoot() . ($relativeFolder !== '' ? '/' . $relativeFolder : '');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new InvalidArgumentException('สร้างโฟลเดอร์รูปภาพไม่สำเร็จ');
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }
        if (!is_writable($dir)) {
            throw new InvalidArgumentException('โฟลเดอร์อัปโหลดไม่มีสิทธิ์เขียนไฟล์');
        }
        return $dir;
    }

    private function findSourcePath(string $token): ?string
    {
        $dir = $this->uploadsRoot() . '/' . self::TEMP_SUBDIR;
        foreach (glob($dir . '/' . $token . '_src.*') ?: [] as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        return null;
    }

    private function findOrigPath(string $token): ?string
    {
        $dir = $this->uploadsRoot() . '/' . self::TEMP_SUBDIR;
        foreach (glob($dir . '/' . $token . '_orig.*') ?: [] as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        return null;
    }

    private function deleteCroppedPreview(string $token): void
    {
        $path = $this->uploadsRoot() . '/' . self::TEMP_SUBDIR . '/' . $token . '_out.jpg';
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function relativeFromAbsolute(string $absolutePath): string
    {
        $root = rtrim($this->uploadsRoot(), '/\\') . '/';
        return ltrim(str_replace('\\', '/', substr($absolutePath, strlen($root))), '/');
    }

    private function fillWhite(\GdImage $img): void
    {
        $white = imagecolorallocate($img, 255, 255, 255);
        if ($white !== false) {
            imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $white);
        }
    }

    private function flipImage(\GdImage $src, bool $horizontal): \GdImage|false
    {
        $width = imagesx($src);
        $height = imagesy($src);
        $dst = imagecreatetruecolor($width, $height);
        if ($dst === false) {
            return false;
        }
        if ($horizontal) {
            for ($x = 0; $x < $width; $x++) {
                imagecopy($dst, $src, $width - $x - 1, 0, $x, 0, 1, $height);
            }
        } else {
            for ($y = 0; $y < $height; $y++) {
                imagecopy($dst, $src, 0, $height - $y - 1, 0, $y, $width, 1);
            }
        }
        return $dst;
    }

    private function scaleDownIfNeeded(string $path, int $maxWidth): void
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return;
        }
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width <= $maxWidth || $width < 1 || $height < 1) {
            return;
        }
        $scale = $maxWidth / $width;
        $newW = (int) round($width * $scale);
        $newH = (int) round($height * $scale);
        $src = $this->loadImage($path);
        if ($src === null) {
            return;
        }
        $dst = imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            imagedestroy($src);
            return;
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($src);
        imagejpeg($dst, $path, 90);
        imagedestroy($dst);
    }

    private function loadImage(string $path): ?\GdImage
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }
        $mime = strtolower((string) ($info['mime'] ?? ''));
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path) ?: null,
            'image/png' => @imagecreatefrompng($path) ?: null,
            'image/gif' => @imagecreatefromgif($path) ?: null,
            'image/webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }
}
