<?php
$plan = $plan ?? [];
$rows = $rows ?? [];
$stats = $stats ?? ['learners' => 0, 'cert_ready' => 0, 'cert_issued' => 0];
$canIssue = !empty($canIssue);
$pid = (int) ($plan['id'] ?? 0);
$required = \App\Services\PlanProgress::isRequired($plan);
?>
<div class="page-head">
  <div>
    <p class="page-kicker">CERTIFICATES</p>
    <h1>เกียรติบัตร · หลักสูตรที่ <?= (int) ($plan['plan_no'] ?? 0) ?></h1>
    <p class="text-muted mb-0">
      <?= e((string) ($plan['title'] ?? '')) ?>
      · <?= number_format((float) ($plan['hours'] ?? 0), 1) ?> ชม.
      · <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
    </p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php if ($canIssue && (int) ($stats['cert_ready'] ?? 0) > 0): ?>
      <form method="post" action="<?= url('/admin/certificates/plans/' . $pid . '/issue-ready') ?>" class="d-inline"
            data-confirm="ออกเกียรติบัตรให้ผู้เรียนที่พร้อมทุกคนในหลักสูตรนี้?" data-confirm-title="ออกเกียรติบัตรรายหลักสูตร">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-success"><i class="fa fa-certificate"></i> ออกเกียรติบัตรผู้พร้อมทั้งหมด</button>
      </form>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= url('/admin/certificates') ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
  </div>
</div>

<div class="row g-3 mb-4 metric-stat-row">
  <div class="col-6 col-lg">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-user-graduate"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ผู้เรียน</span>
        <strong class="metric-stat-value"><?= (int) $stats['learners'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg">
    <div class="metric-stat metric-stat--amber">
      <div class="metric-stat-icon"><i class="fa fa-hourglass-half"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">พร้อมออกเกียรติบัตร</span>
        <strong class="metric-stat-value"><?= (int) $stats['cert_ready'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg">
    <div class="metric-stat metric-stat--teal">
      <div class="metric-stat-icon"><i class="fa fa-certificate"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ออกเกียรติบัตรแล้ว</span>
        <strong class="metric-stat-value"><?= (int) $stats['cert_issued'] ?></strong>
      </div>
    </div>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <h2 class="h6 mb-3">รายชื่อผู้เข้ารับการพัฒนา</h2>
  <?php if ($rows === []): ?>
    <p class="text-muted text-center py-4 mb-0">ยังไม่มีผู้เรียนในหลักสูตรนี้</p>
  <?php else: ?>
    <table id="cert-plan-learners" class="table datatable w-100" data-order='[[3,"desc"]]'>
      <thead>
        <tr>
          <th class="col-primary">เลขที่</th>
          <th class="col-wrap">ผู้เข้ารับการพัฒนา</th>
          <th>สถานะการเรียน</th>
          <th class="text-center">รวม %</th>
          <th class="col-wrap">เกียรติบัตร</th>
          <th class="col-actions text-end">ดูเอกสาร</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $eid = (int) $r['enrollment_id'];
          $planReady = !empty($r['plan_ready']);
          $planCert = $r['plan_cert'] ?? null;
          $planPdfUrl = url('/admin/certificates/enrollments/' . $eid . '/plan/' . $pid . '/view');
        ?>
        <tr>
          <td><code><?= e((string) $r['enrollment_no']) ?></code></td>
          <td class="col-wrap">
            <strong class="d-block"><?= e((string) $r['name']) ?></strong>
            <span class="small text-muted">@<?= e((string) $r['username']) ?></span>
            <?php if ($r['org_name'] !== ''): ?>
              <span class="small text-muted d-block"><?= e((string) $r['org_name']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?= e((string) $r['stage_label']) ?>
            <?php if (!empty($r['plan_passed'])): ?>
              <span class="badge text-bg-success ms-1">ผ่านหลักสูตร</span>
            <?php endif; ?>
          </td>
          <td class="text-center" data-order="<?= (float) $r['overall'] ?>">
            <strong><?= number_format((float) $r['overall'], 1) ?>%</strong>
          </td>
          <td class="col-wrap" data-order="<?= $planCert ? '2' : ($planReady ? '1' : '0') ?>">
            <?php if ($planCert): ?>
              <span class="badge text-bg-success">ออกแล้ว</span>
              <div class="small mt-1"><code><?= e((string) ($planCert['cert_no'] ?? '')) ?></code></div>
            <?php elseif ($planReady): ?>
              <span class="badge text-bg-warning">พร้อมออก</span>
              <div class="small text-muted mt-1">ผ่านเกณฑ์แล้ว</div>
            <?php else: ?>
              <span class="badge text-bg-secondary">ยังไม่ได้</span>
              <div class="small text-muted mt-1">ยังไม่ครบเงื่อนไข</div>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap col-actions">
            <?php if ($planReady || $planCert): ?>
              <a class="btn btn-sm btn-outline-danger" href="<?= $planPdfUrl ?>" title="ดูเกียรติบัตร PDF">
                <i class="fa fa-file-pdf"></i> พิมพ์ PDF
              </a>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="ยังไม่ครบเงื่อนไข">
                <i class="fa fa-file-pdf"></i> พิมพ์ PDF
              </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div></div>
