<div class="page-head">
  <div>
    <p class="page-kicker">ENROLLMENTS</p>
    <h1>การลงทะเบียนอบรม</h1>
  </div>
  <?php if (can_perm('enrollments', 'create')): ?>
  <form method="post" action="<?= url('/admin/enrollments') ?>" class="d-flex gap-2 align-items-end">
    <?= csrf_field() ?>
    <div>
      <label class="form-label small mb-0">ลงทะเบียน (User ID)</label>
      <input type="number" name="user_id" class="form-control form-control-sm" value="<?= (int) auth()->id() ?>" min="1" required>
    </div>
    <button class="btn-add" type="submit"><i class="fa fa-plus"></i> ลงทะเบียน</button>
  </form>
  <?php endif; ?>
</div>

<form id="enrollments-filter" class="row g-2 mb-3 align-items-end">
  <div class="col-md-3">
    <label class="form-label small mb-0">สถานะ</label>
    <select name="status" class="form-select form-select-sm">
      <option value="">ทั้งหมด</option>
      <option value="pending">pending</option>
      <option value="approved">approved</option>
      <option value="in_progress">in_progress</option>
      <option value="completed">completed</option>
      <option value="failed">failed</option>
      <option value="cancelled">cancelled</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa fa-filter"></i> กรอง</button>
  </div>
</form>

<div class="card-soft"><div class="card-body">
  <table id="enrollments-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/enrollments/datatable')) ?>">
    <thead>
      <tr>
        <th>เลขที่</th>
        <th>ผู้เข้ารับการพัฒนา</th>
        <th>หน่วยงาน</th>
        <th>ชั่วโมง</th>
        <th>คะแนน</th>
        <th>สถานะ</th>
        <th></th>
      </tr>
    </thead>
  </table>
</div></div>
