<?php

declare(strict_types=1);

namespace App\Services;

class NotificationService
{
    public function lineNotify(string $message): bool
    {
        $token = env('LINE_NOTIFY_TOKEN', '');
        if (!$token) {
            return false;
        }
        $ch = curl_init('https://notify-api.line.me/api/notify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
            CURLOPT_POSTFIELDS => ['message' => $message],
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }

    public function telegram(string $message): bool
    {
        $token = env('TELEGRAM_BOT_TOKEN', '');
        $chatId = env('TELEGRAM_CHAT_ID', '');
        if (!$token || !$chatId) {
            return false;
        }
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }

    public function broadcast(string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        foreach ($this->adminUserIds() as $uid) {
            $this->send($uid, $title, $message, $type, $link);
        }
        $this->lineNotify("[{$title}] {$message}");
        $this->telegram("<b>{$title}</b>\n{$message}");
    }

    /**
     * รายการใหม่ในกลุ่มกิจกรรม / ผลงาน
     * แจ้งแอดมิน + วิทยากร (ยกเว้นผู้สร้าง)
     */
    public function portfolioCreated(string $type, int $itemId, string $title, int $actorUserId): void
    {
        $mod = portfolio_module($type);
        $short = (string) ($mod['short'] ?? $type);
        $modTitle = (string) ($mod['title'] ?? $type);
        $actor = $this->userName($actorUserId);
        $msg = $actor . ' เพิ่มรายการใหม่ใน' . $modTitle . ': ' . $title;
        $link = '/admin/portfolio/' . $type . '/' . $itemId;
        $targets = array_unique(array_merge($this->adminUserIds(), $this->trainerUserIds()));
        foreach ($targets as $uid) {
            if ($uid === $actorUserId) {
                continue;
            }
            $this->send($uid, 'ผลงานใหม่ · ' . $short, $msg, 'portfolio', $link);
        }
    }

    /**
     * ผู้เรียนส่งภาระงานรอตรวจ
     * Admin = ทุกแผน · Trainer = เฉพาะแผนที่รับผิดชอบ
     */
    public function taskSubmitted(int $planId, int $taskId, int $submissionUserId, string $learnerName = ''): void
    {
        $plan = db()->fetch('SELECT id, plan_no, title FROM learning_plans WHERE id = ?', [$planId]) ?: [];
        $task = db()->fetch('SELECT id, title FROM training_tasks WHERE id = ?', [$taskId]) ?: [];
        if ($plan === [] || $task === []) {
            return;
        }
        if ($learnerName === '') {
            $learnerName = $this->userName($submissionUserId);
        }
        $planNo = (int) ($plan['plan_no'] ?? 0);
        $title = 'ภาระงานรอตรวจ · แผนที่ ' . $planNo;
        $msg = $learnerName . ' ส่งงาน «' . (string) ($task['title'] ?? 'ภาระงาน') . '» ในแผน '
            . (string) ($plan['title'] ?? '') . ' — รอวิทยากรให้คะแนน';
        $link = '/admin/training/' . $planId . '?tab=submissions';

        $admins = $this->adminUserIds();
        $owners = (new PlanOwnerService())->approvedOwnerUserIds($planId);
        // แอดมินได้รับทุกแผนเสมอ (แม้เป็นคนส่งงานในฐานะผู้เรียน)
        foreach ($admins as $uid) {
            $this->send($uid, $title, $msg, 'task_review', $link);
        }
        foreach ($owners as $uid) {
            if ($uid === $submissionUserId || in_array($uid, $admins, true)) {
                continue;
            }
            $this->send($uid, $title, $msg, 'task_review', $link);
        }
    }

    /**
     * ซิงก์แจ้งเตือนภาระงานที่รอตรวจอยู่แล้วในระบบ
     * (กรณีส่งงานก่อนมีโค้ดแจ้งเตือน หรือแอดมินเป็นผู้เรียนเอง)
     *
     * @return int จำนวนแจ้งเตือนที่สร้าง/อัปเดต
     */
    public function syncPendingTaskReviewsForUser(int $userId, string $role): int
    {
        if ($userId <= 0 || !in_array($role, ['admin', 'trainer'], true)) {
            return 0;
        }

        $planFilter = '';
        $params = [];
        if ($role === 'trainer') {
            $owned = (new PlanOwnerService())->approvedPlanIds($userId);
            if ($owned === []) {
                $this->clearStaleTaskReviewAlerts($userId, []);
                return 0;
            }
            $planFilter = ' AND t.plan_id IN (' . implode(',', array_map('intval', $owned)) . ')';
        }

        $rows = db()->fetchAll(
            "SELECT t.plan_id, p.plan_no, p.title AS plan_title, COUNT(*) AS pending
             FROM training_task_submissions s
             INNER JOIN training_tasks t ON t.id = s.task_id AND t.status = 1
             INNER JOIN learning_plans p ON p.id = t.plan_id
             WHERE s.status IN ('submitted','revision'){$planFilter}
             GROUP BY t.plan_id, p.plan_no, p.title
             ORDER BY p.plan_no, t.plan_id",
            $params
        );

        $activePlanIds = [];
        $touched = 0;
        foreach ($rows as $row) {
            $planId = (int) $row['plan_id'];
            $pending = (int) $row['pending'];
            if ($pending < 1) {
                continue;
            }
            $activePlanIds[] = $planId;
            $planNo = (int) ($row['plan_no'] ?? 0);
            $title = 'ภาระงานรอตรวจ · แผนที่ ' . $planNo;
            $msg = 'มี ' . $pending . ' รายการรอให้คะแนนในแผน «' . (string) ($row['plan_title'] ?? '') . '»';
            $link = '/admin/training/' . $planId . '?tab=submissions';
            $this->upsertUnread($userId, $title, $msg, 'task_review', $link);
            $touched++;
        }

        $this->clearStaleTaskReviewAlerts($userId, $activePlanIds);
        return $touched;
    }

    /** @param list<int> $activePlanIds */
    private function clearStaleTaskReviewAlerts(int $userId, array $activePlanIds): void
    {
        $rows = db()->fetchAll(
            "SELECT id, link FROM notifications
             WHERE user_id = ? AND type = 'task_review' AND is_read = 0",
            [$userId]
        );
        foreach ($rows as $n) {
            $link = (string) ($n['link'] ?? '');
            if (!preg_match('#/admin/training/(\d+)#', $link, $m)) {
                continue;
            }
            $pid = (int) $m[1];
            // แผนนี้ไม่มีงานรอแล้ว — ปิดแจ้งเตือนค้าง
            if (!in_array($pid, $activePlanIds, true)) {
                db()->update('notifications', ['is_read' => 1], 'id = ?', [(int) $n['id']]);
            }
        }
    }

    private function upsertUnread(int $userId, string $title, string $message, string $type, string $link): void
    {
        $ex = db()->fetch(
            "SELECT id FROM notifications
             WHERE user_id = ? AND type = ? AND link = ? AND is_read = 0
             ORDER BY id DESC LIMIT 1",
            [$userId, $type, $link]
        );
        if ($ex) {
            db()->update('notifications', [
                'title' => $title,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $ex['id']]);
            return;
        }
        notify($userId, $title, $message, $type, $link);
    }

    /**
     * วิทยากรตรวจให้คะแนนแล้ว — แจ้งผู้เรียนเจ้าของงาน
     */
    public function taskReviewed(int $submissionId, string $status): void
    {
        $sub = db()->fetch(
            "SELECT s.*, t.title AS task_title, t.plan_id, p.plan_no, p.title AS plan_title
             FROM training_task_submissions s
             INNER JOIN training_tasks t ON t.id = s.task_id
             INNER JOIN learning_plans p ON p.id = t.plan_id
             WHERE s.id = ?",
            [$submissionId]
        );
        if (!$sub) {
            return;
        }
        $userId = (int) ($sub['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }
        $planId = (int) ($sub['plan_id'] ?? 0);
        $planNo = (int) ($sub['plan_no'] ?? 0);
        $taskTitle = (string) ($sub['task_title'] ?? 'ภาระงาน');
        $score = $sub['score'] !== null ? number_format((float) $sub['score'], 1) : null;

        if ($status === 'approved') {
            $title = 'ตรวจภาระงานแล้ว · แผนที่ ' . $planNo;
            $msg = 'วิทยากรให้คะแนน «' . $taskTitle . '» แล้ว'
                . ($score !== null ? ' (' . $score . ' คะแนน)' : '')
                . ' ในแผน ' . (string) ($sub['plan_title'] ?? '');
            $type = 'task_graded';
        } elseif ($status === 'revision') {
            $title = 'ขอแก้ไขภาระงาน · แผนที่ ' . $planNo;
            $msg = 'วิทยากรขอให้แก้ไข «' . $taskTitle . '» — กรุณาส่งใหม่';
            $type = 'task_revision';
        } else {
            $title = 'ผลการตรวจภาระงาน · แผนที่ ' . $planNo;
            $msg = 'ภาระงาน «' . $taskTitle . '» ไม่ผ่านการตรวจ';
            $type = 'task_rejected';
        }
        $link = '/admin/learn/' . $planId;
        $this->send($userId, $title, $msg, $type, $link);
    }

    public function send(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        if ($userId <= 0) {
            return;
        }
        // กันซ้ำ: หัวข้อ+ลิงก์เดียวกันที่ยังไม่อ่านใน 10 นาที
        if ($link) {
            $dup = db()->fetch(
                "SELECT id FROM notifications
                 WHERE user_id = ? AND title = ? AND link = ? AND is_read = 0
                   AND created_at >= (NOW() - INTERVAL 10 MINUTE)
                 LIMIT 1",
                [$userId, $title, $link]
            );
            if ($dup) {
                db()->update('notifications', [
                    'message' => $message,
                    'type' => $type,
                    'created_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [(int) $dup['id']]);
                return;
            }
        }
        notify($userId, $title, $message, $type, $link);
    }

    /** @return list<int> */
    private function adminUserIds(): array
    {
        $rows = db()->fetchAll(
            "SELECT id FROM users WHERE role = 'admin' AND status = 1 AND deleted_at IS NULL"
        );
        return array_map(static fn (array $r): int => (int) $r['id'], $rows);
    }

    /** @return list<int> */
    private function trainerUserIds(): array
    {
        $rows = db()->fetchAll(
            "SELECT id FROM users WHERE role = 'trainer' AND status = 1 AND deleted_at IS NULL"
        );
        return array_map(static fn (array $r): int => (int) $r['id'], $rows);
    }

    private function userName(int $userId): string
    {
        $u = db()->fetch(
            'SELECT title, first_name, last_name, username FROM users WHERE id = ?',
            [$userId]
        );
        if (!$u) {
            return 'ผู้ใช้';
        }
        $name = trim(($u['title'] ?? '') . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        return $name !== '' ? $name : (string) ($u['username'] ?? 'ผู้ใช้');
    }
}
