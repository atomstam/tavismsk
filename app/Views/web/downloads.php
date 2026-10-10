<div class="container py-4">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › ดาวน์โหลด</div>
    <h1>ดาวน์โหลดแฟ้มผลงาน</h1>
    <p>เอกสารและไฟล์สำหรับดาวน์โหลด / พิมพ์ PDF</p>
  </div>
  <?php if ($rows): foreach ($rows as $d): ?>
    <div class="card-soft p-3 mb-2 d-flex justify-content-between align-items-center gap-3">
      <div>
        <div class="fw-semibold"><?= e($d['title']) ?></div>
        <?php if (!empty($d['category'])): ?><div class="small text-muted"><?= e($d['category']) ?></div><?php endif; ?>
      </div>
      <a class="ws-btn-primary" href="<?= e(upload_url($d['file_path'])) ?>"><i class="fa fa-download"></i> ดาวน์โหลด</a>
    </div>
  <?php endforeach; else: ?>
    <div class="ws-empty">ยังไม่มีไฟล์ดาวน์โหลด</div>
  <?php endif; ?>
</div>
