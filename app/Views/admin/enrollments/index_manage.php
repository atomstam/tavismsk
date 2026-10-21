<?php
$role = $role ?? 'admin';
$plans = $plans ?? [];
$stats = $stats ?? ['plans' => 0, 'learners' => 0, 'passed' => 0, 'pending_enrollments' => 0];
?>
<div class="page-head">
  <div>
    <p class="page-kicker">PLAN MANAGEMENT</p>
    <h1>บริหารแผนอบรม</h1>
    <p class="text-muted mb-0">
      <?php if ($role === 'trainer'): ?>
        บริหารเฉพาะหลักสูตรที่คุณรับผิดชอบ — ดูสารสนเทศและรายละเอียดผู้เรียนรายแผน
      <?php else: ?>
        บริหารทุกแผนอบรม — สารสนเทศผู้เรียนรายแผน และอนุมัติการลงทะเบียนหลักสูตร
      <?php endif; ?>
    </p>
  </div>
  <?php if ($role === 'admin' && can_perm('enrollments', 'create')): ?>
  <form method="post" action="<?= url('/admin/enrollments') ?>" class="d-flex gap-2 align-items-end">
    <?= csrf_field() ?>
    <div>
      <label class="form-label small mb-0">ลงทะเบียน (User ID)</label>
      <input type="number" name="user_id" class="form-control form-control-sm" value="<?= (int) auth()->id() ?>" min="1" required>
    </div>
    <button class="btn-add" type="submit"><i class="fa fa-plus"></i> ลงทะเบียน</button>
  </form>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4 enroll-stat-row">
  <div class="col-6 col-md-3">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">แผนที่ดูแล</span>
      <strong class="enroll-stat-value"><?= (int) $stats['plans'] ?></strong>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">ผู้เรียนในแผน</span>
      <strong class="enroll-stat-value"><?= (int) $stats['learners'] ?></strong>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">ผ่านแผนแล้ว</span>
      <strong class="enroll-stat-value"><?= (int) $stats['passed'] ?></strong>
    </div></div>
  </div>
  <?php if ($role === 'admin'): ?>
  <div class="col-6 col-md-3">
    <div class="card-soft enroll-stat"><div class="card-body">
      <span class="enroll-stat-label">รออนุมัติลงทะเบียน</span>
      <strong class="enroll-stat-value"><?= (int) $stats['pending_enrollments'] ?></strong>
    </div></div>
  </div>
  <?php endif; ?>
</div>

<div class="card-soft mb-4">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <h2 class="h6 mb-0">สารสนเทศรายแผน</h2>
      <span class="small text-muted"><?= count($plans) ?> แผน</span>
    </div>
    <?php if ($plans === []): ?>
      <div class="text-center text-muted py-4">
        <?php if ($role === 'trainer'): ?>
          ยังไม่มีหลักสูตรที่คุณได้รับมอบหมาย — ติดต่อแอดมินหรือสมัครเป็นเจ้าของหลักสูตร
        <?php else: ?>
          ยังไม่มีแผนการเรียนรู้ในระบบ
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table enroll-plan-table align-middle mb-0">
          <thead>
            <tr>
              <th>แผน</th>
              <th>หัวข้อ</th>
              <th class="text-center">ผู้เรียน</th>
              <th class="text-center">ผ่าน</th>
              <th class="text-center">เฉลี่ย %</th>
              <th>วิทยากร</th>
              <th class="text-end">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($plans as $p):
              $pid = (int) $p['id'];
              $required = (int) ($p['is_required'] ?? 0) === 1 || (int) $p['plan_no'] <= 12;
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
              <td class="text-center"><?= (int) ($p['learner_count'] ?? 0) ?></td>
              <td class="text-center"><?= (int) ($p['passed_count'] ?? 0) ?></td>
              <td class="text-center"><?= $p['avg_percent'] !== null ? number_format((float) $p['avg_percent'], 1) : '-' ?></td>
              <td class="small">
                <?php
                $names = $p['trainers'] ?? [];
                echo $names === [] ? '<span class="text-muted">—</span>' : e(implode(', ', $names));
                ?>
              </td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/plan-enrollments/plans/' . $pid) ?>">
                  <i class="fa fa-table"></i> รายละเอียด
                </a>
                <?php if (can_perm('assessments', 'create')): ?>
                  <a class="btn btn-sm btn-learn-outline" href="<?= url('/admin/training/' . $pid) ?>">
                    <i class="fa fa-chalkboard-user"></i> บทเรียน
                  </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($role === 'admin'): ?>
<div class="card-soft"><div class="card-body">
  <h2 class="h6 mb-3">รายการลงทะเบียนหลักสูตร (อนุมัติ / กรองสถานะ)</h2>
  <form id="enrollments-filter" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label small mb-0">สถานะ</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">ทั้งหมด</option>
        <option value="pending">pending</option>
        <option value="approved">approved</option>
        <option value="in_progress">in_progress</option>
        <option value="completed">completed</option>
        <option value="failed">failed</option>
        <option value="cancelled">cancelled</option>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa fa-filter"></i> กรอง</button>
    </div>
  </form>
  <table id="enrollments-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/enrollments/datatable')) ?>">
    <thead>
      <tr>
        <th>เลขที่</th>
        <th>ผู้เข้ารับการพัฒนา</th>
        <th>หน่วยงาน</th>
        <th>ชั่วโมง</th>
        <th>คะแนน</th>
        <th>สถานะ</th>
        <th></th>
      </tr>
    </thead>
  </table>
</div></div>
<?php endif; ?>
