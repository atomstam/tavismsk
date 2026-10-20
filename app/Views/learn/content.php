<?php
$plan = $plan ?? [];
$content = $content ?? [];
$pid = (int)($plan['id'] ?? 0);
$cid = (int)($content['id'] ?? 0);
$learnBase = rtrim((string)($learnBase ?? '/learn'), '/') ?: '/learn';
$webShell = !empty($webShell);
?>
<?php if ($webShell): ?><div class="container py-4 ws-page-stack learn-web"><?php endif; ?>
<div class="page-head">
  <div>
    <p class="page-kicker">เนื้อหา · แผนที่ <?= (int)($plan['plan_no'] ?? 0) ?></p>
    <h1><?= e($content['title'] ?? '') ?></h1>
  </div>
  <a class="btn <?= $webShell ? 'btn-outline-secondary' : 'btn-learn-outline' ?>" href="<?= url($learnBase . '/' . $pid) ?>">กลับโมดูล</a>
</div>

<div class="card-soft mb-3"><div class="card-body">
  <?php partial('partials/content_viewer', ['preview' => $preview ?? []]); ?>
</div></div>

<form method="post" action="<?= url($learnBase . '/' . $pid . '/content/' . $cid . '/complete') ?>">
  <?= csrf_field() ?>
  <button class="btn btn-success btn-lg" type="submit"><i class="fa fa-check"></i> ทำเครื่องหมายว่าเรียนแล้ว</button>
</form>
<?php if ($webShell): ?></div><?php endif; ?>
