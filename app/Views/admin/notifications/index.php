<div class="page-head"><h1>การแจ้งเตือน</h1>
<form method="post" action="<?= url('/admin/notifications/read-all') ?>"><?= csrf_field() ?><button class="btn btn-light">อ่านทั้งหมด</button></form>
</div>
<div class="card-soft"><div class="card-body">
  <table id="notifications-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/notifications/datatable')) ?>">
    <thead><tr>
      <th>หัวข้อ</th><th>รายละเอียด</th><th>เวลา</th><th>สถานะ</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
