<?php
$role = $role ?? 'user';
$plan = $plan ?? [];
$learners = $learners ?? [];
$mine = $mine ?? null;
$pid = (int) ($plan['id'] ?? 0);
$isManager = $role === 'admin' || $role === 'trainer';
$stageLabel = [
  'pretest' => 'ทดสอบก่อน',
  'content' => 'เนื้อหา',
  'task' => 'ภาระงาน',
  'posttest' => 'สอบหลัง',
  'satisfaction' => 'ความพึงพอใจ',
  'completed' => 'ครบแล้ว',
];
$required = (int) ($plan['is_required'] ?? 0) === 1 || (int) ($plan['plan_no'] ?? 0) <= 12;
$progress = is_array($mine) ? ($mine['progress'] ?? null) : null;
$enrollment = is_array($mine) ? ($mine['enrollment'] ?? null) : null;

$passed = 0;
$avg = 0.0;
if ($learners !== []) {
    $sum = 0.0;
    foreach ($learners as $L) {
        if ((int) ($L['plan_passed'] ?? 0) === 1) {
            $passed++;
        }
        $sum += (float) ($L['overall_percent'] ?? 0);
    }
    $avg = round($sum / count($learners), 1);
}
?>
<div class="page-head">
  <div>
    <p class="page-kicker">PLAN ENROLLMENT</p>
    <h1>หลักสูตรที่ <?= (int) ($plan['plan_no'] ?? 0) ?></h1>
    <p class="text-muted mb-0">
      <?= e($plan['title'] ?? '') ?>
      · <?= number_format((float) ($plan['hours'] ?? 0), 1) ?> ชม.
      · <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
    </p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php if ($isManager && can_perm('assessments', 'create')): ?>
      <a class="btn btn-learn-primary" href="<?= url('/admin/training/' . $pid) ?>"><i class="fa fa-chalkboard-user"></i> จัดการบทเรียน</a>
    <?php endif; ?>
    <?php if ($role === 'user'): ?>
      <a class="btn btn-learn-primary" href="<?= url('/admin/learn/' . $pid) ?>"><i class="fa fa-play"></i> เข้าเรียน</a>
    <?php endif; ?>
    <?php if (can_perm('plan_chat', 'view')): ?>
      <a class="btn btn-learn-outline" href="<?= url('/admin/plan-chat/plan/' . $pid) ?>"><i class="fa fa-comments"></i> กระดานสนทนา</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= url($backUrl ?? (($role === 'admin' || $role === 'trainer') ? '/admin/plan-enrollments' : '/admin/enrollments')) ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
  </div>
</div>

<?php if ($isManager): ?>
<div class="row g-3 mb-4 metric-stat-row">
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-user-graduate"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ผู้เรียนในหลักสูตร</span>
        <strong class="metric-stat-value"><?= count($learners) ?></strong>
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
        <strong class="metric-stat-value"><?= $learners === [] ? '-' : $avg . '%' ?></strong>
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

<div class="card-soft">
  <div class="card-body">
    <h2 class="h6 mb-3">ตารางผู้เรียน · ความก้าวหน้ารายหลักสูตร</h2>
    <?php if ($learners === []): ?>
      <p class="text-muted mb-0 text-center py-4">ยังไม่มีผู้เรียนที่มีความก้าวหน้าในหลักสูตรนี้</p>
    <?php else: ?>
      <table class="table datatable w-100" id="enroll-plan-learners" data-order='[[1,"asc"]]'>
        <thead>
          <tr>
            <th class="col-primary">เลขที่</th>
            <th class="col-wrap">ผู้เข้ารับการพัฒนา</th>
            <th class="col-wrap">หน่วยงาน</th>
            <th>ขั้น</th>
            <th class="text-center">ก่อน</th>
            <th class="text-center">เนื้อหา</th>
            <th class="text-center">งาน</th>
            <th class="text-center">หลัง</th>
            <th class="text-center">รวม</th>
            <th class="text-center">ผล</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($learners as $L): ?>
          <tr>
            <td><code><?= e($L['enrollment_no']) ?></code></td>
            <td class="col-wrap">
              <strong><?= e(trim(($L['title'] ?? '') . ($L['first_name'] ?? '') . ' ' . ($L['last_name'] ?? ''))) ?></strong>
              <span class="small text-muted d-block">@<?= e($L['username'] ?? '') ?></span>
            </td>
            <td class="col-wrap"><?= e($L['org_name'] ?: '-') ?></td>
            <td><?= e($stageLabel[(string) ($L['stage'] ?? '')] ?? (string) ($L['stage'] ?? '-')) ?></td>
            <td class="text-center" data-order="<?= (float) ($L['pretest_percent'] ?? 0) ?>"><?= number_format((float) ($L['pretest_percent'] ?? 0), 0) ?></td>
            <td class="text-center" data-order="<?= (float) ($L['content_percent'] ?? 0) ?>"><?= number_format((float) ($L['content_percent'] ?? 0), 0) ?></td>
            <td class="text-center" data-order="<?= (float) ($L['task_percent'] ?? 0) ?>"><?= number_format((float) ($L['task_percent'] ?? 0), 0) ?></td>
            <td class="text-center" data-order="<?= (float) ($L['posttest_percent'] ?? 0) ?>"><?= number_format((float) ($L['posttest_percent'] ?? 0), 0) ?></td>
            <td class="text-center" data-order="<?= (float) ($L['overall_percent'] ?? 0) ?>">
              <strong><?= number_format((float) ($L['overall_percent'] ?? 0), 1) ?>%</strong>
            </td>
            <td class="text-center" data-order="<?= (int) ($L['plan_passed'] ?? 0) ?>">
              <?php if ((int) ($L['plan_passed'] ?? 0) === 1): ?>
                <span class="badge text-bg-success">ผ่าน</span>
              <?php else: ?>
                <span class="badge text-bg-secondary">ยังไม่ผ่าน</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card-soft h-100"><div class="card-body">
      <h2 class="h6 mb-3">การลงทะเบียนของฉัน</h2>
      <?php if (!$enrollment): ?>
        <p class="text-muted mb-0">ยังไม่พบการลงทะเบียนหลักสูตร — กลับไปหน้าหลักเพื่อลงทะเบียน</p>
      <?php else: ?>
        <dl class="enroll-dl mb-0">
          <div><dt>เลขที่</dt><dd><code><?= e($enrollment['enrollment_no']) ?></code></dd></div>
          <div><dt>สถานะ</dt><dd><span class="badge text-bg-primary"><?= e($enrollment['status']) ?></span></dd></div>
          <div><dt>ชั่วโมง</dt><dd><?= number_format((float) $enrollment['total_hours'], 1) ?> / <?= number_format((float) $enrollment['required_hours'], 1) ?></dd></div>
          <div><dt>คะแนนรวม</dt><dd><?= $enrollment['final_score'] !== null ? number_format((float) $enrollment['final_score'], 1) : '-' ?></dd></div>
        </dl>
      <?php endif; ?>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="card-soft h-100"><div class="card-body">
      <h2 class="h6 mb-3">ความก้าวหน้าหลักสูตรนี้</h2>
      <?php if (!$progress): ?>
        <p class="text-muted">ยังไม่เริ่มเรียนหลักสูตรนี้</p>
        <a class="btn btn-learn-primary" href="<?= url('/admin/learn/' . $pid) ?>"><i class="fa fa-play"></i> เริ่มเรียน</a>
      <?php else: ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between small mb-1">
            <span>ความก้าวหน้า (ครบองค์ประกอบ)</span>
            <strong><?= number_format((float) $progress['overall_percent'], 1) ?>%</strong>
          </div>
          <div class="enroll-mini-bar enroll-mini-bar--lg">
            <span style="width:<?= min(100, max(0, (float) $progress['overall_percent'])) ?>%"></span>
          </div>
          <?php if (isset($progress['score_percent'])): ?>
            <div class="small text-muted mt-1">คะแนนรวม <?= number_format((float) $progress['score_percent'], 1) ?>% (ใช้ตัดสินผ่าน / เกียรติบัตร)</div>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-sm enroll-plan-table mb-3">
            <tbody>
              <?php
              $parts = [
                'pretest_percent' => 'ทดสอบก่อน',
                'content_percent' => 'เนื้อหา',
                'task_percent' => 'ภาระงาน',
                'posttest_percent' => 'สอบหลัง',
                'satisfaction_percent' => 'ความพึงพอใจ',
              ];
              foreach ($parts as $key => $label):
              ?>
              <tr>
                <td><?= e($label) ?> <span class="text-muted small">(คะแนน)</span></td>
                <td class="text-end"><?= number_format((float) ($progress[$key] ?? 0), 1) ?>%</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="mb-2">
          ขั้นปัจจุบัน:
          <strong><?= e($stageLabel[(string) ($progress['stage'] ?? '')] ?? '-') ?></strong>
          <?php if ((int) ($progress['is_passed'] ?? 0) === 1): ?>
            <span class="badge text-bg-success ms-1">ผ่านหลักสูตรนี้แล้ว</span>
          <?php endif; ?>
        </p>
        <a class="btn btn-learn-primary" href="<?= url('/admin/learn/' . $pid) ?>"><i class="fa fa-play"></i> ดำเนินการเรียนต่อ</a>
      <?php endif; ?>
    </div></div>
  </div>
</div>
<?php endif; ?>
