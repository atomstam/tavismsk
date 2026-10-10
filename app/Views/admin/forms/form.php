<?php $r=$row??[]; $fields=$fields??[]; ?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" action="<?= $row?url('/admin/forms/'.$row['id']):url('/admin/forms') ?>">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">ชื่อแบบฟอร์ม</label><input name="title" required class="form-control" value="<?= e($r['title']??'') ?>"></div>
  <div class="mb-3"><label class="form-label">คำอธิบาย</label><textarea name="description" class="form-control"><?= e($r['description']??'') ?></textarea></div>
  <h6>ฟิลด์</h6>
  <div id="fields">
    <?php if (!$fields): $fields=[['label'=>'','type'=>'text','options'=>'','required'=>0]]; endif; ?>
    <?php foreach ($fields as $f): ?>
    <div class="row g-2 mb-2 field-row">
      <div class="col-md-4"><input name="field_label[]" class="form-control" placeholder="ป้ายชื่อ" value="<?= e($f['label']??'') ?>"></div>
      <div class="col-md-3">
        <select name="field_type[]" class="form-select">
          <?php foreach (['text','textarea','date','time','number','dropdown','checkbox','radio','file','signature'] as $t): ?>
            <option value="<?= $t ?>" <?= (($f['type']??'')===$t)?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><input name="field_options[]" class="form-control" placeholder="ตัวเลือก คั่นด้วย ," value="<?= e($f['options']??'') ?>"></div>
      <div class="col-md-2"><label class="form-check"><input type="checkbox" name="field_required[]" value="1" <?= !empty($f['required'])?'checked':'' ?>> จำเป็น</label></div>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-light mb-3" onclick="document.getElementById('fields').insertAdjacentHTML('beforeend', document.querySelector('.field-row').outerHTML)">+ เพิ่มฟิลด์</button>
  <div><button class="btn-add">บันทึกแบบฟอร์ม</button></div>
</form>
</div></div>
