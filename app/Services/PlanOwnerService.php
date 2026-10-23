<?php

declare(strict_types=1);

namespace App\Services;

class PlanOwnerService
{
    /** @return list<int> */
    public function approvedPlanIds(int $userId): array
    {
        $rows = db()->fetchAll(
            "SELECT plan_id FROM learning_plan_owners WHERE user_id = ? AND status = 'approved'",
            [$userId]
        );
        return array_map(static fn (array $r): int => (int) $r['plan_id'], $rows);
    }

    public function isApproved(int $userId, int $planId): bool
    {
        $row = db()->fetch(
            "SELECT id FROM learning_plan_owners WHERE user_id = ? AND plan_id = ? AND status = 'approved'",
            [$userId, $planId]
        );
        return (bool) $row;
    }

    /** @return list<int> วิทยากรเจ้าของแผนที่อนุมัติแล้ว */
    public function approvedOwnerUserIds(int $planId): array
    {
        $rows = db()->fetchAll(
            "SELECT o.user_id
             FROM learning_plan_owners o
             INNER JOIN users u ON u.id = o.user_id AND u.deleted_at IS NULL AND u.status = 1
             WHERE o.plan_id = ? AND o.status = 'approved'",
            [$planId]
        );
        return array_values(array_unique(array_map(static fn (array $r): int => (int) $r['user_id'], $rows)));
    }

    public function canManage(int $planId): bool
    {
        $role = auth()->role();
        if ($role === 'admin') {
            return true;
        }
        if ($role !== 'trainer' || !auth()->id()) {
            return false;
        }
        return $this->isApproved((int) auth()->id(), $planId);
    }

    public function request(int $userId, int $planId, string $note = ''): string
    {
        $plan = db()->fetch('SELECT id FROM learning_plans WHERE id = ? AND status = 1', [$planId]);
        if (!$plan) {
            return 'ไม่พบหลักสูตร';
        }
        $row = db()->fetch(
            'SELECT id, status FROM learning_plan_owners WHERE plan_id = ? AND user_id = ?',
            [$planId, $userId]
        );
        if ($row && $row['status'] === 'approved') {
            return 'คุณเป็นเจ้าของหลักสูตรนี้แล้ว';
        }
        if ($row && $row['status'] === 'pending') {
            return 'คำร้องนี้อยู่ระหว่างรออนุมัติ';
        }
        $now = date('Y-m-d H:i:s');
        if ($row) {
            db()->update('learning_plan_owners', [
                'status' => 'pending',
                'note' => $note !== '' ? mb_substr($note, 0, 255) : null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'updated_at' => $now,
            ], 'id = ?', [(int) $row['id']]);
        } else {
            db()->insert('learning_plan_owners', [
                'plan_id' => $planId,
                'user_id' => $userId,
                'status' => 'pending',
                'note' => $note !== '' ? mb_substr($note, 0, 255) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        return '';
    }

    public function review(int $id, string $status, int $reviewerId): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }
        $row = db()->fetch('SELECT id FROM learning_plan_owners WHERE id = ?', [$id]);
        if (!$row) {
            return false;
        }
        db()->update('learning_plan_owners', [
            'status' => $status,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        return true;
    }

    public function assign(int $planId, int $userId, int $reviewerId): string
    {
        if (!(new UserRoleService())->has($userId, 'trainer')) {
            return 'ผู้ใช้นี้ยังไม่มีบทบาทวิทยากร';
        }
        $plan = db()->fetch('SELECT id FROM learning_plans WHERE id = ? AND status = 1', [$planId]);
        if (!$plan) {
            return 'ไม่พบหลักสูตร';
        }
        $now = date('Y-m-d H:i:s');
        $row = db()->fetch(
            'SELECT id FROM learning_plan_owners WHERE plan_id = ? AND user_id = ?',
            [$planId, $userId]
        );
        $payload = [
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => $now,
            'updated_at' => $now,
        ];
        if ($row) {
            db()->update('learning_plan_owners', $payload, 'id = ?', [(int) $row['id']]);
        } else {
            db()->insert('learning_plan_owners', $payload + [
                'plan_id' => $planId,
                'user_id' => $userId,
                'created_at' => $now,
            ]);
        }
        return '';
    }

    public function remove(int $id): void
    {
        db()->delete('learning_plan_owners', 'id = ?', [$id]);
    }
}
