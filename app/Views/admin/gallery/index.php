<div class="page-head">
  <div><h1>แกลเลอรี่กิจกรรม</h1><p class="text-muted mb-0">จัดการอัลบั้มภาพและวิดีโอ</p></div>
  <?php if (can_perm('portfolio','create')): ?>
    <a class="btn-add" href="<?= url('/admin/gallery/create') ?>"><i class="fa fa-plus"></i> เพิ่มอัลบั้ม</a>
  <?php endif; ?>
</div>
<div class="card-soft"><div class="card-body">
  <table id="gallery-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/gallery/datatable')) ?>">
    <thead><tr>
      <th>วันที่</th><th>ชื่ออัลบั้ม</th><th>หมวด</th><th>ปีการศึกษา</th><th>ไฟล์</th><th>สถานะ</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
