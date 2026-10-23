<?php
$filters = $filters ?? ['cluster' => '', 'areaId' => 0, 'schoolId' => 0, 'role' => ''];
$totals = $totals ?? [];
$ready = !empty($ready);
$byRole = $byRole ?? [];
$byCluster = $byCluster ?? [];
$byAreaTop = $byAreaTop ?? [];
$summary = $summary ?? [];
$maxRole = 0;
foreach ($byRole as $r) {
    $maxRole = max($maxRole, (int) ($r['c'] ?? 0));
}
$roleTone = [
    'admin' => 'rose',
    'trainer' => 'teal',
    'user' => 'blue',
    'teacher' => 'indigo',
];
?>
<div class="page-head">
  <div>
    <p class="page-kicker">REPORTS</p>
    <h1><?= e($title) ?></h1>
    <p class="text-muted mb-0">รายงานผู้ใช้ตามเขตตรวจราชการ · สพม. · โรงเรียน (ข้อมูลจาก Safety Center)</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-primary" href="<?= url('/admin/reports/org-stats/export?' . http_build_query([
      'cluster' => $filters['cluster'], 'area_id' => $filters['areaId'], 'school_id' => $filters['schoolId'], 'role' => $filters['role'],
    ])) ?>"><i class="fa fa-file-excel"></i> ส่งออก Excel</a>
  </div>
</div>

<?php if (!$ready): ?>
<div class="alert alert-warning org-stats-alert">
  <i class="fa fa-triangle-exclamation me-1"></i>
  ยังไม่พบข้อมูลโรงเรียนครบ — รันคำสั่ง
  <code>php scripts/import_safetycenter.php</code>
  เพื่อนำเข้าจาก <code>db/safetycenter-2.sql</code>
</div>
<?php endif; ?>

<div class="row g-3 mb-4 metric-stat-row">
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-users"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ผู้ใช้ทั้งหมด</span>
        <strong class="metric-stat-value"><?= number_format((int) ($totals['users'] ?? 0)) ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--emerald">
      <div class="metric-stat-icon"><i class="fa fa-user-check"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ใช้งานอยู่</span>
        <strong class="metric-stat-value"><?= number_format((int) ($totals['active'] ?? 0)) ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--teal">
      <div class="metric-stat-icon"><i class="fa fa-landmark"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">สพม. / เขต</span>
        <strong class="metric-stat-value"><?= number_format((int) ($totals['areas'] ?? 0)) ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--amber">
      <div class="metric-stat-icon"><i class="fa fa-school"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">โรงเรียน / มี org</span>
        <strong class="metric-stat-value">
          <?= number_format((int) ($totals['schools'] ?? 0)) ?>
          <span class="metric-stat-value--sm text-muted">/ <?= number_format((int) ($totals['with_org'] ?? 0)) ?></span>
        </strong>
      </div>
    </div>
  </div>
</div>

<div class="card-soft org-stats-filter mb-4">
  <div class="card-body">
    <div class="org-stats-filter-head">
      <div>
        <h2 class="h6 mb-0">ตัวกรองรายงาน</h2>
        <p class="small text-muted mb-0">เลือกเขตตรวจ · สพม. · โรงเรียน · บทบาท แล้วกดกรอง</p>
      </div>
      <i class="fa fa-filter text-muted"></i>
    </div>
    <form method="get" class="row g-3 align-items-end" id="org-stats-filter">
      <div class="col-md-3">
        <label class="form-label">เขตตรวจราชการ</label>
        <select name="cluster" class="form-select" id="flt-cluster">
          <option value="">— ทั้งหมด —</option>
          <?php foreach ($clusters as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= ((string) $filters['cluster'] === (string) $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">สพม. / เขตพื้นที่</label>
        <select name="area_id" class="form-select" id="flt-area">
          <option value="0">— ทั้งหมด —</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= (int) $a['id'] ?>" <?= ((int) $filters['areaId'] === (int) $a['id']) ? 'selected' : '' ?>><?= e($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">โรงเรียน</label>
        <select name="school_id" class="form-select" id="flt-school">
          <option value="0">— ทั้งหมด —</option>
          <?php foreach ($schools as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= ((int) $filters['schoolId'] === (int) $s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
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
      <div class="col-md-1">
        <button class="btn btn-primary w-100 org-stats-filter-btn" type="submit"><i class="fa fa-search"></i> กรอง</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card-soft org-stats-panel h-100">
      <div class="card-body">
        <div class="org-stats-panel-head">
          <span class="org-stats-panel-ico org-stats-panel-ico--blue"><i class="fa fa-user-tag"></i></span>
          <div>
            <h2 class="h6 mb-0">แยกตามบทบาท</h2>
            <p class="small text-muted mb-0">ตามตัวกรองปัจจุบัน</p>
          </div>
        </div>
        <ul class="org-stats-role-list">
          <?php foreach ($byRole as $r):
            $role = (string) ($r['role'] ?? '');
            $count = (int) ($r['c'] ?? 0);
            $pct = $maxRole > 0 ? round($count / $maxRole * 100) : 0;
            $tone = $roleTone[$role] ?? 'slate';
          ?>
            <li>
              <div class="org-stats-role-row">
                <span class="org-stats-role-name">
                  <span class="org-stats-dot org-stats-dot--<?= e($tone) ?>"></span>
                  <?= e(role_name($role)) ?>
                </span>
                <strong><?= number_format($count) ?></strong>
              </div>
              <div class="org-stats-bar"><span style="width:<?= $pct ?>%"></span></div>
            </li>
          <?php endforeach; ?>
          <?php if (!$byRole): ?>
            <li class="text-muted text-center py-3">ไม่มีข้อมูล</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-soft org-stats-panel h-100">
      <div class="card-body">
        <div class="org-stats-panel-head">
          <span class="org-stats-panel-ico org-stats-panel-ico--teal"><i class="fa fa-map-location-dot"></i></span>
          <div>
            <h2 class="h6 mb-0">เขตตรวจราชการ</h2>
            <p class="small text-muted mb-0">โรงเรียน / ผู้ใช้</p>
          </div>
        </div>
        <div class="org-stats-scroll">
          <table class="table org-stats-mini-table mb-0">
            <thead>
              <tr><th>เขต</th><th class="text-end">รร.</th><th class="text-end">ผู้ใช้</th></tr>
            </thead>
            <tbody>
            <?php foreach ($byCluster as $r): ?>
              <tr>
                <td><?= e($r['cluster_name'] ?: ('รหัส ' . ($r['cluster_code'] ?? '-'))) ?></td>
                <td class="text-end"><?= number_format((int) $r['schools']) ?></td>
                <td class="text-end fw-semibold"><?= number_format((int) $r['users']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$byCluster): ?>
              <tr><td colspan="3" class="text-center text-muted py-3">ไม่มีข้อมูล</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-soft org-stats-panel h-100">
      <div class="card-body">
        <div class="org-stats-panel-head">
          <span class="org-stats-panel-ico org-stats-panel-ico--amber"><i class="fa fa-ranking-star"></i></span>
          <div>
            <h2 class="h6 mb-0">สพม. ที่มีผู้ใช้มากสุด</h2>
            <p class="small text-muted mb-0">อันดับตามจำนวนผู้ใช้</p>
          </div>
        </div>
        <div class="org-stats-scroll">
          <ol class="org-stats-rank">
            <?php $rank = 1; foreach ($byAreaTop as $r): ?>
              <li>
                <span class="org-stats-rank-n"><?= $rank++ ?></span>
                <span class="org-stats-rank-body">
                  <strong><?= e($r['name']) ?></strong>
                  <?php if (!empty($r['province'])): ?>
                    <span class="small text-muted d-block"><?= e($r['province']) ?></span>
                  <?php endif; ?>
                </span>
                <span class="org-stats-rank-count"><?= number_format((int) $r['users']) ?></span>
              </li>
            <?php endforeach; ?>
            <?php if (!$byAreaTop): ?>
              <li class="text-muted justify-content-center border-0">ไม่มีข้อมูล</li>
            <?php endif; ?>
          </ol>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card-soft org-stats-table-card">
  <div class="card-body">
    <div class="org-stats-table-head mb-3">
      <div>
        <h2 class="h6 mb-0">รายชื่อผู้ใช้ตามตัวกรอง</h2>
        <p class="small text-muted mb-0">สูงสุด 5,000 รายการ · ค้นหาและส่งออกได้จากตาราง</p>
      </div>
      <span class="badge text-bg-light border"><?= number_format(count($summary)) ?> รายการ</span>
    </div>
    <?php if (!$summary): ?>
      <div class="text-center text-muted py-5">ไม่พบผู้ใช้ตามเงื่อนไข (อาจยังไม่มีผู้ผูกโรงเรียน)</div>
    <?php else: ?>
      <table id="org-stats-users" class="table datatable w-100" data-order='[[1,"asc"]]'>
        <thead>
          <tr>
            <th class="col-primary">#</th>
            <th class="col-wrap">ชื่อ-สกุล</th>
            <th>ผู้ใช้</th>
            <th>บทบาท</th>
            <th class="col-wrap">โรงเรียน</th>
            <th class="col-wrap">สพม.</th>
            <th>เขตตรวจ</th>
            <th>สถานะ</th>
          </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ($summary as $r): ?>
          <tr>
            <td data-order="<?= $i ?>"><?= $i++ ?></td>
            <td class="col-wrap"><strong><?= e($r['full_name']) ?></strong></td>
            <td><code class="org-stats-user"><?= e($r['username']) ?></code></td>
            <td>
              <?php
              $role = (string) ($r['role'] ?? '');
              $tone = $roleTone[$role] ?? 'slate';
              ?>
              <span class="org-stats-role-badge org-stats-role-badge--<?= e($tone) ?>"><?= e(role_name($role)) ?></span>
            </td>
            <td class="col-wrap"><?= e($r['school_name'] ?? '-') ?></td>
            <td class="col-wrap"><?= e($r['area_name'] ?? '-') ?></td>
            <td><?= e($r['cluster_name'] ?? '-') ?></td>
            <td data-order="<?= (int) $r['status'] ?>">
              <?= ((int) $r['status'] === 1)
                ? '<span class="badge-pill-ok">ใช้งาน</span>'
                : '<span class="badge-pill-off">ระงับ</span>' ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

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
    const cur = <?= (int) $filters['schoolId'] ?>;
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
