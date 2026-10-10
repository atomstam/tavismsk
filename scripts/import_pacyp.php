<?php
/**
 * นำเข้าบุคลากรโรงเรียนเชียงยืนพิทยาคม
 * แหล่งหลัก: mteachercyp.web_person (ครูทุกคน แยก username/password)
 * เสริม: ci4pacyp.users / pacyp.users (ตำแหน่ง วิทยฐานะ)
 * รหัสโรงเรียน: 1044410601
 */

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

$schoolCode = '1044410601';
echo "E-Portfolio import personnel...\n";

$pdo = db()->pdo();
$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$user = (string) env('DB_USERNAME', 'root');
$pass = (string) env('DB_PASSWORD', '');

function pdo_src(string $db, string $host, string $port, string $user, string $pass): ?PDO
{
    try {
        return new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $e) {
        echo "  skip {$db}: {$e->getMessage()}\n";
        return null;
    }
}

function map_role_from_position(?int $positionId, string $userRole = ''): string
{
    if (stripos($userRole, 'Admin') !== false) {
        return 'admin';
    }
    return match ($positionId) {
        1 => 'director',
        2 => 'deputy',
        default => 'teacher',
    };
}

function clean_email(?string $email): ?string
{
    $email = trim((string) $email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $dummy = ['mail@gmail.com', 'test@test.com', 'user@email.com'];
    if (in_array(strtolower($email), $dummy, true)) {
        return null;
    }
    return $email;
}

function clean_phone(?string $phone): ?string
{
    $phone = trim((string) $phone);
    if ($phone === '' || preg_match('/^x+$/i', $phone) || $phone === '0000000000') {
        return null;
    }
    return $phone;
}

function gender_from_prefix(?string $title): ?string
{
    $t = trim((string) $title);
    if ($t === 'นาย' || str_starts_with($t, 'ว่าที่ร้อย')) {
        return 'male';
    }
    if ($t === 'นาง' || $t === 'นางสาว') {
        return 'female';
    }
    return null;
}

/**
 * @return array{username:string,email:?string,password:string,title:?string,first_name:string,last_name:string,phone:?string,role:string,status:int,legacy_user_id:?int,code:?string,id_card:?string,position_id:int,academic_rank_id:int,salary_step:?string,salary:?string,department_id:int}
 */
function person_row(array $src): array
{
    $idCard = preg_replace('/\D+/', '', (string) ($src['id_card'] ?? ''));
    $username = $idCard !== '' ? $idCard : (string) ($src['username'] ?? ('t' . ($src['legacy_user_id'] ?? uniqid())));
    $pin = (string) ($src['pin'] ?? '');
    if ($pin === '' || $pin === '0') {
        $pin = $idCard !== '' ? substr($idCard, -4) : 'Cyp@2569';
    }
    $hash = (string) ($src['password_hash'] ?? '');
    if ($hash === '' || !str_starts_with($hash, '$2y$')) {
        $hash = password_hash($pin, PASSWORD_BCRYPT);
    }
    $pos = (int) ($src['position_id'] ?? 3);
    if ($pos < 1 || $pos > 4) {
        $pos = 3;
    }
    $rank = (int) ($src['academic_rank_id'] ?? 1);
    if ($rank < 1) {
        $rank = 1;
    }
    $dept = $pos === 1 ? 1 : 2;

    return [
        'username' => $username,
        'email' => clean_email($src['email'] ?? null),
        'password' => $hash,
        'title' => $src['title'] ?: null,
        'first_name' => trim((string) $src['first_name']),
        'last_name' => trim((string) $src['last_name']),
        'phone' => clean_phone($src['phone'] ?? null),
        'role' => map_role_from_position($pos, (string) ($src['user_role'] ?? '')),
        'status' => (int) ($src['status'] ?? 1) === 0 ? 0 : 1,
        'legacy_user_id' => isset($src['legacy_user_id']) ? (int) $src['legacy_user_id'] : null,
        'code' => $src['code'] ?? $username,
        'id_card' => $idCard !== '' ? $idCard : null,
        'position_id' => $pos,
        'academic_rank_id' => $rank,
        'salary_step' => $src['salary_step'] ?? null,
        'salary' => $src['salary'] ?? null,
        'department_id' => $dept,
        'gender' => gender_from_prefix($src['title'] ?? null),
    ];
}

function upsert_person(PDO $pdo, array $row): bool
{
    if ($row['first_name'] === '' || $row['username'] === '' || $row['username'] === 'admin') {
        return false;
    }
    $exists = $pdo->prepare('SELECT id FROM users WHERE username = ? OR (legacy_user_id IS NOT NULL AND legacy_user_id = ?) OR (? IS NOT NULL AND email = ?) LIMIT 1');
    $exists->execute([$row['username'], $row['legacy_user_id'], $row['email'], $row['email']]);
    if ($exists->fetch()) {
        return false;
    }
    $pdo->prepare(
        'INSERT INTO users (username,email,password,title,first_name,last_name,phone,role,status,legacy_user_id,created_at,updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())'
    )->execute([
        $row['username'],
        $row['email'],
        $row['password'],
        $row['title'],
        $row['first_name'],
        $row['last_name'],
        $row['phone'],
        $row['role'],
        $row['status'],
        $row['legacy_user_id'],
    ]);
    $uid = (int) $pdo->lastInsertId();
    $pdo->prepare(
        'INSERT INTO personnel (user_id,code,id_card,title,first_name,last_name,gender,position_id,academic_rank_id,salary_step,salary,department_id,phone,email,status,created_at,updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())'
    )->execute([
        $uid,
        $row['code'],
        $row['id_card'],
        $row['title'],
        $row['first_name'],
        $row['last_name'],
        $row['gender'],
        $row['position_id'],
        $row['academic_rank_id'],
        $row['salary_step'],
        $row['salary'],
        $row['department_id'],
        $row['phone'],
        $row['email'],
        $row['status'],
    ]);
    $pid = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE users SET personnel_id = ?, department_id = ? WHERE id = ?')->execute([$pid, $row['department_id'], $uid]);
    return true;
}

$imported = 0;

// 1) Teachers from mteachercyp (CYP roster)
$mt = pdo_src('mteachercyp', $host, $port, $user, $pass);
if ($mt) {
    $rows = $mt->query('SELECT * FROM web_person ORDER BY CAST(per_posi AS UNSIGNED), per_id')->fetchAll();
    echo 'Read ' . count($rows) . " rows from mteachercyp.web_person.\n";
    foreach ($rows as $p) {
        $ok = upsert_person($pdo, person_row([
            'id_card' => $p['per_idcard'] ?? '',
            'username' => $p['per_idcard'] ?: ('cyp' . $p['per_id']),
            'pin' => $p['per_pin'] ?? '',
            'title' => $p['per_prefix'] ?? '',
            'first_name' => $p['per_fname'] ?? '',
            'last_name' => $p['per_lname'] ?? '',
            'email' => $p['per_email'] ?? '',
            'phone' => $p['per_tel'] ?? '',
            'position_id' => (int) ($p['per_posi'] ?? 3),
            'status' => ((string) ($p['status'] ?? '1') === '1') ? 1 : 0,
            'legacy_user_id' => 200000 + (int) $p['per_id'],
            'code' => $p['per_ids'] ?: ($p['per_idcard'] ?? ''),
        ]));
        if ($ok) {
            $imported++;
        }
    }
}

// 2) Enrich / extra accounts from ci4pacyp then pacyp
foreach (['ci4pacyp', 'pacyp'] as $dbname) {
    $src = pdo_src($dbname, $host, $port, $user, $pass);
    if (!$src) {
        continue;
    }
    $sql = "SELECT * FROM users WHERE sch_id = ? OR user_id = 1 OR user_school LIKE '%เชียงยืน%' ORDER BY user_id";
    $st = $src->prepare($sql);
    $st->execute([$schoolCode]);
    $rows = $st->fetchAll();
    echo 'Read ' . count($rows) . " rows from {$dbname}.users.\n";
    foreach ($rows as $u) {
        if ((int) ($u['user_id'] ?? 0) === 1) {
            $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=COALESCE(email, ?), phone=COALESCE(phone, ?), title=? WHERE username=\'admin\'')
                ->execute([
                    $u['user_fname'] ?: 'ผู้ดูแล',
                    $u['user_lname'] ?: 'ระบบ',
                    clean_email($u['user_email'] ?? null) ?: 'admin@cyp.ac.th',
                    clean_phone($u['user_phone'] ?? null),
                    $u['user_prefix'] ?: 'นาย',
                ]);
            continue;
        }
        $ok = upsert_person($pdo, person_row([
            'id_card' => $u['card number'] ?? $u['user_idcard'] ?? '',
            'username' => $u['user_idcard'] ?: ('u' . $u['user_id']),
            'password_hash' => $u['user_hashpassword'] ?? '',
            'pin' => $u['user_password'] ?? '',
            'title' => $u['user_prefix'] ?? '',
            'first_name' => $u['user_fname'] ?? '',
            'last_name' => $u['user_lname'] ?? '',
            'email' => $u['user_email'] ?? '',
            'phone' => $u['user_phone'] ?? '',
            'position_id' => $u['position_id'] ?? 3,
            'academic_rank_id' => $u['academic_rank_id'] ?? 1,
            'salary_step' => $u['salary_step'] ?? null,
            'salary' => $u['salary'] ?? null,
            'user_role' => $u['user_role'] ?? '',
            'status' => strcasecmp((string) ($u['user_status'] ?? 'Active'), 'Active') === 0 ? 1 : 1,
            'legacy_user_id' => (int) $u['user_id'],
            'code' => $u['user_idcard'] ?? '',
        ]));
        if ($ok) {
            $imported++;
        }
    }
}

echo "Imported {$imported} personnel accounts.\n";

$cnt = (int) $pdo->query('SELECT COUNT(*) FROM portfolio_items')->fetchColumn();
if ($cnt === 0) {
    $owner = $pdo->query("SELECT id FROM users WHERE role IN ('director','admin') ORDER BY FIELD(role,'director','admin'), id LIMIT 1")->fetch();
    $uid = (int) ($owner['id'] ?? 1);
    $samples = [
        ['speaker', 'วิทยากร การอบรมเจ้าพนักงานผู้ดำเนินการเลือกตั้ง', 'สำนักงานคณะกรรมการการเลือกตั้งประจำจังหวัดมหาสารคาม', '2026-02-03', 12, null, null],
        ['speaker', 'วิทยากร ลูกเสือสามัญรุ่นใหญ่', 'สำนักงานลูกเสือจังหวัดมหาสารคาม', '2026-01-21', 12, null, null],
        ['speaker', 'วิทยากร PLC สถานศึกษา', 'โรงเรียนเชียงยืนพิทยาคม', '2026-03-10', 6, null, null],
        ['training', 'การอบรมวิทยากรเขตเลือกตั้ง', 'สำนักงาน กกต.', '2026-01-21', 12, null, null],
        ['innovation', 'ธนาคารโรงเรียนดิจิทัล SMART SCHOOL BANK Model', null, null, 0, 'นวัตกรรมทางเทคโนโลยี', 'ใช้งานจริง'],
    ];
    $ins = $pdo->prepare(
        'INSERT INTO portfolio_items (user_id,type,title,organization,start_date,hours,category,status_name,academic_year,year_be,created_by,created_at,updated_at)
         VALUES (?,?,?,?,?,?,?,?,2569,2569,?,NOW(),NOW())'
    );
    foreach ($samples as $s) {
        $ins->execute([$uid, $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $uid]);
    }
    echo "Inserted demo portfolio items.\n";
}

echo "Done.\n";
echo "ครูเข้าสู่ระบบด้วยเลขบัตรประชาชน และรหัสผ่านเป็น PIN จากระบบครู (ค่าเริ่มต้นส่วนใหญ่ 1234)\n";
echo "ผู้ดูแลระบบ username=admin — ตั้งรหัสด้วย php scripts/set_admin_password.php\n";
