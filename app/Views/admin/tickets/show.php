<div class="page-head"><h1><?= e($row['ticket_no']) ?> — <?= e($row['subject']) ?></h1></div>
<div class="card-soft mb-3"><div class="card-body">
  <div class="text-muted small"><?= e($row['user_name']) ?> · <?= e(thai_date($row['created_at'], true)) ?> · <?= e($row['status']) ?></div>
  <p class="mt-2"><?= nl2br(e($row['message'])) ?></p>
  <?php foreach ($files as $f): ?><a href="<?= e(upload_url($f['file_path'])) ?>"><?= e($f['original_name']) ?></a><?php endforeach; ?>
</div></div>
<?php foreach ($replies as $rp): ?>
<div class="card-soft mb-2"><div class="card-body">
  <strong><?= e($rp['user_name']) ?></strong> <?= !empty($rp['is_admin'])?'<span class="badge bg-primary">ผู้ดูแล</span>':'' ?>
  <div class="small text-muted"><?= e(thai_date($rp['created_at'], true)) ?></div>
  <p class="mb-0"><?= nl2br(e($rp['message'])) ?></p>
</div></div>
<?php endforeach; ?>
<div class="card-soft"><div class="card-body">
<form method="post" action="<?= url('/admin/tickets/'.$row['id'].'/reply') ?>">
  <?= csrf_field() ?>
  <textarea name="message" class="form-control mb-2" required placeholder="ตอบกลับ..."></textarea>
  <button class="btn-add">ส่งข้อความ</button>
</form>
</div></div>
