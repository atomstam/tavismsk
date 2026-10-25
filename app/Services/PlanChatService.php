<?php

declare(strict_types=1);

namespace App\Services;

class PlanChatService
{
    public function isGroup(array $thread): bool
    {
        return (($thread['thread_type'] ?? 'direct') === 'group');
    }

    public function canAccessThread(array $thread, int $userId): bool
    {
        if (auth()->role() === 'admin') {
            return true;
        }
        $planId = (int) ($thread['plan_id'] ?? 0);
        if ($this->isGroup($thread)) {
            return $this->canJoinPlan($planId, $userId);
        }
        return (int) ($thread['learner_id'] ?? 0) === $userId
            || (int) ($thread['trainer_id'] ?? 0) === $userId;
    }

    public function canPost(array $thread, int $userId): bool
    {
        return $this->canAccessThread($thread, $userId);
    }

    public function canJoinPlan(int $planId, int $userId): bool
    {
        if (auth()->role() === 'admin') {
            return true;
        }
        if ($this->isPlanOwner($planId, $userId)) {
            return true;
        }
        // learner with active enrollment (or any user portal account learning)
        $en = db()->fetch(
            "SELECT id FROM course_enrollments WHERE user_id = ? AND status IN ('approved','active','completed','pending') LIMIT 1",
            [$userId]
        );
        return (bool) $en || auth()->role() === 'user';
    }

    public function isPlanOwner(int $planId, int $userId): bool
    {
        $row = db()->fetch(
            "SELECT id FROM learning_plan_owners
             WHERE plan_id = ? AND user_id = ? AND status = 'approved'",
            [$planId, $userId]
        );
        return (bool) $row;
    }

    /** @return list<array<string,mixed>> วิทยากรที่ได้รับมอบหมาย/อนุมัติเป็นเจ้าของหลักสูตรนี้เท่านั้น */
    public function trainersForPlan(int $planId): array
    {
        return db()->fetchAll(
            "SELECT DISTINCT u.id, u.title, u.first_name, u.last_name, u.avatar, u.username
             FROM learning_plan_owners o
             INNER JOIN users u ON u.id = o.user_id AND u.deleted_at IS NULL AND u.status = 1
             INNER JOIN user_roles r ON r.user_id = u.id AND r.role = 'trainer'
             WHERE o.plan_id = ? AND o.status = 'approved'
             ORDER BY u.first_name, u.last_name",
            [$planId]
        );
    }

    /** @return list<array<string,mixed>> ผู้เรียน — ไม่รวมบัญชีวิทยากร/แอดมิน */
    public function learnersForPlan(int $planId = 0): array
    {
        return db()->fetchAll(
            "SELECT DISTINCT u.id, u.title, u.first_name, u.last_name, u.avatar, u.username
             FROM users u
             INNER JOIN user_roles r ON r.user_id = u.id AND r.role = 'user'
             INNER JOIN course_enrollments e ON e.user_id = u.id
               AND e.status IN ('approved','active','completed','pending','in_progress')
             WHERE u.deleted_at IS NULL AND u.status = 1
               AND NOT EXISTS (
                 SELECT 1 FROM user_roles x
                 WHERE x.user_id = u.id AND x.role IN ('trainer', 'admin')
               )
             ORDER BY u.first_name, u.last_name
             LIMIT 300"
        );
    }

    public function ensureGroupThread(int $planId): array
    {
        $plan = db()->fetch('SELECT id FROM learning_plans WHERE id = ? AND status = 1', [$planId]);
        if (!$plan) {
            throw new \RuntimeException('ไม่พบหลักสูตร');
        }
        $row = db()->fetch(
            "SELECT * FROM plan_chat_threads WHERE plan_id = ? AND thread_type = 'group' LIMIT 1",
            [$planId]
        );
        if ($row) {
            return $row;
        }
        $now = date('Y-m-d H:i:s');
        try {
            $id = (int) db()->insert('plan_chat_threads', [
                'plan_id' => $planId,
                'thread_type' => 'group',
                'learner_id' => null,
                'trainer_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            $row = db()->fetch(
                "SELECT * FROM plan_chat_threads WHERE plan_id = ? AND thread_type = 'group' LIMIT 1",
                [$planId]
            );
            if ($row) {
                return $row;
            }
            throw $e;
        }
        return db()->fetch('SELECT * FROM plan_chat_threads WHERE id = ?', [$id]) ?: [];
    }

    public function findOrCreateDirect(int $planId, int $learnerId, int $trainerId): array
    {
        $plan = db()->fetch('SELECT id FROM learning_plans WHERE id = ? AND status = 1', [$planId]);
        if (!$plan) {
            throw new \RuntimeException('ไม่พบหลักสูตร');
        }
        if (!$this->isPlanOwner($planId, $trainerId)) {
            throw new \RuntimeException('วิทยากรนี้ยังไม่ใช่เจ้าของหลักสูตรที่ได้รับอนุมัติ');
        }
        $row = db()->fetch(
            "SELECT * FROM plan_chat_threads
             WHERE plan_id = ? AND thread_type = 'direct' AND learner_id = ? AND trainer_id = ?",
            [$planId, $learnerId, $trainerId]
        );
        if ($row) {
            return $row;
        }
        $now = date('Y-m-d H:i:s');
        $id = (int) db()->insert('plan_chat_threads', [
            'plan_id' => $planId,
            'thread_type' => 'direct',
            'learner_id' => $learnerId,
            'trainer_id' => $trainerId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return db()->fetch('SELECT * FROM plan_chat_threads WHERE id = ?', [$id]) ?: [];
    }

    /** @deprecated */
    public function findOrCreateThread(int $planId, int $learnerId, int $trainerId): array
    {
        return $this->findOrCreateDirect($planId, $learnerId, $trainerId);
    }

    public function thread(int $id): ?array
    {
        return db()->fetch(
            "SELECT t.*,
                    p.plan_no, p.title AS plan_title,
                    CONCAT(COALESCE(l.title,''), l.first_name, ' ', l.last_name) AS learner_name,
                    l.avatar AS learner_avatar,
                    CONCAT(COALESCE(tr.title,''), tr.first_name, ' ', tr.last_name) AS trainer_name,
                    tr.avatar AS trainer_avatar
             FROM plan_chat_threads t
             INNER JOIN learning_plans p ON p.id = t.plan_id
             LEFT JOIN users l ON l.id = t.learner_id
             LEFT JOIN users tr ON tr.id = t.trainer_id
             WHERE t.id = ?",
            [$id]
        ) ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function threadsForUser(int $userId, string $role): array
    {
        $params = [$userId, $userId];
        if ($role === 'admin') {
            $where = '1=1';
        } elseif ($role === 'trainer') {
            $where = "(t.thread_type = 'direct' AND t.trainer_id = ?)
                OR (t.thread_type = 'group' AND EXISTS (
                    SELECT 1 FROM learning_plan_owners o
                    WHERE o.plan_id = t.plan_id AND o.user_id = ? AND o.status = 'approved'
                ))";
            $params[] = $userId;
            $params[] = $userId;
        } else {
            $where = "(t.thread_type = 'direct' AND t.learner_id = ?)
                OR (t.thread_type = 'group')";
            $params[] = $userId;
        }

        return db()->fetchAll(
            "SELECT t.*,
                    p.plan_no, p.title AS plan_title,
                    CONCAT(COALESCE(l.title,''), l.first_name, ' ', l.last_name) AS learner_name,
                    CONCAT(COALESCE(tr.title,''), tr.first_name, ' ', tr.last_name) AS trainer_name,
                    (SELECT m.body FROM plan_chat_messages m
                      WHERE m.thread_id = t.id AND m.deleted_at IS NULL
                      ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT COUNT(*) FROM plan_chat_messages m
                      WHERE m.thread_id = t.id AND m.deleted_at IS NULL
                        AND m.sender_id <> ?
                        AND m.created_at > COALESCE(
                          (SELECT r.last_read_at FROM plan_chat_reads r
                            WHERE r.thread_id = t.id AND r.user_id = ?),
                          '1970-01-01'
                        )
                    ) AS unread_count
             FROM plan_chat_threads t
             INNER JOIN learning_plans p ON p.id = t.plan_id
             LEFT JOIN users l ON l.id = t.learner_id
             LEFT JOIN users tr ON tr.id = t.trainer_id
             WHERE {$where}
             ORDER BY COALESCE(t.last_message_at, t.created_at) DESC, t.id DESC
             LIMIT 300",
            $params
        );
    }

    /** @return list<array<string,mixed>> */
    public function messages(int $threadId, int $afterId = 0, int $limit = 80): array
    {
        $limit = max(1, min(200, $limit));
        $params = [$threadId];
        $afterSql = '';
        if ($afterId > 0) {
            $afterSql = ' AND m.id > ?';
            $params[] = $afterId;
        }
        return db()->fetchAll(
            "SELECT m.id, m.thread_id, m.sender_id, m.body, m.created_at,
                    CONCAT(COALESCE(u.title,''), u.first_name, ' ', u.last_name) AS sender_name,
                    u.avatar AS sender_avatar, u.role AS sender_role
             FROM plan_chat_messages m
             INNER JOIN users u ON u.id = m.sender_id
             WHERE m.thread_id = ? AND m.deleted_at IS NULL{$afterSql}
             ORDER BY m.id ASC
             LIMIT {$limit}",
            $params
        );
    }

    public function post(int $threadId, int $senderId, string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('กรุณากรอกข้อความ');
        }
        if (mb_strlen($body) > 4000) {
            throw new \InvalidArgumentException('ข้อความยาวเกินไป (สูงสุด 4000 ตัวอักษร)');
        }
        $now = date('Y-m-d H:i:s');
        $id = (int) db()->insert('plan_chat_messages', [
            'thread_id' => $threadId,
            'sender_id' => $senderId,
            'body' => $body,
            'created_at' => $now,
        ]);
        db()->update('plan_chat_threads', [
            'last_message_at' => $now,
            'updated_at' => $now,
        ], 'id = ?', [$threadId]);
        return db()->fetch(
            "SELECT m.id, m.thread_id, m.sender_id, m.body, m.created_at,
                    CONCAT(COALESCE(u.title,''), u.first_name, ' ', u.last_name) AS sender_name,
                    u.avatar AS sender_avatar, u.role AS sender_role
             FROM plan_chat_messages m
             INNER JOIN users u ON u.id = m.sender_id
             WHERE m.id = ?",
            [$id]
        ) ?: [];
    }

    public function markRead(array $thread, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $tid = (int) $thread['id'];
        $existing = db()->fetch(
            'SELECT id FROM plan_chat_reads WHERE thread_id = ? AND user_id = ?',
            [$tid, $userId]
        );
        if ($existing) {
            db()->update('plan_chat_reads', ['last_read_at' => $now], 'id = ?', [(int) $existing['id']]);
        } else {
            db()->insert('plan_chat_reads', [
                'thread_id' => $tid,
                'user_id' => $userId,
                'last_read_at' => $now,
            ]);
        }
        // keep legacy columns for direct chats
        if (!$this->isGroup($thread)) {
            if ((int) ($thread['learner_id'] ?? 0) === $userId) {
                db()->update('plan_chat_threads', ['learner_last_read_at' => $now], 'id = ?', [$tid]);
            } elseif ((int) ($thread['trainer_id'] ?? 0) === $userId || auth()->role() === 'admin') {
                db()->update('plan_chat_threads', ['trainer_last_read_at' => $now], 'id = ?', [$tid]);
            }
        }
    }
}
