<div class="container py-4">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › ตรวจสอบเกียรติบัตร</div>
    <h1>ตรวจสอบเกียรติบัตรออนไลน์</h1>
    <p>กรอกรหัสตรวจสอบจากเกียรติบัตร <?= e(app_brand('short_name')) ?> เพื่อยืนยันความถูกต้อง</p>
  </div>

  <form class="card-soft p-3 mb-4" method="get" action="<?= url('/certificate/verify') ?>">
    <label class="form-label">รหัสตรวจสอบ (Verify Code)</label>
    <div class="d-flex gap-2">
      <input class="form-control" name="code" value="<?= e($code ?? '') ?>" placeholder="วางรหัสจากเกียรติบัตร" required>
      <button class="btn-add" type="submit">ตรวจสอบ</button>
    </div>
  </form>

  <?php if (($code ?? '') !== ''): ?>
    <?php if (!empty($cert)): ?>
      <div class="card-soft p-4">
        <p class="badge text-bg-success mb-3">ยืนยันแล้ว · เกียรติบัตรถูกต้อง</p>
        <h2 class="h4"><?= e($cert['recipient_name']) ?></h2>
        <p class="mb-1"><?= e($cert['title']) ?></p>
        <p class="mb-1">เลขที่ <?= e($cert['cert_no']) ?> · <?= number_format((float) $cert['hours'], 1) ?> ชั่วโมง</p>
        <p class="mb-0 text-muted small">หน่วยงาน: <?= e($cert['org_name'] ?? '-') ?> · ออกเมื่อ <?= e(thai_date($cert['issued_at'] ?? null)) ?></p>
      </div>
    <?php else: ?>
      <div class="alert alert-warning">ไม่พบเกียรติบัตรจากรหัสนี้ หรืออาจถูกเพิกถอนแล้ว</div>
    <?php endif; ?>
  <?php endif; ?>
</div>
