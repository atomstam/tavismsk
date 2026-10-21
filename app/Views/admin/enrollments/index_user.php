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
?>
<div class="page-head">
  <div>
    <p class="page-kicker">MY ENROLLMENT</p>
    <h1>ลงทะเบียนอบรม</h1>
    <p class="text-muted mb-0">สถานะการลงทะเบียนของฉัน และแผนที่กำลังเรียน — กดดูรายละเอียดหรือเข้าเรียนได้</p>
  </div>
  <?php if (can_perm('enrollments', 'create') && !$myEnrollment): ?>
  <form method="post" action="<?= url('/admin/enrollments') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= (int) auth()->id() ?>">
    <button class="btn-add" type="submit"><i class="fa fa-plus"></i> ลงทะเบียนอบรม</button>
  </form>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4 enroll-stat-row">
  <div class="col-6 col-md-3">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">แผนของฉัน</span>
      <strong class="enroll-stat-value"><?= (int) $stats['plans'] ?></strong>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">สถานะลงทะเบียน</span>
      <strong class="enroll-stat-value enroll-stat-value--sm">
        <?= $myEnrollment ? e((string) $myEnrollment['status']) : 'ยังไม่ลงทะเบียน' ?>
      </strong>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">ชั่วโมงสะสม</span>
      <strong class="enroll-stat-value enroll-stat-value--sm">
        <?= $myEnrollment
          ? number_format((float) $myEnrollment['total_hours'], 1) . ' / ' . number_format((float) $myEnrollment['required_hours'], 1)
          : '-' ?>
      </strong>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">คะแนนรวม</span>
      <strong class="enroll-stat-value">
        <?= $myEnrollment && $myEnrollment['final_score'] !== null
          ? number_format((float) $myEnrollment['final_score'], 1)
          : '-' ?>
      </strong>
    </div></div>
  </div>
</div>

<?php if ($myEnrollment): ?>
<div class="card-soft mb-4"><div class="card-body d-flex flex-wrap justify-content-between gap-2 align-items-center">
  <div>
    <strong>เลขที่ลงทะเบียน</strong>
    <code class="ms-1"><?= e($myEnrollment['enrollment_no']) ?></code>
    <span class="badge text-bg-primary ms-2"><?= e($myEnrollment['status']) ?></span>
    <?php if ((int) ($myEnrollment['is_passed'] ?? 0) === 1): ?>
      <span class="badge text-bg-success">ผ่านหลักสูตร</span>
    <?php endif; ?>
  </div>
  <a class="btn btn-learn-primary btn-sm" href="<?= url('/admin/learn') ?>"><i class="fa fa-graduation-cap"></i> ไปบทเรียนของฉัน</a>
</div></div>
<?php endif; ?>

<div class="card-soft">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <h2 class="h6 mb-0">แผนที่ลงทะเบียน / เรียนอยู่</h2>
      <span class="small text-muted"><?= count($plans) ?> แผน</span>
    </div>
    <?php if ($plans === []): ?>
      <div class="text-center text-muted py-4">
        ยังไม่มีแผนในบัญชีของคุณ — กดลงทะเบียนอบรมด้านบน หรือเริ่มเรียนจากเมนูบทเรียนของฉัน
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table enroll-plan-table align-middle mb-0">
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
            ?>
            <tr>
              <td>
                <span class="enroll-plan-no">แผนที่ <?= (int) $p['plan_no'] ?></span>
                <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
              </td>
              <td>
                <strong class="d-block"><?= e($p['title']) ?></strong>
                <span class="small text-muted"><?= e($p['topic_group'] ?: '') ?> · <?= number_format((float) $p['hours'], 1) ?> ชม.</span>
              </td>
              <td><?= e($stageLabel[(string) ($mine['stage'] ?? 'pretest')] ?? 'ทดสอบก่อน') ?></td>
              <td class="text-center">
                <div class="enroll-mini-bar" title="<?= number_format((float) ($mine['overall_percent'] ?? 0), 1) ?>%">
                  <span style="width:<?= min(100, max(0, (float) ($mine['overall_percent'] ?? 0))) ?>%"></span>
                </div>
                <span class="small"><?= number_format((float) ($mine['overall_percent'] ?? 0), 1) ?>%</span>
              </td>
              <td class="text-center">
                <?php if ((int) ($mine['is_passed'] ?? 0) === 1): ?>
                  <span class="badge text-bg-success">ผ่าน</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary">ยังไม่ผ่าน</span>
                <?php endif; ?>
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
