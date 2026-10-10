<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\DataTableService;
use App\Services\MediaStorageService;

class GalleryController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['admin', 'trainer']);
        $this->requirePerm('portfolio');
        $this->view('admin/gallery/index', [
            'title' => 'แกลเลอรี่กิจกรรม',
            'breadcrumb' => 'หน้าหลัก / แกลเลอรี่',
            'years' => academic_years_list(),
        ]);
    }

    public function datatable(): void
    {
        $this->requireRole(['admin', 'trainer']);
        $this->requirePerm('portfolio');
        $this->json(DataTableService::gallery(dt_params()));
    }

    public function create(): void
    {
        $this->requireRole(['admin', 'trainer']);
        $this->requirePerm('portfolio', 'create');
        $this->view('admin/gallery/form', [
            'title' => 'เพิ่มอัลบั้ม',
            'album' => null,
            'media' => [],
            'years' => academic_years_list(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['admin', 'trainer']);
        $this->requirePerm('portfolio', 'create');
        $title = trim((string) $this->input('title', ''));
        if ($title === '') {
            $this->flash('error', 'กรุณาระบุชื่ออัลบั้ม');
            $this->redirect('/admin/gallery/create');
        }
        $slug = $this->uniqueSlug($title);
        $id = db()->insert('gallery_albums', [
            'user_id' => auth()->id(),
            'title' => $title,
            'slug' => $slug,
            'description' => trim((string) $this->input('description', '')),
            'category' => trim((string) $this->input('category', '')),
            'location' => trim((string) $this->input('location', '')),
            'event_date' => $this->input('event_date') ?: null,
            'academic_year' => (int) $this->input('academic_year', 0) ?: null,
            'is_public' => $this->input('is_public') ? 1 : 0,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->saveMedia($id);
        activity_log('create', 'gallery', 'เพิ่มอัลบั้มแกลเลอรี่', null, ['id' => $id]);
        $this->flash('success', 'บันทึกอัลบั้มแล้ว');
        $this->redirect('/admin/gallery');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['admin', 'trainer']);
        $this->requirePerm('portfolio', 'edit');
        $album = db()->fetch('SELECT * FROM gallery_albums WHERE id = ? AND deleted_at IS NULL', [(int) $id]);
        if (!$album) {
            $this->flash('error', 'ไม่พบอัลบั้ม');
            $this->redirect('/admin/gallery');
        }
        $media = db()->fetchAll('SELECT * FROM gallery_media WHERE album_id = ? ORDER BY sort_order, id', [(int) $id]);
        $this->view('admin/gallery/form', [
            'title' => 'แก้ไขอัลบั้ม',
            'album' => $album,
            'media' => $media,
            'years' => academic_years_list(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['admin', 'trainer']);
        $this->requirePerm('portfolio', 'edit');
        $album = db()->fetch('SELECT * FROM gallery_albums WHERE id = ? AND deleted_at IS NULL', [(int) $id]);
        if (!$album) {
            $this->flash('error', 'ไม่พบอัลบั้ม');
            $this->redirect('/admin/gallery');
        }
        db()->update('gallery_albums', [
            'title' => trim((string) $this->input('title', $album['title'])),
            'description' => trim((string) $this->input('description', '')),
            'category' => trim((string) $this->input('category', '')),
            'location' => trim((string) $this->input('location', '')),
            'event_date' => $this->input('event_date') ?: null,
            'academic_year' => (int) $this->input('academic_year', 0) ?: null,
            'is_public' => $this->input('is_public') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $id]);
        $this->saveMedia((int) $id);
        activity_log('edit', 'gallery', 'แก้ไขอัลบั้มแกลเลอรี่', $album, ['id' => (int) $id]);
        $this->flash('success', 'อัปเดตอัลบั้มแล้ว');
        $this->redirect('/admin/gallery');
    }

    public function destroy(string $id): void
    {
        $this->requireRole(['admin', 'trainer']);
        $this->requirePerm('portfolio', 'delete');
        db()->update('gallery_albums', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $id]);
        activity_log('delete', 'gallery', 'ลบอัลบั้มแกลเลอรี่', null, ['id' => (int) $id]);
        $this->flash('success', 'ลบอัลบั้มแล้ว');
        $this->redirect('/admin/gallery');
    }

    private function saveMedia(int $albumId): void
    {
        $upload = new MediaStorageService();
        $album = db()->fetch('SELECT user_id FROM gallery_albums WHERE id = ?', [$albumId]);
        $opts = [
            'entity_id' => $albumId,
            'user_id' => (int) ($album['user_id'] ?? auth()->id()),
        ];
        if (!empty($_FILES['cover']['name'])) {
            try {
                $saved = $upload->upload($_FILES['cover'], 'gallery', $opts);
                db()->update('gallery_albums', ['cover_image' => $saved['file_path']], 'id = ?', [$albumId]);
            } catch (\Throwable $e) {
            }
        }
        if (empty($_FILES['media']['name'][0])) {
            return;
        }
        $count = count($_FILES['media']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ((int) ($_FILES['media']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $file = [
                'name' => $_FILES['media']['name'][$i],
                'type' => $_FILES['media']['type'][$i],
                'tmp_name' => $_FILES['media']['tmp_name'][$i],
                'error' => $_FILES['media']['error'][$i],
                'size' => $_FILES['media']['size'][$i],
            ];
            try {
                $saved = $upload->upload($file, 'gallery', $opts);
                $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                db()->insert('gallery_media', [
                    'album_id' => $albumId,
                    'media_type' => $ext === 'mp4' ? 'video' : 'image',
                    'file_path' => $saved['file_path'],
                    'original_name' => $saved['original_name'],
                    'sort_order' => $i,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
            }
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = preg_replace('/[^a-z0-9ก-๙]+/u', '-', mb_strtolower(trim($title))) ?: 'album';
        $base = trim((string) $base, '-');
        $slug = $base;
        $n = 1;
        while (db()->fetch('SELECT id FROM gallery_albums WHERE slug = ?', [$slug])) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
