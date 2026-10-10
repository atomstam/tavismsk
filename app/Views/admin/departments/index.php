<div class="page-head"><div><h1>หน่วยงาน / กลุ่มงาน</h1></div>
  <?php if (can_perm('departments','create')): ?><a class="btn-add" href="<?= url('/admin/departments/create') ?>"><i class="fa fa-plus"></i> เพิ่มหน่วยงาน</a><?php endif; ?>
</div>
<div class="card-soft"><div class="card-body">
  <table id="departments-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/departments/datatable')) ?>">
    <thead><tr>
      <th>ลำดับ</th><th>ชื่อ</th><th>หัวหน้า</th><th>สี</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
