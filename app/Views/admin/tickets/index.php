<div class="page-head"><h1>ระบบบันทึก</h1>
  <a class="btn-add" href="<?= url('/admin/tickets/create') ?>"><i class="fa fa-plus"></i> เปิดบันทึก</a>
</div>
<div class="card-soft"><div class="card-body">
  <form id="tickets-filter" class="dt-filter-bar">
    <input name="q" class="form-control" placeholder="ค้นหาเลขที่ / หัวข้อ">
    <select name="status" class="form-select">
      <option value="">ทุกสถานะ</option>
      <option value="open">open</option>
      <option value="in_progress">in_progress</option>
      <option value="replied">replied</option>
      <option value="closed">closed</option>
    </select>
  </form>
  <table id="tickets-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/tickets/datatable')) ?>">
    <thead><tr>
      <th>เลขที่</th><th>หัวข้อ</th><th>ผู้ส่ง</th><th>สถานะ</th><th>วันที่</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
