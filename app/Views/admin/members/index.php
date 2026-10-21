<?php
$role = $role ?? 'admin';
$plans = $plans ?? [];
$stats = $stats ?? ['plans' => 0, 'learners' => 0, 'passed' => 0];
?>
<div class="page-head">
  <div>
    <p class="page-kicker">LEARNERS BY PLAN</p>
    <h1>ผู้เข้ารับการพัฒนา</h1>
    <p class="text-muted mb-0">
      <?php if ($role === 'trainer'): ?>
        รายการผู้เรียนแยกตามแผนที่คุณรับผิดชอบ — ดูจำนวน ความก้าวหน้า และรายละเอียดรายแผน
      <?php else: ?>
        รายการผู้เรียนแยกตามแผนอบรม — ดูจำนวน ความก้าวหน้าเฉลี่ย และกดเข้าดูรายละเอียดแต่ละแผน
      <?php endif; ?>
    </p>
  </div>
  <?php if (can_perm('members', 'create')): ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('/admin/members/club') ?>"><i class="fa fa-users"></i> สมาชิกชมรม (เดิม)</a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4 enroll-stat-row">
  <div class="col-6 col-md-4">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">แผนที่แสดง</span>
      <strong class="enroll-stat-value" id="members-stat-plans"><?= (int) $stats['plans'] ?></strong>
    </div></div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">ผู้เรียนรวม (แถวความก้าวหน้า)</span>
      <strong class="enroll-stat-value" id="members-stat-learners"><?= (int) $stats['learners'] ?></strong>
    </div></div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">ผ่านแผนแล้ว</span>
      <strong class="enroll-stat-value" id="members-stat-passed"><?= (int) $stats['passed'] ?></strong>
    </div></div>
  </div>
</div>

<div class="card-soft">
  <div class="card-body">
    <form id="members-plans-filter" class="row g-2 mb-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label small mb-0">ค้นหาแผน</label>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="เลขแผน / หัวข้อ / กลุ่มหัวข้อ / วิทยากร">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-0">ประเภท</label>
        <select name="track" class="form-select form-select-sm">
          <option value="">ทั้งหมด</option>
          <option value="required">บังคับ</option>
          <option value="elective">ทั่วไป</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-0">มีผู้เรียน</label>
        <select name="has_learners" class="form-select form-select-sm">
          <option value="">ทั้งหมด</option>
          <option value="1">มีผู้เรียน</option>
          <option value="0">ยังไม่มี</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-0">ความก้าวหน้า</label>
        <select name="progress" class="form-select form-select-sm">
          <option value="">ทั้งหมด</option>
          <option value="gt0">&gt; 0%</option>
          <option value="lt50">&lt; 50%</option>
          <option value="gte50">≥ 50%</option>
          <option value="passed">มีผู้ผ่าน</option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa fa-filter"></i> กรอง</button>
        <button type="reset" class="btn btn-sm btn-outline-secondary">ล้าง</button>
      </div>
    </form>

    <?php if ($plans === []): ?>
      <div class="text-center text-muted py-4">
        <?= $role === 'trainer'
          ? 'ยังไม่มีหลักสูตรที่คุณได้รับมอบหมาย'
          : 'ยังไม่มีแผนการเรียนรู้ในระบบ' ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table enroll-plan-table align-middle mb-0" id="members-plans-table">
          <thead>
            <tr>
              <th>แผน</th>
              <th>หัวข้อ</th>
              <th class="text-center">จำนวนผู้เรียน</th>
              <th class="text-center">ผ่าน</th>
              <th class="text-center">กำลังเรียน</th>
              <th class="text-center">เฉลี่ย %</th>
              <th>วิทยากร</th>
              <th class="text-end">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($plans as $p):
              $pid = (int) $p['id'];
              $required = (int) ($p['is_required'] ?? 0) === 1 || (int) $p['plan_no'] <= 12;
              $learners = (int) ($p['learner_count'] ?? 0);
              $passed = (int) ($p['passed_count'] ?? 0);
              $active = (int) ($p['active_count'] ?? 0);
              $avg = $p['avg_percent'] !== null ? (float) $p['avg_percent'] : 0.0;
              $trainers = implode(', ', $p['trainers'] ?? []);
              $hay = mb_strtolower(
                (string) $p['plan_no'] . ' ' . ($p['title'] ?? '') . ' ' . ($p['topic_group'] ?? '') . ' ' . $trainers,
                'UTF-8'
              );
            ?>
            <tr
              data-track="<?= $required ? 'required' : 'elective' ?>"
              data-learners="<?= $learners ?>"
              data-passed="<?= $passed ?>"
              data-avg="<?= $avg ?>"
              data-search="<?= e($hay) ?>"
            >
              <td>
                <span class="enroll-plan-no">แผนที่ <?= (int) $p['plan_no'] ?></span>
                <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
              </td>
              <td>
                <strong class="d-block"><?= e($p['title']) ?></strong>
                <span class="small text-muted"><?= e($p['topic_group'] ?: '') ?> · <?= number_format((float) $p['hours'], 1) ?> ชม.</span>
              </td>
              <td class="text-center"><strong><?= $learners ?></strong></td>
              <td class="text-center"><?= $passed ?></td>
              <td class="text-center"><?= $active ?></td>
              <td class="text-center">
                <div class="enroll-mini-bar" title="<?= number_format($avg, 1) ?>%">
                  <span style="width:<?= min(100, max(0, $avg)) ?>%"></span>
                </div>
                <span class="small"><?= $learners > 0 ? number_format($avg, 1) . '%' : '-' ?></span>
              </td>
              <td class="small"><?= $trainers !== '' ? e($trainers) : '<span class="text-muted">—</span>' ?></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/members/plans/' . $pid) ?>">
                  <i class="fa fa-users"></i> รายละเอียด
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="small text-muted mt-2 mb-0" id="members-plans-empty" hidden>ไม่พบแผนตามเงื่อนไขที่กรอง</p>
    <?php endif; ?>
  </div>
</div>
<script>
(function () {
  var form = document.getElementById('members-plans-filter');
  var table = document.getElementById('members-plans-table');
  if (!form || !table) return;
  var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
  var empty = document.getElementById('members-plans-empty');
  function apply() {
    var q = (form.q.value || '').trim().toLowerCase();
    var track = form.track.value;
    var has = form.has_learners.value;
    var prog = form.progress.value;
    var shown = 0, learners = 0, passed = 0;
    rows.forEach(function (tr) {
      var ok = true;
      if (q && (tr.getAttribute('data-search') || '').indexOf(q) === -1) ok = false;
      if (ok && track && tr.getAttribute('data-track') !== track) ok = false;
      if (ok && has === '1' && Number(tr.getAttribute('data-learners') || 0) <= 0) ok = false;
      if (ok && has === '0' && Number(tr.getAttribute('data-learners') || 0) > 0) ok = false;
      var avg = Number(tr.getAttribute('data-avg') || 0);
      var p = Number(tr.getAttribute('data-passed') || 0);
      if (ok && prog === 'gt0' && avg <= 0) ok = false;
      if (ok && prog === 'lt50' && avg >= 50) ok = false;
      if (ok && prog === 'gte50' && avg < 50) ok = false;
      if (ok && prog === 'passed' && p <= 0) ok = false;
      tr.hidden = !ok;
      if (ok) {
        shown++;
        learners += Number(tr.getAttribute('data-learners') || 0);
        passed += p;
      }
    });
    if (empty) empty.hidden = shown > 0;
    var elP = document.getElementById('members-stat-plans');
    var elL = document.getElementById('members-stat-learners');
    var elX = document.getElementById('members-stat-passed');
    if (elP) elP.textContent = String(shown);
    if (elL) elL.textContent = String(learners);
    if (elX) elX.textContent = String(passed);
  }
  form.addEventListener('submit', function (e) { e.preventDefault(); apply(); });
  form.addEventListener('reset', function () { setTimeout(apply, 0); });
  form.querySelectorAll('select, input').forEach(function (el) {
    el.addEventListener('change', apply);
    el.addEventListener('input', apply);
  });
})();
</script>
