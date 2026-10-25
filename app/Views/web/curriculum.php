<?php
$typeLabel = [
  'teacher' => 'ครู',
  'police' => 'ตำรวจ',
  'hospital' => 'สาธารณสุข',
  'honda' => 'Honda / TSY',
  'transport' => 'ขนส่ง',
];
$dbByNo = $dbByNo ?? [];
$progressByPlanId = $progressByPlanId ?? [];
$loggedIn = auth()->check();
?>
<div class="container ws-page-stack">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › หลักสูตร</div>
    <h1>หลักสูตร 14 ชั่วโมง</h1>
    <p><?= e(config('app', 'training.course_title', app_brand('full_name'))) ?> — ความรู้ ทักษะ และทัศนคติการใช้รถใช้ถนนอย่างปลอดภัย</p>
    <?php if ($loggedIn): ?>
      <div class="ws-plan-learn-cta">
        <a class="btn btn-learn-primary" href="<?= url('/learn') ?>"><i class="fa fa-graduation-cap"></i> บทเรียนของฉัน</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="row g-4">
    <?php foreach (($plans ?? []) as $p):
      $no = (int) $p['plan_no'];
      $cover = sprintf('img/plans/plan-%02d.png', $no);
      $coverPath = PUBLIC_PATH . '/assets/' . $cover;
      $hasCover = is_file($coverPath);
      $dbRow = $dbByNo[$no] ?? null;
      $planId = (int) ($dbRow['id'] ?? 0);
      $pr = $planId > 0 ? ($progressByPlanId[$planId] ?? null) : null;
      $pct = (float) ($pr['overall_percent'] ?? 0);
      $required = isset($dbRow['is_required'])
        ? (int) $dbRow['is_required'] === 1
        : $no <= 12;
    ?>
    <div class="col-md-6">
      <div class="card-soft ws-plan-card h-100">
        <a href="<?= url('/curriculum/' . $no) ?>" class="d-block text-decoration-none text-reset">
          <div class="ws-plan-cover">
            <?php if ($hasCover): ?>
              <img src="<?= asset($cover) ?>?v=4" alt="หลักสูตรที่ <?= $no ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <div class="ws-plan-cover-fallback"><i class="fa fa-shield-halved"></i></div>
            <?php endif; ?>
            <span class="ws-plan-cover-badge">หลักสูตรที่ <?= $no ?></span>
            <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
            <?php if ($pr): ?>
              <span class="ws-plan-cover-progress"><?= number_format($pct, 0) ?>%</span>
            <?php endif; ?>
          </div>
          <div class="ws-plan-card-body">
            <div class="ws-plan-card-top">
              <span class="ws-pill"><?= e($p['topic_group'] ?? 'โมดูลอบรม') ?></span>
              <span class="ws-plan-card-hours"><i class="fa fa-clock"></i> <?= (float) $p['hours'] ?> ชม.</span>
            </div>
            <h2 class="ws-plan-card-title"><?= e($p['title']) ?></h2>
            <div class="ws-plan-card-meta">
              <span><?= e($typeLabel[$p['instructor_type']] ?? $p['instructor_type']) ?></span>
            </div>
          </div>
        </a>
        <?php if ($planId > 0): ?>
          <div class="ws-plan-card-actions">
            <?php if ($loggedIn): ?>
              <a class="btn btn-sm btn-learn-primary" href="<?= url('/learn/' . $planId) ?>">
                <?= $pct > 0 ? 'เข้าเรียนต่อ' : 'เริ่มเรียน' ?>
              </a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= url('/login?redirect=' . rawurlencode('/learn/' . $planId)) ?>">
                เข้าสู่ระบบเพื่อเรียน
              </a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= url('/curriculum/' . $no) ?>">รายละเอียด</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
