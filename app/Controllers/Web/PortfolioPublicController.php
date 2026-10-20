<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;

/**
 * หน้าแฟ้มสะสมผลงานสาธารณะ (Clean Style)
 */
class PortfolioPublicController extends Controller
{
    public function index(): void
    {
        $this->track();
        $type = trim((string) $this->input('type', ''));
        $q = trim((string) $this->input('q', ''));
        $year = (int) $this->input('year', 0);
        $sort = trim((string) $this->input('sort', 'latest'));
        $view = trim((string) $this->input('view', 'list'));
        $staffId = (int) $this->input('staff', 0);
        $modules = portfolio_modules();
        $activeMod = ($type !== '' && isset($modules[$type])) ? $modules[$type] : null;

        $teachers = $this->listTeachers();

        $where = 'pi.deleted_at IS NULL AND pi.is_public = 1';
        $params = [];
        if ($staffId > 0) {
            $where .= ' AND pi.user_id = ?';
            $params[] = $staffId;
        }
        if ($activeMod) {
            $where .= ' AND pi.type = ?';
            $params[] = $type;
        }
        if ($year > 0) {
            $where .= ' AND pi.academic_year = ?';
            $params[] = $year;
        }
        if ($q !== '') {
            $where .= ' AND (pi.title LIKE ? OR pi.description LIKE ? OR pi.organization LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $order = match ($sort) {
            'views' => 'pi.views DESC, pi.id DESC',
            'title' => 'pi.title ASC',
            default => 'pi.is_featured DESC, pi.start_date DESC, pi.id DESC',
        };

        $works = db()->fetchAll(
            "SELECT pi.*, u.title AS title_name, u.first_name, u.last_name
             FROM portfolio_items pi
             INNER JOIN users u ON u.id = pi.user_id AND u.deleted_at IS NULL
             WHERE {$where}
             ORDER BY {$order}
             LIMIT 120",
            $params
        );

        $countWhere = 'deleted_at IS NULL AND is_public = 1';
        $countParams = [];
        if ($staffId > 0) {
            $countWhere .= ' AND user_id = ?';
            $countParams[] = $staffId;
        }
        $typeCounts = [];
        foreach (db()->fetchAll(
            "SELECT type, COUNT(*) AS c FROM portfolio_items WHERE {$countWhere} GROUP BY type",
            $countParams
        ) as $r) {
            $typeCounts[(string) $r['type']] = (int) $r['c'];
        }

        $selectedStaff = null;
        if ($staffId > 0) {
            foreach ($teachers as $t) {
                if ((int) $t['id'] === $staffId) {
                    $selectedStaff = $t;
                    break;
                }
            }
            if (!$selectedStaff) {
                $selectedStaff = $this->findTeacher($staffId);
            }
        }

        $this->webView('web/portfolio/index', [
            'title' => $activeMod['title'] ?? 'ข้อมูลกิจกรรมและผลงาน',
            'teachers' => $teachers,
            'modules' => $modules,
            'activeType' => $type,
            'activeMod' => $activeMod,
            'works' => $works,
            'typeCounts' => $typeCounts,
            'selectedStaff' => $selectedStaff,
            'staffId' => $staffId,
            'q' => $q,
            'year' => $year,
            'sort' => $sort,
            'view' => $view,
            'years' => academic_years_list(),
            'darkUi' => true,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    private function visitorStats(): array
    {
        try {
            return [
                'today' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE DATE(visited_at) = CURDATE()")['c'] ?? 0),
                'month' => (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE YEAR(visited_at)=YEAR(CURDATE()) AND MONTH(visited_at)=MONTH(CURDATE())")['c'] ?? 0),
                'all' => (int) (db()->fetch('SELECT COUNT(*) AS c FROM visitors')['c'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['today' => 0, 'month' => 0, 'all' => 0];
        }
    }

    public function teacher(string $id): void
    {
        $this->track();
        $user = $this->findTeacher((int) $id);
        if (!$user) {
            http_response_code(404);
            $this->webView('errors/404', ['title' => 'ไม่พบบุคลากร']);
            return;
        }

        $counts = $this->typeCounts((int) $user['id']);
        $total = array_sum($counts);
        $featured = db()->fetchAll(
            "SELECT * FROM portfolio_items
             WHERE user_id = ? AND deleted_at IS NULL AND is_public = 1
             ORDER BY is_featured DESC, views DESC, start_date DESC
             LIMIT 12",
            [(int) $user['id']]
        );

        $this->webView('web/portfolio/cover', [
            'title' => 'แฟ้มสะสมผลงาน ' . trim(($user['title'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']),
            'teacher' => $user,
            'counts' => $counts,
            'total' => $total,
            'featured' => $featured,
            'modules' => portfolio_modules(),
            'layout_clean' => true,
        ]);
    }

    public function summary(string $id): void
    {
        $this->track();
        $user = $this->findTeacher((int) $id);
        if (!$user) {
            http_response_code(404);
            $this->webView('errors/404', ['title' => 'ไม่พบบุคลากร']);
            return;
        }

        $counts = $this->typeCounts((int) $user['id']);
        $items = db()->fetchAll(
            "SELECT id, type, title, description, organization, start_date, is_featured, views, cover_image
             FROM portfolio_items
             WHERE user_id = ? AND deleted_at IS NULL AND is_public = 1
             ORDER BY is_featured DESC, start_date DESC, id DESC",
            [(int) $user['id']]
        );

        $photoCount = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM portfolio_files pf
             INNER JOIN portfolio_items pi ON pi.id = pf.item_id
             WHERE pi.user_id = ? AND pi.deleted_at IS NULL AND pf.kind = 'photo'",
            [(int) $user['id']]
        )['c'] ?? 0);

        $this->webView('web/portfolio/summary', [
            'title' => 'สรุปผลงาน',
            'teacher' => $user,
            'counts' => $counts,
            'items' => $items,
            'modules' => portfolio_modules(),
            'stats' => [
                'works' => count($items),
                'awards' => (int) ($counts['awards'] ?? 0),
                'photos' => $photoCount,
                'featured' => (int) ($counts['outstanding'] ?? 0),
            ],
            'layout_clean' => true,
        ]);
    }

    public function works(string $id): void
    {
        $this->track();
        $user = $this->findTeacher((int) $id);
        if (!$user) {
            http_response_code(404);
            $this->webView('errors/404', ['title' => 'ไม่พบบุคลากร']);
            return;
        }

        $type = trim((string) $this->input('type', ''));
        $q = trim((string) $this->input('q', ''));
        $year = (int) $this->input('year', 0);
        $sort = trim((string) $this->input('sort', 'latest'));
        $view = trim((string) $this->input('view', 'list'));
        $modules = portfolio_modules();
        $counts = $this->typeCounts((int) $user['id']);

        $where = 'user_id = ? AND deleted_at IS NULL AND is_public = 1';
        $params = [(int) $user['id']];
        if ($type !== '' && isset($modules[$type])) {
            $where .= ' AND type = ?';
            $params[] = $type;
        }
        if ($year > 0) {
            $where .= ' AND academic_year = ?';
            $params[] = $year;
        }
        if ($q !== '') {
            $where .= ' AND (title LIKE ? OR description LIKE ? OR organization LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $order = match ($sort) {
            'views' => 'views DESC, id DESC',
            'title' => 'title ASC',
            default => 'is_featured DESC, start_date DESC, id DESC',
        };

        $items = db()->fetchAll(
            "SELECT * FROM portfolio_items WHERE {$where}
             ORDER BY {$order}
             LIMIT 200",
            $params
        );

        $activeMod = $type !== '' ? ($modules[$type] ?? null) : null;

        $this->webView('web/portfolio/works', [
            'title' => $activeMod['title'] ?? 'ข้อมูลกิจกรรมและผลงาน',
            'teacher' => $user,
            'teachers' => $this->listTeachers(),
            'items' => $items,
            'modules' => $modules,
            'counts' => $counts,
            'activeType' => $type,
            'activeMod' => $activeMod,
            'q' => $q,
            'year' => $year,
            'sort' => $sort,
            'view' => $view,
            'years' => academic_years_list(),
            'totalAll' => array_sum($counts),
            'darkUi' => true,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function show(string $id, string $itemId): void
    {
        $this->track();
        $user = $this->findTeacher((int) $id);
        $item = db()->fetch(
            'SELECT * FROM portfolio_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL AND is_public = 1',
            [(int) $itemId, (int) $id]
        );
        if (!$user || !$item) {
            http_response_code(404);
            $this->webView('errors/404', ['title' => 'ไม่พบผลงาน']);
            return;
        }

        db()->query('UPDATE portfolio_items SET views = views + 1 WHERE id = ?', [(int) $item['id']]);
        $item['views'] = (int) $item['views'] + 1;

        $files = db()->fetchAll(
            'SELECT * FROM portfolio_files WHERE item_id = ? ORDER BY sort_order, id',
            [(int) $item['id']]
        );
        $mod = portfolio_module((string) $item['type']);

        $this->webView('web/portfolio/show', [
            'title' => $item['title'],
            'teacher' => $user,
            'teachers' => $this->listTeachers(),
            'item' => $item,
            'files' => $files,
            'mod' => $mod,
            'modules' => portfolio_modules(),
            'counts' => $this->typeCounts((int) $user['id']),
            'darkUi' => true,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    public function gallery(): void
    {
        $this->track();
        $albums = db()->fetchAll(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM gallery_media m WHERE m.album_id = a.id AND m.media_type = 'image') AS image_count,
                    (SELECT COUNT(*) FROM gallery_media m WHERE m.album_id = a.id AND m.media_type = 'video') AS video_count
             FROM gallery_albums a
             WHERE a.deleted_at IS NULL AND a.status = 1 AND a.is_public = 1
             ORDER BY a.event_date DESC, a.id DESC
             LIMIT 100"
        );
        $stats = [
            'albums' => count($albums),
            'images' => (int) array_sum(array_column($albums, 'image_count')),
            'videos' => (int) array_sum(array_column($albums, 'video_count')),
            'categories' => count(array_unique(array_filter(array_column($albums, 'category')))),
        ];

        $this->webView('web/portfolio/gallery', [
            'title' => 'แกลเลอรี่กิจกรรม',
            'albums' => $albums,
            'stats' => $stats,
            'darkUi' => true,
            'visitorStats' => $this->visitorStats(),
        ]);
    }

    private function listTeachers(): array
    {
        return db()->fetchAll(
            "SELECT u.id, u.title, u.first_name, u.last_name, u.avatar, u.role,
                    p.photo, COALESCE(pt.title, p.position_text) AS position_name, ar.name AS rank_name,
                    (SELECT COUNT(*) FROM portfolio_items pi
                      WHERE pi.user_id = u.id AND pi.deleted_at IS NULL AND pi.is_public = 1) AS works_count
             FROM users u
             LEFT JOIN personnel p ON p.user_id = u.id AND p.deleted_at IS NULL
             LEFT JOIN sc_personal_types pt ON pt.id = p.personal_type_id
             LEFT JOIN academic_ranks ar ON ar.id = p.academic_rank_id
             WHERE u.deleted_at IS NULL AND u.status = 1 AND u.role IN ('teacher','director','deputy')
             ORDER BY u.first_name, u.last_name
             LIMIT 200"
        );
    }

    private function findTeacher(int $id): ?array
    {
        return db()->fetch(
            "SELECT u.*, p.photo, p.phone AS p_phone, p.email AS p_email, p.education, p.work_history,
                    p.line_id, p.facebook, COALESCE(pt.title, p.position_text) AS position_name, ar.name AS rank_name,
                    d.name AS department_name
             FROM users u
             LEFT JOIN personnel p ON p.user_id = u.id AND p.deleted_at IS NULL
             LEFT JOIN sc_personal_types pt ON pt.id = p.personal_type_id
             LEFT JOIN academic_ranks ar ON ar.id = p.academic_rank_id
             LEFT JOIN departments d ON d.id = COALESCE(p.department_id, u.department_id)
             WHERE u.id = ? AND u.deleted_at IS NULL AND u.status = 1",
            [$id]
        ) ?: null;
    }

    private function typeCounts(int $userId): array
    {
        $rows = db()->fetchAll(
            "SELECT type, COUNT(*) AS c FROM portfolio_items
             WHERE user_id = ? AND deleted_at IS NULL AND is_public = 1
             GROUP BY type",
            [$userId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['type']] = (int) $r['c'];
        }
        return $out;
    }

    private function track(): void
    {
        try {
            db()->insert('visitors', [
                'ip_address' => request_ip(),
                'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'path' => substr((string) ($_SERVER['REQUEST_URI'] ?? '/'), 0, 255),
                'visited_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // ignore visitor tracking failures
        }
    }
}
