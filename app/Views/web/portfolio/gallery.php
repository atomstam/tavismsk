<section class="container pd-wrap">
  <div class="pd-crumb">
    <a href="<?= url('/') ?>">หน้าแรก</a> › แกลเลอรี่กิจกรรม
  </div>

  <div class="pd-hero" style="background:linear-gradient(105deg,#0f766e,#0369a1)">
    <div class="pd-hero-deco"><i class="fa fa-images"></i><i class="fa fa-video"></i><i class="fa fa-camera"></i></div>
    <div class="pd-hero-top">
      <div class="pd-hero-icon"><i class="fa fa-images"></i></div>
      <div>
        <h1>แกลเลอรี่กิจกรรม</h1>
        <p>คลังภาพกิจกรรม การพัฒนา และผลงานของบุคลากร</p>
      </div>
    </div>
    <div class="pd-hero-stats">
      <div class="pd-stat"><i class="fa fa-folder" style="color:#93c5fd"></i><div><strong><?= (int)$stats['albums'] ?></strong><span>อัลบั้ม</span></div></div>
      <div class="pd-stat"><i class="fa fa-image" style="color:#5eead4"></i><div><strong><?= (int)$stats['images'] ?></strong><span>รูปภาพ</span></div></div>
      <div class="pd-stat"><i class="fa fa-play" style="color:#fde047"></i><div><strong><?= (int)$stats['videos'] ?></strong><span>วิดีโอ</span></div></div>
      <div class="pd-stat"><i class="fa fa-grip" style="color:#86efac"></i><div><strong><?= (int)$stats['categories'] ?></strong><span>หมวดหมู่</span></div></div>
    </div>
  </div>

  <div class="pd-teacher-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
    <?php foreach ($albums as $a): ?>
      <div class="pd-teacher-card text-start p-0 overflow-hidden">
        <?php if ($a['cover_image']): ?>
          <img src="<?= e(upload_url($a['cover_image'])) ?>" class="w-100 rounded-0" style="height:150px;object-fit:cover;border:0;border-radius:0" alt="">
        <?php else: ?>
          <div class="d-flex align-items-center justify-content-center" style="height:150px;background:#0f172a;color:#475569"><i class="fa fa-image fa-2x"></i></div>
        <?php endif; ?>
        <div class="p-3">
          <h3 class="mb-1"><?= e($a['title']) ?></h3>
          <p class="mb-2"><?= e($a['category'] ?: 'ทั่วไป') ?> · <?= e(thai_date($a['event_date'] ?? null)) ?></p>
          <span class="pd-badge" style="background:#2563eb"><?= (int)$a['image_count'] ?> รูป</span>
          <span class="pd-badge" style="background:#059669"><?= (int)$a['video_count'] ?> วิดีโอ</span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$albums): ?>
    <div class="pd-empty"><i class="fa fa-images d-block"></i>ยังไม่มีอัลบั้มในแกลเลอรี่</div>
  <?php endif; ?>
</section>
