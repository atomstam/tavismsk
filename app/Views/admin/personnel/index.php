<div class="page-head">
  <div><h1>บุคลากร</h1></div>
  <div class="d-flex flex-wrap gap-2">
    <?php if (can_perm('personnel','create')): ?>
      <a class="btn btn-outline-secondary" href="<?= url('/admin/personnel/import-template') ?>">
        <i class="fa fa-file-excel"></i> ดาวน์โหลดเทมเพลต
      </a>
      <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#personnel-import-modal">
        <i class="fa fa-file-import"></i> นำเข้า .xlsx
      </button>
      <a class="btn-add" href="<?= url('/admin/personnel/create') ?>"><i class="fa fa-plus"></i> เพิ่มบุคลากร</a>
    <?php endif; ?>
    <?php if (can_perm('personnel','export')): ?>
      <a class="btn btn-outline-primary" href="<?= url('/admin/personnel/export') ?>"><i class="fa fa-download"></i> ส่งออก</a>
    <?php endif; ?>
  </div>
</div>
<div class="card-soft"><div class="card-body">
  <form id="personnel-filter" class="dt-filter-bar flex-wrap">
    <?php partial('partials/registration_list_filters', [
      'searchPlaceholder' => 'ค้นหาชื่อ / รหัส / โทร / หน่วยงาน',
      'showRole' => false,
      'memberTypes' => $memberTypes ?? [],
      'personalTypes' => $personalTypes ?? [],
      'schools' => $schools ?? [],
    ]); ?>
  </form>
  <table id="personnel-datatable" class="table ab-datatable dt-list-fit w-100" data-ajax-url="<?= e(url('/admin/personnel/datatable')) ?>">
    <thead><tr>
      <th>รหัส</th><th>ชื่อ</th><th>ประเภท</th><th>ตำแหน่ง</th><th>โรงเรียน / หน่วยงาน</th><th>จังหวัด</th><th>โทร</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>

<?php if (can_perm('personnel','create')): ?>
<div class="modal fade" id="personnel-import-modal" tabindex="-1" aria-labelledby="personnel-import-title" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" enctype="multipart/form-data" action="<?= url('/admin/personnel/import') ?>">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="personnel-import-title">นำเข้าบุคลากรจาก Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">
          ใช้เทมเพลตที่ดาวน์โหลดจากระบบ แถวแรกคือหัวตาราง
          ถ้ามีเลขบัตรประชาชนซ้ำ ระบบจะอัปเดตข้อมูลเดิม
        </p>
        <label class="form-label">ไฟล์ .xlsx</label>
        <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-success"><i class="fa fa-upload"></i> นำเข้า</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
