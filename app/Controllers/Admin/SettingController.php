<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Setting;
use App\Services\BackupService;

class SettingController extends Controller
{
    public function index(): void
    {
        $this->requirePerm('settings');
        $map = (new Setting())->allMap();
        $backups = [];
        try {
            $backups = db()->fetchAll('SELECT * FROM backups ORDER BY id DESC LIMIT 20');
        } catch (\Throwable $e) {
        }
        $this->view('admin/settings/index', [
            'title' => 'ตั้งค่าระบบ',
            'breadcrumb' => 'หน้าหลัก / ตั้งค่าระบบ',
            's' => array_merge(setting_defaults(), $map),
            'backups' => $backups,
            'years' => academic_years_list(),
        ]);
    }

    public function save(): void
    {
        $this->requirePerm('settings', 'edit');
        $model = new Setting();
        $keys = [
            'site_name', 'site_short_name', 'site_full_name', 'site_tagline', 'site_name_en',
            'site_nav_title', 'site_nav_tag', 'system_purpose',
            'org_name', 'org_name_en', 'org_affiliation',
            'org_address', 'org_phone', 'org_email', 'org_website',
            'org_facebook', 'org_line', 'director_name', 'director_position', 'director_signature', 'academic_year',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_from', 'theme_default',
            'maintenance_mode', 'maps_embed', 'developer_name', 'footer_credit',
            'certificate_prefix',
        ];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $model->put($k, (string) $_POST[$k]);
            }
        }
        $uploadErrors = [];
        if (!empty($_FILES['logo']['name']) || !empty($_POST['logo_crop_token'])) {
            try {
                $path = resolve_cropped_upload('site_logo', 'logo', 'logo_crop_token', $model->get('logo'), [
                    'system' => true,
                ]);
                if ($path) {
                    $model->put('logo', $path);
                }
            } catch (\Throwable $e) {
                $uploadErrors[] = 'โลโก้: ' . $e->getMessage();
            }
        }
        if (!empty($_FILES['favicon']['name']) || !empty($_POST['favicon_crop_token'])) {
            try {
                $path = resolve_cropped_upload('favicon', 'favicon', 'favicon_crop_token', $model->get('favicon'), [
                    'system' => true,
                ]);
                if ($path) {
                    $model->put('favicon', $path);
                }
            } catch (\Throwable $e) {
                $uploadErrors[] = 'Favicon: ' . $e->getMessage();
            }
        }
        activity_log('edit', 'settings', 'บันทึกการตั้งค่าระบบ');
        setting_flush();
        if ($uploadErrors !== []) {
            $this->flash('error', 'บันทึกการตั้งค่าแล้ว แต่รูปไม่สำเร็จ — ' . implode(' | ', $uploadErrors));
        } else {
            $this->flash('success', 'บันทึกการตั้งค่าแล้ว');
        }
        $this->redirect('/admin/settings');
    }

    public function backup(): void
    {
        $this->requirePerm('backup', 'create');
        $info = (new BackupService())->create(auth()->id());
        activity_log('export', 'backup', 'สำรองฐานข้อมูล ' . $info['file_name']);
        $this->flash('success', 'สำรองข้อมูลแล้ว: ' . $info['file_name']);
        $this->redirect('/admin/settings');
    }

    public function downloadBackup(string $id): void
    {
        $this->requirePerm('backup');
        $row = db()->fetch('SELECT * FROM backups WHERE id = ?', [(int) $id]);
        if (!$row) {
            $this->redirect('/admin/settings');
        }
        $path = BASE_PATH . '/' . $row['file_path'];
        if (!is_file($path)) {
            $this->flash('error', 'ไม่พบไฟล์');
            $this->redirect('/admin/settings');
        }
        activity_log('export', 'backup', 'ดาวน์โหลดสำรอง ' . $row['file_name']);
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $row['file_name'] . '"');
        readfile($path);
        exit;
    }

    public function restoreBackup(string $id): void
    {
        $this->requirePerm('backup', 'edit');
        $row = db()->fetch('SELECT * FROM backups WHERE id = ?', [(int) $id]);
        if (!$row) {
            $this->redirect('/admin/settings');
        }
        (new BackupService())->restore(BASE_PATH . '/' . $row['file_path']);
        activity_log('edit', 'backup', 'กู้คืนฐานข้อมูล ' . $row['file_name']);
        $this->flash('success', 'กู้คืนข้อมูลแล้ว');
        $this->redirect('/admin/settings');
    }
}
