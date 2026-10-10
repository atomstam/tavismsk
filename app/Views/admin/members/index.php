<div class="page-head">
  <div><h1>สมาชิกชมรม</h1></div>
  <div class="d-flex flex-wrap gap-2">
    <?php if (can_perm('members','create')): ?>
      <a class="btn-add" href="<?= url('/admin/members/create') ?>"><i class="fa fa-plus"></i> เพิ่มสมาชิก</a>
    <?php endif; ?>
  </div>
</div>
<div class="card-soft"><div class="card-body">
  <form id="members-filter" class="dt-filter-bar">
    <input name="q" class="form-control" placeholder="ค้นหาชื่อ / รหัส / ชั้น / โทร">
  </form>
  <table id="members-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/members/datatable')) ?>">
    <thead><tr>
      <th>รหัส</th><th>ชื่อ</th><th>ชั้น</th><th>ห้อง</th><th>ปีการศึกษา</th><th>โทร</th><th>สถานะ</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
