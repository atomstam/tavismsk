<?php
$r = $row ?? [];
$fields = $fields ?? [];
$isEdit = !empty($r['id']);
$publicUrl = $isEdit && !empty($r['slug']) ? url('/forms/' . $r['slug']) : null;
?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" action="<?= $isEdit ? url('/admin/forms/' . (int) $r['id']) : url('/admin/forms') ?>">
  <?= csrf_field() ?>

  <?php if ($publicUrl): ?>
    <div class="alert alert-info py-2 small mb-3">
      ลิงก์หน้าเว็บ:
      <a href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"><?= e($publicUrl) ?></a>
      · แบบฟอร์มที่สถานะ <strong>เปิด</strong> จะแสดงในเมนูหลักหน้าเว็บใต้ “แบบฟอร์ม”
    </div>
  <?php else: ?>
    <div class="alert alert-light border py-2 small mb-3">
      เมื่อบันทึกและตั้งสถานะเป็น <strong>เปิด</strong> แบบฟอร์มจะปรากฏในเมนูหลักหน้าเว็บสาธารณะ (แยกจากหน้า “ติดต่อ”)
    </div>
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">ชื่อแบบฟอร์ม</label>
    <input name="title" required class="form-control" value="<?= e($r['title'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">คำอธิบาย</label>
    <textarea name="description" class="form-control" rows="3"><?= e($r['description'] ?? '') ?></textarea>
  </div>
  <div class="mb-3" style="max-width:220px">
    <label class="form-label">สถานะบนหน้าเว็บ</label>
    <select name="status" class="form-select">
      <option value="1" <?= ((int) ($r['status'] ?? 1) === 1) ? 'selected' : '' ?>>เปิด (แสดงในเมนูเว็บ)</option>
      <option value="0" <?= ((int) ($r['status'] ?? 1) === 0) ? 'selected' : '' ?>>ปิด</option>
    </select>
  </div>

  <h6>ฟิลด์</h6>
  <div id="fields">
    <?php if (!$fields): $fields = [['label' => '', 'type' => 'text', 'options' => '', 'required' => 0]]; endif; ?>
    <?php foreach ($fields as $i => $f): ?>
    <div class="row g-2 mb-2 field-row">
      <div class="col-md-4"><input name="field_label[]" class="form-control" placeholder="ป้ายชื่อ" value="<?= e($f['label'] ?? '') ?>"></div>
      <div class="col-md-3">
        <select name="field_type[]" class="form-select">
          <?php foreach (['text','textarea','date','time','number','dropdown','checkbox','radio','file','signature'] as $t): ?>
            <option value="<?= $t ?>" <?= (($f['type'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><input name="field_options[]" class="form-control" placeholder="ตัวเลือก คั่นด้วย ," value="<?= e($f['options'] ?? '') ?>"></div>
      <div class="col-md-2">
        <label class="form-check">
          <input type="checkbox" name="field_required[<?= (int) $i ?>]" value="1" <?= !empty($f['required']) ? 'checked' : '' ?>> จำเป็น
        </label>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-light mb-3" id="btn-add-field">+ เพิ่มฟิลด์</button>
  <div><button class="btn-add">บันทึกแบบฟอร์ม</button>
    <a class="btn btn-light" href="<?= url('/admin/forms') ?>">กลับ</a>
  </div>
</form>
</div></div>
<script>
(function () {
  var box = document.getElementById('fields');
  var btn = document.getElementById('btn-add-field');
  if (!box || !btn) return;
  btn.addEventListener('click', function () {
    var idx = box.querySelectorAll('.field-row').length;
    var html = box.querySelector('.field-row').outerHTML;
    box.insertAdjacentHTML('beforeend', html);
    var row = box.lastElementChild;
    row.querySelectorAll('input').forEach(function (el) {
      if (el.type === 'checkbox') {
        el.name = 'field_required[' + idx + ']';
        el.checked = false;
      } else {
        el.value = '';
      }
    });
    var sel = row.querySelector('select');
    if (sel) sel.selectedIndex = 0;
  });
})();
</script>
