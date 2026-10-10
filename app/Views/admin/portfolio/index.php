<?php $mode = $mod['table_mode'] ?? 'hours'; $grad = $mod['gradient'] ?? 'grad-indigo'; ?>
<div class="page-head">
  <div>
    <p class="page-kicker">PORTFOLIO MODULE</p>
    <h1><?= e($mod['title']) ?></h1>
  </div>
  <?php if (can_perm('portfolio', 'create')): ?>
  <a class="btn-add" href="<?= url('/admin/portfolio/' . $type . '/create') ?>"><i class="fa fa-plus"></i> เพิ่มรายการ</a>
  <?php endif; ?>
</div>

<div class="stat-grid">
  <?php if ($mode === 'innovation'): ?>
    <div class="stat-card <?= e($grad) ?>"><small>จำนวนนวัตกรรม</small><strong><?= (int) $stats['count'] ?></strong></div>
    <div class="stat-card <?= e($grad) ?>"><small>ประเภทบ่อยสุด</small><strong style="font-size:18px"><?= e($stats['category']) ?></strong></div>
    <div class="stat-card <?= e($grad) ?>"><small>สถานะบ่อยสุด</small><strong style="font-size:18px"><?= e($stats['status']) ?></strong></div>
  <?php elseif ($mode === 'research'): ?>
    <div class="stat-card <?= e($grad) ?>"><small>จำนวนงานวิจัย</small><strong><?= (int) $stats['count'] ?></strong></div>
    <div class="stat-card <?= e($grad) ?>"><small>ประเภทบ่อยสุด</small><strong style="font-size:18px"><?= e($stats['category']) ?></strong></div>
    <div class="stat-card <?= e($grad) ?>"><small>ปีล่าสุด</small><strong><?= e((string) $stats['year']) ?></strong></div>
  <?php elseif ($mode === 'plc'): ?>
    <div class="stat-card <?= e($grad) ?>"><small>จำนวนกิจกรรม</small><strong><?= (int) $stats['count'] ?></strong></div>
    <div class="stat-card <?= e($grad) ?>"><small>จำนวนชั่วโมง</small><strong><?= e(rtrim(rtrim(number_format((float)$stats['hours'],1,'.',''),'0'),'.')) ?> ชม.</strong></div>
    <div class="stat-card <?= e($grad) ?>"><small>กลุ่มกิจกรรมบ่อยสุด</small><strong style="font-size:18px"><?= e($stats['group']) ?></strong></div>
  <?php else: ?>
    <div class="stat-card <?= e($grad) ?>"><small>จำนวนรายการ</small><strong><?= (int) $stats['count'] ?></strong></div>
    <div class="stat-card <?= e($grad) ?>"><small>จำนวนชั่วโมง</small><strong><?= e(rtrim(rtrim(number_format((float)$stats['hours'],1,'.',''),'0'),'.')) ?></strong></div>
    <div class="stat-card <?= e($grad) ?>"><small>หน่วยงานบ่อยสุด</small><strong style="font-size:16px"><?= e($stats['org']) ?></strong></div>
  <?php endif; ?>
</div>

<div class="card-soft"><div class="card-body">
  <form id="pf-filter" class="dt-filter-bar">
    <input name="q" class="form-control" placeholder="ค้นหา...">
    <?php if (!empty($people)): ?>
    <select name="user_id" class="form-select">
      <option value="">แสดงทั้งหมด</option>
      <?php foreach ($people as $p): ?>
        <option value="<?= (int)$p['id'] ?>"><?= e(trim(($p['title']??'').$p['first_name'].' '.$p['last_name'])) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <select name="year" class="form-select">
      <option value="">ทุกปีการศึกษา</option>
      <?php foreach ($years as $y): ?><option value="<?= (int)$y ?>"><?= (int)$y ?></option><?php endforeach; ?>
    </select>
  </form>
  <table id="pf-datatable" class="table ab-datatable w-100" data-mode="<?= e($mode) ?>" data-ajax-url="<?= e(url('/admin/portfolio/'.$type.'/datatable')) ?>">
    <thead><tr>
      <th>ชื่อบุคคล</th>
      <th><?= $mode==='innovation'?'ชื่อนวัตกรรม':($mode==='research'?'ชื่อวิจัย':($mode==='plc'?'ชื่อกิจกรรม':'รายการ')) ?></th>
      <?php if ($mode==='innovation'): ?><th>ปี พ.ศ.</th><th>สถานะ</th>
      <?php elseif ($mode==='research'): ?><th>ปีการศึกษา</th><th>ประเภทการวิจัย</th>
      <?php elseif ($mode==='plc'): ?><th>ครั้งที่</th><th>ปีการศึกษา</th>
      <?php else: ?><th>วันที่</th><th>ชั่วโมง</th><?php endif; ?>
      <th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
