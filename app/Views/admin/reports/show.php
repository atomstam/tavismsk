<?php
$org = setting('org_name', 'โรงเรียนเชียงยืนพิทยาคม');
$director = setting('director_name', 'นายชัดสกร พิกุลทอง');
$position = setting('director_position', 'ผู้อำนวยการโรงเรียนเชียงยืนพิทยาคม');
$reporter = trim(($item['user_title']??'').$item['first_name'].' '.$item['last_name']);
?>
<div class="page-head">
  <div><h1>รายงาน: <?= e($mod['short']) ?></h1></div>
  <div class="d-flex gap-2">
    <a class="btn btn-light" href="<?= url('/admin/reports/'.$type) ?>"><i class="fa fa-arrow-left"></i> กลับหน้ารายการ</a>
    <button class="btn btn-dark" onclick="window.print()"><i class="fa fa-print"></i> พิมพ์รายงาน</button>
    <a class="btn btn-success" href="<?= url('/admin/reports/'.$type.'/'.$item['id'].'/download') ?>"><i class="fa fa-download"></i> ดาวน์โหลด PDF</a>
  </div>
</div>
<div class="doc-paper">
  <div class="text-center mb-3">
    <div class="sidebar-brand-logo mx-auto mb-2" style="width:64px;height:64px"><i class="fa fa-graduation-cap"></i></div>
    <h4 class="mb-1"><?= e($mod['report_title']) ?></h4>
    <div><?= e($org) ?></div>
  </div>
  <table class="table table-bordered">
    <tr><th width="180">ผู้รายงาน</th><td><?= e($reporter) ?></td></tr>
    <tr><th>หัวข้อ/เรื่อง</th><td><?= e($item['title']) ?></td></tr>
    <tr><th>หน่วยงาน</th><td><?= e($item['organization'] ?? '-') ?></td></tr>
    <tr><th>วันที่เริ่มต้น</th><td><?= e(thai_date($item['start_date'] ?? null)) ?></td></tr>
    <tr><th>ชั่วโมง</th><td><?= e((string)$item['hours']) ?></td></tr>
  </table>
  <?php if (!empty($item['description'])): ?><p><?= nl2br(e($item['description'])) ?></p><?php endif; ?>
  <?php if ($photos): ?>
  <div class="photo-grid my-3">
    <?php $n=1; foreach ($photos as $p): ?>
      <figure><img src="<?= e(upload_url($p['file_path'])) ?>" alt=""><figcaption>ภาพที่ <?= $n++ ?></figcaption></figure>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="sign-row">
    <div>
      <div>ลงชื่อ................................ผู้รายงาน</div>
      <div>(<?= e($reporter) ?>)</div>
      <div><?= e(role_name($item['user_role'] ?? 'teacher')) ?></div>
    </div>
    <div>
      <div>ลงชื่อ................................ผู้รับรอง</div>
      <div>(<?= e($director) ?>)</div>
      <div><?= e($position) ?></div>
    </div>
  </div>
</div>
