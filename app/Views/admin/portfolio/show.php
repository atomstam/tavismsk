<div class="page-head">
  <div><p class="page-kicker">PORTFOLIO MODULE</p><h1><?= e($mod['title']) ?></h1></div>
  <div class="d-flex gap-2">
    <a class="btn btn-light" href="<?= url('/admin/portfolio/'.$type) ?>">กลับ</a>
    <a class="btn btn-outline-primary" href="<?= url('/admin/portfolio/'.$type.'/'.$item['id'].'/edit') ?>">แก้ไข</a>
    <a class="btn btn-dark" href="<?= url('/admin/reports/'.$type.'/'.$item['id']) ?>">ดูรายงาน</a>
  </div>
</div>
<div class="card-soft"><div class="card-body">
  <h4><?= e($item['title']) ?></h4>
  <p class="text-muted"><?= e(trim(($item['user_title']??'').$item['first_name'].' '.$item['last_name'])) ?></p>
  <div class="row g-2">
    <div class="col-md-4"><strong>หน่วยงาน:</strong> <?= e($item['organization'] ?? '-') ?></div>
    <div class="col-md-4"><strong>วันที่:</strong> <?= e(thai_date($item['start_date'] ?? null)) ?></div>
    <div class="col-md-4"><strong>ชั่วโมง:</strong> <?= e((string)$item['hours']) ?></div>
  </div>
  <p class="mt-3"><?= nl2br(e($item['description'] ?? '')) ?></p>
  <?php if ($photos): ?>
  <div class="photo-grid mt-3">
    <?php $n=1; foreach ($photos as $p): ?>
      <figure><img src="<?= e(upload_url($p['file_path'])) ?>" alt=""><figcaption>ภาพที่ <?= $n++ ?></figcaption></figure>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php foreach ($files as $f): ?>
    <div class="mt-2"><a href="<?= e(upload_url($f['file_path'])) ?>" target="_blank"><i class="fa fa-paperclip"></i> <?= e($f['original_name']) ?></a></div>
  <?php endforeach; ?>
</div></div>
