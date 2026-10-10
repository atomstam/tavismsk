<?php
$plan = $plan ?? [];
$content = $content ?? [];
$pid = (int)($plan['id'] ?? 0);
$cid = (int)($content['id'] ?? 0);
$type = (string)($content['content_type'] ?? 'link');
$learnBase = rtrim((string)($learnBase ?? '/learn'), '/') ?: '/learn';
$webShell = !empty($webShell);
?>
<?php if ($webShell): ?><div class="container py-4 ws-page-stack learn-web"><?php endif; ?>
<div class="page-head">
  <div>
    <p class="page-kicker">เนื้อหา · แผนที่ <?= (int)($plan['plan_no'] ?? 0) ?></p>
    <h1><?= e($content['title'] ?? '') ?></h1>
  </div>
  <a class="btn btn-outline-secondary" href="<?= url($learnBase . '/' . $pid) ?>">กลับโมดูล</a>
</div>

<?php if (!empty($content['description'])): ?>
  <p class="text-muted"><?= nl2br(e($content['description'])) ?></p>
<?php endif; ?>

<div class="card-soft mb-3"><div class="card-body">
  <?php if ($type === 'youtube' && !empty($embedYoutube)): ?>
    <div class="ratio ratio-16x9 mb-3">
      <iframe src="<?= e($embedYoutube) ?>" title="YouTube" allowfullscreen loading="lazy"></iframe>
    </div>
  <?php elseif ($type === 'pdf' && !empty($fileUrl)): ?>
    <iframe src="<?= e($fileUrl) ?>" style="width:100%;height:70vh;border:1px solid #e2e8f0;border-radius:8px"></iframe>
  <?php elseif (!empty($fileUrl)): ?>
    <p>ดาวน์โหลดไฟล์แนบ:</p>
    <a class="btn btn-outline-primary" href="<?= e($fileUrl) ?>" target="_blank" rel="noopener">
      <i class="fa fa-download"></i> เปิด/ดาวน์โหลดไฟล์
    </a>
  <?php endif; ?>

  <?php if (!empty($content['external_url']) && $type !== 'youtube'): ?>
    <p class="mt-3 mb-1">ลิงก์ภายนอก:</p>
    <a href="<?= e($content['external_url']) ?>" target="_blank" rel="noopener" class="btn btn-primary">
      <i class="fa fa-external-link"></i>
      <?= $type === 'gdrive' ? 'เปิด Google Drive' : 'เปิดลิงก์' ?>
    </a>
  <?php elseif ($type === 'youtube' && !empty($content['external_url']) && empty($embedYoutube)): ?>
    <a href="<?= e($content['external_url']) ?>" target="_blank" rel="noopener" class="btn btn-danger"><i class="fab fa-youtube"></i> เปิด YouTube</a>
  <?php endif; ?>

  <?php if (!empty($content['body_text'])): ?>
    <hr>
    <div><?= nl2br(e($content['body_text'])) ?></div>
  <?php endif; ?>
</div></div>

<form method="post" action="<?= url($learnBase . '/' . $pid . '/content/' . $cid . '/complete') ?>">
  <?= csrf_field() ?>
  <button class="btn btn-success btn-lg" type="submit"><i class="fa fa-check"></i> ทำเครื่องหมายว่าเรียนแล้ว</button>
</form>
<?php if ($webShell): ?></div><?php endif; ?>
