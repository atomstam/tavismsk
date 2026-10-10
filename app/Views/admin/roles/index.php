<div class="page-head"><h1>บทบาทและสิทธิ์</h1></div>
<div class="row g-3">
<?php foreach ($roles as $k=>$r): ?>
  <div class="col-md-3">
    <a class="card-soft d-block p-3" href="<?= url('/admin/roles/'.$k) ?>">
      <h5><?= e($r['label']) ?></h5>
      <div class="text-muted small"><?= e($k) ?></div>
    </a>
  </div>
<?php endforeach; ?>
</div>
