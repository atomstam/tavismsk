<div class="page-head">
  <div>
    <p class="page-kicker">ORGANIZATION</p>
    <h1>โครงสร้างหน่วยงาน</h1>
  </div>
</div>
<p class="text-muted">แยกสารสนเทศตาม กระทรวง → หน่วยงาน → เขตพื้นที่ → โรงเรียน สำหรับใช้งานทั่วประเทศ</p>

<form id="org-units-filter" class="row g-2 mb-3 align-items-end">
  <div class="col-md-3">
    <label class="form-label small mb-0">ประเภท</label>
    <select name="org_type" class="form-select form-select-sm">
      <option value="">ทั้งหมด</option>
      <?php foreach (($types ?? []) as $k => $label): ?>
        <option value="<?= e($k) ?>"><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa fa-filter"></i> กรอง</button>
  </div>
</form>

<div class="card-soft"><div class="card-body">
  <table id="org-units-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/org-units/datatable')) ?>">
    <thead>
      <tr>
        <th>รหัส</th>
        <th>ชื่อหน่วยงาน</th>
        <th>ประเภท</th>
        <th>สังกัด</th>
        <th>จังหวัด</th>
        <th>ผู้ใช้</th>
      </tr>
    </thead>
  </table>
</div></div>
