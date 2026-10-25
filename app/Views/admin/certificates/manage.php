<?php
$rows = $rows ?? [];
$stats = $stats ?? ['plans' => 0, 'learners' => 0, 'cert_ready' => 0, 'cert_issued' => 0];
?>
<div class="page-head">
  <div>
    <p class="page-kicker">CERTIFICATES</p>
    <h1>เกียรติบัตรออนไลน์</h1>
    <p class="text-muted mb-0">บริหารเกียรติบัตรของผู้เรียนในหลักสูตรที่รับผิดชอบ — กดเข้าหลักสูตรเพื่อดูรายชื่อและพิมพ์เอกสารรายหลักสูตร</p>
  </div>
</div>

<div class="row g-3 mb-4 metric-stat-row">
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-layer-group"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">หลักสูตรที่ดูแล</span>
        <strong class="metric-stat-value"><?= (int) $stats['plans'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--teal">
      <div class="metric-stat-icon"><i class="fa fa-user-graduate"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ผู้เรียนรวม</span>
        <strong class="metric-stat-value"><?= (int) $stats['learners'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--amber">
      <div class="metric-stat-icon"><i class="fa fa-certificate"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">พร้อมออกเกียรติบัตร</span>
        <strong class="metric-stat-value"><?= (int) $stats['cert_ready'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--emerald">
      <div class="metric-stat-icon"><i class="fa fa-award"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ออกเกียรติบัตรแล้ว</span>
        <strong class="metric-stat-value"><?= (int) $stats['cert_issued'] ?></strong>
      </div>
    </div>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <?php if ($rows === []): ?>
    <div class="text-center text-muted py-4">
      ยังไม่มีหลักสูตรที่รับผิดชอบ
      <?php if (auth()->role() === 'trainer'): ?>
        — <a href="<?= url('/admin/trainer-apply') ?>">สมัครเป็นวิทยากรของหลักสูตร</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table id="cert-manage-plans" class="table datatable w-100" data-order='[[1,"asc"]]'>
      <thead>
        <tr>
          <th class="col-primary">หลักสูตร</th>
          <th class="col-wrap">หัวข้อ</th>
          <th class="text-center">ผู้เรียน</th>
          <th class="text-center">พร้อมออก</th>
          <th class="text-center">ออกเกียรติบัตรแล้ว</th>
          <th class="text-center">เฉลี่ย %</th>
          <th class="col-actions text-end">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row):
          $plan = $row['plan'];
          $pid = (int) $plan['id'];
          $required = \App\Services\PlanProgress::isRequired($plan);
          $trainers = $row['trainers'] ?? [];
          $avg = $row['avg_percent'] !== null ? (float) $row['avg_percent'] : null;
        ?>
        <tr>
          <td data-order="<?= (int) ($plan['plan_no'] ?? 0) ?>">
            <span class="enroll-plan-no">หลักสูตรที่ <?= (int) ($plan['plan_no'] ?? 0) ?></span>
            <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
          </td>
          <td class="col-wrap">
            <strong class="d-block"><?= e((string) ($plan['title'] ?? '')) ?></strong>
            <span class="small text-muted d-block"><?= e((string) ($plan['topic_group'] ?? '')) ?> · <?= number_format((float) ($plan['hours'] ?? 0), 1) ?> ชม.</span>
            <span class="small d-block mt-1 enroll-trainers-meta">
              <i class="fa fa-chalkboard-user text-muted me-1"></i>
              <?php if ($trainers !== []): ?>
                <span class="text-muted">วิทยากร:</span> <?= e(implode(', ', $trainers)) ?>
              <?php else: ?>
                <span class="text-muted">ยังไม่มีวิทยากร</span>
              <?php endif; ?>
            </span>
          </td>
          <td class="text-center" data-order="<?= (int) $row['learner_count'] ?>"><?= (int) $row['learner_count'] ?></td>
          <td class="text-center" data-order="<?= (int) $row['cert_ready'] ?>">
            <?php if ((int) $row['cert_ready'] > 0): ?>
              <span class="badge text-bg-warning"><?= (int) $row['cert_ready'] ?></span>
            <?php else: ?>
              <span class="text-muted">0</span>
            <?php endif; ?>
          </td>
          <td class="text-center" data-order="<?= (int) $row['cert_issued'] ?>"><?= (int) $row['cert_issued'] ?></td>
          <td class="text-center" data-order="<?= $avg !== null ? $avg : -1 ?>">
            <?= $avg !== null ? number_format($avg, 1) . '%' : '-' ?>
          </td>
          <td class="text-end col-actions">
            <a class="btn btn-sm btn-primary" href="<?= url('/admin/certificates/plans/' . $pid) ?>">
              <i class="fa fa-users"></i> ดูผู้เรียน
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div></div>
