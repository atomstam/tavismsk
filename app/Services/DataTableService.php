<?php

declare(strict_types=1);

namespace App\Services;

class DataTableService
{
    public static function users(array $p, ?string $role = null): array
    {
        $where = 'u.deleted_at IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone LIKE ? OR u.org_name LIKE ? OR u.affiliation LIKE ? OR u.position_text LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s, $s, $s, $s, $s, $s];
        }
        if ($role) {
            $where .= ' AND u.role = ?';
            $params[] = $role;
        }
        $flt = RegistrationProfile::listFilter('u');
        $where .= $flt['where'];
        $params = array_merge($params, $flt['params']);
        $total = (int) (db()->fetch('SELECT COUNT(*) AS c FROM users u WHERE u.deleted_at IS NULL')['c'] ?? 0);
        $unfiltered = $p['search'] === '' && $role === null && $flt['where'] === '';
        $filtered = $unfiltered
            ? $total
            : (int) (db()->fetch("SELECT COUNT(*) AS c FROM users u WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            0 => 'u.first_name',
            1 => 'u.email',
            2 => 'u.role',
            3 => 'u.member_type',
            4 => 'sch.name',
            5 => 'pv.name',
            6 => 'u.status',
        ], $p['order_column'], $p['order_dir'], 'u.id DESC');

        $rows = db()->fetchAll(
            "SELECT u.id, u.username, u.email, u.title, u.first_name, u.last_name, u.avatar, u.role, u.status,
                    u.member_type, u.position_text, u.org_name, u.affiliation,
                    sch.name AS school_name, pt.title AS position_name, pv.name AS province_name
             FROM users u
             LEFT JOIN org_units sch ON sch.id = u.org_unit_id AND sch.org_type = 'school' AND sch.deleted_at IS NULL
             LEFT JOIN sc_personal_types pt ON pt.id = u.personal_type_id
             LEFT JOIN province pv ON pv.id = u.province_id
             WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $canEdit = can_perm('users', 'edit');
        $canDelete = can_perm('users', 'delete');
        $data = [];
        foreach ($rows as $u) {
            $name = e(trim(($u['title'] ?? '') . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')));
            $avatar = e(avatar_url($u['avatar'] ?? null));
            $userHtml = '<div class="d-flex align-items-center gap-2">'
                . '<img src="' . $avatar . '" width="36" height="36" class="rounded-circle" style="object-fit:cover" alt="">'
                . '<div><div class="fw-semibold">' . $name . '</div><small class="text-muted">@' . e($u['username']) . '</small></div></div>';
            $isStaff = in_array((string) $u['role'], OrgMembershipService::STAFF_ROLES, true);
            $memberLabels = ['staff' => 'ครูและบุคลากรทางการศึกษา', 'student' => 'นักเรียน', 'general' => 'ทั่วไป'];
            $position = (string) (($u['position_name'] ?? '') !== '' ? $u['position_name'] : ($u['position_text'] ?? ''));
            if ($isStaff) {
                $memberHtml = '<span class="text-muted">ไม่สังกัด</span>';
                $orgHtml = '<span class="text-muted">—</span>';
                $provinceName = '—';
            } else {
                $memberLabel = $memberLabels[$u['member_type'] ?? ''] ?? (string) ($u['member_type'] ?? '-');
                $memberHtml = '<div>' . e($memberLabel) . '</div>';
                if ($position !== '') {
                    $memberHtml .= '<small class="text-muted">' . e($position) . '</small>';
                }
                $schoolName = (string) (($u['school_name'] ?? '') !== '' ? $u['school_name'] : ($u['org_name'] ?? ''));
                $aff = (string) ($u['affiliation'] ?? '');
                $orgHtml = e($schoolName !== '' ? $schoolName : '-');
                if ($aff !== '' && $schoolName !== '') {
                    $orgHtml = '<div>' . e($schoolName) . '</div><small class="text-muted">' . e($aff) . '</small>';
                } elseif ($aff !== '') {
                    $orgHtml = e($aff);
                }
                $provinceName = (string) (($u['province_name'] ?? '') !== '' ? $u['province_name'] : '-');
            }
            $actions = dt_actions_wrap(
                dt_btn('detail', url('/admin/users/' . $u['id'])),
                $canEdit ? dt_btn('edit', url('/admin/users/' . $u['id'] . '/edit')) : '',
                $canEdit ? dt_btn('key', url('/admin/users/' . $u['id'] . '/reset-password'), ['method' => 'post', 'confirm' => 'รีเซ็ตรหัสผ่านเป็นค่าเริ่มต้น?']) : '',
                $canDelete ? dt_btn('delete', url('/admin/users/' . $u['id'] . '/delete'), ['method' => 'post', 'confirm_delete' => 'ลบผู้ใช้นี้?']) : ''
            );
            $data[] = [
                'user_html' => $userHtml,
                'email' => (string) ($u['email'] ?? '-'),
                'role_label' => role_name((string) $u['role']),
                'member_html' => $memberHtml,
                'org_name' => $orgHtml,
                'province_name' => $provinceName,
                'status_html' => ((int) $u['status'] === 1)
                    ? '<span class="badge-pill-ok">ใช้งาน</span>'
                    : '<span class="badge-pill-off">ระงับ</span>',
                'actions' => $actions,
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function personnel(array $p): array
    {
        $where = 'p.deleted_at IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (p.code LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.id_card LIKE ? OR p.phone LIKE ? OR p.email LIKE ? OR p.org_name LIKE ? OR p.affiliation LIKE ? OR p.position_text LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s, $s, $s, $s, $s, $s, $s];
        }
        $flt = RegistrationProfile::listFilter('p');
        $where .= $flt['where'];
        $params = array_merge($params, $flt['params']);
        if (!can_see_all_personnel() && auth()->id()) {
            $where .= ' AND p.user_id = ?';
            $params[] = auth()->id();
        }
        $total = (int) (db()->fetch('SELECT COUNT(*) AS c FROM personnel p WHERE p.deleted_at IS NULL')['c'] ?? 0);
        $unfiltered = $p['search'] === '' && $flt['where'] === '' && (can_see_all_personnel() || !auth()->id());
        $filtered = $unfiltered
            ? $total
            : (int) (db()->fetch("SELECT COUNT(*) AS c FROM personnel p WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            0 => 'p.code',
            1 => 'p.first_name',
            2 => 'p.member_type',
            3 => 'pt.title',
            4 => 'sch.name',
            5 => 'pv.name',
            6 => 'p.phone',
        ], $p['order_column'], $p['order_dir'], 'p.sort_order ASC, p.id ASC');

        $rows = db()->fetchAll(
            "SELECT p.*, sch.name AS school_name, pt.title AS position_name, pv.name AS province_name
             FROM personnel p
             LEFT JOIN org_units sch ON sch.id = p.org_unit_id AND sch.deleted_at IS NULL
             LEFT JOIN sc_personal_types pt ON pt.id = p.personal_type_id
             LEFT JOIN province pv ON pv.id = p.province_id
             WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $canEdit = can_perm('personnel', 'edit');
        $canDelete = can_perm('personnel', 'delete');
        $data = [];
        foreach ($rows as $row) {
            $fullName = trim(($row['title'] ?? '') . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $photoUrl = personnel_photo_url($row['photo'] ?? null);
            $avatarSrc = e($photoUrl ?? avatar_url(null));
            $nameHtml = '<div class="d-flex align-items-center gap-2">'
                . '<img src="' . $avatarSrc . '" width="40" height="40" class="rounded-circle" style="object-fit:cover" alt="">'
                . '<div class="fw-semibold">' . e($fullName) . '</div></div>';
            $actions = dt_actions_wrap(
                dt_btn('detail', url('/admin/personnel/' . $row['id'])),
                $canEdit ? dt_btn('edit', url('/admin/personnel/' . $row['id'] . '/edit')) : '',
                $canDelete ? dt_btn('delete', url('/admin/personnel/' . $row['id'] . '/delete'), ['method' => 'post', 'confirm_delete' => 'ลบข้อมูลบุคลากรนี้?']) : ''
            );
            $memberLabels = ['staff' => 'ครูและบุคลากรทางการศึกษา', 'student' => 'นักเรียน', 'general' => 'ทั่วไป'];
            $position = (string) (($row['position_name'] ?? '') !== '' ? $row['position_name'] : ($row['position_text'] ?? ''));
            $schoolName = (string) (($row['school_name'] ?? '') !== '' ? $row['school_name'] : ($row['org_name'] ?? ''));
            $aff = (string) ($row['affiliation'] ?? '');
            $orgHtml = e($schoolName !== '' ? $schoolName : '-');
            if ($aff !== '' && $schoolName !== '') {
                $orgHtml = '<div>' . e($schoolName) . '</div><small class="text-muted">' . e($aff) . '</small>';
            }
            $data[] = [
                'code_html' => '<span class="fw-semibold">' . e((string) ($row['code'] ?? '-')) . '</span>',
                'name_html' => $nameHtml,
                'member_label' => (string) ($memberLabels[$row['member_type'] ?? ''] ?? ($row['member_type'] ?? '-')),
                'position' => $position !== '' ? $position : '-',
                'org_html' => $orgHtml,
                'province_name' => (string) (($row['province_name'] ?? '') !== '' ? $row['province_name'] : '-'),
                'phone' => (string) ($row['phone'] ?? '-'),
                'actions' => $actions,
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function portfolio(array $p, string $type, array $filters = []): array
    {
        $seeAll = can_see_all_personnel();
        $where = 'i.deleted_at IS NULL AND i.type = ?';
        $params = [$type];
        if (!$seeAll) {
            $where .= ' AND i.user_id = ?';
            $params[] = auth()->id();
        } elseif (!empty($filters['user_id'])) {
            $where .= ' AND i.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }
        if (!empty($filters['year'])) {
            $where .= ' AND i.academic_year = ?';
            $params[] = (int) $filters['year'];
        }
        $search = $filters['search'] ?? $p['search'];
        if ($search !== '') {
            $where .= ' AND (i.title LIKE ? OR i.organization LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        $totalSql = 'SELECT COUNT(*) AS c FROM portfolio_items i WHERE i.deleted_at IS NULL AND i.type = ?';
        $totalParams = [$type];
        if (!$seeAll) {
            $totalSql .= ' AND i.user_id = ?';
            $totalParams[] = auth()->id();
        }
        $total = (int) (db()->fetch($totalSql, $totalParams)['c'] ?? 0);
        $filtered = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM portfolio_items i JOIN users u ON u.id = i.user_id WHERE {$where}",
            $params
        )['c'] ?? 0);

        $order = dt_order_sql([
            1 => 'u.first_name',
            2 => 'i.title',
            3 => 'i.start_date',
            4 => 'i.hours',
            5 => 'i.year_be',
        ], $p['order_column'], $p['order_dir'], 'i.start_date DESC, i.id DESC');

        $rows = db()->fetchAll(
            "SELECT i.*, u.title AS user_title, u.first_name, u.last_name
             FROM portfolio_items i JOIN users u ON u.id = i.user_id
             WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $canEdit = can_perm('portfolio', 'edit');
        $canDelete = can_perm('portfolio', 'delete');
        $mod = portfolio_module($type);
        $mode = $mod['table_mode'] ?? 'hours';
        $data = [];
        foreach ($rows as $row) {
            $own = (int) $row['user_id'] === (int) auth()->id();
            $allowEdit = $canEdit && ($seeAll || $own);
            $allowDel = $canDelete && ($seeAll || $own);
            $person = e(trim(($row['user_title'] ?? '') . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
            $sub = e((string) ($row['organization'] ?: $row['location'] ?: $row['category'] ?: ''));
            $itemHtml = '<div class="fw-semibold">' . e($row['title']) . '</div>'
                . ($sub !== '' ? '<div class="text-muted small">' . $sub . '</div>' : '');
            $actions = !empty($filters['report'])
                ? dt_actions_wrap(
                    dt_btn('view', url('/admin/reports/' . $type . '/' . $row['id']), ['title' => 'ดูรายงาน']),
                    dt_btn('print', url('/admin/reports/' . $type . '/' . $row['id']), ['title' => 'พิมพ์']),
                    dt_btn('download', url('/admin/reports/' . $type . '/' . $row['id'] . '/download'), ['title' => 'โหลด PDF'])
                )
                : dt_actions_wrap(
                    dt_btn('view', url('/admin/portfolio/' . $type . '/' . $row['id'])),
                    $allowEdit ? dt_btn('edit', url('/admin/portfolio/' . $type . '/' . $row['id'] . '/edit')) : '',
                    $allowDel ? dt_btn('delete', url('/admin/portfolio/' . $type . '/' . $row['id'] . '/delete'), ['method' => 'post', 'confirm_delete' => 'ลบรายการนี้?']) : ''
                );
            $data[] = [
                'person' => $person,
                'item_html' => $itemHtml,
                'date' => thai_date($row['start_date'] ?? null),
                'hours' => rtrim(rtrim(number_format((float) $row['hours'], 1, '.', ''), '0'), '.') ?: '0',
                'year' => (string) ($row['year_be'] ?: $row['academic_year'] ?: '-'),
                'status' => (string) ($row['status_name'] ?: $row['category'] ?: $row['instance_no'] ?: '-'),
                'actions' => $actions,
                '_mode' => $mode,
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function activityLogs(array $p): array
    {
        $where = '1=1';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (a.action LIKE ? OR a.module LIKE ? OR a.description LIKE ? OR CONCAT(u.first_name,\' \',u.last_name) LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s, $s];
        }
        $total = (int) db()->count('activity_logs');
        $filtered = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id WHERE {$where}",
            $params
        )['c'] ?? 0);
        $order = dt_order_sql([
            1 => 'a.created_at',
            2 => 'u.first_name',
            3 => 'a.action',
            4 => 'a.module',
            5 => 'a.description',
            6 => 'a.ip_address',
        ], $p['order_column'], $p['order_dir'], 'a.id DESC');
        $rows = db()->fetchAll(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
             FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
             WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $data = [];
        foreach ($rows as $a) {
            $data[] = [
                'created_at' => thai_date($a['created_at'], true),
                'user_name' => (string) ($a['user_name'] ?? '-'),
                'action' => (string) $a['action'],
                'module' => (string) ($a['module'] ?? ''),
                'description' => (string) ($a['description'] ?? ''),
                'ip_address' => (string) ($a['ip_address'] ?? ''),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function loginLogs(array $p): array
    {
        $where = '1=1';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (username LIKE ? OR ip_address LIKE ? OR status LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s];
        }
        $total = (int) db()->count('login_logs');
        $filtered = (int) (db()->fetch("SELECT COUNT(*) AS c FROM login_logs WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            1 => 'created_at',
            2 => 'username',
            3 => 'status',
            4 => 'message',
            5 => 'ip_address',
        ], $p['order_column'], $p['order_dir'], 'id DESC');
        $rows = db()->fetchAll(
            "SELECT * FROM login_logs WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $data = [];
        foreach ($rows as $r) {
            $status = (string) ($r['status'] ?? '');
            $cls = $status === 'success' || $status === '2fa' ? 'ok' : ($status === 'failed' ? 'off' : 'warn');
            $data[] = [
                'created_at' => thai_date($r['created_at'] ?? null, true),
                'username' => (string) ($r['username'] ?? '-'),
                'status_html' => '<span class="badge-pill-' . $cls . '">' . e($status) . '</span>',
                'message' => (string) ($r['message'] ?? '-'),
                'ip_address' => (string) ($r['ip_address'] ?? '-'),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function tickets(array $p): array
    {
        $where = '1=1';
        $params = [];
        if (!auth()->hasRole(['admin', 'director'])) {
            $where .= ' AND t.user_id = ?';
            $params[] = auth()->id();
        }
        if ($p['search'] !== '') {
            $where .= ' AND (t.ticket_no LIKE ? OR t.subject LIKE ? OR CONCAT(u.first_name,\' \',u.last_name) LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        $status = trim((string) ($_GET['status'] ?? ''));
        if ($status !== '') {
            $where .= ' AND t.status = ?';
            $params[] = $status;
        }
        $totalSql = 'SELECT COUNT(*) AS c FROM tickets t';
        $totalParams = [];
        if (!auth()->hasRole(['admin', 'director'])) {
            $totalSql .= ' WHERE t.user_id = ?';
            $totalParams[] = auth()->id();
        }
        $total = (int) (db()->fetch($totalSql, $totalParams)['c'] ?? 0);
        $filtered = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM tickets t JOIN users u ON u.id = t.user_id WHERE {$where}",
            $params
        )['c'] ?? 0);
        $order = dt_order_sql([
            1 => 't.ticket_no',
            2 => 't.subject',
            3 => 'u.first_name',
            4 => 't.status',
            5 => 't.created_at',
        ], $p['order_column'], $p['order_dir'], 't.id DESC');
        $rows = db()->fetchAll(
            "SELECT t.*, CONCAT(u.first_name,' ',u.last_name) AS user_name
             FROM tickets t JOIN users u ON u.id = t.user_id
             WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $data = [];
        foreach ($rows as $r) {
            $st = (string) $r['status'];
            $cls = $st === 'open' ? 'warn' : ($st === 'replied' || $st === 'closed' ? 'ok' : 'off');
            $data[] = [
                'ticket_no' => '<a href="' . e(url('/admin/tickets/' . $r['id'])) . '" class="fw-semibold">' . e($r['ticket_no']) . '</a>',
                'subject' => (string) $r['subject'],
                'user_name' => (string) ($r['user_name'] ?? '-'),
                'status_html' => '<span class="badge-pill-' . $cls . '">' . e($st) . '</span>',
                'created_at' => thai_date($r['created_at'] ?? null, true),
                'actions' => dt_actions_wrap(dt_btn('open', url('/admin/tickets/' . $r['id']))),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function forms(array $p): array
    {
        $where = '1=1';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (title LIKE ? OR slug LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s];
        }
        $total = (int) db()->count('forms');
        $filtered = (int) (db()->fetch("SELECT COUNT(*) AS c FROM forms WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            1 => 'title',
            2 => 'slug',
            3 => 'status',
            4 => 'updated_at',
        ], $p['order_column'], $p['order_dir'], 'id DESC');
        $rows = db()->fetchAll(
            "SELECT * FROM forms WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $canEdit = can_perm('forms', 'edit');
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'title' => (string) $r['title'],
                'slug_html' => '<a href="' . e(url('/forms/' . $r['slug'])) . '" target="_blank" rel="noopener">' . e($r['slug']) . '</a>',
                'status_html' => ((int) $r['status'] === 1)
                    ? '<span class="badge-pill-ok">เปิด · เมนูเว็บ</span>'
                    : '<span class="badge-pill-off">ปิด</span>',
                'updated_at' => thai_date($r['updated_at'] ?? $r['created_at'] ?? null, true),
                'actions' => dt_actions_wrap(
                    dt_btn('list', url('/admin/forms/' . $r['id'] . '/submissions'), ['title' => 'คำตอบ']),
                    $canEdit ? dt_btn('edit', url('/admin/forms/' . $r['id'] . '/edit')) : '',
                    dt_btn('export', url('/admin/forms/' . $r['id'] . '/export'), ['title' => 'Excel'])
                ),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function departments(array $p): array
    {
        $where = 'deleted_at IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (name LIKE ? OR code LIKE ? OR head_name LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s];
        }
        $total = (int) db()->count('departments', 'deleted_at IS NULL');
        $filtered = (int) (db()->fetch("SELECT COUNT(*) AS c FROM departments WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            1 => 'sort_order',
            2 => 'name',
            3 => 'head_name',
            4 => 'color',
        ], $p['order_column'], $p['order_dir'], 'sort_order ASC, id ASC');
        $rows = db()->fetchAll(
            "SELECT * FROM departments WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $canEdit = can_perm('departments', 'edit');
        $canDelete = can_perm('departments', 'delete');
        $data = [];
        foreach ($rows as $r) {
            $color = (string) ($r['color'] ?? '#2563eb');
            $data[] = [
                'sort_order' => (int) $r['sort_order'],
                'name' => (string) $r['name'],
                'head_name' => (string) ($r['head_name'] ?? '-'),
                'color_html' => '<span class="badge" style="background:' . e($color) . '">&nbsp;&nbsp;</span> ' . e($color),
                'actions' => dt_actions_wrap(
                    $canEdit ? dt_btn('edit', url('/admin/departments/' . $r['id'] . '/edit')) : '',
                    $canDelete ? dt_btn('delete', url('/admin/departments/' . $r['id'] . '/delete'), ['method' => 'post', 'confirm_delete' => 'ลบหน่วยงาน?']) : ''
                ),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function notifications(array $p): array
    {
        $uid = (int) auth()->id();
        $where = 'user_id = ?';
        $params = [$uid];
        if ($p['search'] !== '') {
            $where .= ' AND (title LIKE ? OR message LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params[] = $s;
            $params[] = $s;
        }
        $total = (int) db()->count('notifications', 'user_id = ?', [$uid]);
        $filtered = (int) (db()->fetch("SELECT COUNT(*) AS c FROM notifications WHERE {$where}", $params)['c'] ?? 0);
        $rows = db()->fetchAll(
            "SELECT * FROM notifications WHERE {$where} ORDER BY id DESC LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $data = [];
        foreach ($rows as $n) {
            $unread = (int) $n['is_read'] === 0;
            $isNew = notification_is_new($n['created_at'] ?? null, $n['is_read'] ?? 1);
            if ($isNew) {
                $statusHtml = '<span class="badge-pill-warn">ใหม่</span>';
            } elseif ($unread) {
                $statusHtml = '<span class="badge-pill-muted">ยังไม่อ่าน</span>';
            } else {
                $statusHtml = '<span class="badge-pill-ok">อ่านแล้ว</span>';
            }
            $data[] = [
                'title_html' => ($unread ? '<strong>' : '') . e($n['title']) . ($unread ? '</strong>' : ''),
                'message' => (string) ($n['message'] ?? ''),
                'created_at' => time_ago($n['created_at'] ?? null),
                'status_html' => $statusHtml,
                'actions' => dt_actions_wrap(dt_btn('view', url('/admin/notifications/' . $n['id']))),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function gallery(array $p): array
    {
        $where = 'a.deleted_at IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (a.title LIKE ? OR a.category LIKE ? OR a.location LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s];
        }
        if (!can_see_all_personnel()) {
            $where .= ' AND a.user_id = ?';
            $params[] = (int) auth()->id();
        }
        $totalSql = 'SELECT COUNT(*) AS c FROM gallery_albums a WHERE a.deleted_at IS NULL'
            . (can_see_all_personnel() ? '' : ' AND a.user_id = ' . (int) auth()->id());
        $total = (int) (db()->fetch($totalSql)['c'] ?? 0);
        $filtered = (int) (db()->fetch("SELECT COUNT(*) AS c FROM gallery_albums a WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            0 => 'a.event_date',
            1 => 'a.title',
            2 => 'a.category',
            3 => 'a.academic_year',
        ], $p['order_column'], $p['order_dir'], 'a.event_date DESC, a.id DESC');
        $rows = db()->fetchAll(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM gallery_media m WHERE m.album_id = a.id) AS media_count
             FROM gallery_albums a
             WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $canEdit = can_perm('portfolio', 'edit');
        $canDelete = can_perm('portfolio', 'delete');
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'event_date' => $r['event_date'] ? thai_date($r['event_date']) : '-',
                'title' => (string) $r['title'],
                'category' => (string) ($r['category'] ?? '-'),
                'academic_year' => (string) ($r['academic_year'] ?? '-'),
                'media_count' => (int) ($r['media_count'] ?? 0),
                'status_html' => ((int) $r['is_public'] === 1)
                    ? '<span class="badge-pill-ok">สาธารณะ</span>'
                    : '<span class="badge-pill-off">ส่วนตัว</span>',
                'actions' => dt_actions_wrap(
                    $canEdit ? dt_btn('edit', url('/admin/gallery/' . $r['id'] . '/edit')) : '',
                    $canDelete ? dt_btn('delete', url('/admin/gallery/' . $r['id'] . '/delete'), ['method' => 'post', 'confirm_delete' => 'ลบอัลบั้มนี้?']) : ''
                ),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function curriculum(array $p): array
    {
        $where = 'status = 1';
        $params = [];
        if (auth()->role() === 'trainer') {
            $ids = (new \App\Services\PlanOwnerService())->approvedPlanIds((int) auth()->id());
            $where .= $ids === [] ? ' AND 1=0' : ' AND id IN (' . implode(',', array_map('intval', $ids)) . ')';
        }
        if ($p['search'] !== '') {
            $where .= ' AND (title LIKE ? OR topic_group LIKE ? OR instructor_label LIKE ? OR CAST(plan_no AS CHAR) LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s, $s];
        }
        $scope = 'status = 1';
        if (auth()->role() === 'trainer') {
            $ids = (new \App\Services\PlanOwnerService())->approvedPlanIds((int) auth()->id());
            $scope .= $ids === [] ? ' AND 1=0' : ' AND id IN (' . implode(',', array_map('intval', $ids)) . ')';
        }
        $total = (int) db()->count('learning_plans', $scope);
        $filtered = (int) (db()->fetch("SELECT COUNT(*) AS c FROM learning_plans WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            0 => 'plan_no',
            1 => 'title',
            2 => 'hours',
            3 => 'instructor_type',
            4 => 'topic_group',
        ], $p['order_column'], $p['order_dir'], 'plan_no ASC');
        $rows = db()->fetchAll(
            "SELECT * FROM learning_plans WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'plan_no' => (int) $r['plan_no'],
                'title' => (string) $r['title'],
                'hours' => (string) $r['hours'],
                'instructor' => (string) ($r['instructor_label'] ?: $r['instructor_type']),
                'topic_group' => (string) ($r['topic_group'] ?? '-'),
                'actions' => dt_actions_wrap(
                    dt_btn('view', url('/admin/curriculum/' . $r['id']), ['title' => 'รายละเอียด'])
                ),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    /** ผู้เรียนในหลักสูตร (training_module_progress) — สำหรับหน้ารายละเอียดผู้เข้ารับการพัฒนา */
    public static function planLearners(int $planId, array $p): array
    {
        $where = 'tmp.plan_id = ?';
        $params = [$planId];

        if ($p['search'] !== '') {
            $where .= ' AND (e.enrollment_no LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR o.name LIKE ?)';
            $s = '%' . $p['search'] . '%';
            array_push($params, $s, $s, $s, $s, $s);
        }
        $stage = trim((string) ($_GET['stage'] ?? ''));
        if ($stage !== '') {
            $where .= ' AND tmp.stage = ?';
            $params[] = $stage;
        }
        $passed = trim((string) ($_GET['passed'] ?? ''));
        if ($passed === '1' || $passed === '0') {
            $where .= ' AND tmp.is_passed = ?';
            $params[] = (int) $passed;
        }
        $orgId = (int) ($_GET['org_id'] ?? 0);
        if ($orgId > 0) {
            $where .= ' AND e.org_unit_id = ?';
            $params[] = $orgId;
        }

        $baseFrom = "FROM training_module_progress tmp
             INNER JOIN course_enrollments e ON e.id = tmp.enrollment_id
             INNER JOIN users u ON u.id = e.user_id AND u.deleted_at IS NULL
             LEFT JOIN org_units o ON o.id = e.org_unit_id";

        $total = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM training_module_progress tmp WHERE tmp.plan_id = ?",
            [$planId]
        )['c'] ?? 0);
        $filtered = (int) (db()->fetch(
            "SELECT COUNT(*) AS c {$baseFrom} WHERE {$where}",
            $params
        )['c'] ?? 0);

        $order = dt_order_sql([
            1 => 'e.enrollment_no',
            2 => 'u.first_name',
            3 => 'o.name',
            4 => 'tmp.stage',
            5 => 'tmp.overall_percent',
            6 => 'tmp.pretest_percent',
            7 => 'tmp.content_percent',
            8 => 'tmp.task_percent',
            9 => 'tmp.posttest_percent',
            10 => 'tmp.is_passed',
        ], $p['order_column'], $p['order_dir'], 'tmp.overall_percent DESC, u.first_name ASC');

        $rows = db()->fetchAll(
            "SELECT e.enrollment_no, e.status AS enrollment_status,
                    u.title, u.first_name, u.last_name, u.username,
                    o.name AS org_name,
                    tmp.overall_percent, tmp.stage, tmp.is_passed AS plan_passed,
                    tmp.pretest_percent, tmp.content_percent, tmp.task_percent,
                    tmp.posttest_percent, tmp.satisfaction_percent
             {$baseFrom}
             WHERE {$where}
             ORDER BY {$order}
             LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $stageLabel = [
            'pretest' => 'ทดสอบก่อน',
            'content' => 'เนื้อหา',
            'task' => 'ภาระงาน',
            'posttest' => 'สอบหลัง',
            'satisfaction' => 'ความพึงพอใจ',
            'completed' => 'ครบแล้ว',
        ];
        $data = [];
        foreach ($rows as $r) {
            $st = (string) ($r['stage'] ?? '');
            $data[] = [
                'enrollment_no_html' => '<code>' . e((string) $r['enrollment_no']) . '</code>',
                'user_name' => trim(($r['title'] ?? '') . ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                'org_name' => (string) ($r['org_name'] ?? '-'),
                'stage_label' => $stageLabel[$st] ?? $st,
                'overall' => number_format((float) ($r['overall_percent'] ?? 0), 1) . '%',
                'pretest' => number_format((float) ($r['pretest_percent'] ?? 0), 0),
                'content' => number_format((float) ($r['content_percent'] ?? 0), 0),
                'task' => number_format((float) ($r['task_percent'] ?? 0), 0),
                'posttest' => number_format((float) ($r['posttest_percent'] ?? 0), 0),
                'passed_html' => ((int) ($r['plan_passed'] ?? 0) === 1)
                    ? '<span class="badge text-bg-success">ผ่าน</span>'
                    : '<span class="badge text-bg-secondary">ยังไม่ผ่าน</span>',
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function clubMembers(array $p): array
    {
        $where = 'deleted_at IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (first_name LIKE ? OR last_name LIKE ? OR student_code LIKE ? OR phone LIKE ? OR grade_level LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s, $s, $s];
        }
        $total = (int) db()->count('club_members', 'deleted_at IS NULL');
        $filtered = (int) (db()->fetch("SELECT COUNT(*) AS c FROM club_members WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            0 => 'student_code',
            1 => 'first_name',
            2 => 'grade_level',
            3 => 'room',
            4 => 'academic_year',
            5 => 'phone',
        ], $p['order_column'], $p['order_dir'], 'first_name ASC, last_name ASC');
        $rows = db()->fetchAll(
            "SELECT * FROM club_members WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );
        $canEdit = can_perm('members', 'edit');
        $canDelete = can_perm('members', 'delete');
        $data = [];
        foreach ($rows as $r) {
            $name = trim(($r['title'] ?? '') . ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            $data[] = [
                'student_code' => (string) ($r['student_code'] ?? '-'),
                'name' => $name,
                'grade_level' => (string) ($r['grade_level'] ?? '-'),
                'room' => (string) ($r['room'] ?? '-'),
                'academic_year' => (string) ($r['academic_year'] ?? '-'),
                'phone' => (string) ($r['phone'] ?? '-'),
                'status_html' => ((int) $r['status'] === 1)
                    ? '<span class="badge-pill-ok">ใช้งาน</span>'
                    : '<span class="badge-pill-off">ปิด</span>',
                'actions' => dt_actions_wrap(
                    $canEdit ? dt_btn('edit', url('/admin/members/' . $r['id'] . '/edit')) : '',
                    $canDelete ? dt_btn('delete', url('/admin/members/' . $r['id'] . '/delete'), ['method' => 'post', 'confirm_delete' => 'ลบสมาชิกคนนี้?']) : ''
                ),
            ];
        }
        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function orgUnits(array $p): array
    {
        $where = 'o.deleted_at IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (o.code LIKE ? OR o.name LIKE ? OR o.name_en LIKE ? OR o.province LIKE ? OR p.name LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s, $s, $s];
        }
        $type = trim((string) ($_GET['org_type'] ?? ''));
        if ($type !== '') {
            $where .= ' AND o.org_type = ?';
            $params[] = $type;
        }

        $total = (int) (db()->fetch('SELECT COUNT(*) AS c FROM org_units o WHERE o.deleted_at IS NULL')['c'] ?? 0);
        $filtered = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM org_units o LEFT JOIN org_units p ON p.id = o.parent_id WHERE {$where}",
            $params
        )['c'] ?? 0);

        $order = dt_order_sql([
            1 => 'o.code',
            2 => 'o.name',
            3 => 'o.org_type',
            4 => 'p.name',
            5 => 'o.province',
            6 => 'user_count',
        ], $p['order_column'], $p['order_dir'], "FIELD(o.org_type,'ministry','agency','area','school'), o.sort_order, o.id");

        $rows = db()->fetchAll(
            "SELECT o.*, p.name AS parent_name,
                    (SELECT COUNT(*) FROM users u WHERE u.org_unit_id = o.id AND u.deleted_at IS NULL) AS user_count
             FROM org_units o
             LEFT JOIN org_units p ON p.id = o.parent_id
             WHERE {$where}
             ORDER BY {$order}
             LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $types = config('app', 'org_types') ?: [];
        $data = [];
        foreach ($rows as $r) {
            $nameHtml = '<strong>' . e($r['name']) . '</strong>';
            if (!empty($r['name_en'])) {
                $nameHtml .= '<div class="small text-muted">' . e($r['name_en']) . '</div>';
            }
            $data[] = [
                'code_html' => '<code>' . e($r['code'] ?? '-') . '</code>',
                'name_html' => $nameHtml,
                'type_html' => '<span class="badge text-bg-light border">' . e($types[$r['org_type']] ?? $r['org_type']) . '</span>',
                'parent_name' => (string) ($r['parent_name'] ?? '—'),
                'province' => (string) ($r['province'] ?? '-'),
                'user_count' => number_format((int) ($r['user_count'] ?? 0)),
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function enrollments(array $p): array
    {
        $where = '1=1';
        $params = [];
        // แอดมินเท่านั้นที่ดูรายการลงทะเบียนหลักสูตรทั้งระบบ — วิทยากรใช้หน้ารายหลักสูตร
        if (auth()->role() === 'trainer') {
            $owned = (new PlanOwnerService())->approvedPlanIds((int) auth()->id());
            if ($owned === []) {
                return dt_response($p['draw'], 0, 0, []);
            }
            $in = implode(',', array_fill(0, count($owned), '?'));
            $where .= " AND EXISTS (
                SELECT 1 FROM training_module_progress tmp
                WHERE tmp.enrollment_id = e.id AND tmp.plan_id IN ({$in})
            )";
            array_push($params, ...$owned);
        } elseif (is_learner()) {
            $where .= ' AND e.user_id = ?';
            $params[] = (int) auth()->id();
        }
        if ($p['search'] !== '') {
            $where .= ' AND (e.enrollment_no LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR o.name LIKE ?)';
            $s = '%' . $p['search'] . '%';
            array_push($params, $s, $s, $s, $s, $s);
        }
        $status = trim((string) ($_GET['status'] ?? ''));
        if ($status !== '') {
            $where .= ' AND e.status = ?';
            $params[] = $status;
        }

        $totalSql = 'SELECT COUNT(*) AS c FROM course_enrollments e';
        $totalParams = [];
        if (auth()->role() === 'trainer') {
            $owned = (new PlanOwnerService())->approvedPlanIds((int) auth()->id());
            if ($owned === []) {
                return dt_response($p['draw'], 0, 0, []);
            }
            $in = implode(',', array_fill(0, count($owned), '?'));
            $totalSql .= " WHERE EXISTS (
                SELECT 1 FROM training_module_progress tmp
                WHERE tmp.enrollment_id = e.id AND tmp.plan_id IN ({$in})
            )";
            $totalParams = $owned;
        } elseif (is_learner()) {
            $totalSql .= ' WHERE e.user_id = ?';
            $totalParams[] = (int) auth()->id();
        }
        $total = (int) (db()->fetch($totalSql, $totalParams)['c'] ?? 0);
        $filtered = (int) (db()->fetch(
            "SELECT COUNT(*) AS c
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE {$where}",
            $params
        )['c'] ?? 0);

        $order = dt_order_sql([
            1 => 'e.enrollment_no',
            2 => 'u.first_name',
            3 => 'o.name',
            4 => 'e.total_hours',
            5 => 'e.final_score',
            6 => 'e.status',
        ], $p['order_column'], $p['order_dir'], 'e.id DESC');

        $rows = db()->fetchAll(
            "SELECT e.*, u.username, u.first_name, u.last_name, u.title, o.name AS org_name
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE {$where}
             ORDER BY {$order}
             LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $canApprove = can_perm('enrollments', 'edit');
        $data = [];
        foreach ($rows as $r) {
            $st = (string) $r['status'];
            $badge = match ($st) {
                'completed' => 'success',
                'approved', 'in_progress' => 'primary',
                'pending' => 'warning',
                'failed', 'cancelled' => 'secondary',
                default => 'light',
            };
            $statusHtml = '<span class="badge text-bg-' . $badge . '">' . e($st) . '</span>';
            if ((int) $r['is_passed']) {
                $statusHtml .= ' <span class="badge text-bg-success">ผ่าน</span>';
            }
            $actions = '';
            if ($st === 'pending' && $canApprove) {
                $actions = dt_actions_wrap(
                    '<form method="post" action="' . e(url('/admin/enrollments/' . (int) $r['id'] . '/approve')) . '" class="d-inline">'
                    . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                    . '<button class="btn btn-sm btn-outline-success" type="submit" title="อนุมัติ"><i class="fa fa-check"></i></button></form>'
                );
            }
            $data[] = [
                'enrollment_no_html' => '<code>' . e($r['enrollment_no']) . '</code>',
                'user_name' => trim(($r['title'] ?? '') . ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                'org_name' => (string) ($r['org_name'] ?? '-'),
                'hours' => number_format((float) $r['total_hours'], 1) . ' / ' . number_format((float) $r['required_hours'], 1),
                'score' => $r['final_score'] !== null ? number_format((float) $r['final_score'], 1) : '-',
                'status_html' => $statusHtml,
                'actions' => $actions,
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function certificates(array $p): array
    {
        $where = 'c.plan_id IS NOT NULL';
        $params = [];
        if (is_learner()) {
            $where .= ' AND c.user_id = ?';
            $params[] = (int) auth()->id();
        }
        if ($p['search'] !== '') {
            $where .= ' AND (c.cert_no LIKE ? OR c.recipient_name LIKE ? OR c.org_name LIKE ? OR c.verify_code LIKE ? OR u.username LIKE ?)';
            $s = '%' . $p['search'] . '%';
            array_push($params, $s, $s, $s, $s, $s);
        }

        $totalSql = 'SELECT COUNT(*) AS c FROM certificates c WHERE c.plan_id IS NOT NULL';
        $totalParams = [];
        if (is_learner()) {
            $totalSql .= ' AND c.user_id = ?';
            $totalParams[] = (int) auth()->id();
        }
        $total = (int) (db()->fetch($totalSql, $totalParams)['c'] ?? 0);
        $filtered = (int) (db()->fetch(
            "SELECT COUNT(*) AS c FROM certificates c INNER JOIN users u ON u.id = c.user_id WHERE {$where}",
            $params
        )['c'] ?? 0);

        $order = dt_order_sql([
            1 => 'c.cert_no',
            2 => 'c.recipient_name',
            3 => 'c.org_name',
            4 => 'c.hours',
            5 => 'c.issued_at',
        ], $p['order_column'], $p['order_dir'], 'c.id DESC');

        $rows = db()->fetchAll(
            "SELECT c.*, u.username
             FROM certificates c
             INNER JOIN users u ON u.id = c.user_id
             WHERE {$where}
             ORDER BY {$order}
             LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $data = [];
        foreach ($rows as $c) {
            $data[] = [
                'cert_no_html' => '<code>' . e($c['cert_no']) . '</code>',
                'recipient_name' => (string) $c['recipient_name'],
                'org_name' => (string) ($c['org_name'] ?? '-'),
                'hours' => number_format((float) $c['hours'], 1),
                'issued_at' => thai_date($c['issued_at'] ?? null, true),
                'actions' => dt_actions_wrap(
                    dt_btn('view', url('/admin/certificates/' . (int) $c['id'])),
                    dt_btn('open', url('/certificate/verify?code=' . urlencode((string) $c['verify_code'])), [
                        'title' => 'ตรวจสอบ',
                        'blank' => true,
                    ])
                ),
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function certificatesReady(array $p): array
    {
        $where = 'e.is_passed = 1 AND c.id IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (e.enrollment_no LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.name LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s, $s];
        }

        $total = (int) (db()->fetch(
            "SELECT COUNT(*) AS c
             FROM course_enrollments e
             LEFT JOIN certificates c ON c.enrollment_id = e.id AND c.status = 'active'
             WHERE e.is_passed = 1 AND c.id IS NULL"
        )['c'] ?? 0);
        $filtered = (int) (db()->fetch(
            "SELECT COUNT(*) AS c
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             LEFT JOIN certificates c ON c.enrollment_id = e.id AND c.status = 'active'
             WHERE {$where}",
            $params
        )['c'] ?? 0);

        $order = dt_order_sql([
            1 => 'e.enrollment_no',
            2 => 'u.first_name',
            3 => 'o.name',
            4 => 'e.total_hours',
        ], $p['order_column'], $p['order_dir'], 'e.completed_at DESC, e.id DESC');

        $rows = db()->fetchAll(
            "SELECT e.id, e.enrollment_no, e.total_hours, e.final_score,
                    u.title, u.first_name, u.last_name, o.name AS org_name
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             LEFT JOIN certificates c ON c.enrollment_id = e.id AND c.status = 'active'
             WHERE {$where}
             ORDER BY {$order}
             LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $canIssue = can_perm('certificates', 'issue_cert');
        $data = [];
        foreach ($rows as $r) {
            $actions = '';
            if ($canIssue) {
                $actions = dt_actions_wrap(
                    '<form method="post" action="' . e(url('/admin/certificates/issue/' . (int) $r['id'])) . '" class="d-inline">'
                    . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                    . '<button class="btn btn-sm btn-outline-success" type="submit" title="ออกเกียรติบัตร"><i class="fa fa-certificate"></i></button></form>'
                );
            }
            $data[] = [
                'enrollment_no_html' => '<code>' . e($r['enrollment_no']) . '</code>',
                'user_name' => trim(($r['title'] ?? '') . ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                'org_name' => (string) ($r['org_name'] ?? '-'),
                'hours' => number_format((float) $r['total_hours'], 1),
                'actions' => $actions,
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }

    public static function trainingModules(array $p): array
    {
        $where = 'p.status = 1';
        $params = [];
        if (auth()->role() === 'trainer') {
            $ids = (new \App\Services\PlanOwnerService())->approvedPlanIds((int) auth()->id());
            $where .= $ids === [] ? ' AND 1=0' : ' AND p.id IN (' . implode(',', array_map('intval', $ids)) . ')';
        }
        if ($p['search'] !== '') {
            $where .= ' AND (p.title LIKE ? OR p.instructor_label LIKE ? OR p.topic_group LIKE ? OR CAST(p.plan_no AS CHAR) LIKE ?)';
            $s = '%' . $p['search'] . '%';
            $params = [$s, $s, $s, $s];
        }

        $scope = 'p.status = 1';
        if (auth()->role() === 'trainer') {
            $owned = (new \App\Services\PlanOwnerService())->approvedPlanIds((int) auth()->id());
            $scope .= $owned === [] ? ' AND 1=0' : ' AND p.id IN (' . implode(',', array_map('intval', $owned)) . ')';
        }
        $total = (int) (db()->fetch("SELECT COUNT(*) AS c FROM learning_plans p WHERE {$scope}")['c'] ?? 0);
        $filtered = (int) (db()->fetch("SELECT COUNT(*) AS c FROM learning_plans p WHERE {$where}", $params)['c'] ?? 0);
        $order = dt_order_sql([
            1 => 'p.plan_no',
            2 => 'p.title',
            3 => 'pretest_q',
            4 => 'content_count',
            5 => 'task_count',
            6 => 'posttest_q',
        ], $p['order_column'], $p['order_dir'], 'p.plan_no ASC, p.id ASC');

        $rows = db()->fetchAll(
            "SELECT p.*,
                (SELECT COUNT(*) FROM training_contents c WHERE c.plan_id = p.id AND c.status = 1) AS content_count,
                (SELECT COUNT(*) FROM training_tasks t WHERE t.plan_id = p.id AND t.status = 1) AS task_count,
                (SELECT COUNT(*) FROM training_quiz_questions q
                  INNER JOIN training_quizzes z ON z.id = q.quiz_id
                  WHERE z.plan_id = p.id AND z.quiz_type = 'pretest' AND q.status = 1) AS pretest_q,
                (SELECT COUNT(*) FROM training_quiz_questions q
                  INNER JOIN training_quizzes z ON z.id = q.quiz_id
                  WHERE z.plan_id = p.id AND z.quiz_type = 'pretest' AND q.status = 1) AS posttest_q
             FROM learning_plans p
             WHERE {$where}
             ORDER BY {$order}
             LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $data = [];
        foreach ($rows as $r) {
            $titleHtml = '<strong>' . e($r['title']) . '</strong>'
                . '<div class="small text-muted">' . e((string) ($r['hours'] ?? 1)) . ' ชม. · ' . e($r['instructor_label'] ?? '') . '</div>';
            $data[] = [
                'plan_no_html' => '<span class="badge text-bg-primary">' . (int) $r['plan_no'] . '</span>',
                'title_html' => $titleHtml,
                'pretest_q' => (int) ($r['pretest_q'] ?? 0) . ' ข้อ',
                'content_count' => (string) (int) ($r['content_count'] ?? 0),
                'task_count' => (string) (int) ($r['task_count'] ?? 0),
                'posttest_q' => (int) ($r['posttest_q'] ?? 0) . ' ข้อ',
                'actions' => dt_actions_wrap(
                    dt_btn('edit', url('/admin/training/' . (int) $r['id']), ['title' => 'จัดการ', 'label' => 'จัดการ'])
                ),
            ];
        }

        return dt_response($p['draw'], $total, $filtered, $data);
    }
}
