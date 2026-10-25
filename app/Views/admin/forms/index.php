<div class="page-head">
  <div>
    <h1>แบบฟอร์มออนไลน์</h1>
    <p class="text-muted small mb-0">
      สร้างแบบฟอร์มเก็บข้อมูลบนหน้าเว็บสาธารณะ (เมนู “แบบฟอร์ม”) · แยกจากหน้า “ติดต่อ” ที่เก็บข้อความติดต่อในเมนูเนื้อหาเว็บ
    </p>
  </div>
  <a class="btn-add" href="<?= url('/admin/forms/create') ?>"><i class="fa fa-plus"></i> สร้างแบบฟอร์ม</a>
</div>
<div class="card-soft"><div class="card-body">
  <table id="forms-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/forms/datatable')) ?>">
    <thead><tr>
      <th>ชื่อ</th><th>ลิงก์หน้าเว็บ</th><th>สถานะ</th><th>อัปเดต</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
