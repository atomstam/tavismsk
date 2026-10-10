<div class="container py-4">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › FAQ</div>
    <h1>คำถามที่พบบ่อย</h1>
    <p>คำตอบสำหรับคำถามที่ผู้ใช้งานระบบถามบ่อย</p>
  </div>
  <?php foreach ($rows as $f): ?>
    <div class="card-soft p-3 mb-2">
      <strong class="d-block mb-1"><i class="fa fa-circle-question text-primary me-2"></i><?= e($f['question']) ?></strong>
      <div class="text-muted"><?= nl2br(e($f['answer'])) ?></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$rows): ?><div class="ws-empty">ยังไม่มีคำถาม</div><?php endif; ?>
</div>
