<div class="page-head">
  <div>
    <p class="page-kicker">NOTIFICATIONS</p>
    <h1>การแจ้งเตือน</h1>
    <p class="text-muted mb-0">ผลงานใหม่ · ภาระงานรอตรวจ · ผลการให้คะแนน</p>
  </div>
  <form method="post" action="<?= url('/admin/notifications/read-all') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-outline-primary" type="submit"><i class="fa fa-check-double"></i> อ่านทั้งหมด</button>
  </form>
</div>
<div class="card-soft"><div class="card-body">
  <table id="notifications-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/notifications/datatable')) ?>">
    <thead>
      <tr>
        <th class="col-primary">หัวข้อ</th>
        <th class="col-wrap">รายละเอียด</th>
        <th>เวลา</th>
        <th>สถานะ</th>
        <th class="col-actions">จัดการ</th>
      </tr>
    </thead>
  </table>
</div></div>
