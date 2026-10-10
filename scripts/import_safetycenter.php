<?php
/**
 * นำตารางจาก db/safetycenter-2.sql เข้า DB ของโปรเจกต์ (tavis)
 *
 * สำคัญ: ไม่แตะฐาน `safetycenter` เดิมเลย
 * - ใช้ staging DB ชั่วคราว `_tavis_sc_stage` แล้วคัดลอกตารางเข้า tavis
 * - ตารางดิบใน tavis: amphur, area, cluster, province, religion, school, tumbon
 * - sync เพิ่มเติม → sc_* lookups + org_units (สำหรับสมัคร/CRUD/รายงาน)
 * - ไม่คัดลอก prefix / position / personaltype (legacy) — ใช้ sc_prefixes / sc_personal_types แทน
 *
 * Usage:
 *   php scripts/import_safetycenter.php
 *   php scripts/import_safetycenter.php --skip-dump   # ถ้า staging มีข้อมูลแล้ว
 *   php scripts/import_safetycenter.php --keep-stage  # ไม่ลบ staging หลังจบ
 */

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$user = (string) env('DB_USERNAME', 'root');
$pass = (string) env('DB_PASSWORD', '');
$appDb = (string) env('DB_DATABASE', 'tavis');
$dump = dirname(__DIR__) . '/db/safetycenter-2.sql';
$stageDb = '_tavis_sc_stage';
$skipDump = in_array('--skip-dump', $argv ?? [], true);
$keepStage = in_array('--keep-stage', $argv ?? [], true);

/** ตารางจาก safetycenter-2.sql ที่จะคัดลอกเข้า tavis */
$rawTables = [
    'amphur', 'area', 'cluster',
    'province', 'religion', 'school', 'tumbon',
];

echo "=== TAVIS ← Safety Center dump (ไม่แตะ DB safetycenter) ===\n";
echo "  target DB: {$appDb}\n";
echo "  staging DB: {$stageDb}\n";

if (!is_file($dump)) {
    fwrite(STDERR, "Missing dump: {$dump}\n");
    exit(1);
}

function mysql_bin(): string
{
    $candidates = [
        '/Applications/XAMPP/xamppfiles/bin/mysql',
        '/usr/local/mysql/bin/mysql',
        'mysql',
    ];
    foreach ($candidates as $bin) {
        if ($bin === 'mysql' || is_executable($bin)) {
            return $bin;
        }
    }
    return 'mysql';
}

function mysql_cli(string $sqlOrArgs, string $host, string $port, string $user, string $pass, ?string $inputFile = null): int
{
    $bin = mysql_bin();
    $auth = sprintf('-h%s -P%s -u%s', $host, $port, escapeshellarg($user));
    if ($pass !== '') {
        $auth .= ' -p' . escapeshellarg($pass);
    }
    if ($inputFile) {
        $cmd = sprintf('%s %s %s < %s', escapeshellarg($bin), $auth, $sqlOrArgs, escapeshellarg($inputFile));
    } else {
        $cmd = sprintf('%s %s -e %s', escapeshellarg($bin), $auth, escapeshellarg($sqlOrArgs));
    }
    echo "  > {$cmd}\n";
    passthru($cmd, $code);
    return (int) $code;
}

function src_pdo(string $db, string $host, string $port, string $user, string $pass): PDO
{
    return new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

function ensure_columns(PDO $pdo): void
{
    $cols = $pdo->query('SHOW COLUMNS FROM org_units')->fetchAll();
    $names = array_column($cols, 'Field');
    $alters = [];
    if (!in_array('external_id', $names, true)) {
        $alters[] = 'ADD COLUMN `external_id` VARCHAR(40) DEFAULT NULL AFTER `code`';
    }
    if (!in_array('cluster_code', $names, true)) {
        $alters[] = 'ADD COLUMN `cluster_code` VARCHAR(10) DEFAULT NULL AFTER `province`';
    }
    if (!in_array('latitude', $names, true)) {
        $alters[] = 'ADD COLUMN `latitude` VARCHAR(40) DEFAULT NULL';
    }
    if (!in_array('longitude', $names, true)) {
        $alters[] = 'ADD COLUMN `longitude` VARCHAR(40) DEFAULT NULL';
    }
    if ($alters) {
        $pdo->exec('ALTER TABLE org_units ' . implode(', ', $alters));
        echo "  altered org_units columns\n";
    }

    $ucols = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(), 'Field');
    $ualters = [];
    if (!in_array('personal_type_id', $ucols, true)) {
        $ualters[] = 'ADD COLUMN `personal_type_id` INT UNSIGNED DEFAULT NULL AFTER `org_unit_id`';
    }
    if ($ualters) {
        $pdo->exec('ALTER TABLE users ' . implode(', ', $ualters));
        echo "  altered users columns\n";
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `sc_clusters` (
      `id` INT UNSIGNED NOT NULL, `name` VARCHAR(255) NOT NULL, `short_name` VARCHAR(80) DEFAULT NULL, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sc_prefixes` (
      `id` INT UNSIGNED NOT NULL, `title` VARCHAR(80) NOT NULL, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sc_personal_types` (
      `id` INT UNSIGNED NOT NULL, `title` VARCHAR(120) NOT NULL, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sc_import_meta` (
      `meta_key` VARCHAR(60) NOT NULL, `meta_value` TEXT,
      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`meta_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Guard: never allow staging name to be the live safetycenter DB
if (strcasecmp($stageDb, 'safetycenter') === 0) {
    fwrite(STDERR, "Refusing to use safetycenter as staging DB\n");
    exit(1);
}

if (!$skipDump) {
    echo "1) Preparing staging DB `{$stageDb}` (ไม่แตะ safetycenter)...\n";
    mysql_cli(
        "DROP DATABASE IF EXISTS `{$stageDb}`; CREATE DATABASE `{$stageDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci",
        $host,
        $port,
        $user,
        $pass
    );
    echo "2) Importing dump → staging...\n";
    $code = mysql_cli($stageDb, $host, $port, $user, $pass, $dump);
    if ($code !== 0) {
        fwrite(STDERR, "mysql import failed (code {$code})\n");
        exit(1);
    }
} else {
    echo "1-2) Skipping dump import (--skip-dump)\n";
}

$dst = db()->pdo();
$src = src_pdo($stageDb, $host, $port, $user, $pass);
ensure_columns($dst);

echo "3) Copying raw tables into `{$appDb}`...\n";
foreach ($rawTables as $table) {
    $exists = $src->query("SHOW TABLES LIKE " . $src->quote($table))->fetch();
    if (!$exists) {
        echo "  skip missing in dump: {$table}\n";
        continue;
    }
    $dst->exec("DROP TABLE IF EXISTS `{$table}`");
    $dst->exec("CREATE TABLE `{$table}` LIKE `{$stageDb}`.`{$table}`");
    $dst->exec("INSERT INTO `{$table}` SELECT * FROM `{$stageDb}`.`{$table}`");
    $cnt = (int) $dst->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    echo "  {$table}: {$cnt}\n";
}

echo "4) Syncing lookup tables (sc_*)...\n";
$dst->exec('DELETE FROM sc_clusters');
$st = $dst->prepare('INSERT INTO sc_clusters (id, name, short_name) VALUES (?,?,?)');
foreach ($src->query('SELECT clus_id, clus_name, clus_short FROM cluster ORDER BY clus_id') as $r) {
    $st->execute([(int) $r['clus_id'], $r['clus_name'], $r['clus_short'] ?: null]);
}
echo '  clusters: ' . $dst->query('SELECT COUNT(*) FROM sc_clusters')->fetchColumn() . "\n";

$dst->exec('DELETE FROM sc_prefixes');
$st = $dst->prepare('INSERT INTO sc_prefixes (id, title) VALUES (?,?)');
foreach ($src->query('SELECT prefix_id, prefix_title FROM prefix ORDER BY prefix_id') as $r) {
    $st->execute([(int) $r['prefix_id'], $r['prefix_title']]);
}
echo '  prefixes: ' . $dst->query('SELECT COUNT(*) FROM sc_prefixes')->fetchColumn() . "\n";

$dst->exec('DELETE FROM sc_personal_types');
$st = $dst->prepare('INSERT INTO sc_personal_types (id, title) VALUES (?,?)');
foreach ($src->query('SELECT per_id, per_title FROM personaltype ORDER BY per_id') as $r) {
    $st->execute([(int) $r['per_id'], $r['per_title']]);
}
echo '  personal_types: ' . $dst->query('SELECT COUNT(*) FROM sc_personal_types')->fetchColumn() . "\n";

echo "5) Ensuring ministry / agency roots...\n";
$dst->exec("INSERT INTO org_units (id, code, name, name_en, org_type, parent_id, province, color, sort_order, status)
  VALUES (1,'MOE','กระทรวงศึกษาธิการ','Ministry of Education','ministry',NULL,'กรุงเทพมหานคร','#6b21a8',1,1)
  ON DUPLICATE KEY UPDATE name=VALUES(name), status=1");
$dst->exec("INSERT INTO org_units (id, code, name, name_en, org_type, parent_id, province, color, sort_order, status)
  VALUES (2,'OBEC','สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน','OBEC','agency',1,'กรุงเทพมหานคร','#7c3aed',2,1)
  ON DUPLICATE KEY UPDATE name=VALUES(name), parent_id=1, status=1");
$obecId = 2;

echo "6) Syncing areas (สพม.) → org_units...\n";
$areaMap = [];
$findArea = $dst->prepare("SELECT id FROM org_units WHERE org_type='area' AND (external_id=? OR code=?) AND deleted_at IS NULL LIMIT 1");
$updateArea = $dst->prepare(
    "UPDATE org_units SET name=?, parent_id=?, province=?, address=?, phone=?, cluster_code=?, latitude=?, longitude=?, code=?, external_id=?, status=1, deleted_at=NULL WHERE id=?"
);
$insertArea = $dst->prepare(
    "INSERT INTO org_units (code, external_id, name, org_type, parent_id, province, address, phone, cluster_code, latitude, longitude, color, sort_order, status)
     VALUES (?, ?, ?, 'area', ?, ?, ?, ?, ?, ?, ?, '#f59e0b', ?, 1)"
);

$nArea = 0;
foreach ($src->query('SELECT * FROM area ORDER BY area_id') as $a) {
    $code = trim((string) ($a['area_code'] ?: $a['area_code2'] ?: ('AREA' . $a['area_id'])));
    $ext = (string) $a['area_id'];
    $name = trim((string) ($a['area_name'] ?: ('เขต ' . $ext)));
    if ($name === '') {
        continue;
    }
    $province = trim((string) ($a['provinceName'] ?? '')) ?: null;
    $phone = trim((string) ($a['phone1'] ?? '')) ?: null;
    $addr = trim(implode(' ', array_filter([
        $a['houseNumber'] ?? '', $a['street'] ?? '', $a['subdistrictName'] ?? '', $a['districtName'] ?? '',
    ]))) ?: null;
    $clus = trim((string) ($a['area_clus'] ?? '')) ?: null;
    $lat = trim((string) ($a['latitude'] ?? '')) ?: null;
    $lng = trim((string) ($a['longitude'] ?? '')) ?: null;

    $findArea->execute([$ext, $code]);
    $existing = $findArea->fetch();
    if ($existing) {
        $updateArea->execute([$name, $obecId, $province, $addr, $phone, $clus, $lat, $lng, $code, $ext, (int) $existing['id']]);
        $areaMap[$code] = (int) $existing['id'];
        $areaMap['id:' . $ext] = (int) $existing['id'];
    } else {
        $insertArea->execute([$code, $ext, $name, $obecId, $province, $addr, $phone, $clus, $lat, $lng, (int) $a['area_id']]);
        $id = (int) $dst->lastInsertId();
        $areaMap[$code] = $id;
        $areaMap['id:' . $ext] = $id;
    }
    $code2 = trim((string) ($a['area_code2'] ?? ''));
    if ($code2 !== '' && isset($areaMap[$code])) {
        $areaMap[$code2] = $areaMap[$code];
    }
    $nArea++;
}
echo "  areas upserted: {$nArea}\n";

echo "7) Syncing schools → org_units (batched)...\n";
$findSchool = $dst->prepare("SELECT id FROM org_units WHERE org_type='school' AND (external_id=? OR code=?) AND deleted_at IS NULL LIMIT 1");
$updateSchool = $dst->prepare(
    "UPDATE org_units SET name=?, parent_id=?, province=?, address=?, phone=?, cluster_code=?, latitude=?, longitude=?, code=?, external_id=?, status=1, deleted_at=NULL WHERE id=?"
);
$insertSchool = $dst->prepare(
    "INSERT INTO org_units (code, external_id, name, org_type, parent_id, province, address, phone, cluster_code, latitude, longitude, color, sort_order, status)
     VALUES (?, ?, ?, 'school', ?, ?, ?, ?, ?, ?, ?, '#fbbf24', ?, 1)"
);

$nSchool = 0;
$nSkip = 0;
$stmt = $src->query('SELECT * FROM school ORDER BY sch_id');
$dst->beginTransaction();
while ($s = $stmt->fetch()) {
    $code = trim((string) ($s['sch_code'] ?: ('SCH' . $s['sch_id'])));
    $ext = (string) $s['sch_id'];
    $name = trim((string) ($s['sch_name'] ?? ''));
    if ($name === '') {
        $nSkip++;
        continue;
    }
    $areaKey = trim((string) ($s['sch_area'] ?? ''));
    $parentId = $areaMap[$areaKey] ?? $areaMap[trim((string) ($s['sch_area2'] ?? ''))] ?? $obecId;
    $province = trim((string) ($s['provinceName'] ?? '')) ?: null;
    $phone = trim((string) ($s['phone1'] ?? '')) ?: null;
    $addr = trim(implode(' ', array_filter([
        $s['houseNumber'] ?? '', $s['street'] ?? '', $s['subDistrictName'] ?? '', $s['districtName'] ?? '',
    ]))) ?: null;
    $clus = trim((string) ($s['sch_clus'] ?? '')) ?: null;
    $lat = trim((string) ($s['latitude'] ?? '')) ?: null;
    $lng = trim((string) ($s['longitude'] ?? '')) ?: null;

    $findSchool->execute([$ext, $code]);
    $existing = $findSchool->fetch();
    if ($existing) {
        $updateSchool->execute([$name, $parentId, $province, $addr, $phone, $clus, $lat, $lng, $code, $ext, (int) $existing['id']]);
    } else {
        $insertSchool->execute([$code, $ext, $name, $parentId, $province, $addr, $phone, $clus, $lat, $lng, (int) $s['sch_id']]);
    }
    $nSchool++;
    if ($nSchool % 500 === 0) {
        $dst->commit();
        $dst->beginTransaction();
        echo "  ... {$nSchool}\n";
    }
}
$dst->commit();
echo "  schools upserted: {$nSchool} (skipped empty name: {$nSkip})\n";

$meta = $dst->prepare('INSERT INTO sc_import_meta (meta_key, meta_value) VALUES (?,?) ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)');
$meta->execute(['last_import_at', date('c')]);
$meta->execute(['areas', (string) $nArea]);
$meta->execute(['schools', (string) $nSchool]);
$meta->execute(['source_dump', 'db/safetycenter-2.sql']);
$meta->execute(['staging_db', $stageDb]);

if (!$keepStage) {
    echo "8) Dropping staging DB `{$stageDb}`...\n";
    mysql_cli("DROP DATABASE IF EXISTS `{$stageDb}`", $host, $port, $user, $pass);
} else {
    echo "8) Keeping staging DB (--keep-stage)\n";
}

// Safety check: confirm we did not wipe safetycenter
try {
    $check = src_pdo('safetycenter', $host, $port, $user, $pass);
    $scTables = (int) $check->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='safetycenter'")->fetchColumn();
    echo "  safetycenter still present: {$scTables} tables (untouched by this import)\n";
} catch (Throwable $e) {
    echo "  note: could not verify safetycenter ({$e->getMessage()})\n";
}

$areaCount = (int) $dst->query("SELECT COUNT(*) FROM org_units WHERE org_type='area' AND deleted_at IS NULL")->fetchColumn();
$schoolCount = (int) $dst->query("SELECT COUNT(*) FROM org_units WHERE org_type='school' AND deleted_at IS NULL")->fetchColumn();
echo "Done. org_units areas={$areaCount}, schools={$schoolCount}\n";
