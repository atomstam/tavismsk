<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\PlanChatService;

class PlanChatController extends Controller
{
    private PlanChatService $chat;

    public function __construct()
    {
        $this->chat = new PlanChatService();
    }

    public function index(): void
    {
        $this->requirePerm('plan_chat', 'view');
        $userId = (int) auth()->id();
        $role = (string) (auth()->role() ?? 'user');
        $threads = $this->chat->threadsForUser($userId, $role);
        $plans = db()->fetchAll(
            'SELECT id, plan_no, title FROM learning_plans WHERE status = 1 ORDER BY plan_no'
        );
        $trainersByPlan = [];
        $learners = [];
        foreach ($plans as $p) {
            $trainersByPlan[(int) $p['id']] = $this->chat->trainersForPlan((int) $p['id']);
        }
        if ($role === 'trainer') {
            $learners = $this->chat->learnersForPlan(0);
        }
        $this->view('admin/plan_chat/index', [
            'title' => 'กระดานสนทนา',
            'breadcrumb' => 'หน้าหลัก / กระดานสนทนา',
            'threads' => $threads,
            'plans' => $plans,
            'trainersByPlan' => $trainersByPlan,
            'learners' => $learners,
            'role' => $role,
        ]);
    }

    public function start(): void
    {
        $this->requirePerm('plan_chat', 'create');
        $planId = (int) $this->input('plan_id', 0);
        $room = (string) $this->input('room', 'group');
        $uid = (int) auth()->id();
        $role = (string) (auth()->role() ?? 'user');

        try {
            if ($room === 'group') {
                if (!$this->chat->canJoinPlan($planId, $uid)) {
                    throw new \RuntimeException('ไม่มีสิทธิ์เข้าห้องรวมของแผนนี้');
                }
                $thread = $this->chat->ensureGroupThread($planId);
            } else {
                $trainerId = (int) $this->input('trainer_id', 0);
                $learnerId = (int) $this->input('learner_id', 0);
                if ($role === 'trainer') {
                    $trainerId = $uid;
                    if (!$this->chat->isPlanOwner($planId, $trainerId)) {
                        throw new \RuntimeException('คุณยังไม่ได้รับมอบหมายเป็นวิทยากรของแผนนี้');
                    }
                    if ($learnerId <= 0) {
                        throw new \RuntimeException('กรุณาเลือกผู้เรียน');
                    }
                } else {
                    // ผู้เรียน / แอดมิน: เลือกเฉพาะวิทยากรที่ดูแลแผนนี้
                    $learnerId = $learnerId > 0 ? $learnerId : $uid;
                    if ($trainerId <= 0) {
                        throw new \RuntimeException('กรุณาเลือกวิทยากรของแผนนี้');
                    }
                    if (!$this->chat->isPlanOwner($planId, $trainerId)) {
                        throw new \RuntimeException('วิทยากรที่เลือกไม่ได้ดูแลแผนนี้');
                    }
                }
                $thread = $this->chat->findOrCreateDirect($planId, $learnerId, $trainerId);
            }
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/admin/plan-chat');
        }
        $this->redirect('/admin/plan-chat/' . (int) $thread['id']);
    }

    public function show(string $id): void
    {
        $this->requirePerm('plan_chat', 'view');
        $thread = $this->chat->thread((int) $id);
        if (!$thread || !$this->chat->canAccessThread($thread, (int) auth()->id())) {
            $this->flash('error', 'ไม่พบการสนทนา หรือไม่มีสิทธิ์เข้าดู');
            $this->redirect('/admin/plan-chat');
        }
        $this->chat->markRead($thread, (int) auth()->id());
        $messages = $this->chat->messages((int) $thread['id']);
        $this->view('admin/plan_chat/show', [
            'title' => 'สนทนา · แผนที่ ' . (int) $thread['plan_no'],
            'breadcrumb' => 'หน้าหลัก / กระดานสนทนา / รายละเอียด',
            'thread' => $thread,
            'messages' => $messages,
            'meId' => (int) auth()->id(),
            'isGroup' => $this->chat->isGroup($thread),
        ]);
    }

    public function messages(string $id): void
    {
        $this->requirePerm('plan_chat', 'view');
        $thread = $this->chat->thread((int) $id);
        if (!$thread || !$this->chat->canAccessThread($thread, (int) auth()->id())) {
            $this->json(['ok' => false, 'error' => 'forbidden'], 403);
        }
        $after = (int) $this->input('after', 0);
        $rows = $this->chat->messages((int) $thread['id'], $after);
        $this->chat->markRead($thread, (int) auth()->id());
        $me = (int) auth()->id();
        $this->json([
            'ok' => true,
            'messages' => array_map(
                fn (array $m) => $this->serializeMessage($m, $me, $thread),
                $rows
            ),
        ]);
    }

    public function post(string $id): void
    {
        $this->requirePerm('plan_chat', 'create');
        $thread = $this->chat->thread((int) $id);
        $uid = (int) auth()->id();
        if (!$thread || !$this->chat->canPost($thread, $uid)) {
            $this->json(['ok' => false, 'error' => 'forbidden'], 403);
        }
        if (
            !$this->chat->isGroup($thread)
            && auth()->role() === 'trainer'
            && (int) ($thread['trainer_id'] ?? 0) !== $uid
        ) {
            $this->json(['ok' => false, 'error' => 'forbidden'], 403);
        }
        try {
            $msg = $this->chat->post((int) $thread['id'], $uid, (string) $this->input('body', ''));
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
        $this->chat->markRead($thread, $uid);
        $this->json([
            'ok' => true,
            'message' => $this->serializeMessage($msg, $uid, $thread),
        ]);
    }

    /** จากหน้าแผน — ให้เลือกห้องรวมหรือตัวต่อตัว */
    public function fromPlan(string $planId): void
    {
        $this->requirePerm('plan_chat', 'view');
        $pid = (int) $planId;
        $uid = (int) auth()->id();
        $role = (string) (auth()->role() ?? 'user');
        $plan = db()->fetch('SELECT id, plan_no, title FROM learning_plans WHERE id = ?', [$pid]);
        if (!$plan) {
            $this->flash('error', 'ไม่พบแผน');
            $this->redirect('/admin/plan-chat');
        }

        $room = (string) $this->input('room', '');
        if ($room === 'group') {
            try {
                if (!$this->chat->canJoinPlan($pid, $uid)) {
                    throw new \RuntimeException('ไม่มีสิทธิ์เข้าห้องรวม');
                }
                $thread = $this->chat->ensureGroupThread($pid);
                $this->redirect('/admin/plan-chat/' . (int) $thread['id']);
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
                $this->redirect('/admin/learn/' . $pid);
            }
        }

        $trainers = $this->chat->trainersForPlan($pid);
        $learners = $role === 'trainer' ? $this->chat->learnersForPlan($pid) : [];

        $this->view('admin/plan_chat/pick_room', [
            'title' => 'เลือกห้องสนทนา',
            'breadcrumb' => 'หน้าหลัก / กระดานสนทนา / เลือกห้อง',
            'plan' => $plan,
            'trainers' => $trainers,
            'learners' => $learners,
            'role' => $role,
        ]);
    }

    /** @param array<string,mixed> $m @param array<string,mixed> $thread */
    private function serializeMessage(array $m, int $meId, array $thread): array
    {
        $senderId = (int) ($m['sender_id'] ?? 0);
        $trainerId = (int) ($thread['trainer_id'] ?? 0);
        $isTrainer = $trainerId > 0
            ? $senderId === $trainerId
            : in_array((string) ($m['sender_role'] ?? ''), ['trainer', 'admin'], true)
                || $this->chat->isPlanOwner((int) ($thread['plan_id'] ?? 0), $senderId);

        return [
            'id' => (int) ($m['id'] ?? 0),
            'sender_id' => $senderId,
            'sender_name' => (string) ($m['sender_name'] ?? ''),
            'body' => (string) ($m['body'] ?? ''),
            'created_at' => (string) ($m['created_at'] ?? ''),
            'created_label' => function_exists('thai_date')
                ? thai_date((string) ($m['created_at'] ?? ''), true)
                : (string) ($m['created_at'] ?? ''),
            'mine' => $senderId === $meId,
            'is_trainer' => $isTrainer,
        ];
    }
}
