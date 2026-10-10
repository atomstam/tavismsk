<div class="container py-4">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › <a href="<?= url('/news') ?>">ข่าวสาร</a> › รายละเอียด</div>
    <h1><?= e($row['title']) ?></h1>
    <p><?= e(thai_date($row['published_at'] ?? null)) ?> · เข้าชม <?= number_format((int)($row['views'] ?? 0)) ?></p>
  </div>
  <div class="card-soft">
    <div class="card-body p-4">
      <?php if (!empty($row['cover_image'])): ?>
        <img src="<?= e(upload_url($row['cover_image'])) ?>" class="img-fluid rounded-3 mb-3 w-100" style="max-height:360px;object-fit:cover" alt="">
      <?php endif; ?>
      <div><?= $row['content'] ?></div>
    </div>
  </div>
</div>
