<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Services\DashboardStatsService;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->track();
        $modules = portfolio_modules();

        $works = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM portfolio_items WHERE deleted_at IS NULL AND is_public = 1"
        )['c'] ?? 0);
        $awards = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM portfolio_items WHERE deleted_at IS NULL AND is_public = 1 AND type = 'assessments'"
        )['c'] ?? 0);
        $teachers = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND status = 1 AND role IN ('teacher','director','deputy','admin')"
        )['c'] ?? 0);
        $members = 0;
        try {
            $members = (int) (db()->fetch(
                "SELECT COUNT(*) AS c FROM club_members WHERE deleted_at IS NULL AND status = 1"
            )['c'] ?? 0);
        } catch (\Throwable $e) {
            $members = 0;
        }

        $featuredWorks = db()->fetchAll(
            "SELECT pi.*, u.title AS title_name, u.first_name, u.last_name
             FROM portfolio_items pi
             INNER JOIN users u ON u.id = pi.user_id AND u.deleted_at IS NULL
             WHERE pi.deleted_at IS NULL AND pi.is_public = 1
             ORDER BY pi.is_featured DESC, pi.views DESC, pi.start_date DESC, pi.id DESC
             LIMIT 6"
        );

        $albums = db()->fetchAll(
            "SELECT * FROM gallery_albums
             WHERE deleted_at IS NULL AND status = 1 AND is_public = 1
             ORDER BY event_date DESC, id DESC LIMIT 4"
        );

        $news = db()->fetchAll(
            'SELECT * FROM news WHERE status = 1 ORDER BY published_at DESC LIMIT 4'
        );

        $teacherCards = db()->fetchAll(
            "SELECT u.id, u.title, u.first_name, u.last_name, u.avatar, u.role,
                    p.photo, COALESCE(pt.title, p.position_text) AS position_name
             FROM users u
             LEFT JOIN personnel p ON p.user_id = u.id AND p.deleted_at IS NULL
             LEFT JOIN sc_personal_types pt ON pt.id = p.personal_type_id
             WHERE u.deleted_at IS NULL AND u.status = 1 AND u.role IN ('teacher','director','deputy')
             ORDER BY u.first_name LIMIT 4"
        );

        // สุ่มบุคลากรสำหรับ Hero (เน้นคนที่มีรูปส่วนตัว)
        $heroStaff = db()->fetch(
            "SELECT u.id, u.title, u.first_name, u.last_name, u.avatar, u.role,
                    p.photo, COALESCE(pt.title, p.position_text) AS position_name, ar.name AS rank_name
             FROM users u
             LEFT JOIN personnel p ON p.user_id = u.id AND p.deleted_at IS NULL
             LEFT JOIN sc_personal_types pt ON pt.id = p.personal_type_id
             LEFT JOIN academic_ranks ar ON ar.id = p.academic_rank_id
             WHERE u.deleted_at IS NULL AND u.status = 1
               AND u.role IN ('teacher','director','deputy')
             ORDER BY
               CASE
                 WHEN COALESCE(NULLIF(p.photo,''), NULLIF(u.avatar,'')) IS NOT NULL THEN 0
                 ELSE 1
               END,
               RAND()
             LIMIT 1"
        ) ?: ($teacherCards[0] ?? null);

        $this->webView('web/home', [
            'title' => app_brand('name'),
            'modules' => $modules,
            'stats' => [
                'years' => setting('school_years_label') ?: '14',
                'works' => $works,
                'awards' => $awards,
                'teachers' => $teachers,
                'members' => $members,
            ],
            'featuredWorks' => $featuredWorks,
            'albums' => $albums,
            'news' => $news,
            'teachers' => $teacherCards,
            'heroStaff' => $heroStaff,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    private function visitorStats(): array
    {
        try {
            $today = (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE DATE(visited_at) = CURDATE()")['c'] ?? 0);
            $month = (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE YEAR(visited_at)=YEAR(CURDATE()) AND MONTH(visited_at)=MONTH(CURDATE())")['c'] ?? 0);
            $all = (int) (db()->fetch('SELECT COUNT(*) AS c FROM visitors')['c'] ?? 0);
            return compact('today', 'month', 'all');
        } catch (\Throwable $e) {
            return ['today' => 0, 'month' => 0, 'all' => 0];
        }
    }

    public function card(string $key): void
    {
        $this->releaseSession();
        $data = (new DashboardStatsService())->card($key, 0, true);
        $this->json($data, !empty($data['ok']) ? 200 : 404);
    }

    public function chart(string $key): void
    {
        $this->releaseSession();
        $data = (new DashboardStatsService())->chart($key, 0, true, null, false);
        $this->json($data, !empty($data['ok']) ? 200 : 404);
    }

    public function about(): void
    {
        $this->track();
        $row = db()->fetch("SELECT * FROM pages WHERE slug = 'about'");
        $this->webView('web/page', [
            'title' => $row['title'] ?? 'เกี่ยวกับหน่วยงาน',
            'row' => $row,
            'pageHero' => true,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function news(): void
    {
        $this->track();
        $rows = db()->fetchAll('SELECT * FROM news WHERE status = 1 ORDER BY published_at DESC');
        $this->webView('web/news', [
            'title' => 'ข่าวสารและประกาศ',
            'rows' => $rows,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function newsShow(string $slug): void
    {
        $this->track();
        $row = db()->fetch('SELECT * FROM news WHERE slug = ? AND status = 1', [$slug]);
        if (!$row) {
            http_response_code(404);
            $this->webView('errors/404', ['title' => 'ไม่พบข่าว']);
            return;
        }
        db()->query('UPDATE news SET views = views + 1 WHERE id = ?', [$row['id']]);
        $this->webView('web/news-show', [
            'title' => $row['title'],
            'row' => $row,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function downloads(): void
    {
        $this->track();
        $rows = db()->fetchAll('SELECT * FROM downloads WHERE status = 1 ORDER BY id DESC');
        $this->webView('web/downloads', [
            'title' => 'ดาวน์โหลดแฟ้มผลงาน',
            'rows' => $rows,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function faq(): void
    {
        $this->track();
        $rows = db()->fetchAll('SELECT * FROM faqs WHERE status = 1 ORDER BY sort_order, id');
        $this->webView('web/faq', [
            'title' => 'คำถามที่พบบ่อย',
            'rows' => $rows,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function contact(): void
    {
        $this->track();
        $this->webView('web/contact', [
            'title' => 'ติดต่อสอบถาม / ประสานงาน',
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function contactStore(): void
    {
        $captcha = strtoupper(trim((string) $this->input('captcha', '')));
        if ($captcha === '' || $captcha !== strtoupper((string) ($_SESSION['captcha'] ?? ''))) {
            $this->flash('error', 'รหัส Captcha ไม่ถูกต้อง');
            $this->redirect('/contact');
        }
        db()->insert('contact_messages', [
            'name' => trim((string) $this->input('name', '')),
            'email' => trim((string) $this->input('email', '')),
            'phone' => trim((string) $this->input('phone', '')),
            'subject' => trim((string) $this->input('subject', '')),
            'message' => trim((string) $this->input('message', '')),
            'ip_address' => request_ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->flash('success', 'ส่งข้อความเรียบร้อย ขอบคุณที่ติดต่อ');
        $this->redirect('/contact');
    }

    public function captcha(): void
    {
        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $_SESSION['captcha'] = $code;
        header('Content-Type: image/png');
        $im = imagecreatetruecolor(140, 44);
        $bg = imagecolorallocate($im, 241, 245, 249);
        $fg = imagecolorallocate($im, 29, 78, 216);
        imagefilledrectangle($im, 0, 0, 140, 44, $bg);
        imagestring($im, 5, 28, 14, $code, $fg);
        imagepng($im);
        imagedestroy($im);
        exit;
    }

    public function formShow(string $slug): void
    {
        $form = db()->fetch('SELECT * FROM forms WHERE slug = ? AND status = 1', [$slug]);
        if (!$form) {
            http_response_code(404);
            $this->webView('errors/404', ['title' => 'ไม่พบแบบฟอร์ม']);
            return;
        }
        $fields = db()->fetchAll('SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order', [$form['id']]);
        $this->webView('web/form', ['title' => $form['title'], 'form' => $form, 'fields' => $fields]);
    }

    public function formStore(string $slug): void
    {
        $form = db()->fetch('SELECT * FROM forms WHERE slug = ? AND status = 1', [$slug]);
        if (!$form) {
            $this->redirect('/');
        }
        $fields = db()->fetchAll('SELECT * FROM form_fields WHERE form_id = ?', [$form['id']]);
        $payload = [];
        foreach ($fields as $f) {
            $payload[$f['name']] = $_POST[$f['name']] ?? '';
        }
        db()->insert('form_submissions', [
            'form_id' => $form['id'],
            'user_id' => auth()->id(),
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->flash('success', 'ส่งแบบฟอร์มเรียบร้อย');
        $this->redirect('/forms/' . $slug);
    }

    private function page(string $slug, string $fallbackTitle): void
    {
        $row = db()->fetch('SELECT * FROM pages WHERE slug = ?', [$slug]);
        $this->webView('web/page', [
            'title' => $row['title'] ?? $fallbackTitle,
            'row' => $row,
        ]);
    }

    private function track(): void
    {
        try {
            db()->insert('visitors', [
                'ip_address' => request_ip(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'path' => substr($_SERVER['REQUEST_URI'] ?? '/', 0, 255),
                'visited_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
