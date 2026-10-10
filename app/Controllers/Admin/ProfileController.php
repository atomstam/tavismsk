<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\User;

class ProfileController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function edit(): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->redirect('/login');
        }
        $this->view('admin/profile/edit', [
            'title' => 'แก้ไขข้อมูลส่วนตัว',
            'breadcrumb' => 'หน้าหลัก / ข้อมูลส่วนตัว',
            'profile' => $user,
        ]);
    }

    public function update(): void
    {
        $authUser = auth()->user();
        if (!$authUser) {
            $this->redirect('/login');
        }
        $id = (int) $authUser['id'];

        $errors = $this->validate($_POST, [
            'first_name' => 'required',
            'last_name' => 'required',
        ]);
        $pass = (string) $this->input('password', '');
        $confirm = (string) $this->input('password_confirmation', '');
        if ($pass !== '') {
            if (strlen($pass) < 8) {
                $errors['password'][] = 'รหัสผ่านอย่างน้อย 8 ตัวอักษร';
            }
            if ($pass !== $confirm) {
                $errors['password_confirmation'][] = 'รหัสผ่านยืนยันไม่ตรงกัน';
            }
        }
        if ($errors) {
            $this->flash('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
            set_old_input($_POST);
            $this->redirect('/admin/profile');
        }

        $data = [
            'title' => trim((string) $this->input('title', '')) ?: null,
            'first_name' => trim((string) $this->input('first_name', '')),
            'last_name' => trim((string) $this->input('last_name', '')),
            'email' => trim((string) $this->input('email', '')) ?: null,
            'phone' => trim((string) $this->input('phone', '')) ?: null,
        ];
        if ($pass !== '') {
            $data['password'] = hash_password($pass);
        }

        $this->users->update($id, $data);

        try {
            $this->saveAvatar($id, $authUser['avatar'] ?? null);
        } catch (\Throwable $e) {
            $this->flash('error', 'บันทึกข้อมูลแล้ว แต่รูปโปรไฟล์ไม่สำเร็จ: ' . $e->getMessage());
            $fresh = $this->users->find($id);
            if ($fresh) {
                auth()->login($fresh, !empty($_COOKIE['remember_token']));
            }
            $this->redirect('/admin/profile');
        }

        $fresh = $this->users->find($id);
        if ($fresh) {
            auth()->login($fresh, !empty($_COOKIE['remember_token']));
        }

        activity_log('edit', 'profile', 'แก้ไขข้อมูลส่วนตัว', null, ['id' => $id]);
        $this->flash('success', 'บันทึกข้อมูลส่วนตัวเรียบร้อย');
        $this->redirect('/admin/profile');
    }

    private function saveAvatar(int $id, ?string $old = null): void
    {
        $path = resolve_cropped_upload('avatar', 'avatar', 'avatar_crop_token', $old, [
            'user_id' => $id,
        ]);
        if ($path) {
            $this->users->update($id, ['avatar' => $path]);
        }
    }
}
