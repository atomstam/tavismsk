<?php
$fullName = trim(($teacher['title'] ?? '') . $teacher['first_name'] . ' ' . $teacher['last_name']);
$counts = $counts ?? [];
$modules = $modules ?? portfolio_modules();
$showSidebar = can_view_owner_sidebar($teacher);
?>
<section class="container pd-wrap">
  <div class="pd-crumb">
    <a href="<?= url('/') ?>">หน้าแรก</a> ›
    <a href="<?= url('/portfolios') ?>">ผลงาน</a> ›
    <a href="<?= url('/portfolios/' . $teacher['id'] . '/works') ?>"><?= e($fullName) ?></a> ›
    รายละเอียด
  </div>

  <div class="pd-layout<?= $showSidebar ? '' : ' pd-layout-full' ?>">
    <div>
      <div class="pd-hero" style="margin-bottom:16px">
        <div class="pd-hero-top mb-0">
          <div class="pd-hero-icon"><i class="fa <?= e($mod['icon'] ?? 'fa-file-lines') ?>"></i></div>
          <div>
            <?php if ($mod): ?><span class="pd-badge mb-2 d-inline-flex" style="background:rgba(255,255,255,.2)"><?= e($mod['title']) ?></span><?php endif; ?>
            <h1 style="font-size:1.4rem"><?= e($item['title']) ?></h1>
            <p><?= e($fullName) ?> · <?= e(thai_date($item['start_date'] ?? null)) ?> · <?= number_format((int)$item['views']) ?> ครั้ง</p>
          </div>
        </div>
      </div>

      <div class="pd-card" style="flex-direction:column">
        <?php if (!empty($item['cover_image'])): ?>
          <img src="<?= e(upload_url($item['cover_image'])) ?>" class="w-100 rounded-3 mb-3" style="max-height:320px;object-fit:cover" alt="">
        <?php endif; ?>
        <?php if (!empty($item['organization'])): ?>
          <p style="color:#cbd5e1"><strong style="color:#fff">หน่วยงาน:</strong> <?= e($item['organization']) ?></p>
        <?php endif; ?>
        <?php if (!empty($item['location'])): ?>
          <p style="color:#cbd5e1"><strong style="color:#fff">สถานที่:</strong> <?= e($item['location']) ?></p>
        <?php endif; ?>
        <div style="color:#e2e8f0;line-height:1.7"><?= nl2br(e((string) ($item['description'] ?? ''))) ?></div>

        <?php if (!empty($files)): ?>
          <hr style="border-color:#334155">
          <h2 style="color:#fff;font-size:1rem">ไฟล์แนบ</h2>
          <ul class="list-unstyled mb-0">
            <?php foreach ($files as $f): ?>
              <li class="mb-2">
                <a href="<?= e(upload_url($f['file_path'])) ?>" target="_blank" rel="noopener" style="color:#fb7185">
                  <i class="fa fa-paperclip"></i> <?= e($f['original_name'] ?: $f['file_name']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <div class="mt-3">
          <a class="pd-btn-detail" href="<?= url('/portfolios/' . $teacher['id'] . '/works') ?>"><i class="fa fa-arrow-left"></i> กลับรายการผลงาน</a>
        </div>
      </div>
    </div>
    <?php if ($showSidebar): ?>
      <?php partial('partials/portfolio-sidebar', [
        'teacher' => $teacher,
        'modules' => $modules,
        'counts' => $counts,
      ]); ?>
    <?php endif; ?>
  </div>
</section>
