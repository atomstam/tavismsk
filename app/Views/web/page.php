<div class="container py-4 ws-page-stack">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › เกี่ยวกับ</div>
    <h1><?= e($row['title'] ?? $title) ?></h1>
    <p><?= e(setting('org_name')) ?> · <?= e(setting('org_affiliation')) ?></p>
  </div>
  <div class="card-soft">
    <div class="card-body content-body">
      <?= $row['content'] ?? '<p>สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม</p>' ?>
    </div>
  </div>
</div>
