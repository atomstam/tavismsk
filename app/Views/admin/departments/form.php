<?php $r=$row??[]; ?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" enctype="multipart/form-data" action="<?= $row?url('/admin/departments/'.$row['id']):url('/admin/departments') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">ลำดับ</label><input type="number" name="sort_order" class="form-control" value="<?= e((string)($r['sort_order']??0)) ?>"></div>
    <div class="col-md-5"><label class="form-label">ชื่อหน่วยงาน *</label><input name="name" required class="form-control" value="<?= e($r['name']??'') ?>"></div>
    <div class="col-md-4"><label class="form-label">หัวหน้าหน่วยงาน</label><input name="head_name" class="form-control" value="<?= e($r['head_name']??'') ?>"></div>
    <div class="col-md-4"><label class="form-label">สีประจำหน่วยงาน</label><input type="color" name="color" class="form-control form-control-color" value="<?= e($r['color']??'#2563eb') ?>"></div>
    <div class="col-md-8"><label class="form-label">คำอธิบาย</label><textarea name="description" class="form-control"><?= e($r['description']??'') ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control" accept="image/*"></div>
  </div>
  <button class="btn-add mt-3">บันทึก</button>
</form>
</div></div>
