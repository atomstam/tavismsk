<?php
$plan = $plan ?? [];
$orgs = $orgs ?? [];
$summary = $summary ?? null;
$pid = (int) ($plan['id'] ?? 0);
$required = (int) ($plan['is_required'] ?? 0) === 1 || (int) ($plan['plan_no'] ?? 0) <= 12;
$learners = (int) ($summary['learner_count'] ?? 0);
$passed = (int) ($summary['passed_count'] ?? 0);
$avg = $summary['avg_percent'] ?? null;
?>
<div class="page-head">
  <div>
    <p class="page-kicker">PLAN LEARNERS</p>
    <h1>ผู้เรียน · หลักสูตรที่ <?= (int) ($plan['plan_no'] ?? 0) ?></h1>
    <p class="text-muted mb-0">
      <?= e($plan['title'] ?? '') ?>
      · <?= number_format((float) ($plan['hours'] ?? 0), 1) ?> ชม.
      · <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
    </p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php if (can_perm('assessments', 'create')): ?>
      <a class="btn btn-learn-outline" href="<?= url('/admin/training/' . $pid) ?>"><i class="fa fa-chalkboard-user"></i> จัดการบทเรียน</a>
    <?php endif; ?>
    <?php if (can_perm('assessments', 'edit')): ?>
      <a class="btn btn-learn-outline" href="<?= url('/admin/training/' . $pid . '?tab=submissions') ?>"><i class="fa fa-clipboard-check"></i> ตรวจภาระงาน</a>
    <?php endif; ?>
    <?php if (can_perm('plan_chat', 'view')): ?>
      <a class="btn btn-learn-outline" href="<?= url('/admin/plan-chat/plan/' . $pid) ?>"><i class="fa fa-comments"></i> กระดานสนทนา</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= url('/admin/members') ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
  </div>
</div>

<div class="row g-3 mb-4 metric-stat-row">
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-user-graduate"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ผู้เรียนในหลักสูตร</span>
        <strong class="metric-stat-value"><?= $learners ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--emerald">
      <div class="metric-stat-icon"><i class="fa fa-award"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ผ่านหลักสูตรแล้ว</span>
        <strong class="metric-stat-value"><?= $passed ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--teal">
      <div class="metric-stat-icon"><i class="fa fa-chart-line"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">เฉลี่ยความก้าวหน้า</span>
        <strong class="metric-stat-value"><?= $learners > 0 && $avg !== null ? number_format((float) $avg, 1) . '%' : '-' ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--slate">
      <div class="metric-stat-icon"><i class="fa fa-tags"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">กลุ่มหัวข้อ</span>
        <strong class="metric-stat-value metric-stat-value--sm"><?= e($plan['topic_group'] ?: '-') ?></strong>
      </div>
    </div>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <h2 class="h6 mb-3">ตารางผู้เรียนเฉพาะหลักสูตรนี้</h2>
  <form id="plan-learners-filter" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label small mb-0">ค้นหา</label>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="ชื่อ / เลขที่ / username">
    </div>
    <div class="col-md-2">
      <label class="form-label small mb-0">ขั้นปัจจุบัน</label>
      <select name="stage" class="form-select form-select-sm">
        <option value="">ทั้งหมด</option>
        <option value="pretest">ทดสอบก่อน</option>
        <option value="content">เนื้อหา</option>
        <option value="task">ภาระงาน</option>
        <option value="posttest">สอบหลัง</option>
        <option value="satisfaction">ความพึงพอใจ</option>
        <option value="completed">ครบแล้ว</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small mb-0">ผลหลักสูตร</label>
      <select name="passed" class="form-select form-select-sm">
        <option value="">ทั้งหมด</option>
        <option value="1">ผ่าน</option>
        <option value="0">ยังไม่ผ่าน</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-0">หน่วยงาน</label>
      <select name="org_id" class="form-select form-select-sm">
        <option value="">ทั้งหมด</option>
        <?php foreach ($orgs as $o): ?>
          <option value="<?= (int) $o['id'] ?>"><?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa fa-filter"></i> กรอง</button>
    </div>
  </form>
  <table
    id="plan-learners-datatable"
    class="table ab-datatable w-100"
    data-ajax-url="<?= e(url('/admin/members/plans/' . $pid . '/datatable')) ?>"
  >
    <thead>
      <tr>
        <th>เลขที่</th>
        <th>ชื่อผู้เรียน</th>
        <th>หน่วยงาน</th>
        <th>ขั้น</th>
        <th>รวม %</th>
        <th>ก่อน</th>
        <th>เนื้อหา</th>
        <th>งาน</th>
        <th>หลัง</th>
        <th>ผล</th>
      </tr>
    </thead>
  </table>
</div></div>
