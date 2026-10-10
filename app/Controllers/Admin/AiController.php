<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\AiService;

/**
 * AI ผู้ช่วย TAVIS — เชื่อม OpenAI Chat Completions
 */
class AiController extends Controller
{
    private AiService $ai;

    public function __construct()
    {
        $this->ai = new AiService();
    }

    public function index(): void
    {
        $this->requirePerm('ai');
        $this->view('admin/ai/index', [
            'title' => 'AI ผู้ช่วย',
            'breadcrumb' => 'หน้าหลัก / AI ผู้ช่วย',
            'configured' => $this->ai->isConfigured(),
            'model' => $this->ai->model(),
        ]);
    }

    public function chat(): void
    {
        $this->requirePerm('ai');
        $message = trim((string) $this->input('message', ''));
        $result = $this->ai->assistant($message, 'chat');
        $this->respond($result, 'chat');
    }

    public function summarize(): void
    {
        $this->requirePerm('ai');
        $text = trim((string) $this->input('text', $this->input('message', '')));
        if ($text === '') {
            $this->json(['ok' => false, 'error' => 'กรุณาวางข้อความที่ต้องการสรุป'], 422);
        }
        $prompt = "สรุปข้อความต่อไปนี้เป็นภาษาไทย ชัดเจน เป็นหัวข้อย่อย:\n\n" . $text;
        $result = $this->ai->assistant($prompt, 'summarize');
        $this->respond($result, 'summarize');
    }

    public function search(): void
    {
        $this->requirePerm('ai');
        $query = trim((string) $this->input('query', $this->input('q', $this->input('message', ''))));
        $result = $this->ai->search($query);
        $this->respond($result, 'search');
    }

    public function news(): void
    {
        $this->requirePerm('ai');
        $topic = trim((string) $this->input('topic', $this->input('message', '')));
        if ($topic === '') {
            $this->json(['ok' => false, 'error' => 'ระบุหัวข้อข่าวหรือกิจกรรม'], 422);
        }
        $prompt = "ช่วยร่างข่าวประชาสัมพันธ์สั้นๆ (หัวข้อ + เนื้อหา 2–3 ย่อหน้า + แฮชแท็ก) "
            . "สำหรับกิจกรรมชมรมวัคซีนจราจร สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม หัวข้อ: {$topic}";
        $result = $this->ai->assistant($prompt, 'news');
        $this->respond($result, 'news');
    }

    public function dashboard(): void
    {
        $this->requirePerm('ai');
        $stats = [
            'learning_plans' => (int) (db()->fetch('SELECT COUNT(*) AS c FROM learning_plans WHERE status = 1')['c'] ?? 0),
            'club_members' => (int) (db()->fetch('SELECT COUNT(*) AS c FROM club_members WHERE deleted_at IS NULL AND status = 1')['c'] ?? 0),
            'progress_done' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM learning_progress WHERE status = 'completed'")['c'] ?? 0),
            'users' => (int) (db()->fetch('SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND status = 1')['c'] ?? 0),
            'tickets_open' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM tickets WHERE status IN ('open','in_progress')")['c'] ?? 0),
            'logins_7d' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM login_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'success'")['c'] ?? 0),
        ];

        $prompt = "วิเคราะห์แดชบอร์ด TAVIS จากตัวเลขจริงต่อไปนี้ (อย่าแต่งตัวเลขเพิ่ม):\n"
            . json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\nสรุปสถานะระบบ 3–5 ข้อ และข้อเสนอแนะการติดตามสำหรับผู้ดูแลหน่วยงาน";

        $result = $this->ai->assistant($prompt, 'dashboard');
        if ($result['ok']) {
            $result['stats'] = $stats;
        }
        $this->respond($result, 'dashboard');
    }

    /**
     * @param array<string,mixed> $result
     */
    private function respond(array $result, string $action): void
    {
        $ok = !empty($result['ok']);
        $payload = [
            'ok' => $ok,
            'action' => $action,
            'stub' => false,
            'configured' => $this->ai->isConfigured(),
            'model' => $result['model'] ?? $this->ai->model(),
            'content' => $result['content'] ?? null,
            'error' => $result['error'] ?? null,
            'matches' => $result['matches'] ?? null,
            'stats' => $result['stats'] ?? null,
            'usage' => $result['usage'] ?? null,
            'offline' => $result['offline'] ?? false,
        ];

        if ($ok && function_exists('activity_log')) {
            try {
                activity_log('ai_' . $action, 'ai', 'เรียกใช้ AI: ' . $action);
            } catch (\Throwable $e) {
                // ignore audit failures
            }
        }

        $this->json($payload, $ok ? 200 : 422);
    }
}
