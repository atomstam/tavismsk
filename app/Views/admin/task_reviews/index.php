<?php
$rows = $rows ?? [];
$totalPending = (int) ($totalPending ?? 0);
$totalGraded = 0;
$totalPlans = count($rows);
foreach ($rows as $row) {
    $totalGraded += (int) ($row['graded'] ?? 0);
}
?>
<div class="page-head">
  <div>
    <p class="page-kicker">TRAINER</p>
    <h1>ตรวจภาระงาน</h1>
    <p class="text-muted mb-0">ตรวจให้คะแนนภาระงานของผู้เรียนในแผนที่คุณรับผิดชอบ · ค่าเริ่มต้น = คะแนนเต็มของแต่ละภาระงาน</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('/admin/training') ?>"><i class="fa fa-chalkboard-user"></i> จัดการบทเรียน LMS</a>
  </div>
</div>

<div class="row g-3 mb-4 metric-stat-row">
  <div class="col-6 col-lg-4">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-layer-group"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">แผนที่ดูแล</span>
        <strong class="metric-stat-value"><?= $totalPlans ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4">
    <div class="metric-stat metric-stat--amber">
      <div class="metric-stat-icon"><i class="fa fa-hourglass-half"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">รอตรวจ</span>
        <strong class="metric-stat-value"><?= $totalPending ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4">
    <div class="metric-stat metric-stat--emerald">
      <div class="metric-stat-icon"><i class="fa fa-clipboard-check"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ตรวจแล้ว</span>
        <strong class="metric-stat-value"><?= $totalGraded ?></strong>
      </div>
    </div>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <?php if (!$rows): ?>
    <div class="text-center text-muted py-4">
      ยังไม่มีแผนที่รับผิดชอบ
      <?php if (auth()->role() === 'trainer'): ?>
        — <a href="<?= url('/admin/trainer-apply') ?>">สมัครเป็นวิทยากรของแผน</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table id="task-reviews-datatable" class="table datatable w-100" data-order='[[4,"desc"]]'>
      <thead>
        <tr>
          <th class="col-primary">แผนที่</th>
          <th class="col-wrap">ชื่อแผน</th>
          <th class="text-center">ภาระงาน</th>
          <th class="text-center">รอตรวจ</th>
          <th class="text-center">ตรวจแล้ว</th>
          <th class="col-actions text-end">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row):
          $plan = $row['plan'];
          $pid = (int) $plan['id'];
          $pending = (int) $row['pending'];
          $isReq = (int) ($plan['is_required'] ?? 0) === 1
            || ((int) ($plan['plan_no'] ?? 0) > 0 && (int) ($plan['plan_no'] ?? 0) <= 12);
        ?>
          <tr class="<?= $pending > 0 ? 'table-warning' : '' ?>">
            <td data-order="<?= (int) ($plan['plan_no'] ?? 0) ?>" class="fw-semibold"><?= (int) ($plan['plan_no'] ?? 0) ?></td>
            <td class="col-wrap">
              <div class="fw-semibold"><?= e((string) ($plan['title'] ?? '')) ?></div>
              <div class="small text-muted">
                <span class="badge <?= $isReq ? 'text-bg-danger' : 'text-bg-primary' ?>"><?= $isReq ? 'บังคับ' : 'ทั่วไป' ?></span>
                · <?= number_format((float) ($plan['hours'] ?? 0), 1) ?> ชม.
                <?php if (!empty($plan['topic_group'])): ?>
                  · <?= e((string) $plan['topic_group']) ?>
                <?php endif; ?>
              </div>
            </td>
            <td class="text-center" data-order="<?= (int) $row['task_count'] ?>"><?= (int) $row['task_count'] ?></td>
            <td class="text-center" data-order="<?= $pending ?>">
              <?php if ($pending > 0): ?>
                <span class="badge text-bg-warning"><?= $pending ?></span>
              <?php else: ?>
                <span class="text-muted">0</span>
              <?php endif; ?>
            </td>
            <td class="text-center" data-order="<?= (int) $row['graded'] ?>"><?= (int) $row['graded'] ?></td>
            <td class="text-end col-actions">
              <a class="btn btn-sm <?= $pending > 0 ? 'btn-warning' : 'btn-outline-primary' ?>"
                 href="<?= url('/admin/training/' . $pid . '?tab=submissions') ?>">
                <i class="fa fa-clipboard-check"></i>
                <?= $pending > 0 ? 'ตรวจงาน (' . $pending . ')' : 'เปิดตรวจงาน' ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div></div>
