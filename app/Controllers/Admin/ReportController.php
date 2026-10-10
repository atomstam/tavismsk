<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\PortfolioItem;
use App\Services\DataTableService;
use App\Services\ExportService;

class ReportController extends Controller
{
    public function index(string $type): void
    {
        $mod = $this->mod($type);
        $this->requirePerm('reports');
        $people = can_see_all_personnel() ? db()->fetchAll(
            "SELECT id, title, first_name, last_name FROM users WHERE deleted_at IS NULL AND status = 1 ORDER BY first_name"
        ) : [];

        $this->view('admin/reports/index', [
            'title' => 'รายงาน: ' . $mod['short'],
            'breadcrumb' => 'หน้าหลัก / รายงาน: ' . $mod['short'],
            'mod' => $mod,
            'type' => $type,
            'people' => $people,
            'years' => academic_years_list(),
        ]);
    }

    public function datatable(string $type): void
    {
        $this->mod($type);
        $this->requirePerm('reports');
        $this->json(DataTableService::portfolio(dt_params(), $type, [
            'user_id' => (int) $this->input('user_id', 0),
            'year' => (int) $this->input('year', 0),
            'search' => trim((string) $this->input('q', '')),
            'report' => true,
        ]));
    }

    public function show(string $type, string $id): void
    {
        $mod = $this->mod($type);
        $this->requirePerm('reports');
        $item = (new PortfolioItem())->withUser((int) $id);
        if (!$item || $item['type'] !== $type) {
            $this->flash('error', 'ไม่พบรายงาน');
            $this->redirect('/admin/reports/' . $type);
        }
        if (!can_see_all_personnel() && (int) $item['user_id'] !== (int) auth()->id()) {
            $this->flash('error', 'ไม่มีสิทธิ์');
            $this->redirect('/admin/reports/' . $type);
        }
        $this->view('admin/reports/show', [
            'title' => $mod['report_title'],
            'breadcrumb' => 'หน้าหลัก / รายงาน: ' . $mod['short'],
            'mod' => $mod,
            'type' => $type,
            'item' => $item,
            'photos' => (new PortfolioItem())->files((int) $id, 'photo'),
            'files' => (new PortfolioItem())->files((int) $id, 'file'),
        ]);
    }

    public function export(string $type): void
    {
        $mod = $this->mod($type);
        $this->requirePerm('reports', 'export');
        $format = strtolower((string) $this->input('format', 'xlsx'));
        $filters = $this->filters();
        $rows = $this->query($type, $filters);
        $headers = ['ลำดับ', 'ชื่อบุคคล', 'รายการ', 'หน่วยงาน', 'วันที่', 'ชั่วโมง', 'ปีการศึกษา'];
        $data = [];
        $n = 1;
        foreach ($rows as $r) {
            $data[] = [
                $n++,
                trim(($r['user_title'] ?? '') . ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                $r['title'],
                $r['organization'] ?? '',
                thai_date($r['start_date'] ?? null),
                $r['hours'],
                $r['academic_year'] ?? '',
            ];
        }
        $ex = new ExportService();
        if ($format === 'pdf') {
            $html = $this->tableHtml($mod['report_title'], $headers, $data);
            $ex->pdfDocument($html, $type . '.pdf', 'L');
        }
        if ($format === 'word' || $format === 'doc') {
            $ex->wordHtml($this->tableHtml($mod['report_title'], $headers, $data), $type . '.doc');
        }
        $ex->spreadsheet($mod['report_title'], $headers, $data, $format === 'csv' ? 'csv' : 'xlsx');
    }

    public function download(string $type, string $id): void
    {
        $this->mod($type);
        $this->requirePerm('reports', 'export');
        $item = (new PortfolioItem())->withUser((int) $id);
        if (!$item) {
            $this->redirect('/admin/reports/' . $type);
        }
        ob_start();
        $mod = portfolio_module($type);
        $photos = (new PortfolioItem())->files((int) $id, 'photo');
        $files = (new PortfolioItem())->files((int) $id, 'file');
        $title = $mod['report_title'] ?? 'รายงาน';
        include VIEW_PATH . '/admin/reports/pdf_body.php';
        $html = ob_get_clean();
        (new ExportService())->pdfDocument($html, 'report-' . $id . '.pdf');
    }

    private function mod(string $type): array
    {
        $mod = portfolio_module($type);
        if (!$mod) {
            $this->redirect('/admin/dashboard');
        }
        return $mod;
    }

    private function filters(): array
    {
        return [
            'q' => trim((string) $this->input('q', '')),
            'user_id' => (int) $this->input('user_id', 0),
            'year' => (int) $this->input('year', 0),
        ];
    }

    private function query(string $type, array $filters): array
    {
        $where = 'i.deleted_at IS NULL AND i.type = ?';
        $params = [$type];
        if (!can_see_all_personnel()) {
            $where .= ' AND i.user_id = ?';
            $params[] = auth()->id();
        } elseif ($filters['user_id']) {
            $where .= ' AND i.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if ($filters['year']) {
            $where .= ' AND i.academic_year = ?';
            $params[] = $filters['year'];
        }
        if ($filters['q'] !== '') {
            $where .= ' AND (i.title LIKE ? OR i.organization LIKE ? OR i.location LIKE ?)';
            $s = '%' . $filters['q'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        return db()->fetchAll(
            "SELECT i.*, u.title AS user_title, u.first_name, u.last_name
             FROM portfolio_items i JOIN users u ON u.id = i.user_id
             WHERE {$where} ORDER BY i.start_date DESC, i.id DESC",
            $params
        );
    }

    private function tableHtml(string $title, array $headers, array $rows): string
    {
        $org = e(setting('org_name', 'โรงเรียนเชียงยืนพิทยาคม'));
        $h = '<h2 style="text-align:center">' . e($title) . '</h2><p style="text-align:center">' . $org . '</p>';
        $h .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:13px"><thead><tr>';
        foreach ($headers as $col) {
            $h .= '<th>' . e($col) . '</th>';
        }
        $h .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $h .= '<tr>';
            foreach ($row as $cell) {
                $h .= '<td>' . e((string) $cell) . '</td>';
            }
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }
}
