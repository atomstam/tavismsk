<div class="page-head">
  <div>
    <p class="page-kicker">LMS</p>
    <h1>จัดการบทเรียน (Pretest · Content · Task · Posttest)</h1>
  </div>
  <a class="btn btn-outline-primary" href="<?= url('/admin/learn') ?>"><i class="fa fa-graduation-cap"></i> มุมมองผู้เรียน</a>
</div>

<p class="text-muted mb-3">วิทยากรเพิ่มข้อสอบ Pretest/Posttest เนื้อหา (PDF/Word/Excel, Google Drive, YouTube) และภาระงาน — ระบบจะรวมเป็นความก้าวหน้า 100% ของผู้เรียน</p>

<div class="card-soft"><div class="card-body">
  <table id="training-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/training/datatable')) ?>">
    <thead>
      <tr>
        <th>แผนที่</th>
        <th>ชื่อโมดูล</th>
        <th>Pretest</th>
        <th>เนื้อหา</th>
        <th>ภาระงาน</th>
        <th>Posttest</th>
        <th></th>
      </tr>
    </thead>
  </table>
</div></div>
