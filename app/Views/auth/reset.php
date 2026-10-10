<div class="auth-wrap">
  <div class="auth-visual"><div><h1>ตั้งรหัสผ่านใหม่</h1></div></div>
  <div class="auth-panel"><div class="auth-form">
    <h2 class="auth-title">รหัสผ่านใหม่</h2>
    <form method="post" action="<?= url('/reset-password') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
      <div class="mb-3"><label class="form-label">รหัสผ่านใหม่</label>
        <div class="auth-input"><i class="fa fa-lock"></i><input type="password" name="password" class="form-control" minlength="8" required></div></div>
      <div class="mb-3"><label class="form-label">ยืนยันรหัสผ่าน</label>
        <div class="auth-input"><i class="fa fa-lock"></i><input type="password" name="password_confirmation" class="form-control" minlength="8" required></div></div>
      <button class="auth-submit">บันทึก</button>
    </form>
  </div></div>
</div>
