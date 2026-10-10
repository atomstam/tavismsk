<?php
$sys = (string) app_brand('short_name');
$org = (string) setting('org_name');
$fav = setting('favicon');
?>
<!DOCTYPE html>
<html lang="th" data-web-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="theme-color" content="#f8fafc" id="meta-theme-color">
  <title><?= e($title ?? 'เข้าสู่ระบบ') ?> | <?= e(app_brand('name')) ?></title>
  <script>
  (function () {
    try {
      var t = localStorage.getItem('tavis-web-theme');
      if (t !== 'light' && t !== 'dark') t = 'light';
      document.documentElement.setAttribute('data-web-theme', t);
      document.documentElement.style.colorScheme = t;
    } catch (e) {}
  })();
  </script>
  <?php if ($fav): ?><link rel="icon" href="<?= e(upload_url((string) $fav)) ?>"><?php endif; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>?v=6.5.2">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
  <link rel="stylesheet" href="<?= asset('css/auth-frontend.css') ?>?v=11">
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
</head>
<body class="auth-page auth-light">
<script>
(function () {
  var t = document.documentElement.getAttribute('data-web-theme') || 'light';
  document.body.classList.remove('auth-dark', 'auth-light');
  document.body.classList.add(t === 'light' ? 'auth-light' : 'auth-dark');
})();
</script>
<button type="button" class="auth-theme-toggle" id="auth-theme-toggle" data-web-theme-toggle
        title="สลับธีมสว่าง/มืด" aria-label="สลับธีมสว่างหรือมืด">
  <i class="fa fa-moon" aria-hidden="true"></i>
  <i class="fa fa-sun" aria-hidden="true"></i>
</button>
<?php if (!empty($flash['message'])):
  $ft = (string) ($flash['type'] ?? 'success');
  $flashId = match ($ft) {
    'error' => 'error',
    'warning' => 'warning',
    'info' => 'info',
    default => 'success',
  };
?>
  <div id="flash-<?= e($flashId) ?>" data-msg="<?= e($flash['message']) ?>" hidden></div>
<?php endif; ?>
<?= $content ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= asset('js/app.js') ?>?v=7"></script>
<script>
(function () {
  var KEY = 'tavis-web-theme';
  function applyTheme(theme) {
    var t = theme === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-web-theme', t);
    document.documentElement.style.colorScheme = t;
    document.body.classList.remove('auth-dark', 'auth-light');
    document.body.classList.add(t === 'light' ? 'auth-light' : 'auth-dark');
    var meta = document.getElementById('meta-theme-color');
    if (meta) meta.setAttribute('content', t === 'light' ? '#f8fafc' : '#0b1220');
    document.querySelectorAll('[data-web-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-pressed', t === 'light' ? 'true' : 'false');
      btn.title = t === 'light' ? 'สลับเป็นธีมมืด' : 'สลับเป็นธีมสว่าง';
    });
    try { localStorage.setItem(KEY, t); } catch (e) {}
  }
  applyTheme(document.documentElement.getAttribute('data-web-theme') || 'light');
  document.querySelectorAll('[data-web-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var cur = document.documentElement.getAttribute('data-web-theme') || 'light';
      applyTheme(cur === 'dark' ? 'light' : 'dark');
    });
  });
})();
</script>
</body>
</html>
