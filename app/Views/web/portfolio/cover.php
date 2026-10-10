<?php
$fullName = trim(($teacher['title'] ?? '') . $teacher['first_name'] . ' ' . $teacher['last_name']);
$photo = $teacher['photo'] ?? $teacher['avatar'] ?? null;
$photoUrl = $photo ? upload_url($photo) : asset('img/default-avatar.svg');
$back_url = url('/portfolios');
?>
<div class="pf-brand">
  <div class="pf-brand-icon"><i class="fa fa-graduation-cap"></i></div>
  <h1><?= e(setting('org_name')) ?></h1>
  <p class="affil"><?= e(setting('org_affiliation') ?: 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม') ?></p>
  <span class="pf-badge">แฟ้มสะสมผลงาน (Portfolio)</span>
</div>

<h2 class="pf-hero-title">แฟ้มสะสมผลงานและกิจกรรม ของครูผู้สอน</h2>
<p class="pf-hero-sub">เพื่อประกอบการประเมินวิทยฐานะ / ข้อตกลงในการพัฒนางาน (PA)</p>

<img class="pf-photo" src="<?= e($photoUrl) ?>" alt="<?= e($fullName) ?>">
<p class="text-center fw-semibold mt-3 mb-1" style="font-size:1.15rem"><?= e($fullName) ?></p>
<p class="text-center text-muted mb-4">
  <?= e($teacher['position_name'] ?? role_name($teacher['role'] ?? 'teacher')) ?>
  <?php if (!empty($teacher['rank_name'])): ?> · <?= e($teacher['rank_name']) ?><?php endif; ?>
</p>

<div class="d-flex justify-content-center gap-2 flex-wrap no-print">
  <a class="btn btn-primary rounded-pill px-4" href="<?= url('/portfolios/' . $teacher['id'] . '/summary') ?>">
    <i class="fa fa-list"></i> สรุปผลงาน
  </a>
  <a class="btn btn-outline-primary rounded-pill px-4" href="<?= url('/portfolios/' . $teacher['id'] . '/works') ?>">
    <i class="fa fa-folder-open"></i> ดูผลงานทั้งหมด (<?= (int) $total ?>)
  </a>
</div>
