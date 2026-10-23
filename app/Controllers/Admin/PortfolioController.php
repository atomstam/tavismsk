<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\PortfolioItem;
use App\Services\DataTableService;
use App\Services\MediaStorageService;

class PortfolioController extends Controller
{
    private PortfolioItem $items;

    public function __construct()
    {
        $this->items = new PortfolioItem();
    }

    public function index(string $type): void
    {
        $mod = $this->moduleOrFail($type);
        $this->requirePerm('portfolio');
        $seeAll = can_see_all_personnel();
        $stats = $this->items->stats($type, auth()->id(), $seeAll);
        $people = $seeAll ? db()->fetchAll(
            "SELECT id, title, first_name, last_name FROM users WHERE deleted_at IS NULL AND status = 1 ORDER BY first_name"
        ) : [];

        $this->view('admin/portfolio/index', [
            'title' => $mod['title'],
            'breadcrumb' => 'หน้าหลัก / ' . $mod['short'],
            'mod' => $mod,
            'type' => $type,
            'stats' => $stats,
            'people' => $people,
            'years' => academic_years_list(),
        ]);
    }

    public function datatable(string $type): void
    {
        $this->moduleOrFail($type);
        $this->requirePerm('portfolio');
        $this->json(DataTableService::portfolio(dt_params(), $type, [
            'user_id' => (int) $this->input('user_id', 0),
            'year' => (int) $this->input('year', 0),
            'search' => trim((string) $this->input('q', '')),
        ]));
    }

    public function create(string $type): void
    {
        $mod = $this->moduleOrFail($type);
        $this->requirePerm('portfolio', 'create');
        $this->view('admin/portfolio/form', [
            'title' => 'เพิ่ม' . $mod['title'],
            'breadcrumb' => 'หน้าหลัก / ' . $mod['short'] . ' / เพิ่มรายการ',
            'mod' => $mod,
            'type' => $type,
            'item' => null,
            'people' => $this->people(),
            'files' => [],
            'photos' => [],
        ]);
    }

    public function store(string $type): void
    {
        $mod = $this->moduleOrFail($type);
        $this->requirePerm('portfolio', 'create');
        $payload = $this->payload($type, null);
        $id = $this->items->create($payload);
        $this->saveUploads($id);
        activity_log('create', $type, 'เพิ่มรายการพอร์ตโฟลิโอ', null, ['id' => $id]);
        try {
            (new \App\Services\NotificationService())->portfolioCreated(
                $type,
                (int) $id,
                (string) ($payload['title'] ?? ($mod['title'] ?? 'รายการใหม่')),
                (int) ($payload['user_id'] ?? auth()->id())
            );
        } catch (\Throwable $e) {
            // ไม่บล็อกการบันทึกหากแจ้งเตือนล้มเหลว
        }
        $this->flash('success', 'บันทึกรายการสำเร็จ');
        $this->redirect('/admin/portfolio/' . $type);
    }

    public function show(string $type, string $id): void
    {
        $mod = $this->moduleOrFail($type);
        $item = $this->ownedItem((int) $id, $type);
        $this->view('admin/portfolio/show', [
            'title' => $mod['title'],
            'breadcrumb' => 'หน้าหลัก / ' . $mod['short'],
            'mod' => $mod,
            'type' => $type,
            'item' => $item,
            'files' => $this->items->files((int) $id, 'file'),
            'photos' => $this->items->files((int) $id, 'photo'),
        ]);
    }

    public function edit(string $type, string $id): void
    {
        $mod = $this->moduleOrFail($type);
        $this->requirePerm('portfolio', 'edit');
        $item = $this->ownedItem((int) $id, $type);
        $this->view('admin/portfolio/form', [
            'title' => 'แก้ไข' . $mod['title'],
            'breadcrumb' => 'หน้าหลัก / ' . $mod['short'] . ' / แก้ไข',
            'mod' => $mod,
            'type' => $type,
            'item' => $item,
            'people' => $this->people(),
            'files' => $this->items->files((int) $id, 'file'),
            'photos' => $this->items->files((int) $id, 'photo'),
        ]);
    }

    public function update(string $type, string $id): void
    {
        $this->moduleOrFail($type);
        $this->requirePerm('portfolio', 'edit');
        $item = $this->ownedItem((int) $id, $type);
        $this->items->update((int) $id, $this->payload($type, $item));
        $this->saveUploads((int) $id);
        activity_log('edit', $type, 'แก้ไขรายการพอร์ตโฟลิโอ', $item, ['id' => (int) $id]);
        $this->flash('success', 'อัปเดตรายการสำเร็จ');
        $this->redirect('/admin/portfolio/' . $type);
    }

    public function destroy(string $type, string $id): void
    {
        $this->moduleOrFail($type);
        $this->requirePerm('portfolio', 'delete');
        $item = $this->ownedItem((int) $id, $type);
        $this->items->update((int) $id, ['deleted_at' => date('Y-m-d H:i:s')]);
        activity_log('delete', $type, 'ลบรายการพอร์ตโฟลิโอ', $item);
        $this->flash('success', 'ลบรายการแล้ว');
        $this->redirect('/admin/portfolio/' . $type);
    }

    private function moduleOrFail(string $type): array
    {
        $mod = portfolio_module($type);
        if (!$mod) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'ไม่พบโมดูล'], null);
            exit;
        }
        return $mod;
    }

    private function ownedItem(int $id, string $type): array
    {
        $item = $this->items->withUser($id);
        if (!$item || $item['type'] !== $type) {
            $this->flash('error', 'ไม่พบรายการ');
            $this->redirect('/admin/portfolio/' . $type);
        }
        if (!can_see_all_personnel() && (int) $item['user_id'] !== (int) auth()->id()) {
            $this->flash('error', 'คุณไม่มีสิทธิ์เข้าถึงรายการนี้');
            $this->redirect('/admin/portfolio/' . $type);
        }
        return $item;
    }

    private function payload(string $type, ?array $old): array
    {
        $userId = (int) $this->input('user_id', auth()->id());
        if (!can_see_all_personnel()) {
            $userId = (int) auth()->id();
        }
        $title = trim((string) $this->input('title', ''));
        if ($title === '') {
            $this->flash('error', 'กรุณากรอกชื่อรายการ');
            $this->back();
        }
        $year = (int) $this->input('academic_year', current_academic_year());
        return [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'description' => trim((string) $this->input('description', '')),
            'organization' => trim((string) $this->input('organization', '')) ?: null,
            'location' => trim((string) $this->input('location', '')) ?: null,
            'role_name' => trim((string) $this->input('role_name', '')) ?: null,
            'category' => trim((string) $this->input('category', '')) ?: null,
            'status_name' => trim((string) $this->input('status_name', '')) ?: null,
            'level_name' => trim((string) $this->input('level_name', '')) ?: null,
            'group_name' => trim((string) $this->input('group_name', '')) ?: null,
            'instance_no' => trim((string) $this->input('instance_no', '')) ?: null,
            'start_date' => $this->input('start_date') ?: null,
            'end_date' => $this->input('end_date') ?: null,
            'hours' => (float) $this->input('hours', 0),
            'academic_year' => $year,
            'year_be' => (int) $this->input('year_be', $year) ?: $year,
            'is_featured' => $this->input('is_featured') ? 1 : 0,
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
            'created_by' => $old['created_by'] ?? auth()->id(),
        ];
    }

    private function saveUploads(int $itemId): void
    {
        $media = new MediaStorageService();
        $owner = db()->fetch('SELECT user_id FROM portfolio_items WHERE id = ?', [$itemId]);
        $opts = ['user_id' => (int) ($owner['user_id'] ?? auth()->id())];
        if (!empty($_FILES['files']['name']) && is_array($_FILES['files']['name'])) {
            foreach ($_FILES['files']['name'] as $i => $name) {
                if (!$name) {
                    continue;
                }
                $file = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i],
                ];
                try {
                    $saved = $media->upload($file, 'portfolio', $opts);
                    db()->insert('portfolio_files', [
                        'item_id' => $itemId,
                        'file_name' => $saved['file_name'],
                        'original_name' => $saved['original_name'],
                        'file_path' => $saved['file_path'],
                        'file_type' => $saved['file_type'],
                        'file_size' => $saved['file_size'],
                        'kind' => 'file',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                }
            }
        }
        if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
            foreach ($_FILES['photos']['name'] as $i => $name) {
                if (!$name) {
                    continue;
                }
                $file = [
                    'name' => $_FILES['photos']['name'][$i],
                    'type' => $_FILES['photos']['type'][$i],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                    'error' => $_FILES['photos']['error'][$i],
                    'size' => $_FILES['photos']['size'][$i],
                ];
                try {
                    $saved = $media->upload($file, 'portfolio', $opts);
                    db()->insert('portfolio_files', [
                        'item_id' => $itemId,
                        'file_name' => $saved['file_name'],
                        'original_name' => $saved['original_name'],
                        'file_path' => $saved['file_path'],
                        'file_type' => $saved['file_type'],
                        'file_size' => $saved['file_size'],
                        'kind' => 'photo',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                }
            }
        }
    }

    private function people(): array
    {
        if (!can_see_all_personnel()) {
            return [];
        }
        return db()->fetchAll(
            "SELECT id, title, first_name, last_name FROM users WHERE deleted_at IS NULL AND status = 1 ORDER BY first_name"
        );
    }
}
