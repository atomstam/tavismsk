<?php
$plans = $plans ?? [];
$stats = $stats ?? ['plans' => 0, 'learners' => 0, 'passed' => 0, 'pending_enrollments' => 0];
$myEnrollment = $myEnrollment ?? null;
$stageLabel = [
  'pretest' => 'ทดสอบก่อน',
  'content' => 'เนื้อหา',
  'task' => 'ภาระงาน',
  'posttest' => 'สอบหลัง',
  'satisfaction' => 'ความพึงพอใจ',
  'completed' => 'ครบแล้ว',
];
$statusMap = [
  'pending' => 'รออนุมัติ',
  'approved' => 'อนุมัติแล้ว',
  'in_progress' => 'กำลังเรียน',
  'completed' => 'จบหลักสูตร',
  'rejected' => 'ไม่ผ่านการอนุมัติ',
];
$statusKey = $myEnrollment ? (string) ($myEnrollment['status'] ?? '') : '';
$statusLabel = $statusMap[$statusKey] ?? ($statusKey !== '' ? $statusKey : 'ยังไม่ลงทะเบียน');
?>
<div class="page-head enroll-user-head">
  <div>
    <p class="page-kicker">MY ENROLLMENT</p>
    <h1>ลงทะเบียน/เข้าเรียน</h1>
    <p class="text-muted mb-0">ติดตามสถานะลงทะเบียน ความก้าวหน้า และคะแนนของแต่ละแผน — เข้าเรียนต่อได้ทันที</p>
  </div>
  <?php if (can_perm('enrollments', 'create') && !$myEnrollment): ?>
  <form method="post" action="<?= url('/admin/enrollments') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= (int) auth()->id() ?>">
    <button class="btn-add" type="submit"><i class="fa fa-plus"></i> ลงทะเบียนเรียน</button>
  </form>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4 metric-stat-row enroll-user-stats">
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-layer-group"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">แผนของฉัน</span>
        <strong class="metric-stat-value"><?= (int) $stats['plans'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--teal">
      <div class="metric-stat-icon"><i class="fa fa-id-card"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">สถานะลงทะเบียน</span>
        <strong class="metric-stat-value metric-stat-value--sm"><?= e($statusLabel) ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--amber">
      <div class="metric-stat-icon"><i class="fa fa-clock"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ชั่วโมงสะสม</span>
        <strong class="metric-stat-value metric-stat-value--sm">
          <?= $myEnrollment
            ? number_format((float) $myEnrollment['total_hours'], 1) . ' / ' . number_format((float) $myEnrollment['required_hours'], 1)
            : '-' ?>
        </strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--emerald">
      <div class="metric-stat-icon"><i class="fa fa-chart-line"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">คะแนนรวม</span>
        <strong class="metric-stat-value">
          <?= $myEnrollment && $myEnrollment['final_score'] !== null
            ? number_format((float) $myEnrollment['final_score'], 1)
            : '-' ?>
        </strong>
      </div>
    </div>
  </div>
</div>

<div class="card-soft enroll-plans-card">
  <div class="card-body">
    <div class="enroll-plans-head">
      <div>
        <h2 class="h6 mb-0">แผนที่ลงทะเบียน / เรียนอยู่</h2>
        <p class="small text-muted mb-0 mt-1">ความก้าวหน้า = ครบองค์ประกอบ · คะแนนใช้ตัดสินผ่านและเกียรติบัตร</p>
      </div>
      <span class="enroll-plans-count"><?= count($plans) ?> แผน</span>
    </div>
    <?php if ($plans === []): ?>
      <div class="enroll-empty">
        <i class="fa fa-book-open"></i>
        <p class="mb-0">ยังไม่มีแผนในบัญชีของคุณ — กดลงทะเบียนด้านบน หรือเริ่มเรียนจากเมนูบทเรียนของฉัน</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table enroll-plan-table enroll-user-table align-middle mb-0">
          <thead>
            <tr>
              <th>แผน</th>
              <th>หัวข้อ</th>
              <th>ขั้นปัจจุบัน</th>
              <th class="text-center">ความก้าวหน้า</th>
              <th class="text-center">ผล</th>
              <th class="text-end">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($plans as $p):
              $pid = (int) $p['id'];
              $required = (int) ($p['is_required'] ?? 0) === 1 || (int) $p['plan_no'] <= 12;
              $mine = $p['my_progress'] ?? null;
              $pct = (float) ($mine['overall_percent'] ?? 0);
              $score = isset($mine['score_percent']) ? (float) $mine['score_percent'] : null;
              $passed = (int) ($mine['is_passed'] ?? 0) === 1;
              $started = $mine !== null && ($pct > 0 || (string) ($mine['stage'] ?? '') !== 'pretest' || $passed);
            ?>
            <tr class="<?= $passed ? 'is-passed' : ($started ? 'is-active-row' : '') ?>">
              <td>
                <span class="enroll-plan-no">แผนที่ <?= (int) $p['plan_no'] ?></span>
                <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
              </td>
              <td>
                <strong class="d-block enroll-plan-title"><?= e($p['title']) ?></strong>
                <span class="small text-muted"><?= e($p['topic_group'] ?: '') ?> · <?= number_format((float) $p['hours'], 1) ?> ชม.</span>
              </td>
              <td>
                <span class="enroll-stage-chip"><?= e($stageLabel[(string) ($mine['stage'] ?? 'pretest')] ?? 'ทดสอบก่อน') ?></span>
              </td>
              <td class="text-center">
                <div class="enroll-mini-bar <?= $passed ? 'is-done' : '' ?>" title="<?= number_format($pct, 1) ?>%">
                  <span style="width:<?= min(100, max(0, $pct)) ?>%"></span>
                </div>
                <span class="enroll-pct-label"><?= number_format($pct, 1) ?>%</span>
              </td>
              <td class="text-center">
                <div class="enroll-result">
                  <?php if ($passed): ?>
                    <span class="badge text-bg-success">ผ่าน</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary">ยังไม่ผ่าน</span>
                  <?php endif; ?>
                  <span class="enroll-result-score<?= $passed ? ' is-pass' : '' ?>">
                    <?= ($score !== null && ($started || $passed)) ? number_format($score, 1) . '%' : '-' ?>
                  </span>
                </div>
              </td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/enrollments/plans/' . $pid) ?>">
                  <i class="fa fa-table"></i> รายละเอียด
                </a>
                <a class="btn btn-sm btn-learn-primary" href="<?= url('/admin/learn/' . $pid) ?>">
                  <i class="fa fa-play"></i> เรียน
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
