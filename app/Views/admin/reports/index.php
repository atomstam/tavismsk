<div class="page-head">
  <div><p class="page-kicker">REPORTS</p><h1>รายงาน<?= e($mod['title']) ?></h1></div>
</div>
<div class="card-soft"><div class="card-body">
  <form id="reports-filter" class="dt-filter-bar">
    <input name="q" class="form-control" placeholder="ค้นหาหัวข้อ/สถานที่...">
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
      <?php foreach ($years as $y): ?><option value="<?= $y ?>"><?= $y ?></option><?php endforeach; ?>
    </select>
  </form>
  <table id="reports-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/reports/'.$type.'/datatable')) ?>">
    <thead><tr>
      <th>ชื่อบุคคล</th><th>รายการ</th><th>วันที่</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
