<?php

declare(strict_types=1);

namespace App\Services;

use PDOException;

class CatalogService
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $rows = config('catalogs');
        return is_array($rows) ? $rows : [];
    }

    /** @return array<string, mixed> */
    public function get(string $key): array
    {
        $row = $this->all()[$key] ?? null;
        if (!is_array($row) || !$this->safeIdent((string) ($row['table'] ?? ''))) {
            return [];
        }
        return $row;
    }

    /** @param array<string, mixed> $p */
    public function datatable(string $key, array $p): array
    {
        $cat = $this->get($key);
        if ($cat === []) {
            return dt_response((int) ($p['draw'] ?? 1), 0, 0, []);
        }
        $table = (string) $cat['table'];
        $searchCols = $this->idents((array) ($cat['search'] ?? []));
        $where = '1=1';
        $params = [];
        if (($p['search'] ?? '') !== '' && $searchCols !== []) {
            $parts = [];
            $like = '%' . $p['search'] . '%';
            foreach ($searchCols as $col) {
                $parts[] = "`{$col}` LIKE ?";
                $params[] = $like;
            }
            $where .= ' AND (' . implode(' OR ', $parts) . ')';
        }
        $total = (int) db()->count($table, '1=1');
        $filtered = ($p['search'] ?? '') === ''
            ? $total
            : (int) (db()->fetch("SELECT COUNT(*) AS c FROM `{$table}` WHERE {$where}", $params)['c'] ?? 0);

        $map = [];
        foreach (array_values((array) ($cat['columns'] ?? [])) as $i => $col) {
            if (is_array($col) && $this->safeIdent((string) ($col['sql'] ?? ''))) {
                $map[$i + 1] = '`' . $col['sql'] . '`';
            }
        }
        $order = dt_order_sql($map, (int) ($p['order_column'] ?? 0), (string) ($p['order_dir'] ?? 'asc'), (string) ($cat['sort'] ?? 'id DESC'));
        $rows = db()->fetchAll(
            "SELECT * FROM `{$table}` WHERE {$where} ORDER BY {$order} LIMIT {$p['length']} OFFSET {$p['start']}",
            $params
        );

        $canEdit = can_perm((string) $cat['module'], 'edit');
        $canDelete = can_perm((string) $cat['module'], 'delete');
        $data = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ((array) $cat['columns'] as $col) {
                if (!is_array($col)) {
                    continue;
                }
                $item[(string) $col['key']] = $this->cell($row, $col);
            }
            $id = (int) ($row['id'] ?? 0);
            $item['actions'] = dt_actions_wrap(
                $canEdit ? dt_btn('edit', url('/admin/catalog/' . $key . '/' . $id . '/edit')) : '',
                $canDelete ? dt_btn('delete', url('/admin/catalog/' . $key . '/' . $id . '/delete'), [
                    'method' => 'post',
                    'confirm_delete' => 'ลบรายการนี้?',
                ]) : ''
            );
            $data[] = $item;
        }
        return dt_response((int) ($p['draw'] ?? 1), $total, $filtered, $data);
    }

    public function find(string $key, int $id): ?array
    {
        $cat = $this->get($key);
        if ($cat === [] || $id < 1) {
            return null;
        }
        $table = (string) $cat['table'];
        return db()->fetch("SELECT * FROM `{$table}` WHERE `id` = ? LIMIT 1", [$id]);
    }

    /** @param array<string, mixed> $input */
    public function save(string $key, ?int $id, array $input): int
    {
        $cat = $this->get($key);
        if ($cat === []) {
            throw new \InvalidArgumentException('ไม่พบชุดข้อมูล');
        }
        if ($id === null && empty($cat['creatable']) && array_key_exists('creatable', $cat)) {
            throw new \InvalidArgumentException('ชุดข้อมูลนี้เพิ่มจากหน้านี้ไม่ได้');
        }
        $data = $this->payload($cat, $input, $id);
        if ($id === null && !empty($cat['manual_id'])) {
            $manual = (int) ($data['id'] ?? 0);
            if ($manual < 1) {
                throw new \InvalidArgumentException('กรุณาระบุรหัส');
            }
            if ($this->find($key, $manual)) {
                throw new \InvalidArgumentException('รหัสนี้มีแล้ว');
            }
        }
        if ($id !== null) {
            unset($data['id']);
        }
        $table = (string) $cat['table'];
        try {
            if ($id === null) {
                if ($key === 'news') {
                    $data['created_by'] = auth()->id() ?: null;
                }
                $newId = (int) db()->insert($table, $data);
                $id = $newId > 0 ? $newId : (int) ($data['id'] ?? 0);
            } else {
                if ($this->find($key, $id) === null) {
                    throw new \InvalidArgumentException('ไม่พบรายการ');
                }
                if ($data !== []) {
                    db()->update($table, $data, '`id` = ?', [$id]);
                }
            }
        } catch (PDOException) {
            throw new \InvalidArgumentException('บันทึกไม่สำเร็จ ข้อมูลอาจซ้ำหรือไม่ถูกต้อง');
        }
        $this->applyExclusive($cat, $id, $data);
        return $id;
    }

    public function delete(string $key, int $id): void
    {
        $cat = $this->get($key);
        if ($cat === [] || $id < 1 || $this->find($key, $id) === null) {
            throw new \InvalidArgumentException('ไม่พบรายการ');
        }
        db()->delete((string) $cat['table'], '`id` = ?', [$id]);
    }

    /** @param array<string, mixed> $cat @param array<string, mixed> $input */
    private function payload(array $cat, array $input, ?int $id): array
    {
        $creating = $id === null;
        $data = [];
        foreach ((array) ($cat['fields'] ?? []) as $field) {
            if (!is_array($field) || !$this->safeIdent((string) ($field['name'] ?? ''))) {
                continue;
            }
            $name = (string) $field['name'];
            if (!empty($field['readonly']) || (!$creating && !empty($field['create_only']))) {
                continue;
            }
            $raw = trim((string) ($input[$name] ?? ''));
            $value = $this->cast($field, $raw);
            if (!empty($field['unique'])) {
                $this->assertUnique((string) $cat['table'], $name, $value, $id);
            }
            $data[$name] = $value;
        }
        if ($creating && isset($data['slug']) && $data['slug'] === '' && !empty($input['title'])) {
            $data['slug'] = $this->slug((string) $input['title']);
        }
        return $data;
    }

    /** @param array<string, mixed> $field */
    private function cast(array $field, string $raw): mixed
    {
        $type = (string) ($field['type'] ?? 'text');
        $required = !empty($field['required']);
        if ($raw === '' && isset($field['default'])) {
            return $field['default'];
        }
        if ($raw === '' && $required && $type !== 'number') {
            throw new \InvalidArgumentException('กรุณากรอก' . (string) ($field['label'] ?? ''));
        }
        if ($type === 'number') {
            if ($raw === '') {
                return 0;
            }
            if (!preg_match('/^-?\d+$/', $raw)) {
                throw new \InvalidArgumentException((string) ($field['label'] ?? 'ตัวเลข') . 'ไม่ถูกต้อง');
            }
            return (int) $raw;
        }
        if ($type === 'select') {
            $allowed = [];
            foreach ((array) ($field['options'] ?? []) as $opt) {
                if (is_array($opt)) {
                    $allowed[] = (string) $opt[0];
                }
            }
            if (!in_array($raw, $allowed, true)) {
                $raw = $allowed[0] ?? '';
            }
            return $raw === '' ? null : $raw;
        }
        if ($type === 'datetime') {
            if ($raw === '') {
                return null;
            }
            $ts = strtotime($raw);
            if ($ts === false) {
                throw new \InvalidArgumentException('วันเวลาไม่ถูกต้อง');
            }
            return date('Y-m-d H:i:s', $ts);
        }
        $max = (int) ($field['max'] ?? 0);
        if ($max > 0 && mb_strlen($raw) > $max) {
            $raw = mb_substr($raw, 0, $max);
        }
        if ($raw === '' && !$required) {
            return $type === 'text' ? null : '';
        }
        return $raw;
    }

    private function assertUnique(string $table, string $column, mixed $value, ?int $ignoreId): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $sql = "SELECT `id` FROM `{$table}` WHERE `{$column}` = ?";
        $params = [$value];
        if ($ignoreId) {
            $sql .= ' AND `id` <> ?';
            $params[] = $ignoreId;
        }
        if (db()->fetch($sql . ' LIMIT 1', $params)) {
            throw new \InvalidArgumentException('ข้อมูลนี้มีอยู่แล้ว');
        }
    }

    /** @param array<string, mixed> $cat @param array<string, mixed> $data */
    private function applyExclusive(array $cat, int $id, array $data): void
    {
        $column = (string) ($cat['exclusive'] ?? '');
        if ($column === '' || !$this->safeIdent($column) || (int) ($data[$column] ?? 0) !== 1 || $id < 1) {
            return;
        }
        db()->update((string) $cat['table'], [$column => 0], "`id` <> ? AND `{$column}` = 1", [$id]);
    }

    /** @param array<string, mixed> $row @param array<string, mixed> $col */
    private function cell(array $row, array $col): string
    {
        $key = (string) ($col['key'] ?? '');
        $sql = (string) ($col['sql'] ?? '');
        $value = (string) ($row[$sql] ?? '');
        if ($key === 'read_html') {
            return (int) $value === 1
                ? '<span class="badge-pill-ok">อ่านแล้ว</span>'
                : '<span class="badge-pill-off">ยังไม่อ่าน</span>';
        }
        if ($key === 'current_html') {
            return (int) $value === 1
                ? '<span class="badge-pill-ok">ปีปัจจุบัน</span>'
                : '<span class="badge-pill-off">—</span>';
        }
        if (str_ends_with($key, '_html')) {
            $on = (int) $value === 1;
            return $on
                ? '<span class="badge-pill-ok">ใช้งาน</span>'
                : '<span class="badge-pill-off">ปิด</span>';
        }
        if ($key === 'audience_label') {
            return ['both' => 'ทุกประเภท', 'staff' => 'ครูและบุคลากร', 'student' => 'นักเรียน', 'general' => 'ทั่วไป'][$value] ?? $value;
        }
        if ($key === 'kind_label') {
            return ['staff' => 'ครูและบุคลากร', 'student' => 'นักเรียน'][$value] ?? $value;
        }
        if ($key === 'created_label') {
            return thai_date($value, true);
        }
        return $value !== '' ? $value : '—';
    }

    private function slug(string $title): string
    {
        $slug = trim(preg_replace('/\s+/u', '-', $title) ?? $title);
        $slug = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $slug) ?? $slug;
        return mb_substr($slug, 0, 180);
    }

    /** @param list<mixed> $names @return list<string> */
    private function idents(array $names): array
    {
        $out = [];
        foreach ($names as $name) {
            if ($this->safeIdent((string) $name)) {
                $out[] = (string) $name;
            }
        }
        return $out;
    }

    private function safeIdent(string $name): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $name);
    }
}
