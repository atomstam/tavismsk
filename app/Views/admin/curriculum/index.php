<div class="page-head">
  <div>
    <p class="page-kicker">CURRICULUM</p>
    <h1>แผนการเรียนรู้ 14 ชั่วโมง</h1>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php if (can_perm('curriculum','export')): ?>
      <a class="btn btn-outline-primary" href="<?= url('/admin/curriculum/export') ?>"><i class="fa fa-download"></i> ส่งออก CSV</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php
  $typeLabel = [
    'teacher' => 'ครู',
    'police' => 'ตำรวจ',
    'hospital' => 'สาธารณสุข',
    'honda' => 'Honda/TSY',
    'transport' => 'ขนส่ง',
  ];
  foreach (($plans ?? []) as $p):
    $no = (int) $p['plan_no'];
    $db = $dbPlans[$no] ?? null;
    $href = $db ? url('/admin/curriculum/' . (int)$db['id']) : url('/admin/curriculum/' . $no);
  ?>
  <div class="col-md-6 col-xl-4">
    <a href="<?= e($href) ?>" class="card-soft d-block h-100 text-decoration-none text-dark" style="padding:16px">
      <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <span class="badge" style="background:var(--p-primary)">แผนที่ <?= $no ?></span>
        <span class="badge text-bg-warning text-dark"><?= (float)$p['hours'] ?> ชม.</span>
      </div>
      <h2 class="h6 mb-2"><?= e($p['title']) ?></h2>
      <div class="small text-muted">
        <?= e($p['topic_group'] ?? '') ?> ·
        <?= e($typeLabel[$p['instructor_type']] ?? $p['instructor_type']) ?>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<div class="card-soft"><div class="card-body">
  <form id="curriculum-filter" class="dt-filter-bar">
    <input name="q" class="form-control" placeholder="ค้นหาแผน / กลุ่มหัวข้อ / วิทยากร">
  </form>
  <table id="curriculum-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/curriculum/datatable')) ?>">
    <thead><tr>
      <th>แผนที่</th><th>หัวข้อ</th><th>ชม.</th><th>วิทยากร</th><th>กลุ่มหัวข้อ</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
