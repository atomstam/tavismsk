<div class="page-head"><h1>เปิดบันทึกใหม่</h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" enctype="multipart/form-data" action="<?= url('/admin/tickets') ?>">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">หัวข้อ</label><input name="subject" required class="form-control"></div>
  <div class="mb-3"><label class="form-label">รายละเอียด</label><textarea name="message" required class="form-control" rows="5"></textarea></div>
  <div class="mb-3"><label class="form-label">ความสำคัญ</label>
    <select name="priority" class="form-select"><option value="normal">ปกติ</option><option value="high">สูง</option><option value="low">ต่ำ</option></select></div>
  <div class="mb-3"><label class="form-label">แนบไฟล์</label><input type="file" name="attachment" class="form-control"></div>
  <button class="btn-add">ส่งบันทึก</button>
</form>
</div></div>
