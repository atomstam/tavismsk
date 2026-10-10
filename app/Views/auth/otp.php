<div class="auth-wrap">
  <div class="auth-visual"><div><h1>ยืนยันตัวตน 2FA</h1><p>ระบบได้ส่งรหัส OTP ไปยังอีเมลของคุณแล้ว</p></div></div>
  <div class="auth-panel"><div class="auth-form">
    <h2 class="auth-title">กรอกรหัส OTP</h2>
    <p class="text-muted">ส่งไปที่ <?= e($email ?? 'อีเมลของคุณ') ?></p>
    <form method="post" action="<?= url('/verify-otp') ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><div class="auth-input"><i class="fa fa-key"></i>
        <input class="form-control" name="otp" maxlength="6" required autofocus style="letter-spacing:8px;font-weight:700"></div></div>
      <button class="auth-submit">ยืนยัน</button>
    </form>
  </div></div>
</div>
