<?php /** @var array $cert */ ?>
<div class="page-head">
  <div>
    <p class="page-kicker">CERTIFICATE</p>
    <h1>เกียรติบัตรออนไลน์</h1>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary" type="button" onclick="window.print()"><i class="fa fa-print"></i> พิมพ์</button>
    <a class="btn btn-outline-primary" href="<?= url('/admin/certificates') ?>">กลับ</a>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <div class="tavis-cert" id="cert-print">
    <div class="tavis-cert-border">
      <p class="text-center mb-1" style="letter-spacing:.2em;color:#6b21a8;font-weight:700"><?= e(app_brand('short_name')) ?></p>
      <h2 class="text-center h4 mb-1"><?= e(app_brand('full_name')) ?></h2>
      <p class="text-center text-muted mb-4">Certificate of Completion · <?= e(app_brand('name_en')) ?></p>
      <p class="text-center mb-1">ขอรับรองว่า</p>
      <h3 class="text-center mb-3" style="color:#6b21a8"><?= e($cert['recipient_name']) ?></h3>
      <p class="text-center mb-2">ได้ผ่านหลักสูตรอบรมพัฒนาด้านความปลอดภัยทางถนน<br><strong><?= e($cert['title']) ?></strong></p>
      <p class="text-center mb-4">
        จำนวน <?= number_format((float) $cert['hours'], 1) ?> ชั่วโมง
        <?php if ($cert['final_score'] !== null): ?> · คะแนน <?= number_format((float) $cert['final_score'], 1) ?><?php endif; ?>
      </p>
      <div class="row text-center small">
        <div class="col-md-4">
          <div class="border-top pt-2 mt-4">เลขที่ <?= e($cert['cert_no']) ?></div>
        </div>
        <div class="col-md-4">
          <div class="border-top pt-2 mt-4"><?= e($cert['org_name'] ?: 'หน่วยงานต้นสังกัด') ?></div>
        </div>
        <div class="col-md-4">
          <div class="border-top pt-2 mt-4">ออกเมื่อ <?= e(thai_date($cert['issued_at'] ?? null)) ?></div>
        </div>
      </div>
      <p class="text-center small text-muted mt-4 mb-0">
        ตรวจสอบความถูกต้อง: <?= e(url('/certificate/verify?code=' . $cert['verify_code'])) ?>
      </p>
      <p class="text-center small text-muted mt-2 mb-0">จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม</p>
    </div>
  </div>
</div></div>

<style>
.tavis-cert-border {
  border: 4px double #7c3aed;
  border-radius: 16px;
  padding: 36px 28px;
  background: linear-gradient(180deg, #faf5ff, #fff);
}
@media print {
  .page-head, .sidebar, .topbar, .admin-footer, .btn { display: none !important; }
  .admin-shell, .main-wrap, .content { display: block !important; width: 100% !important; }
}
</style>
