<?php
$plans = $plans ?? [];
$statOwned = 0;
$statPending = 0;
$statOpen = 0;
foreach ($plans as $p) {
    $st = (string) ($p['status'] ?? '');
    if ($st === 'approved') {
        $statOwned++;
    } elseif ($st === 'pending') {
        $statPending++;
    } else {
        $statOpen++;
    }
}
$statusMeta = static function (string $status): array {
    return match ($status) {
        'approved' => ['เป็นเจ้าของแล้ว', 'success'],
        'pending' => ['รออนุมัติ', 'warning'],
        'rejected' => ['ไม่ผ่าน', 'danger'],
        default => ['ยังไม่ขอ', 'secondary'],
    };
};
?>
<div class="page-head">
  <div>
    <p class="page-kicker">TRAINER</p>
    <h1>สมัครเป็นเจ้าของหลักสูตร</h1>
    <p class="text-muted mb-0">ส่งคำร้องเพื่อเป็นวิทยากรเจ้าของหลักสูตร Admin เป็นผู้อนุมัติ และหนึ่งหลักสูตรมีเจ้าของได้หลายคน</p>
  </div>
</div>

<div class="row g-3 mb-4 metric-stat-row">
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-layer-group"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">หลักสูตรทั้งหมด</span>
        <strong class="metric-stat-value"><?= count($plans) ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--teal">
      <div class="metric-stat-icon"><i class="fa fa-user-check"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">เป็นเจ้าของแล้ว</span>
        <strong class="metric-stat-value"><?= $statOwned ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--amber">
      <div class="metric-stat-icon"><i class="fa fa-hourglass-half"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">รออนุมัติ</span>
        <strong class="metric-stat-value"><?= $statPending ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--slate">
      <div class="metric-stat-icon"><i class="fa fa-inbox"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ยังไม่ขอ</span>
        <strong class="metric-stat-value"><?= $statOpen ?></strong>
      </div>
    </div>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <table id="trainer-apply-datatable" class="table datatable w-100" data-order='[[1,"asc"]]'>
    <thead>
      <tr>
        <th class="col-primary">หลักสูตร</th>
        <th class="col-wrap">หลักสูตร</th>
        <th>สถานะ</th>
        <th class="col-actions"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($plans as $p):
        $status = (string) ($p['status'] ?? '');
        [$label, $tone] = $statusMeta($status);
    ?>
      <tr>
        <td data-order="<?= (int) $p['plan_no'] ?>">
          <span class="enroll-plan-no">หลักสูตรที่ <?= (int) $p['plan_no'] ?></span>
        </td>
        <td class="col-wrap">
          <strong class="d-block"><?= e($p['title']) ?></strong>
          <?php if (isset($p['hours'])): ?>
            <span class="small text-muted"><?= number_format((float) $p['hours'], 1) ?> ชม.</span>
          <?php endif; ?>
        </td>
        <td data-order="<?= e($status !== '' ? $status : 'none') ?>">
          <span class="badge text-bg-<?= e($tone) ?>"><?= e($label) ?></span>
        </td>
        <td class="text-end col-actions">
          <?php if ($status !== 'approved' && $status !== 'pending'): ?>
            <form method="post" action="<?= url('/admin/trainer-apply') ?>" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>">
              <button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-paper-plane"></i> ส่งคำร้อง</button>
            </form>
          <?php else: ?>
            <span class="text-muted small">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
