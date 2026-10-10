<?php
/** @var array $teacher */
/** @var array $modules */
/** @var array $counts */
$fullName = trim(($teacher['title'] ?? '') . ($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));
$photo = $teacher['photo'] ?? $teacher['avatar'] ?? null;
$photoUrl = $photo ? upload_url($photo) : asset('img/default-avatar.svg');
$profileUrl = !empty($teacher['id']) ? url('/portfolios/' . $teacher['id']) : url('/about');
$summaryKeys = ['outstanding', 'awards', 'activities', 'training', 'speaker'];
?>
<aside class="pd-side">
  <div class="pd-profile">
    <img src="<?= e($photoUrl) ?>" alt="<?= e($fullName) ?>">
    <h2><?= e($fullName ?: setting('org_name')) ?></h2>
    <div class="role"><?= e($teacher['position_name'] ?? $teacher['role_label'] ?? setting('org_affiliation') ?: 'บุคลากร') ?></div>
    <a class="pd-btn-detail" href="<?= e($profileUrl) ?>">ดูข้อมูลส่วนตัว</a>
  </div>
  <div class="pd-summary">
    <div class="pd-summary-head">
      <h3>สรุปผลงาน</h3>
      <a href="<?= !empty($teacher['id']) ? url('/portfolios/' . $teacher['id'] . '/works') : url('/portfolios') ?>">ดูทั้งหมด</a>
    </div>
    <?php foreach ($summaryKeys as $k):
      if (empty($modules[$k])) continue;
      $m = $modules[$k];
      $n = (int) ($counts[$k] ?? 0);
    ?>
      <a class="pd-summary-row text-decoration-none" href="<?= !empty($teacher['id'])
        ? url('/portfolios/' . $teacher['id'] . '/works?type=' . urlencode($k))
        : url('/portfolios?type=' . urlencode($k)) ?>">
        <span class="ico" style="background:<?= e($m['color']) ?>"><i class="fa <?= e($m['icon']) ?>"></i></span>
        <span class="lbl"><?= e($m['short']) ?></span>
        <span class="num"><?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?> รายการ</span>
      </a>
    <?php endforeach; ?>
  </div>
</aside>
