<?php
$u = $profile ?? [];
?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" enctype="multipart/form-data" action="<?= url('/admin/profile') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">ชื่อผู้ใช้</label>
      <input class="form-control" value="<?= e($u['username'] ?? '') ?>" readonly>
      <div class="form-text">ชื่อผู้ใช้ไม่สามารถเปลี่ยนได้</div>
    </div>
    <div class="col-md-4">
      <label class="form-label">อีเมล</label>
      <input name="email" type="email" class="form-control" value="<?= e((string) old('email', $u['email'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">โทร</label>
      <input name="phone" class="form-control" value="<?= e((string) old('phone', $u['phone'] ?? '')) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">คำนำหน้า</label>
      <input name="title" class="form-control" value="<?= e((string) old('title', $u['title'] ?? '')) ?>">
    </div>
    <div class="col-md-5">
      <label class="form-label">ชื่อ *</label>
      <input name="first_name" class="form-control" required value="<?= e((string) old('first_name', $u['first_name'] ?? '')) ?>">
    </div>
    <div class="col-md-5">
      <label class="form-label">นามสกุล *</label>
      <input name="last_name" class="form-control" required value="<?= e((string) old('last_name', $u['last_name'] ?? '')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">รหัสผ่านใหม่</label>
      <input name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" placeholder="เว้นว่างหากไม่เปลี่ยน">
    </div>
    <div class="col-md-6">
      <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
      <input name="password_confirmation" type="password" class="form-control" minlength="8" autocomplete="new-password">
    </div>
    <?php partial('partials/image_crop_field', [
      'type' => 'avatar',
      'name' => 'avatar',
      'tokenName' => 'avatar_crop_token',
      'label' => 'รูปโปรไฟล์',
      'existing' => $u['avatar'] ?? '',
      'colClass' => 'col-md-6',
    ]); ?>
    <div class="col-md-6 d-flex align-items-end">
      <div class="text-muted small">
        บทบาท: <strong><?= e(role_name((string) ($u['role'] ?? ''))) ?></strong>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2 mt-3">
    <button class="btn-add" type="submit"><i class="fa fa-save"></i> บันทึก</button>
    <a class="btn btn-outline-secondary" href="<?= url('/admin/dashboard') ?>">ยกเลิก</a>
  </div>
</form>
</div></div>
