<?php

declare(strict_types=1);

/**
 * Helpers สำหรับ DataTables server-side (รูปแบบเดียวกับ antibully)
 */

if (!function_exists('dt_params')) {
    /**
     * @return array{draw:int,start:int,length:int,search:string,order_column:int,order_dir:string}
     */
    function dt_params(): array
    {
        $search = '';
        if (isset($_GET['search']) && is_array($_GET['search'])) {
            $search = trim((string) ($_GET['search']['value'] ?? ''));
        }
        if ($search === '') {
            $search = trim((string) ($_GET['q'] ?? ''));
        }

        $orderColumn = 1;
        $orderDir = 'DESC';
        if (isset($_GET['order'][0]) && is_array($_GET['order'][0])) {
            $orderColumn = (int) ($_GET['order'][0]['column'] ?? 1);
            $dir = strtolower((string) ($_GET['order'][0]['dir'] ?? 'desc'));
            $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';
        }

        $length = (int) ($_GET['length'] ?? 15);
        if ($length < 1) {
            $length = 15;
        }
        if ($length > 100) {
            $length = 100;
        }

        return [
            'draw' => max(1, (int) ($_GET['draw'] ?? 1)),
            'start' => max(0, (int) ($_GET['start'] ?? 0)),
            'length' => $length,
            'search' => $search,
            'order_column' => $orderColumn,
            'order_dir' => $orderDir,
        ];
    }
}

if (!function_exists('dt_order_sql')) {
    /**
     * แปลงคอลัมน์ DataTables → SQL ORDER BY (index 0 = expand ข้าม)
     *
     * @param array<int,string> $map columnIndex => "table.column"
     */
    function dt_order_sql(array $map, int $columnIndex, string $dir, string $default): string
    {
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        $col = $map[$columnIndex] ?? null;
        if (!$col) {
            return $default;
        }
        return $col . ' ' . $dir;
    }
}

if (!function_exists('dt_response')) {
    /**
     * @param list<array<string,mixed>> $data
     * @return array{draw:int,recordsTotal:int,recordsFiltered:int,data:list<array<string,mixed>>}
     */
    function dt_response(int $draw, int $recordsTotal, int $recordsFiltered, array $data): array
    {
        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
}

if (!function_exists('dt_action_btn')) {
    function dt_action_btn(string $html): string
    {
        return $html;
    }
}

if (!function_exists('dt_actions_wrap')) {
    /** ห่อกลุ่มปุ่ม CRUD ให้สไตล์เดียวกัน */
    function dt_actions_wrap(string ...$parts): string
    {
        $html = array_values(array_filter($parts, static fn ($p) => $p !== ''));
        if (!$html) {
            return '';
        }
        return '<div class="dt-actions">' . implode('', $html) . '</div>';
    }
}

if (!function_exists('dt_btn')) {
    /**
     * ปุ่มไอคอนมีขอบ (outline) รูปแบบเดียวกันทั้งระบบ
     * @param 'view'|'edit'|'delete'|'key'|'image'|'export'|'list'|string $kind
     */
    function dt_btn(string $kind, string $url = '#', array $opts = []): string
    {
        $title = (string) ($opts['title'] ?? match ($kind) {
            'view' => 'รายละเอียด',
            'edit' => 'แก้ไข',
            'delete' => 'ลบ',
            'key' => 'รีเซ็ตรหัสผ่าน',
            'image' => 'สร้างภาพปก',
            'export' => 'Export',
            'list' => 'ผลลัพธ์',
            'open' => 'เปิด',
            'print' => 'พิมพ์',
            'download' => 'ดาวน์โหลด',
            default => '',
        });
        $confirm = (string) ($opts['confirm'] ?? '');
        $confirmDelete = (string) ($opts['confirm_delete'] ?? '');
        $label = (string) ($opts['label'] ?? '');
        $method = (string) ($opts['method'] ?? 'get'); // get|post
        $csrf = csrf_token();

        $map = [
            'view' => ['outline-secondary', 'fa-eye'],
            'edit' => ['outline-primary', 'fa-pen'],
            'delete' => ['outline-danger', 'fa-trash'],
            'key' => ['outline-secondary', 'fa-key'],
            'image' => ['outline-primary', 'fa-image'],
            'export' => ['outline-success', 'fa-file-export'],
            'list' => ['outline-secondary', 'fa-table-list'],
            'open' => ['outline-primary', 'fa-folder-open'],
            'print' => ['outline-dark', 'fa-print'],
            'download' => ['outline-success', 'fa-download'],
        ];
        [$btnClass, $icon] = $map[$kind] ?? ['outline-secondary', 'fa-circle'];
        $iconHtml = '<i class="fa ' . $icon . '"></i>';
        if ($label !== '') {
            $iconHtml .= ' <span class="dt-btn-label">' . e($label) . '</span>';
        }

        $attrs = ' class="btn btn-sm btn-' . $btnClass . '" title="' . e($title) . '"';
        if ($confirm !== '') {
            $attrs .= ' data-confirm="' . e($confirm) . '"';
        }
        if ($confirmDelete !== '') {
            $attrs .= ' data-confirm-delete="' . e($confirmDelete) . '"';
        }

        if ($method === 'post') {
            $btnType = !empty($opts['submit']) ? 'submit' : 'button';
            return '<form method="post" action="' . e($url) . '" class="d-inline">'
                . '<input type="hidden" name="_token" value="' . e($csrf) . '">'
                . '<button type="' . $btnType . '"' . $attrs . '>' . $iconHtml . '</button></form>';
        }

        $target = !empty($opts['blank']) ? ' target="_blank" rel="noopener"' : '';
        return '<a href="' . e($url) . '"' . $attrs . $target . '>' . $iconHtml . '</a>';
    }
}
