<?php
$r = $row ?? [];
$isEdit = !empty($row['id']);
$years = $years ?? [current_academic_year()];
$prefixes = $prefixes ?? [];
?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" action="<?= $isEdit ? url('/admin/members/' . (int)$row['id']) : url('/admin/members') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">รหัสนักเรียน</label>
      <input name="student_code" class="form-control" value="<?= e($r['student_code'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">เลขบัตรประชาชน</label>
      <input name="id_card" class="form-control" maxlength="13" value="<?= e($r['id_card'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">คำนำหน้า</label>
      <select name="title" class="form-select">
        <option value="">-</option>
        <?php foreach ($prefixes as $p): $t = is_array($p) ? ($p['title'] ?? '') : (string)$p; ?>
          <option value="<?= e($t) ?>" <?= (($r['title'] ?? '') === $t) ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">ชื่อ *</label>
      <input name="first_name" required class="form-control" value="<?= e($r['first_name'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">นามสกุล *</label>
      <input name="last_name" required class="form-control" value="<?= e($r['last_name'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">เพศ</label>
      <select name="gender" class="form-select">
        <option value="">-</option>
        <option value="male" <?= (($r['gender'] ?? '') === 'male') ? 'selected' : '' ?>>ชาย</option>
        <option value="female" <?= (($r['gender'] ?? '') === 'female') ? 'selected' : '' ?>>หญิง</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">วันเกิด</label>
      <input type="date" name="birth_date" class="form-control" value="<?= e($r['birth_date'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">ชั้น</label>
      <input name="grade_level" class="form-control" placeholder="ม.4" value="<?= e($r['grade_level'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">ห้อง</label>
      <input name="room" class="form-control" value="<?= e($r['room'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">ปีการศึกษา</label>
      <select name="academic_year" class="form-select">
        <?php foreach ($years as $y): $yv = is_array($y) ? (int)($y['year_be'] ?? $y) : (int)$y; ?>
          <option value="<?= $yv ?>" <?= ((int)($r['academic_year'] ?? current_academic_year()) === $yv) ? 'selected' : '' ?>><?= $yv ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">โทร</label>
      <input name="phone" class="form-control" value="<?= e($r['phone'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">อีเมล</label>
      <input name="email" type="email" class="form-control" value="<?= e($r['email'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">สถานะ</label>
      <select name="status" class="form-select">
        <option value="1" <?= ((int)($r['status'] ?? 1) === 1) ? 'selected' : '' ?>>ใช้งาน</option>
        <option value="0" <?= ((int)($r['status'] ?? 1) === 0) ? 'selected' : '' ?>>ปิด</option>
      </select>
    </div>
  </div>
  <div class="mt-4 d-flex gap-2">
    <button class="btn-add" type="submit"><i class="fa fa-save"></i> บันทึก</button>
    <a class="btn btn-light" href="<?= url('/admin/members') ?>">ยกเลิก</a>
  </div>
</form>
</div></div>
