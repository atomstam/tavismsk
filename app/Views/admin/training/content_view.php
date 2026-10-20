<?php
$plan = $plan ?? [];
$content = $content ?? [];
$pid = (int) ($plan['id'] ?? 0);
?>
<div class="page-head">
  <div>
    <p class="page-kicker">เปิดเนื้อหา · โมดูลที่ <?= (int) ($plan['plan_no'] ?? 0) ?></p>
    <h1><?= e($content['title'] ?? '') ?></h1>
  </div>
  <a class="btn btn-outline-secondary" href="<?= url('/admin/training/' . $pid . '?tab=content') ?>">กลับรายการ</a>
</div>
<div class="card-soft"><div class="card-body">
  <?php partial('partials/content_viewer', ['preview' => $preview ?? []]); ?>
</div></div>
