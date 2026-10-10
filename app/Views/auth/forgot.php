<div class="auth-wrap">
  <div class="auth-visual"><div><h1>ลืมรหัสผ่าน</h1><p>กรอกอีเมลเพื่อรับลิงก์ตั้งรหัสผ่านใหม่</p></div></div>
  <div class="auth-panel"><div class="auth-form">
    <h2 class="auth-title">ขอรีเซ็ตรหัสผ่าน</h2>
    <form method="post" action="<?= url('/forgot-password') ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">อีเมล</label>
        <div class="auth-input"><i class="fa fa-envelope"></i><input type="email" name="email" class="form-control" required></div>
      </div>
      <button class="auth-submit">ส่งลิงก์</button>
    </form>
    <div class="mt-3"><a href="<?= url('/login') ?>">กลับเข้าสู่ระบบ</a></div>
  </div></div>
</div>
