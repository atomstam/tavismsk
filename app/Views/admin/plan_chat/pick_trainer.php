<?php
$plan = $plan ?? [];
$trainers = $trainers ?? [];
?>
<div class="page-head">
  <div>
    <p class="page-kicker">PLAN CHAT</p>
    <h1>เลือกวิทยากร</h1>
    <p class="text-muted mb-0">หลักสูตรที่ <?= (int) ($plan['plan_no'] ?? 0) ?> · <?= e($plan['title'] ?? '') ?></p>
  </div>
  <a class="btn btn-learn-outline" href="<?= url('/admin/learn/' . (int) ($plan['id'] ?? 0)) ?>">กลับบทเรียน</a>
</div>
<div class="row g-3">
  <?php foreach ($trainers as $t): ?>
    <div class="col-md-6 col-lg-4">
      <form method="post" action="<?= url('/admin/plan-chat/start') ?>" class="card-soft h-100">
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= (int) ($plan['id'] ?? 0) ?>">
        <input type="hidden" name="trainer_id" value="<?= (int) $t['id'] ?>">
        <div class="card-body d-flex flex-column">
          <strong><?= e(trim(($t['title'] ?? '') . ($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''))) ?></strong>
          <span class="small text-muted mb-3">@<?= e($t['username']) ?></span>
          <button class="btn btn-primary mt-auto" type="submit"><i class="fa fa-comments"></i> สนทนา</button>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
</div>
