<?php
$fullName = trim(($teacher['title'] ?? '') . $teacher['first_name'] . ' ' . $teacher['last_name']);
$photo = $teacher['photo'] ?? $teacher['avatar'] ?? null;
$photoUrl = $photo ? upload_url($photo) : asset('img/default-avatar.svg');
$back_url = url('/portfolios/' . $teacher['id']);
$org = (string) setting('org_name');
?>
<div class="pf-brand">
  <div class="pf-brand-icon"><i class="fa fa-graduation-cap"></i></div>
  <h1><?= e($org) ?></h1>
  <p class="affil"><?= e(setting('org_affiliation') ?: 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม') ?></p>
</div>

<div class="pf-profile-row">
  <img src="<?= e($photoUrl) ?>" alt="<?= e($fullName) ?>">
  <div>
    <h2><?= e($fullName) ?></h2>
    <p><?= e($teacher['position_name'] ?? role_name($teacher['role'] ?? 'teacher')) ?>
      <?php if (!empty($teacher['rank_name'])): ?> · <?= e($teacher['rank_name']) ?><?php endif; ?>
    </p>
    <?php if (!empty($teacher['department_name'])): ?>
      <p><?= e($teacher['department_name']) ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="pf-stat-grid">
  <div class="pf-stat"><strong><?= (int) ($stats['works'] ?? 0) ?></strong><span>ผลงานในแฟ้มนี้</span></div>
  <div class="pf-stat"><strong><?= (int) ($stats['awards'] ?? 0) ?></strong><span>รางวัล/เกียรติบัตร</span></div>
  <div class="pf-stat"><strong><?= (int) ($stats['photos'] ?? 0) ?></strong><span>รูปภาพกิจกรรม</span></div>
  <div class="pf-stat"><strong><?= (int) ($stats['featured'] ?? 0) ?></strong><span>ผลงานดีเด่น</span></div>
</div>

<div class="pf-index-title"><i class="fa fa-list-ol text-primary"></i> สารบัญผลงาน (<?= count($items) ?> รายการ)</div>
<ol class="pf-index-list">
  <?php foreach ($items as $i => $it): ?>
    <?php $mod = $modules[$it['type']] ?? null; ?>
    <li>
      <span class="num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
      <span class="title"><?= e($it['title']) ?></span>
      <?php if (!empty($it['is_featured'])): ?><span class="tag-hot">ผลงานดีเด่น</span><?php endif; ?>
      <?php if ($mod): ?><small class="text-muted"><?= e($mod['short']) ?></small><?php endif; ?>
      <a class="go no-print" href="<?= url('/portfolios/' . $teacher['id'] . '/works/' . $it['id']) ?>"><i class="fa fa-arrow-right"></i></a>
    </li>
  <?php endforeach; ?>
  <?php if (!$items): ?>
    <li><span class="title text-muted">ยังไม่มีรายการผลงานที่เผยแพร่</span></li>
  <?php endif; ?>
</ol>
