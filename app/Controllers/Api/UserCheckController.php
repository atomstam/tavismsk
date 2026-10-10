<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;

/** AJAX uniqueness check for username / email (register + admin CRUD). */
class UserCheckController extends Controller
{
    public function check(): void
    {
        $field = strtolower(trim((string) $this->input('field', '')));
        $value = trim((string) $this->input('value', ''));
        $excludeId = (int) $this->input('exclude_id', 0);

        if (!in_array($field, ['username', 'email'], true)) {
            $this->json(['available' => false, 'message' => 'ฟิลด์ไม่ถูกต้อง'], 400);
            return;
        }

        if ($value === '') {
            $this->json(['available' => true, 'field' => $field, 'message' => '']);
            return;
        }

        if ($field === 'username' && mb_strlen($value) < 3) {
            $this->json([
                'available' => false,
                'field' => $field,
                'message' => 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร',
                'clear' => false,
            ]);
            return;
        }

        if ($field === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->json([
                    'available' => false,
                    'field' => $field,
                    'message' => 'รูปแบบอีเมลไม่ถูกต้อง',
                    'clear' => false,
                ]);
                return;
            }
        }

        $sql = "SELECT id FROM users WHERE {$field} = ? AND deleted_at IS NULL";
        $params = [$value];
        if ($excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $row = db()->fetch($sql, $params);

        if ($row) {
            $msg = $field === 'username'
                ? 'ชื่อผู้ใช้นี้ถูกใช้แล้ว กรุณาใช้ชื่ออื่น'
                : 'อีเมลนี้ถูกใช้แล้ว กรุณาใช้อีเมลอื่น';
            $this->json([
                'available' => false,
                'field' => $field,
                'message' => $msg,
                'clear' => true,
            ]);
            return;
        }

        $this->json([
            'available' => true,
            'field' => $field,
            'message' => $field === 'username' ? 'ชื่อผู้ใช้ใช้ได้' : 'อีเมลใช้ได้',
            'clear' => false,
        ]);
    }
}
