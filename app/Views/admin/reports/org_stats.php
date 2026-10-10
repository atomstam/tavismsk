<?php
$filters = $filters ?? ['cluster' => '', 'areaId' => 0, 'schoolId' => 0, 'role' => ''];
$totals = $totals ?? [];
$ready = !empty($ready);
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center gap-2">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="text-muted mb-0">รายงานผู้ใช้ตามเขตตรวจราชการ · สพม. · โรงเรียน (ข้อมูลจาก Safety Center)</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('/admin/reports/org-stats/export?' . http_build_query([
      'cluster' => $filters['cluster'], 'area_id' => $filters['areaId'], 'school_id' => $filters['schoolId'], 'role' => $filters['role'],
    ])) ?>"><i class="fa fa-file-excel"></i> ส่งออก</a>
  </div>
</div>

<?php if (!$ready): ?>
<div class="alert alert-warning">
  ยังไม่พบข้อมูลโรงเรียนครบ — รันคำสั่ง
  <code>php scripts/import_safetycenter.php</code>
  เพื่อนำเข้าจาก <code>db/safetycenter-2.sql</code>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="card-soft p-3"><div class="text-muted small">ผู้ใช้ทั้งหมด</div><div class="fs-4 fw-bold"><?= number_format((int)($totals['users'] ?? 0)) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="card-soft p-3"><div class="text-muted small">ใช้งานอยู่</div><div class="fs-4 fw-bold"><?= number_format((int)($totals['active'] ?? 0)) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="card-soft p-3"><div class="text-muted small">สพม. / เขต</div><div class="fs-4 fw-bold"><?= number_format((int)($totals['areas'] ?? 0)) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="card-soft p-3"><div class="text-muted small">โรงเรียน / มี org</div><div class="fs-4 fw-bold"><?= number_format((int)($totals['schools'] ?? 0)) ?> <span class="fs-6 text-muted">/ <?= number_format((int)($totals['with_org'] ?? 0)) ?></span></div></div></div>
</div>

<div class="card-soft mb-3"><div class="card-body">
  <form method="get" class="row g-2 align-items-end" id="org-stats-filter">
    <div class="col-md-3">
      <label class="form-label">เขตตรวจราชการ</label>
      <select name="cluster" class="form-select" id="flt-cluster">
        <option value="">— ทั้งหมด —</option>
        <?php foreach ($clusters as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((string)$filters['cluster'] === (string)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">สพม. / เขตพื้นที่</label>
      <select name="area_id" class="form-select" id="flt-area">
        <option value="0">— ทั้งหมด —</option>
        <?php foreach ($areas as $a): ?>
          <option value="<?= (int)$a['id'] ?>" <?= ((int)$filters['areaId'] === (int)$a['id']) ? 'selected' : '' ?>><?= e($a['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">โรงเรียน</label>
      <select name="school_id" class="form-select" id="flt-school">
        <option value="0">— ทั้งหมด —</option>
        <?php foreach ($schools as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= ((int)$filters['schoolId'] === (int)$s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">บทบาท</label>
      <select name="role" class="form-select">
        <option value="">— ทั้งหมด —</option>
        <?php foreach ($roles as $k => $meta): ?>
          <option value="<?= e($k) ?>" <?= ($filters['role'] === $k) ? 'selected' : '' ?>><?= e($meta['label'] ?? $k) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">กรอง</button></div>
  </form>
</div></div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card-soft"><div class="card-body">
      <h2 class="h6">แยกตามบทบาท (ตามตัวกรอง)</h2>
      <ul class="list-unstyled mb-0">
        <?php foreach ($byRole as $r): ?>
          <li class="d-flex justify-content-between py-1 border-bottom"><span><?= e(role_name((string)$r['role'])) ?></span><strong><?= number_format((int)$r['c']) ?></strong></li>
        <?php endforeach; ?>
        <?php if (!$byRole): ?><li class="text-muted">ไม่มีข้อมูล</li><?php endif; ?>
      </ul>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card-soft"><div class="card-body">
      <h2 class="h6">เขตตรวจราชการ (โรงเรียน / ผู้ใช้)</h2>
      <div style="max-height:280px;overflow:auto">
        <table class="table table-sm mb-0">
          <thead><tr><th>เขต</th><th class="text-end">รร.</th><th class="text-end">ผู้ใช้</th></tr></thead>
          <tbody>
          <?php foreach ($byCluster as $r): ?>
            <tr>
              <td><?= e($r['cluster_name'] ?: ('รหัส ' . ($r['cluster_code'] ?? '-'))) ?></td>
              <td class="text-end"><?= number_format((int)$r['schools']) ?></td>
              <td class="text-end"><?= number_format((int)$r['users']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card-soft"><div class="card-body">
      <h2 class="h6">สพม. ที่มีผู้ใช้มากสุด</h2>
      <div style="max-height:280px;overflow:auto">
        <table class="table table-sm mb-0">
          <thead><tr><th>สพม.</th><th class="text-end">ผู้ใช้</th></tr></thead>
          <tbody>
          <?php foreach ($byAreaTop as $r): ?>
            <tr>
              <td><?= e($r['name']) ?><?php if (!empty($r['province'])): ?><br><small class="text-muted"><?= e($r['province']) ?></small><?php endif; ?></td>
              <td class="text-end"><?= number_format((int)$r['users']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div></div>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <h2 class="h6 mb-3">รายชื่อผู้ใช้ตามตัวกรอง <span class="text-muted fw-normal">(สูงสุด 5,000 รายการ)</span></h2>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead>
        <tr>
          <th>#</th><th>ชื่อ-สกุล</th><th>ผู้ใช้</th><th>บทบาท</th><th>โรงเรียน</th><th>สพม.</th><th>เขตตรวจ</th><th>สถานะ</th>
        </tr>
      </thead>
      <tbody>
      <?php $i = 1; foreach ($summary as $r): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= e($r['full_name']) ?></td>
          <td><?= e($r['username']) ?></td>
          <td><?= e(role_name((string)$r['role'])) ?></td>
          <td><?= e($r['school_name'] ?? '-') ?></td>
          <td><?= e($r['area_name'] ?? '-') ?></td>
          <td><?= e($r['cluster_name'] ?? '-') ?></td>
          <td><?= ((int)$r['status'] === 1) ? '<span class="badge-pill-ok">ใช้งาน</span>' : '<span class="badge-pill-off">ระงับ</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$summary): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">ไม่พบผู้ใช้ตามเงื่อนไข (อาจยังไม่มีผู้ผูกโรงเรียน)</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div>

<script>
(function () {
  const areaUrl = <?= json_encode(url('/api/org/areas')) ?>;
  const schoolUrl = <?= json_encode(url('/api/org/schools')) ?>;
  const cluster = document.getElementById('flt-cluster');
  const area = document.getElementById('flt-area');
  const school = document.getElementById('flt-school');

  async function loadAreas() {
    const c = cluster?.value || '';
    const res = await fetch(areaUrl + '?cluster=' + encodeURIComponent(c));
    const json = await res.json();
    const cur = area?.value;
    if (!area) return;
    area.innerHTML = '<option value="0">— ทั้งหมด —</option>';
    (json.data || []).forEach((a) => {
      const opt = document.createElement('option');
      opt.value = a.id;
      opt.textContent = a.name;
      if (String(a.id) === String(cur)) opt.selected = true;
      area.appendChild(opt);
    });
    await loadSchools();
  }
  async function loadSchools() {
    const aid = area?.value || '0';
    if (!school) return;
    school.innerHTML = '<option value="0">— ทั้งหมด —</option>';
    if (!aid || aid === '0') return;
    const res = await fetch(schoolUrl + '?area_id=' + encodeURIComponent(aid));
    const json = await res.json();
    const cur = <?= (int)$filters['schoolId'] ?>;
    (json.data || []).forEach((s) => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.name;
      if (Number(s.id) === cur) opt.selected = true;
      school.appendChild(opt);
    });
  }
  cluster?.addEventListener('change', loadAreas);
  area?.addEventListener('change', loadSchools);
})();
</script>
