<?php
$rows = $rows ?? [];
$planRows = $planRows ?? [];
$stats = $stats ?? ['learners' => 0, 'not_passed' => 0, 'passed' => 0, 'issued' => 0];
$requiredTotal = (int) ($requiredTotal ?? 12);
$isLearner = !empty($isLearner);
$canIssue = !empty($canIssue);
?>
<div class="page-head">
  <div>
    <p class="page-kicker">DIPLOMAS</p>
    <h1><?= $isLearner ? 'วุฒิบัตรของฉัน' : 'วุฒิบัตร' ?></h1>
    <p class="text-muted mb-0">
      วุฒิบัตรออกได้เมื่อเรียนและผ่านหลักสูตรบังคับครบทุกหลักสูตร (<?= $requiredTotal ?> หลักสูตร)
      <?= $isLearner ? ' — ติดตามความก้าวหน้าและพิมพ์เอกสารเมื่อผ่านเกณฑ์' : ' — ดูรายชื่อผู้เข้ารับการอบรม ความก้าวหน้า และพิมพ์ PDF เมื่อออกให้แล้ว' ?>
    </p>
  </div>
</div>

<div class="row g-3 mb-4 metric-stat-row">
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--blue">
      <div class="metric-stat-icon"><i class="fa fa-user-graduate"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label"><?= $isLearner ? 'หลักสูตรบังคับทั้งหมด' : 'ผู้เข้ารับการอบรม' ?></span>
        <strong class="metric-stat-value"><?= $isLearner ? $requiredTotal : (int) $stats['learners'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--slate">
      <div class="metric-stat-icon"><i class="fa fa-hourglass-half"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label"><?= $isLearner ? 'หลักสูตรที่ยังไม่ผ่าน' : 'ยังไม่ผ่าน' ?></span>
        <strong class="metric-stat-value"><?= (int) $stats['not_passed'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--amber">
      <div class="metric-stat-icon"><i class="fa fa-circle-check"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label"><?= $isLearner ? 'หลักสูตรที่ผ่านแล้ว' : 'ผ่านแล้ว พร้อมออก' ?></span>
        <strong class="metric-stat-value"><?= (int) $stats['passed'] ?></strong>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="metric-stat metric-stat--emerald">
      <div class="metric-stat-icon"><i class="fa fa-award"></i></div>
      <div class="metric-stat-body">
        <span class="metric-stat-label">ออกวุฒิบัตรแล้ว</span>
        <strong class="metric-stat-value"><?= (int) $stats['issued'] ?></strong>
      </div>
    </div>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <h2 class="h6 mb-3">รายชื่อผู้เข้ารับการอบรม</h2>
  <?php if ($rows === []): ?>
    <p class="text-muted text-center py-4 mb-0">ยังไม่มีผู้เข้ารับการอบรม</p>
  <?php else: ?>
    <table id="diploma-roster" class="table datatable w-100" data-order='[[4,"desc"]]'>
      <thead>
        <tr>
          <th class="col-primary">เลขที่</th>
          <th class="col-wrap">ผู้เข้ารับการอบรม</th>
          <th class="text-center">จำนวน</th>
          <th class="text-center">ร้อยละ</th>
          <th class="col-wrap">ความก้าวหน้า</th>
          <th class="text-center">สถานะ</th>
          <th class="col-actions text-end">พิมพ์ PDF</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $eid = (int) $r['enrollment_id'];
          $passed = (int) $r['passed'];
          $total = (int) $r['total'];
          $pct = (float) $r['percent'];
          $issued = !empty($r['issued']);
          $ready = !empty($r['ready']);
          $cert = $r['cert'] ?? null;
          $pdfUrl = $isLearner
            ? url('/admin/certificates/diploma/view')
            : url('/admin/certificates/enrollments/' . $eid . '/diploma/view');
        ?>
        <tr>
          <td><code><?= e((string) $r['enrollment_no']) ?></code></td>
          <td class="col-wrap">
            <strong class="d-block"><?= e((string) $r['name']) ?></strong>
            <?php if ((string) $r['username'] !== ''): ?>
              <span class="small text-muted">@<?= e((string) $r['username']) ?></span>
            <?php endif; ?>
            <?php if ((string) $r['org_name'] !== ''): ?>
              <span class="small text-muted d-block"><?= e((string) $r['org_name']) ?></span>
            <?php endif; ?>
          </td>
          <td class="text-center" data-order="<?= $passed ?>">
            <strong><?= $passed ?></strong>
            <span class="text-muted">/ <?= $total ?> หลักสูตร</span>
          </td>
          <td class="text-center" data-order="<?= $pct ?>">
            <strong><?= number_format($pct, 1) ?>%</strong>
          </td>
          <td class="col-wrap" data-order="<?= $pct ?>">
            <div class="enroll-mini-bar <?= $ready ? 'is-done' : '' ?>" title="<?= number_format($pct, 1) ?>%">
              <span style="width:<?= min(100, max(0, $pct)) ?>%"></span>
            </div>
            <span class="enroll-pct-label"><?= $passed ?> / <?= $total ?> หลักสูตร · <?= number_format((float) $r['hours'], 1) ?> ชม.</span>
          </td>
          <td class="text-center" data-order="<?= $issued ? '2' : ($ready ? '1' : '0') ?>">
            <?php if ($issued): ?>
              <span class="badge text-bg-success">ออกแล้ว</span>
              <?php if (!empty($cert['cert_no'])): ?>
                <div class="small mt-1"><code><?= e((string) $cert['cert_no']) ?></code></div>
              <?php endif; ?>
            <?php elseif ($ready): ?>
              <span class="badge text-bg-warning">ผ่านแล้ว</span>
              <div class="small text-muted mt-1">พร้อมออกวุฒิบัตร</div>
            <?php else: ?>
              <span class="badge text-bg-secondary">ยังไม่ผ่าน</span>
              <div class="small text-muted mt-1">ยังไม่ครบ <?= $total ?> หลักสูตร</div>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap col-actions">
            <?php if ($issued || ($ready && ($canIssue || $isLearner))): ?>
              <a class="btn btn-sm <?= $issued ? 'btn-outline-danger' : 'btn-outline-primary' ?>" href="<?= $pdfUrl ?>" title="<?= $issued ? 'พิมพ์วุฒิบัตร PDF' : 'ออกและพิมพ์วุฒิบัตร PDF' ?>">
                <i class="fa fa-file-pdf"></i> <?= $issued ? 'พิมพ์ PDF' : 'ออกวุฒิบัตร' ?>
              </a>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="ต้องผ่านหลักสูตรบังคับครบทุกหลักสูตรก่อน">
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

<?php if ($isLearner && $planRows !== []): ?>
<div class="card-soft mt-3"><div class="card-body">
  <h2 class="h6 mb-3">หลักสูตรบังคับที่นับเป็นวุฒิบัตร</h2>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>หลักสูตร</th>
          <th class="col-wrap">หัวข้อ</th>
          <th class="text-center">ความก้าวหน้า</th>
          <th class="text-center">สถานะ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($planRows as $row):
          $plan = $row['plan'] ?? [];
          $p = $row['progress'] ?? [];
          $ok = !empty($row['passed']);
          $pct = (float) ($p['overall_percent'] ?? 0);
        ?>
        <tr>
          <td>
            <span class="enroll-plan-no">หลักสูตรที่ <?= (int) ($plan['plan_no'] ?? 0) ?></span>
          </td>
          <td class="col-wrap">
            <strong class="d-block"><?= e((string) ($plan['title'] ?? '')) ?></strong>
            <span class="small text-muted"><?= number_format((float) ($plan['hours'] ?? 0), 1) ?> ชม.</span>
          </td>
          <td class="text-center">
            <div class="enroll-mini-bar <?= $ok ? 'is-done' : '' ?>">
              <span style="width:<?= min(100, max(0, $pct)) ?>%"></span>
            </div>
            <span class="enroll-pct-label"><?= number_format($pct, 1) ?>%</span>
          </td>
          <td class="text-center">
            <?php if ($ok): ?>
              <span class="badge text-bg-success">ผ่าน</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">ยังไม่ผ่าน</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php endif; ?>
