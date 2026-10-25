<?php
$requiredRows = $requiredRows ?? [];
$generalRows = $generalRows ?? [];
$passNoteTask = (float) ($passNoteTask ?? 70);
$passNotePost = (float) ($passNotePost ?? 70);

$mark = static function (bool $ok): string {
    if ($ok) {
        return '<span class="cert-mark is-ok" title="ครบ"><i class="fa fa-check"></i></span>';
    }
    return '<span class="cert-mark is-off" title="ยังไม่ครบ"><i class="fa fa-minus"></i></span>';
};

$renderTable = static function (array $rows, float $passNoteTask, float $passNotePost) use ($mark): void {
    ?>
  <div class="table-responsive">
    <table class="table cert-table align-middle mb-0">
      <thead>
        <tr>
          <th>หลักสูตร</th>
          <th class="text-center">สอบก่อน</th>
          <th class="text-center">เนื้อหา</th>
          <th class="text-center">ภาระงาน</th>
          <th class="text-center">สอบหลัง</th>
          <th class="text-center">แบบสอบถาม</th>
          <th class="text-center">พิมพ์เกียรติบัตร</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีหลักสูตรในกลุ่มนี้</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row):
          $plan = $row['plan'] ?? [];
          $p = $row['progress'] ?? [];
          $pid = (int) ($plan['id'] ?? 0);
          $ready = !empty($row['ready']);
          $cert = $row['certificate'] ?? null;
          $taskPct = (float) ($p['task_percent'] ?? 0);
          $postPct = (float) ($p['posttest_percent'] ?? 0);
          $isReq = \App\Services\PlanProgress::isRequired($plan);
        ?>
          <tr class="<?= $ready ? 'is-ready' : '' ?>">
            <td>
              <div class="cert-plan-title">หลักสูตรที่ <?= (int) ($plan['plan_no'] ?? 0) ?></div>
              <div class="cert-plan-name"><?= e((string) ($plan['title'] ?? '')) ?></div>
              <div class="cert-plan-meta">
                <span class="cert-track <?= $isReq ? 'is-required' : 'is-general' ?>"><?= $isReq ? 'บังคับ' : 'ทั่วไป' ?></span>
                <span><?= number_format((float) ($plan['hours'] ?? 0), 1) ?> ชม.</span>
                <span>ความก้าวหน้า <?= number_format((float) ($p['overall_percent'] ?? 0), 1) ?>%</span>
                <span>คะแนน <?= number_format((float) ($p['score_percent'] ?? 0), 1) ?>%</span>
              </div>
            </td>
            <td class="text-center"><?= $mark((int) ($p['pretest_done'] ?? 0) === 1) ?></td>
            <td class="text-center"><?= $mark((int) ($p['content_done'] ?? 0) === 1) ?></td>
            <td class="text-center">
              <?= $mark((int) ($p['task_done'] ?? 0) === 1 && $taskPct + 0.001 >= $passNoteTask) ?>
              <div class="cert-cell-note">
                <?php if ((int) ($p['task_done'] ?? 0) === 1): ?>
                  <?= number_format((float) ($p['task_score_earned'] ?? 0), 1) ?>/<?= number_format((float) ($p['task_score_max'] ?? 0), 0) ?>
                  <br><?= number_format($taskPct, 0) ?>%
                <?php elseif ($taskPct <= 0): ?>
                  รอตรวจ
                <?php else: ?>
                  <?= number_format($taskPct, 0) ?>% · รอตรวจครบ
                <?php endif; ?>
              </div>
            </td>
            <td class="text-center">
              <?= $mark((int) ($p['posttest_done'] ?? 0) === 1 && $postPct + 0.001 >= $passNotePost) ?>
              <div class="cert-cell-note"><?= number_format($postPct, 0) ?>%</div>
            </td>
            <td class="text-center"><?= $mark((int) ($p['satisfaction_done'] ?? 0) === 1) ?></td>
            <td class="text-center">
              <?php if ($ready): ?>
                <a class="btn btn-sm cert-btn-print" href="<?= url('/admin/certificates/plan/' . $pid . '/view') ?>">
                  <i class="fa fa-file-pdf"></i> พิมพ์
                </a>
                <?php if (!empty($cert['cert_no'])): ?>
                  <div class="cert-cell-note mt-1"><?= e((string) $cert['cert_no']) ?></div>
                <?php endif; ?>
              <?php else: ?>
                <span class="cert-chip-wait">ยังไม่ครบเงื่อนไข</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
    <?php
};
?>
<div class="page-head cert-page-head">
  <div>
    <p class="page-kicker">CERTIFICATES</p>
    <h1>เกียรติบัตรของฉัน</h1>
    <p class="cert-lead">ติดตามความครบถ้วนของแต่ละหลักสูตร และพิมพ์เกียรติบัตรรายหลักสูตรเมื่อผ่านเกณฑ์</p>
  </div>
</div>

<div class="card-soft cert-section mb-3">
  <div class="card-body">
    <div class="cert-section-head">
      <div>
        <span class="cert-track is-required">บังคับ</span>
        <h2 class="cert-section-title">ตารางที่ 1 · วิชาบังคับ</h2>
        <p class="cert-section-desc">พิมพ์เกียรติบัตรรายหลักสูตรได้เมื่อผ่านเกณฑ์ของหลักสูตรนั้น — วุฒิบัตรดูที่เมนูวุฒิบัตรเมื่อผ่านครบทุกหลักสูตร</p>
      </div>
      <div class="cert-section-count"><?= count($requiredRows) ?> หลักสูตร</div>
    </div>
    <?php $renderTable($requiredRows, $passNoteTask, $passNotePost); ?>
  </div>
</div>

<div class="card-soft cert-section mb-3">
  <div class="card-body">
    <div class="cert-section-head">
      <div>
        <span class="cert-track is-general">ทั่วไป</span>
        <h2 class="cert-section-title">ตารางที่ 2 · วิชาทั่วไป</h2>
        <p class="cert-section-desc">เรียนตามความสนใจ พิมพ์เกียรติบัตรรายหลักสูตรได้เมื่อผ่านเกณฑ์ของหลักสูตรนั้น</p>
      </div>
      <div class="cert-section-count"><?= count($generalRows) ?> หลักสูตร</div>
    </div>
    <?php $renderTable($generalRows, $passNoteTask, $passNotePost); ?>
  </div>
</div>

<div class="cert-note card-soft mb-3">
  <div class="card-body">
    <strong>หมายเหตุเกณฑ์การผ่านของทุกหลักสูตร</strong>
    <ul class="mb-0 mt-2">
      <li>ดำเนินการครบทุกขั้นตอน: สอบก่อน · เนื้อหา · ภาระงาน · สอบหลัง · แบบสอบถามความพึงพอใจ</li>
      <li>คะแนนภาระงานคิดหลังวิทยากรตรวจแล้ว ไม่ต่ำกว่า <?= number_format($passNoteTask, 0) ?>%</li>
      <li>คะแนนสอบหลังเรียนไม่ต่ำกว่า <?= number_format($passNotePost, 0) ?>%</li>
      <li>เกียรติบัตรออกเป็นรายหลักสูตร ส่วนวุฒิบัตรอยู่ที่เมนูวุฒิบัตร และออกได้เมื่อผ่านวิชาบังคับครบทุกหลักสูตร</li>
    </ul>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <h2 class="cert-section-title mb-3">เอกสารที่ออกแล้ว</h2>
  <table id="certificates-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/certificates/datatable')) ?>">
    <thead>
      <tr>
        <th>เลขที่เกียรติบัตร</th>
        <th>ผู้รับ</th>
        <th>หน่วยงาน</th>
        <th>ชั่วโมง</th>
        <th>วันที่ออก</th>
        <th></th>
      </tr>
    </thead>
  </table>
</div></div>
