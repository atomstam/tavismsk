<?php
$sys = (string) app_brand('short_name');
$org = (string) setting('org_name');
$site = (string) app_brand('name');
$navTitle = (string) (app_brand('nav_title') ?: ($sys . ($org ? ' ' . $org : '')));
$navTag = (string) (app_brand('nav_tag') ?: app_brand('tagline'));
$fav = setting('favicon');
$path = current_path();
$modules = portfolio_modules();
$fb = setting('org_facebook') ?: '#';
$line = setting('org_line') ?: '#';
$email = setting('org_email') ?: '';
$yt = setting('org_youtube') ?: '#';
$isHome = ($path === '/' || $path === '');
$pfType = (string) ($_GET['type'] ?? '');
$clubTypes = array_keys($modules);
$isClubMenu = str_starts_with($path, '/portfolios') && !str_contains($path, 'gallery');
$isCurriculum = str_starts_with($path, '/curriculum');
$isLearn = str_starts_with($path, '/learn');
$authUser = auth()->check() ? auth()->user() : null;
$authName = $authUser ? user_full_name($authUser) : '';
$authRole = $authUser ? role_name((string) ($authUser['role'] ?? '')) : '';
$authAvatar = $authUser ? avatar_url($authUser['avatar'] ?? null) : '';
?>
<!DOCTYPE html>
<html lang="th" data-web-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $site) ?> · <?= e($org) ?></title>
  <meta name="description" content="<?= e(app_brand('description') ?: (app_brand('full_name') . ' — ' . app_brand('name_en'))) ?>">
  <meta name="theme-color" content="#f8fafc" id="meta-theme-color">
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>?v=6.5.2">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=37">
  <link rel="stylesheet" href="<?= asset('css/web-site.css') ?>?v=40">
  <link rel="stylesheet" href="<?= asset('css/portfolio-clean.css') ?>?v=4">
  <link rel="stylesheet" href="<?= asset('css/portfolio-dark.css') ?>?v=9">
  <link rel="stylesheet" href="<?= asset('css/web-dark-home.css') ?>?v=27">
  <link rel="stylesheet" href="<?= asset('css/web-theme.css') ?>?v=20">
  <link rel="stylesheet" href="<?= asset('css/learn.css') ?>?v=10">
  <script>
    tailwind.config = {
      corePlugins: { preflight: false },
      blocklist: [
        'collapse', 'container', 'row', 'col', 'btn', 'card', 'modal', 'nav',
        'navbar', 'dropdown', 'table', 'alert', 'badge', 'form', 'input',
        'list', 'pagination', 'progress', 'spinner', 'toast', 'tooltip',
      ],
      theme: { extend: { fontFamily: { sans: ['Prompt', 'Sarabun', 'sans-serif'] } } }
    };
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
  <style id="tavis-bootstrap-guard">
    /* Tailwind CDN injects late — keep Bootstrap layout intact */
    body.web-site .container {
      width: 100% !important;
      max-width: 1140px !important;
      margin-left: auto !important;
      margin-right: auto !important;
      padding-left: 12px !important;
      padding-right: 12px !important;
    }
    @media (min-width: 1400px) {
      body.web-site .container { max-width: 1320px !important; }
    }
  </style>
</head>
<body class="web-site d-flex flex-column pf-light">
<script>
(function () {
  var t = document.documentElement.getAttribute('data-web-theme') || 'light';
  document.body.classList.remove('pf-dark', 'pf-light');
  document.body.classList.add(t === 'light' ? 'pf-light' : 'pf-dark');
})();
</script>
<header class="ws-nav ws-nav-dark">
  <div class="container ws-nav-inner position-relative">
    <a href="<?= url('/') ?>" class="ws-brand">
      <span class="ws-brand-icon"><i class="fa fa-shield-halved"></i></span>
      <span class="ws-brand-text">
        <span class="name d-block"><?= e(mb_strimwidth($navTitle, 0, 42, '…')) ?></span>
        <span class="tag"><?= e($navTag) ?></span>
      </span>
    </a>

    <nav class="ws-menu" id="ws-menu">
      <a href="<?= url('/') ?>" class="<?= $isHome ? 'active' : '' ?>">หน้าแรก</a>
      <a href="<?= url('/about') ?>" class="<?= str_starts_with($path, '/about') ? 'active' : '' ?>">เกี่ยวกับเรา</a>

      <div class="ws-drop" data-drop>
        <button type="button" class="<?= ($isCurriculum || $isLearn) ? 'active' : '' ?>">
          แผนการเรียนรู้ <i class="fa fa-chevron-down fa-xs"></i>
        </button>
        <div class="ws-drop-panel">
          <a href="<?= url('/curriculum') ?>"><i class="fa fa-list"></i> แผนทั้งหมด (14 ชม.)</a>
          <?php if ($authUser && can_perm('learn', 'view')): ?>
            <a href="<?= url('/learn') ?>" class="<?= $isLearn ? 'active' : '' ?>"><i class="fa fa-graduation-cap"></i> บทเรียนของฉัน</a>
          <?php endif; ?>
          <?php
          $planQuick = function_exists('curriculum_plans') ? curriculum_plans() : [];
          foreach (array_slice($planQuick, 0, 6, true) as $pn => $pp): ?>
            <a href="<?= url('/curriculum/' . (int) $pn) ?>"><i class="fa fa-book-open"></i> แผนที่ <?= (int) $pn ?></a>
          <?php endforeach; ?>
          <a href="<?= url('/curriculum') ?>"><i class="fa fa-ellipsis"></i> ดูแผนที่ 7–14</a>
        </div>
      </div>

      <div class="ws-drop" data-drop>
        <button type="button" class="<?= $isClubMenu ? 'active' : '' ?>">
          กิจกรรมชมรม <i class="fa fa-chevron-down fa-xs"></i>
        </button>
        <div class="ws-drop-panel">
          <a href="<?= url('/portfolios') ?>"><i class="fa fa-folder-open"></i> ทั้งหมด</a>
          <?php foreach ($clubTypes as $k):
            if (empty($modules[$k])) continue; $m = $modules[$k]; ?>
            <a href="<?= url('/portfolios?type=' . urlencode($k)) ?>"><i class="fa <?= e($m['icon']) ?>"></i> <?= e($m['title']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <a href="<?= url('/news') ?>" class="<?= str_starts_with($path, '/news') ? 'active' : '' ?>">ข่าวสาร</a>
      <a href="<?= url('/portfolios/gallery') ?>" class="<?= str_contains($path, 'gallery') ? 'active' : '' ?>">แกลเลอรี่กิจกรรม</a>
      <a href="<?= url('/contact') ?>" class="<?= str_starts_with($path, '/contact') ? 'active' : '' ?>">ติดต่อ</a>
    </nav>

    <div class="ws-nav-tools">
      <button type="button" class="ws-nav-toggle" id="ws-nav-toggle" aria-label="เมนู">
        <i class="fa fa-bars"></i>
      </button>
      <div class="ws-nav-actions">
        <button type="button" class="ws-theme-toggle" id="ws-theme-toggle" data-web-theme-toggle
                title="สลับธีมสว่าง/มืด" aria-label="สลับธีมสว่างหรือมืด">
          <i class="fa fa-moon" aria-hidden="true"></i>
          <i class="fa fa-sun" aria-hidden="true"></i>
        </button>
        <?php if ($authUser): ?>
          <div class="ws-drop ws-user-menu" data-drop>
            <button type="button" class="ws-user-chip" aria-haspopup="true" aria-expanded="false" title="เมนูผู้ใช้">
              <img src="<?= e($authAvatar) ?>" alt="">
              <span class="ws-user-meta">
                <strong><?= e(mb_strimwidth($authName, 0, 22, '…')) ?></strong>
                <small><?= e($authRole) ?></small>
              </span>
              <i class="fa fa-chevron-down"></i>
            </button>
            <div class="ws-drop-panel ws-user-menu-panel">
              <div class="ws-user-menu-head">
                <strong><?= e($authName) ?></strong>
                <small><?= e($authRole) ?></small>
              </div>
              <a href="<?= url('/admin/dashboard') ?>"><i class="fa fa-gauge"></i> เข้าสู่ระบบจัดการ</a>
              <a href="<?= url('/curriculum') ?>"><i class="fa fa-book-open"></i> แผนการเรียนรู้</a>
              <?php if (can_perm('learn', 'view')): ?>
                <a href="<?= url('/learn') ?>"><i class="fa fa-graduation-cap"></i> บทเรียนของฉัน</a>
              <?php endif; ?>
              <a href="<?= url('/admin/profile') ?>"><i class="fa fa-user-pen"></i> แก้ไขข้อมูลส่วนตัว</a>
              <form method="post" action="<?= url('/logout') ?>" class="ws-user-logout">
                <?= csrf_field() ?>
                <button type="submit"
                        data-confirm="ต้องการออกจากระบบหรือไม่?"
                        data-confirm-title="ออกจากระบบ"
                        data-confirm-ok="ออกจากระบบ">
                  <i class="fa fa-right-from-bracket"></i> ออกจากระบบ
                </button>
              </form>
            </div>
          </div>
        <?php else: ?>
          <a class="ws-btn-login" href="<?= url('/login') ?>"><i class="fa fa-right-to-bracket"></i> <span class="hide-sm">เข้าสู่ระบบ</span></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="ws-nav-glow" aria-hidden="true"></div>
</header>

<main class="web-main">
<?php if (!empty($flash['message'])):
  $flashType = (string) ($flash['type'] ?? 'success');
  $flashId = match ($flashType) {
    'error' => 'error',
    'warning' => 'warning',
    'info' => 'info',
    default => 'success',
  };
?>
  <div id="flash-<?= e($flashId) ?>" data-msg="<?= e($flash['message']) ?>" hidden></div>
<?php endif; ?>
<?= $content ?>
</main>

<footer class="ws-footer ws-footer-rich">
  <div class="container">
    <div class="row g-4 align-items-start">
      <div class="col-lg-4">
        <div class="ws-quote-block">
          <span class="q">“</span>
          <p><?= e(app_brand('description')) ?></p>
          <span class="q end">”</span>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h4>เมนูทางลัด</h4>
        <ul class="ws-footer-links">
          <li><a href="<?= url('/') ?>">หน้าแรก</a></li>
          <li><a href="<?= url('/about') ?>">เกี่ยวกับระบบ</a></li>
          <li><a href="<?= url('/curriculum') ?>">โมดูลการอบรม</a></li>
          <li><a href="<?= url('/certificate/verify') ?>">ตรวจสอบเกียรติบัตร</a></li>
          <li><a href="<?= url('/faq') ?>">คำถามที่พบบ่อย</a></li>
          <li><a href="<?= url('/downloads') ?>">ดาวน์โหลด</a></li>
          <li><a href="<?= url('/contact') ?>">ติดต่อ</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-3">
        <h4>ช่องทางติดต่อ</h4>
        <ul class="ws-footer-contact">
          <li><i class="fa fa-location-dot"></i> <?= e(setting('org_address')) ?></li>
          <li><i class="fa fa-phone"></i> <?= e(setting('org_phone')) ?></li>
          <li><i class="fa fa-envelope"></i> <?= e(setting('org_email')) ?></li>
          <li><i class="fa fa-globe"></i> <?= e(setting('org_website') ?: 'tavis.spmmsk.go.th') ?></li>
        </ul>
        <div class="ws-footer-socials">
          <a class="fb" href="<?= e($fb) ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
          <a class="yt" href="<?= e($yt !== '#' ? $yt : 'https://www.youtube.com') ?>" target="_blank" rel="noopener"><i class="fab fa-youtube"></i></a>
          <a class="ln" href="<?= e(str_starts_with((string)$line, 'http') ? $line : 'https://line.me/ti/p/' . ltrim((string)$line, '@')) ?>" target="_blank" rel="noopener"><i class="fab fa-line"></i></a>
          <a class="em" href="mailto:<?= e($email) ?>"><i class="fa fa-envelope"></i></a>
        </div>
      </div>
      <div class="col-lg-3">
        <h4>สถิติผู้เข้าชม</h4>
        <?php
          $vs = !empty($visitorStats) && is_array($visitorStats) ? $visitorStats : visitor_stats();
        ?>
        <div class="ws-visitor-grid">
          <div><b><?= number_format((int)($vs['today'] ?? 0)) ?></b><span>วันนี้</span></div>
          <div><b><?= number_format((int)($vs['month'] ?? 0)) ?></b><span>เดือนนี้</span></div>
          <div><b><?= number_format((int)($vs['all'] ?? 0)) ?></b><span>ทั้งหมด</span></div>
        </div>
        <p class="small text-secondary mt-2 mb-0">IP ของคุณ: <?= e(request_ip()) ?></p>
      </div>
    </div>
    <div class="ws-footer-bottom">
      <div><?= e(site_footer_text()) ?></div>
    </div>
  </div>
</footer>
<button type="button" id="pd-top" aria-label="ขึ้นบน"><i class="fa fa-arrow-up"></i></button>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= asset('js/app.js') ?>?v=8"></script>
<script>
(function () {
  const KEY = 'tavis-web-theme';
  const applyTheme = (theme) => {
    const t = theme === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-web-theme', t);
    document.documentElement.style.colorScheme = t;
    document.body.classList.remove('pf-dark', 'pf-light');
    document.body.classList.add(t === 'light' ? 'pf-light' : 'pf-dark');
    const meta = document.getElementById('meta-theme-color');
    if (meta) meta.setAttribute('content', t === 'light' ? '#f8fafc' : '#0b1220');
    document.querySelectorAll('[data-web-theme-toggle]').forEach((btn) => {
      btn.setAttribute('aria-pressed', t === 'light' ? 'true' : 'false');
      btn.title = t === 'light' ? 'สลับเป็นธีมมืด' : 'สลับเป็นธีมสว่าง';
    });
    try { localStorage.setItem(KEY, t); } catch (e) {}
  };
  applyTheme(document.documentElement.getAttribute('data-web-theme') || 'light');
  document.querySelectorAll('[data-web-theme-toggle]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const cur = document.documentElement.getAttribute('data-web-theme') || 'light';
      applyTheme(cur === 'dark' ? 'light' : 'dark');
    });
  });

  const toggle = document.getElementById('ws-nav-toggle');
  const menu = document.getElementById('ws-menu');
  const setMenuOpen = (open) => {
    menu?.classList.toggle('open', open);
    toggle?.classList.toggle('is-open', open);
    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    const icon = toggle?.querySelector('i');
    if (icon) {
      icon.className = open ? 'fa fa-xmark' : 'fa fa-bars';
    }
  };
  toggle?.setAttribute('aria-expanded', 'false');
  toggle?.setAttribute('aria-controls', 'ws-menu');
  toggle?.addEventListener('click', (e) => {
    e.stopPropagation();
    const willOpen = !menu?.classList.contains('open');
    setMenuOpen(willOpen);
    if (willOpen) {
      document.querySelectorAll('[data-drop].open').forEach((o) => {
        o.classList.remove('open');
        o.querySelector('button')?.setAttribute('aria-expanded', 'false');
      });
    }
  });
  document.querySelectorAll('[data-drop]').forEach((el) => {
    const btn = el.querySelector('button');
    btn?.addEventListener('click', (e) => {
      e.stopPropagation();
      document.querySelectorAll('[data-drop].open').forEach((o) => {
        if (o !== el) {
          o.classList.remove('open');
          o.querySelector('button')?.setAttribute('aria-expanded', 'false');
        }
      });
      const open = !el.classList.contains('open');
      el.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (el.classList.contains('ws-user-menu') && open) {
        setMenuOpen(false);
      }
    });
  });
  document.addEventListener('click', (e) => {
    if (!menu?.contains(e.target) && !toggle?.contains(e.target)) {
      setMenuOpen(false);
    }
    document.querySelectorAll('[data-drop].open').forEach((o) => {
      if (!o.contains(e.target)) {
        o.classList.remove('open');
        o.querySelector('button')?.setAttribute('aria-expanded', 'false');
      }
    });
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 991) setMenuOpen(false);
    document.querySelectorAll('[data-drop].open').forEach((o) => {
      o.classList.remove('open');
      o.querySelector('button')?.setAttribute('aria-expanded', 'false');
    });
  });
  const topBtn = document.getElementById('pd-top');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 400) topBtn?.classList.add('show'); else topBtn?.classList.remove('show');
  });
  topBtn?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
})();
</script>
<?php if (!empty($useCharts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<?php if (!empty($scripts)) echo $scripts; ?>
</body>
</html>
