<?php
$modules = $modules ?? [];
$courseOverall = (float)($courseOverall ?? 0);
$learnBase = rtrim((string)($learnBase ?? '/learn'), '/') ?: '/learn';
$webShell = !empty($webShell);
?>
<?php if ($webShell): ?><div class="container py-4 ws-page-stack learn-web"><?php endif; ?>
<div class="page-head">
  <div>
    <p class="page-kicker">พื้นที่เรียน</p>
    <h1>บทเรียนของฉัน</h1>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('/curriculum') ?>"><i class="fa fa-list"></i> แผนการเรียนรู้</a>
    <?php if (can_perm('assessments', 'create')): ?>
    <a class="btn btn-outline-secondary" href="<?= url('/admin/training') ?>"><i class="fa fa-chalkboard-user"></i> จัดการบทเรียน</a>
    <?php endif; ?>
  </div>
</div>

<div class="learn-course-banner">
  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <strong>ความก้าวหน้าทั้งหลักสูตร</strong>
    <span class="fs-5 fw-bold" style="color:var(--p-primary)"><?= number_format($courseOverall, 1) ?>%</span>
  </div>
  <div class="progress mb-2">
    <div class="progress-bar" style="width:<?= min(100, $courseOverall) ?>%"></div>
  </div>
  <p class="small text-muted mb-0">ลำดับการเรียนทุกแผน: <strong>ทดสอบก่อน → เนื้อหา → ภาระงาน → สอบหลัง → ประเมินความพึงพอใจ</strong></p>
</div>

<div class="row g-3">
<?php foreach ($modules as $m):
  $p = $m['plan'];
  $pr = $m['progress'];
  $pct = (float)($pr['overall_percent'] ?? 0);
  $stage = (string)($pr['stage'] ?? 'pretest');
  $planNo = (int)($p['plan_no'] ?? 0);
  $coverRel = sprintf('img/plans/plan-%02d.png', $planNo);
  $coverFile = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/assets/' . $coverRel : '';
  $hasCover = $coverFile !== '' && is_file($coverFile);
  $stageLabel = match ($stage) {
    'completed' => '✓ ครบแล้ว',
    'satisfaction' => 'ประเมินความพึงพอใจ',
    'posttest' => 'สอบหลัง',
    'task' => 'ภาระงาน',
    'content' => 'เนื้อหา',
    default => 'ทดสอบก่อน',
  };
?>
  <div class="col-md-6 col-xl-4">
    <article class="learn-plan-card">
      <div class="learn-plan-cover">
        <?php if ($hasCover): ?>
          <img src="<?= asset($coverRel) ?>?v=4" alt="แผนที่ <?= $planNo ?>" loading="lazy" decoding="async">
        <?php else: ?>
          <div class="learn-plan-cover-fallback">
            <span>แผนที่</span>
            <strong><?= $planNo ?></strong>
          </div>
        <?php endif; ?>
        <span class="learn-plan-cover-badge">แผนที่ <?= $planNo ?></span>
        <span class="learn-plan-cover-hours"><i class="fa fa-clock"></i> <?= e((string)$p['hours']) ?> ชม.</span>
      </div>
      <div class="card-body d-flex flex-column">
        <h2 class="learn-plan-title"><?= e($p['title']) ?></h2>
        <div class="d-flex justify-content-between small mb-1">
          <span class="fw-semibold" style="color:var(--p-primary)"><?= number_format($pct,1) ?>%</span>
          <span class="text-muted"><?= e($stageLabel) ?></span>
        </div>
        <div class="progress mb-2">
          <div class="progress-bar" style="width:<?= min(100,$pct) ?>%"></div>
        </div>
        <div class="learn-plan-steps small text-muted mb-3">
          <span class="<?= (int)($pr['pretest_done']??0) ? 'is-on' : '' ?>"><?= (int)($pr['pretest_done']??0)?'✓':'○' ?> ก่อน</span>
          <span class="<?= (int)($pr['content_done']??0) ? 'is-on' : '' ?>"><?= (int)($pr['content_done']??0)?'✓':'○' ?> เนื้อหา</span>
          <span class="<?= (int)($pr['task_done']??0) ? 'is-on' : '' ?>"><?= (int)($pr['task_done']??0)?'✓':'○' ?> งาน</span>
          <span class="<?= (int)($pr['posttest_done']??0) ? 'is-on' : '' ?>"><?= (int)($pr['posttest_done']??0)?'✓':'○' ?> หลัง</span>
          <span class="<?= (int)($pr['satisfaction_done']??0) ? 'is-on' : '' ?>"><?= (int)($pr['satisfaction_done']??0)?'✓':'○' ?> ประเมิน</span>
        </div>
        <a class="btn btn-learn-primary mt-auto" href="<?= url($learnBase . '/' . (int)$p['id']) ?>">
          <?= $pct >= 100 ? 'ดูรายละเอียด' : 'เข้าเรียนต่อ' ?> →
        </a>
      </div>
    </article>
  </div>
<?php endforeach; ?>
<?php if (!$modules): ?>
  <div class="col-12"><div class="card-soft"><div class="card-body text-center text-muted">ยังไม่มีโมดูลการอบรม</div></div></div>
<?php endif; ?>
</div>
<?php if ($webShell): ?></div><?php endif; ?>
