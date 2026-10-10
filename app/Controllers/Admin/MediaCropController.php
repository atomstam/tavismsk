<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Security;
use App\Services\ImageCropService;
use InvalidArgumentException;

class MediaCropController extends Controller
{
    public function uploadTemp(): void
    {
        $this->requireLoginJson();
        $this->requireCsrfJson();
        try {
            $type = trim((string) $this->input('type', ''));
            $service = new ImageCropService($type);
            $upload = $this->file('image')
                ?: $this->file('cover_image')
                ?: $this->file('photo')
                ?: $this->file('avatar')
                ?: $this->file('logo')
                ?: $this->file('favicon');
            if (!$upload) {
                throw new InvalidArgumentException('กรุณาเลือกรูปภาพ');
            }
            $result = $service->uploadTemp($upload);
            $this->json(['success' => true] + $result);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable) {
            $this->json(['success' => false, 'message' => 'อัปโหลดไม่สำเร็จ'], 500);
        }
    }

    public function applyCrop(): void
    {
        $this->requireLoginJson();
        $this->requireCsrfJson();
        try {
            $type = trim((string) $this->input('type', ''));
            $service = new ImageCropService($type);
            $token = trim((string) $this->input('token', ''));
            $x1 = (int) $this->input('x1', 0);
            $y1 = (int) $this->input('y1', 0);
            $w = (int) ($this->input('w1') ?? $this->input('thumb_width') ?? 0);
            $h = (int) ($this->input('h1') ?? $this->input('thumb_height') ?? 0);
            $result = $service->applyCrop($token, $x1, $y1, $w, $h);
            $this->json(['success' => true] + $result);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable) {
            $this->json(['success' => false, 'message' => 'ตัดรูปไม่สำเร็จ'], 500);
        }
    }

    public function transform(): void
    {
        $this->requireLoginJson();
        $this->requireCsrfJson();
        try {
            $type = trim((string) $this->input('type', ''));
            $service = new ImageCropService($type);
            $token = trim((string) $this->input('token', ''));
            $action = trim((string) $this->input('action', ''));
            $result = $service->transformTemp($token, $action);
            $this->json(['success' => true] + $result);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable) {
            $this->json(['success' => false, 'message' => 'ปรับรูปภาพไม่สำเร็จ'], 500);
        }
    }

    public function presets(): void
    {
        $this->requireLoginJson();
        $list = [];
        foreach (ImageCropService::presets() as $key => $preset) {
            $list[$key] = [
                'label' => $preset['label'],
                'width' => $preset['width'],
                'height' => $preset['height'],
                'shape' => $preset['shape'],
                'accept_sizes' => $preset['accept_sizes'] ?? [[$preset['width'], $preset['height']]],
            ];
        }
        $this->json(['success' => true, 'presets' => $list]);
    }

    protected function file(string $key): ?array
    {
        if (empty($_FILES[$key]) || !is_array($_FILES[$key])) {
            return null;
        }
        if (($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $_FILES[$key];
    }

    private function requireLoginJson(): void
    {
        if (!auth()->check()) {
            $this->json(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
        }
    }

    private function requireCsrfJson(): void
    {
        $token = $this->input('_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!Security::verifyCsrf(is_string($token) ? $token : null)) {
            $this->json(['success' => false, 'message' => 'CSRF token mismatch'], 419);
        }
    }
}
