<?php
$mods = portfolio_modules();
$filterUser = (int) ($filterUser ?? 0);
$isAdmin = is_admin_like();
$isTrainer = is_trainer_like();
$isLearner = is_learner();
$dashKicker = (string) config('app', 'dashboard.kicker', 'OVERVIEW');
$dashTitle = (string) config('app', 'dashboard.title', config('app', 'short_name', 'TAVIS'));
if ($isLearner) {
    $dashSubtitle = (string) config('app', 'dashboard.subtitle_user', config('app', 'tagline', ''));
} elseif ($isTrainer && !$isAdmin) {
    $dashSubtitle = (string) config('app', 'dashboard.subtitle_trainer', config('app', 'tagline', ''));
} else {
    $dashSubtitle = (string) config('app', 'dashboard.subtitle_admin', config('app', 'tagline', ''));
}
?>
<div class="page-head">
  <div>
    <p class="page-kicker"><?= e($dashKicker) ?></p>
    <h1><?= e($dashTitle) ?></h1>
    <?php if ($dashSubtitle !== ''): ?>
    <p class="text-muted mb-0 small"><?= e($dashSubtitle) ?></p>
    <?php endif; ?>
  </div>
  <?php if (!empty($people) && $isAdmin): ?>
  <form method="get" class="d-flex align-items-center gap-2">
    <label class="small text-muted mb-0">กรองผู้ใช้</label>
    <select name="user_id" class="form-select" style="width:auto;min-width:220px" onchange="this.form.submit()">
      <option value="">แสดงทั้งหมด</option>
      <?php foreach ($people as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= $filterUser===(int)$p['id']?'selected':'' ?>><?= e(trim(($p['title']??'').$p['first_name'].' '.$p['last_name'])) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>
</div>

<div data-stats-root
     data-card-url="<?= e(url('/admin/dashboard/card')) ?>"
     data-chart-url="<?= e(url('/admin/dashboard/chart')) ?>"
     data-query="<?= $filterUser > 0 ? '?user_id='.(int)$filterUser : '' ?>">
<?php partial('partials/overview-cards'); ?>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card-soft"><div class="card-body">
      <h6 class="mb-3">เมนูด่วน</h6>
      <div class="quick-menu">
        <?php if (can_perm('learn', 'view')): ?>
          <a href="<?= url('/learn') ?>"><i class="fa fa-graduation-cap"></i> บทเรียน</a>
        <?php endif; ?>
        <?php if (can_perm('assessments', 'create')): ?>
          <a href="<?= url('/admin/training') ?>"><i class="fa fa-chalkboard-user"></i> จัดการ LMS</a>
        <?php endif; ?>
        <?php if (can_perm('enrollments', 'view')): ?>
          <a href="<?= url('/admin/enrollments') ?>"><i class="fa fa-clipboard-list"></i> ลงทะเบียน</a>
        <?php endif; ?>
        <?php if (can_perm('certificates', 'view')): ?>
          <a href="<?= url('/admin/certificates') ?>"><i class="fa fa-certificate"></i> เกียรติบัตร</a>
        <?php endif; ?>
        <?php if (can_perm('curriculum', 'view')): ?>
          <a href="<?= url('/admin/curriculum') ?>"><i class="fa fa-book-open"></i> โมดูล</a>
        <?php endif; ?>
        <?php if (can_perm('members', 'view') && $isTrainer): ?>
          <a href="<?= url('/admin/members') ?>"><i class="fa fa-user-graduate"></i> ผู้เรียน</a>
        <?php endif; ?>
        <?php if (can_perm('org_units', 'view')): ?>
          <a href="<?= url('/admin/org-units') ?>"><i class="fa fa-sitemap"></i> หน่วยงาน</a>
        <?php endif; ?>
        <?php if (can_perm('ai', 'view')): ?>
          <a href="<?= url('/admin/ai') ?>"><i class="fa fa-robot"></i> AI</a>
        <?php endif; ?>
        <?php if (can_perm('portfolio', 'view')): ?>
          <?php foreach ($mods as $k => $m): ?>
            <a href="<?= url('/admin/portfolio/'.$k) ?>"><i class="fa <?= e($m['icon']) ?>"></i> <?= e($m['short']) ?></a>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (can_perm('personnel', 'view')): ?>
          <a href="<?= url('/admin/personnel') ?>"><i class="fa fa-id-card"></i> บุคลากร</a>
        <?php endif; ?>
        <?php if (can_perm('reports', 'view')): ?>
          <a href="<?= url('/admin/reports/training') ?>"><i class="fa fa-file-lines"></i> รายงาน</a>
        <?php endif; ?>
        <?php if (can_perm('logs', 'view')): ?>
          <a href="<?= url('/admin/logs') ?>"><i class="fa fa-clipboard-list"></i> Log</a>
        <?php endif; ?>
        <?php if (can_perm('users', 'view')): ?>
          <a href="<?= url('/admin/users') ?>"><i class="fa fa-users"></i> ผู้ใช้</a>
        <?php endif; ?>
      </div>
    </div></div>
  </div>
</div>

<div class="row g-3">
  <?php partial('partials/overview-charts', [
    'showLoginStatus' => false,
    'isLearner' => $isLearner,
    'isAdmin' => $isAdmin,
  ]); ?>
</div>
</div>
<?php
$scripts = '<script src="'.e(asset('js/dashboard-charts.js')).'?v=3"></script>
<script src="'.e(asset('js/dashboard-async.js')).'?v=3"></script>';
?>
