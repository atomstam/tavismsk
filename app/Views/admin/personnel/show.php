<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
  <div class="d-flex gap-3 align-items-center mb-3">
    <img src="<?= e(personnel_photo_url($row['photo']??null) ?? avatar_url(null)) ?>" width="88" height="88" class="rounded-circle" style="object-fit:cover" alt="">
    <div><h3 class="mb-0"><?= e(trim(($row['title']??'').$row['first_name'].' '.$row['last_name'])) ?></h3>
      <div class="text-muted"><?= e($row['position_name']??'') ?> · <?= e($row['rank_name']??'') ?></div></div>
  </div>
  <div class="row g-2">
    <div class="col-md-4">รหัส: <?= e($row['code']??'-') ?></div>
    <div class="col-md-4">โทร: <?= e($row['phone']??'-') ?></div>
    <div class="col-md-4">อีเมล: <?= e($row['email']??'-') ?></div>
    <div class="col-12">ที่อยู่: <?= e($row['address']??'-') ?></div>
    <div class="col-12">วุฒิการศึกษา:<br><?= nl2br(e($row['education']??'-')) ?></div>
  </div>
  <a class="btn btn-primary mt-3" href="<?= url('/admin/personnel/'.$row['id'].'/edit') ?>">แก้ไข</a>
</div></div>
