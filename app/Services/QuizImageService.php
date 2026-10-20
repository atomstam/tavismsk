<?php

declare(strict_types=1);

namespace App\Services;

/**
 * รูปข้อสอบ: ครอปให้กว้างกว่าสูง แล้วอัปโหลด
 */
class QuizImageService
{
    private const MAX_W = 960;
    private const MAX_H = 540;

    public function store(?array $file): ?string
    {
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }
        $this->makeLandscape($tmp, (string) ($file['name'] ?? ''));
        return upload_file($file, 'quiz', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    /** @param array<string, mixed> $files โครงสร้าง $_FILES ของ input แบบ array */
    public function storeAt(array $files, int $index): ?string
    {
        if (!isset($files['error'][$index]) || (int) $files['error'][$index] !== UPLOAD_ERR_OK) {
            return null;
        }
        return $this->store([
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ]);
    }

    private function makeLandscape(string $path, string $originalName): void
    {
        if (!function_exists('imagecreatefromstring')) {
            return;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return;
        }
        $src = @imagecreatefromstring($raw);
        if (!$src) {
            return;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);
            return;
        }

        $cropW = $w;
        $cropH = $h;
        $targetRatio = self::MAX_W / self::MAX_H;
        if (($w / $h) < $targetRatio) {
            $cropH = (int) max(1, round($w / $targetRatio));
        }
        $srcX = (int) max(0, floor(($w - $cropW) / 2));
        $srcY = (int) max(0, floor(($h - $cropH) / 2));

        $scale = min(1, self::MAX_W / $cropW, self::MAX_H / $cropH);
        $outW = (int) max(1, round($cropW * $scale));
        $outH = (int) max(1, round($cropH * $scale));

        $dst = imagecreatetruecolor($outW, $outH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $clear = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $outW, $outH, $clear);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $outW, $outH, $cropW, $cropH);
        imagedestroy($src);

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $ok = match ($ext) {
            'png' => imagepng($dst, $path, 6),
            'gif' => imagegif($dst, $path),
            'webp' => function_exists('imagewebp') ? imagewebp($dst, $path, 82) : imagejpeg($dst, $path, 82),
            default => imagejpeg($dst, $path, 82),
        };
        imagedestroy($dst);
        if (!$ok) {
            return;
        }
    }
}
