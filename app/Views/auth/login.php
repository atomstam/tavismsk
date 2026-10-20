<?php
$sys = (string) app_brand('short_name');
$org = (string) setting('org_name');
$aff = (string) setting('org_affiliation');
$oauthProviders = $oauthProviders ?? [];
$oauthBtn = static function (string $providerId, string $label, string $cssClass) use ($oauthProviders): void {
    $on = isset($oauthProviders[$providerId]);
    $href = $on ? url('/auth/' . $providerId) : '#';
    $cls = 'login-oauth-btn ' . $cssClass . ($on ? '' : ' login-oauth-btn--disabled');
    $attr = $on ? '' : ' aria-disabled="true" title="ยังไม่ได้ตั้งค่า OAuth ใน .env"';
    echo '<a href="' . e($href) . '" class="' . e($cls) . '"' . $attr . '>';
    if ($providerId === 'facebook') {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
    } elseif ($providerId === 'google') {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>';
    } else {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>';
    }
    echo e($label) . '</a>';
};
?>
<div class="auth-wrap">
  <div class="auth-visual">
    <div>
      <div class="auth-brand">
        <span class="auth-brand-icon"><i class="fa fa-shield-halved"></i></span>
        <span class="auth-brand-text">
          <span class="name"><?= e($sys) ?></span>
          <span class="tag"><?= e(app_brand('nav_tag')) ?></span>
        </span>
      </div>
      <p class="mb-2"><?= e($org) ?><?= $aff !== '' ? ' · ' . e($aff) : '' ?></p>
      <h1><?= e(app_brand('name')) ?></h1>
      <p class="mt-3"><?= e(app_brand('full_name')) ?></p>
    </div>
  </div>
  <div class="auth-panel">
    <div class="auth-form">
      <p class="auth-kicker">ยินดีต้อนรับ</p>
      <h2 class="auth-title">เข้าสู่ระบบ</h2>
      <p class="auth-sub">สำหรับผู้ดูแลระบบ วิทยากร และผู้เข้ารับการพัฒนา</p>
      <form method="post" action="<?= url('/login') ?>" class="auth-fields" onsubmit="var b=this.querySelector('.auth-submit'); if(b){ b.disabled=true; b.innerHTML='<i class=\'fa fa-spinner fa-spin\'></i> กำลังตรวจสอบ...'; }">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">ชื่อผู้ใช้ หรือ อีเมล</label>
          <div class="auth-input"><i class="fa fa-user"></i>
            <input class="form-control" name="username" value="<?= e((string) old('username')) ?>" required autofocus autocomplete="username">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">รหัสผ่าน</label>
          <div class="auth-input auth-input--password">
            <i class="fa fa-lock" aria-hidden="true"></i>
            <input class="form-control" type="password" name="password" id="login-password" required autocomplete="current-password">
            <button type="button" class="auth-password-toggle" id="login-password-toggle" aria-label="แสดงรหัสผ่าน" aria-pressed="false" title="แสดงรหัสผ่าน">
              <i class="fa fa-eye" aria-hidden="true"></i>
            </button>
          </div>
        </div>
        <?php $portal = (string) old('portal', 'user'); ?>
        <div class="login-role-group mb-3">
          <label class="login-role-label">
            <i class="fa fa-user-tag"></i> เลือกบทบาทการเข้าสู่ระบบ
          </label>
          <div class="role-options">
            <label class="role-option role-admin">
              <input type="radio" name="portal" value="admin" class="d-none" <?= $portal === 'admin' ? 'checked' : '' ?> required>
              <span class="role-card">
                <i class="fa fa-user-shield"></i>
                <span>ผู้ดูแลระบบ</span>
              </span>
            </label>
            <label class="role-option role-trainer">
              <input type="radio" name="portal" value="trainer" class="d-none" <?= $portal === 'trainer' ? 'checked' : '' ?>>
              <span class="role-card">
                <i class="fa fa-chalkboard-user"></i>
                <span>วิทยากร</span>
              </span>
            </label>
            <label class="role-option role-user">
              <input type="radio" name="portal" value="user" class="d-none" <?= $portal === 'user' ? 'checked' : '' ?>>
              <span class="role-card">
                <i class="fa fa-user-graduate"></i>
                <span>ผู้รับการพัฒนา</span>
              </span>
            </label>
          </div>
        </div>
        <div class="auth-meta">
          <label class="form-check"><input class="form-check-input" type="checkbox" name="remember" value="1"> จดจำฉัน</label>
          <a href="<?= url('/forgot-password') ?>">ลืมรหัสผ่าน?</a>
        </div>
        <button class="auth-submit" type="submit"><i class="fa fa-right-to-bracket"></i> เข้าสู่ระบบ</button>
      </form>

      <div class="login-oauth">
        <p class="login-oauth-divider"><span>หรือเข้าสู่ระบบด้วย</span></p>
        <div class="login-oauth-buttons">
          <?php $oauthBtn('facebook', 'Facebook', 'login-oauth-btn--facebook'); ?>
          <?php $oauthBtn('google', 'Google', 'login-oauth-btn--google'); ?>
          <?php $oauthBtn('line', 'LINE', 'login-oauth-btn--line'); ?>
        </div>
        <p class="login-oauth-hint">ต้องมีบัญชีในระบบและอีเมลตรงกับที่ลงทะเบียนไว้</p>
      </div>

      <p class="mt-3 mb-0 text-center"><a href="<?= url('/register') ?>">ยังไม่มีบัญชี? สมัครสมาชิก</a></p>
      <a class="auth-back" href="<?= url('/') ?>"><i class="fa fa-arrow-left"></i> กลับหน้าเว็บไซต์</a>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.login-oauth-btn--disabled').forEach(function (el) {
  el.addEventListener('click', function (e) { e.preventDefault(); });
});
(function () {
  var input = document.getElementById('login-password');
  var btn = document.getElementById('login-password-toggle');
  if (!input || !btn) return;
  btn.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    btn.setAttribute('aria-label', show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
    btn.title = show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน';
    var icon = btn.querySelector('i');
    if (icon) {
      icon.classList.toggle('fa-eye', !show);
      icon.classList.toggle('fa-eye-slash', show);
    }
  });
})();
</script>
