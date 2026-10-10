<div class="page-head"><h1>แบบฟอร์มออนไลน์</h1>
  <a class="btn-add" href="<?= url('/admin/forms/create') ?>"><i class="fa fa-plus"></i> สร้างแบบฟอร์ม</a>
</div>
<div class="card-soft"><div class="card-body">
  <table id="forms-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/forms/datatable')) ?>">
    <thead><tr>
      <th>ชื่อ</th><th>ลิงก์</th><th>สถานะ</th><th>อัปเดต</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
