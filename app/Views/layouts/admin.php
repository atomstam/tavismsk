<?php
$user = auth()->user();
$unread = 0;
try {
    if ($user) {
        $unread = db()->count('notifications', 'user_id = ? AND is_read = 0', [(int) $user['id']]);
    }
} catch (Throwable $e) {}
$flashData = $flash ?? null;
$org = (string) setting('org_name');
$sys = (string) app_brand('short_name');
$logo = setting('logo');
$sidebarItems = (new \App\Services\SidebarMenuService())->items(null, (int) $unread);
?>
<!DOCTYPE html>
<html lang="th" data-theme="<?= e($user['theme'] ?? 'light') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($title ?? 'Dashboard') ?> | <?= e(app_brand('name')) ?></title>
  <?php if ($fav = setting('favicon')): ?><link rel="icon" href="<?= e(upload_url($fav)) ?>"><?php endif; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>?v=6.5.2">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
  <link rel="stylesheet" href="<?= asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=27">
  <link rel="stylesheet" href="<?= asset('css/learn.css') ?>?v=3">
  <link rel="stylesheet" href="<?= asset('css/image-crop.css') ?>?v=1">
  <script>
    tailwind.config = { corePlugins: { preflight: false }, blocklist: ['collapse', 'container'] };
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>window.APP_URL = <?= json_encode(rtrim((string) env('APP_URL', ''), '/')) ?>;</script>
</head>
<body>
<?php if (!empty($flashData['message'])):
  $ft = (string) ($flashData['type'] ?? 'success');
  $flashId = match ($ft) {
    'error' => 'error',
    'warning' => 'warning',
    'info' => 'info',
    default => 'success',
  };
?>
  <div id="flash-<?= e($flashId) ?>" data-msg="<?= e($flashData['message']) ?>" hidden></div>
<?php endif; ?>

<div class="admin-shell">
  <div class="sidebar-backdrop" id="sidebar-backdrop" data-close-sidebar hidden aria-hidden="true"></div>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <a href="<?= url('/admin/dashboard') ?>" class="sidebar-brand-link">
        <?php if ($logo): ?>
          <img src="<?= e(upload_url($logo)) ?>" alt="" class="sidebar-logo">
        <?php else: ?>
          <img src="<?= asset('img/logo.svg') ?>" alt="" class="sidebar-logo">
        <?php endif; ?>
        <div class="sidebar-brand-text">
          <div class="name"><?= e($sys) ?></div>
          <div class="tag"><?= e($org) ?></div>
        </div>
      </a>
      <button type="button" class="sidebar-close" data-close-sidebar aria-label="ปิดเมนู"><i class="fa fa-xmark"></i></button>
    </div>
    <div class="sidebar-scroll" id="sidebar-scroll">
      <nav class="sidebar-nav">
        <?php foreach ($sidebarItems as $item):
          $type = (string) ($item['type'] ?? 'link');
          if ($type === 'label'): ?>
        <div class="nav-label"><?= e((string) ($item['label'] ?? '')) ?></div>
          <?php elseif ($type === 'group'):
            $open = !empty($item['open']); ?>
        <div class="nav-group<?= $open ? ' open' : '' ?>" data-nav-group>
          <button type="button" class="nav-group-toggle<?= $open ? ' is-current' : '' ?>" data-nav-toggle<?= $open ? ' aria-current="true"' : '' ?>>
            <i class="fa <?= e((string) ($item['icon'] ?? 'fa-folder')) ?>"></i>
            <span><?= e((string) ($item['label'] ?? '')) ?></span>
            <i class="fa fa-chevron-right chevron"></i>
          </button>
          <div class="nav-submenu">
            <?php foreach (($item['children'] ?? []) as $child):
              $cActive = !empty($child['active']); ?>
            <a class="<?= $cActive ? 'active' : '' ?>" href="<?= e((string) ($child['href'] ?? '#')) ?>"<?= $cActive ? ' aria-current="page"' : '' ?>><?= e((string) ($child['label'] ?? '')) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
          <?php else:
            $active = !empty($item['active']); ?>
        <a class="<?= $active ? 'active' : '' ?>" href="<?= e((string) ($item['href'] ?? '#')) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
          <i class="fa <?= e((string) ($item['icon'] ?? 'fa-circle')) ?>"></i>
          <?= e((string) ($item['label'] ?? '')) ?>
          <?php if (!empty($item['badge'])): ?>
          <span class="badge bg-danger ms-auto"><?= (int) $item['badge'] ?></span>
          <?php endif; ?>
        </a>
          <?php endif;
        endforeach; ?>
      </nav>
    </div>
    <form method="post" action="<?= url('/logout') ?>" class="sidebar-logout">
      <?= csrf_field() ?>
      <button type="submit"
              data-confirm="ต้องการออกจากระบบหรือไม่?"
              data-confirm-title="ออกจากระบบ"
              data-confirm-ok="ออกจากระบบ">
        <i class="fa fa-right-from-bracket"></i> ออกจากระบบ
      </button>
    </form>
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <div class="d-flex align-items-center gap-2 min-w-0">
        <button class="btn btn-light btn-sm" data-toggle-sidebar type="button"><i class="fa fa-bars"></i></button>
        <div class="topbar-crumb text-muted small"><?= e($breadcrumb ?? ($title ?? '')) ?></div>
      </div>
      <div class="search-box d-none d-md-flex">
        <i class="fa fa-search"></i>
        <input id="global-search" class="form-control" placeholder="ค้นหา" data-url="<?= url('/admin/search') ?>">
        <div id="global-search-results" class="dropdown-menu show position-absolute w-100 mt-1" style="display:none;max-height:280px;overflow:auto"></div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="icon-btn" data-toggle-theme type="button" title="สลับธีม"><i class="fa fa-moon"></i></button>
        <a href="<?= url('/admin/notifications') ?>" class="icon-btn position-relative" title="การแจ้งเตือน">
          <i class="fa fa-bell"></i>
          <?php if ($unread): ?><span class="notif-dot"></span><?php endif; ?>
        </a>
        <div class="dropdown user-menu">
          <button class="user-chip user-menu-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="เมนูผู้ใช้">
            <div class="user-chip-avatar">
              <?php if (!empty($user['avatar'])): ?>
                <img src="<?= e(avatar_url($user['avatar'])) ?>" alt="">
              <?php else: ?>
                <i class="fa fa-user"></i>
              <?php endif; ?>
            </div>
            <div class="user-chip-text">
              <strong><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'ผู้ใช้') ?></strong>
              <small><?= e(role_name($user['role'] ?? '')) ?></small>
            </div>
            <i class="fa fa-chevron-down user-menu-caret"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end user-menu-panel shadow">
            <li class="user-menu-head px-3 py-2">
              <div class="fw-semibold"><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'ผู้ใช้') ?></div>
              <div class="small text-muted"><?= e(role_name($user['role'] ?? '')) ?></div>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item" href="<?= url('/') ?>" target="_blank" rel="noopener">
                <i class="fa fa-globe me-2 text-muted"></i> ไปหน้าเว็บไซต์
              </a>
            </li>
            <?php if (can_perm('learn', 'view')): ?>
            <li>
              <a class="dropdown-item" href="<?= url('/learn') ?>">
                <i class="fa fa-graduation-cap me-2 text-muted"></i> บทเรียนของฉัน
              </a>
            </li>
            <?php endif; ?>
            <li>
              <a class="dropdown-item" href="<?= url('/admin/profile') ?>">
                <i class="fa fa-user-pen me-2 text-muted"></i> แก้ไขข้อมูลส่วนตัว
              </a>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <form method="post" action="<?= url('/logout') ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="dropdown-item text-danger" type="submit"
                        data-confirm="ต้องการออกจากระบบหรือไม่?"
                        data-confirm-title="ออกจากระบบ"
                        data-confirm-ok="ออกจากระบบ">
                  <i class="fa fa-right-from-bracket me-2"></i> ออกจากระบบ
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </header>
    <main class="content">
      <?= $content ?>
    </main>
    <footer class="admin-footer"><?= e(site_footer_text()) ?></footer>
  </div>
</div>
<div id="loading-overlay" class="loading-overlay"><div class="spinner"></div></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="<?= asset('js/vfs_fonts_thsarabun.js') ?>?v=1"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="<?= asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') ?>"></script>
<script src="<?= asset('js/ab-datatable.js') ?>?v=3"></script>
<script src="<?= asset('js/admin-ajax-tables.js') ?>?v=4"></script>
  <script src="<?= asset('js/app.js') ?>?v=7"></script>
<link rel="stylesheet" href="<?= asset('css/image-crop.css') ?>?v=1">
<script>
window.AB_IMAGE_CROP = {
  uploadUrl: <?= json_encode(url('/admin/media/crop/upload-temp'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  cropUrl: <?= json_encode(url('/admin/media/crop/apply'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  transformUrl: <?= json_encode(url('/admin/media/crop/transform'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  presets: <?= json_encode(array_map(static function ($p) {
      return [
          'label' => $p['label'],
          'width' => $p['width'],
          'height' => $p['height'],
          'shape' => $p['shape'],
          'accept_sizes' => $p['accept_sizes'] ?? [[$p['width'], $p['height']]],
      ];
  }, \App\Services\ImageCropService::presets()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= asset('js/image-crop.js') ?>?v=1"></script>
<?php partial('partials/image_crop_modal'); ?>
<script>
$('#global-search').on('input', function () {
  const q = $(this).val();
  clearTimeout(window.__epSearch);
  if (q.length < 2) { $('#global-search-results').hide().empty(); return; }
  window.__epSearch = setTimeout(function () {
    $.getJSON(window.APP_URL + '/admin/search', { q: q }, function (res) {
      const box = $('#global-search-results').empty().show();
      let found = false;
      const groups = {personnel:'บุคลากร', portfolio:'รายการ', users:'ผู้ใช้'};
      Object.keys(groups).forEach(function (g) {
        const rows = (res.results && res.results[g]) || [];
        if (!rows.length) return;
        found = true;
        box.append('<div class="dropdown-header">' + groups[g] + '</div>');
        rows.forEach(function (item) {
          const label = item.title ? ((item.first_name? item.title+item.first_name+' '+item.last_name : item.title)) : ((item.first_name||'')+' '+(item.last_name||'')) || item.username;
          let href = '#';
          if (g === 'personnel') href = window.APP_URL + '/admin/personnel/' + item.id;
          if (g === 'portfolio') href = window.APP_URL + '/admin/portfolio/' + item.type + '/' + item.id;
          if (g === 'users') href = window.APP_URL + '/admin/users/' + item.id + '/edit';
          box.append('<a class="dropdown-item py-2" href="'+href+'">'+$('<div>').text(label).html()+'</a>');
        });
      });
      if (!found) box.append('<div class="p-2 text-muted">ไม่พบผลลัพธ์</div>');
    });
  }, 280);
});
</script>
<?php if (!empty($scripts)) echo $scripts; ?>
</body>
</html>
