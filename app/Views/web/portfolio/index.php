<?php
$activeMod = $activeMod ?? null;
$activeType = $activeType ?? '';
$typeCounts = $typeCounts ?? [];
$works = $works ?? [];
$teachers = $teachers ?? [];
$q = $q ?? '';
$year = (int) ($year ?? 0);
$sort = $sort ?? 'latest';
$staffId = (int) ($staffId ?? 0);
$selectedStaff = $selectedStaff ?? null;
$viewMode = ($view ?? 'list') === 'grid' ? 'grid' : 'list';
$years = $years ?? academic_years_list();
$totalAll = array_sum($typeCounts);
$heroTitle = $activeMod['title'] ?? 'ข้อมูลกิจกรรมและผลงาน';
$staffName = $selectedStaff
  ? trim(($selectedStaff['title'] ?? '') . ($selectedStaff['first_name'] ?? '') . ' ' . ($selectedStaff['last_name'] ?? ''))
  : '';
$heroDesc = $staffName !== ''
  ? (($activeMod['report_title'] ?? $heroTitle) . ' ของ ' . $staffName)
  : ($activeMod['report_title'] ?? 'รวมผลงาน รางวัล กิจกรรม และการพัฒนาของบุคลากร');
$sidebarTeacher = $selectedStaff;
$showSidebar = can_view_owner_sidebar($sidebarTeacher);
$queryBase = [];
if ($staffId > 0) $queryBase['staff'] = $staffId;
$pillUrl = static function (string $type = '') use ($queryBase): string {
    $q = $queryBase;
    if ($type !== '') $q['type'] = $type;
    $qs = http_build_query($q);
    return url('/portfolios' . ($qs !== '' ? '?' . $qs : ''));
};
?>
<section class="container pd-wrap">
  <div class="pd-crumb">
    <a href="<?= url('/') ?>">หน้าแรก</a> ›
    <a href="<?= url('/portfolios') ?>">ผลงาน</a>
    <?php if ($staffName !== ''): ?> › <?= e($staffName) ?><?php endif; ?>
    <?php if ($activeType): ?> › <?= e($heroTitle) ?><?php endif; ?>
  </div>

  <div class="pd-hero">
    <div class="pd-hero-deco">
      <i class="fa fa-trophy"></i>
      <i class="fa fa-chart-column"></i>
      <i class="fa fa-code"></i>
    </div>
    <div class="pd-hero-top">
      <div class="pd-hero-icon"><i class="fa <?= e($activeMod['icon'] ?? 'fa-calendar-check') ?>"></i></div>
      <div>
        <h1><?= e($heroTitle) ?></h1>
        <p><?= e($heroDesc) ?></p>
      </div>
    </div>
    <div class="pd-hero-stats">
      <div class="pd-stat">
        <i class="fa fa-briefcase" style="color:#93c5fd"></i>
        <div><strong><?= (int) $totalAll ?></strong><span>ผลงานทั้งหมด</span></div>
      </div>
      <div class="pd-stat">
        <i class="fa fa-grip" style="color:#5eead4"></i>
        <div><strong><?= count($modules) ?></strong><span>หมวดหมู่</span></div>
      </div>
      <div class="pd-stat">
        <i class="fa fa-medal" style="color:#fde047"></i>
        <div><strong><?= (int) ($typeCounts['awards'] ?? 0) ?></strong><span>รางวัล/เกียรติบัตร</span></div>
      </div>
      <div class="pd-stat">
        <i class="fa fa-filter" style="color:#86efac"></i>
        <div><strong><?= count($works) ?></strong><span>ผลลัพธ์ที่แสดง</span></div>
      </div>
    </div>
  </div>

  <div class="pd-pills">
    <a class="<?= $activeType === '' ? 'active' : '' ?>" href="<?= e($pillUrl()) ?>">
      <span class="dot" style="background:#e11d48"></span> ทั้งหมด <span class="cnt"><?= (int) $totalAll ?></span>
    </a>
    <?php foreach ($modules as $k => $m): if (empty($m['public'])) continue; ?>
      <a class="<?= $activeType === $k ? 'active' : '' ?>" href="<?= e($pillUrl($k)) ?>">
        <span class="dot" style="background:<?= e($m['color']) ?>"></span>
        <?= e($m['short']) ?>
        <span class="cnt"><?= (int) ($typeCounts[$k] ?? 0) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <form class="pd-toolbar" method="get" id="pd-filter-form">
    <?php if ($activeType): ?><input type="hidden" name="type" value="<?= e($activeType) ?>"><?php endif; ?>
    <input type="hidden" name="view" id="pd-view-input" value="<?= e($viewMode) ?>">
    <div class="pd-search">
      <i class="fa fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาข้อมูลกิจกรรมหรือผลงาน..." aria-label="ค้นหา">
    </div>
    <div class="pd-toolbar-filters">
      <?php partial('partials/portfolio-staff-filter', [
        'teachers' => $teachers,
        'staffId' => $staffId,
        'mode' => 'index',
        'activeType' => $activeType,
        'q' => $q,
        'year' => $year,
        'sort' => $sort,
        'viewMode' => $viewMode,
      ]); ?>
      <label class="pd-select">
        <span class="pd-select-ico" aria-hidden="true"><i class="fa fa-calendar"></i></span>
        <select name="year" aria-label="ปีการศึกษา" onchange="this.form.submit()">
          <option value="0">ปีการศึกษา — ทั้งหมด</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= (int) $y ?>" <?= $year === (int) $y ? 'selected' : '' ?>><?= (int) $y ?></option>
          <?php endforeach; ?>
        </select>
        <span class="pd-select-caret" aria-hidden="true"><i class="fa fa-chevron-down"></i></span>
      </label>
      <label class="pd-select">
        <span class="pd-select-ico" aria-hidden="true"><i class="fa fa-arrow-down-wide-short"></i></span>
        <select name="sort" aria-label="เรียงลำดับ" onchange="this.form.submit()">
          <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>เรียงล่าสุด</option>
          <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>เข้าชมมากสุด</option>
          <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>ชื่อ A-Z</option>
        </select>
        <span class="pd-select-caret" aria-hidden="true"><i class="fa fa-chevron-down"></i></span>
      </label>
    </div>
    <div class="pd-view-toggle" role="group" aria-label="รูปแบบการแสดงผล">
      <button type="button" class="<?= $viewMode === 'grid' ? 'active' : '' ?>" data-view="grid" title="ตาราง"><i class="fa fa-border-all"></i></button>
      <button type="button" class="<?= $viewMode === 'list' ? 'active' : '' ?>" data-view="list" title="รายการ"><i class="fa fa-list"></i></button>
    </div>
  </form>

  <div class="pd-found">พบ <?= count($works) ?> รายการ<?= $staffName !== '' ? ' · ' . e($staffName) : '' ?></div>

  <div class="pd-layout<?= $showSidebar ? '' : ' pd-layout-full' ?>">
    <div>
      <?php if ($works): ?>
        <div class="<?= $viewMode === 'grid' ? 'pd-grid' : '' ?>">
          <?php foreach ($works as $w):
            $mod = $modules[$w['type']] ?? null;
            $href = url('/portfolios/' . (int)$w['user_id'] . '/works/' . (int)$w['id']);
            $owner = trim(($w['title_name'] ?? '') . ($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''));
          ?>
            <article class="pd-card">
              <div class="pd-thumb">
                <?php if (!empty($w['cover_image'])): ?>
                  <img src="<?= e(upload_url($w['cover_image'])) ?>" alt="">
                <?php else: ?>
                  <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="color:#475569">
                    <i class="fa <?= e($mod['icon'] ?? 'fa-image') ?> fa-2x"></i>
                  </div>
                <?php endif; ?>
                <?php if ($mod): ?>
                  <span class="badge-ico" style="background:<?= e($mod['color']) ?>"><i class="fa <?= e($mod['icon']) ?>"></i></span>
                <?php endif; ?>
              </div>
              <div class="pd-card-body">
                <div class="pd-card-top">
                  <?php if ($mod): ?><span class="pd-badge" style="background:<?= e($mod['color']) ?>"><?= e($mod['short']) ?></span><?php endif; ?>
                  <?php if (!empty($w['is_featured'])): ?><span class="pd-badge" style="background:#f59e0b">ผลงานดีเด่น</span><?php endif; ?>
                </div>
                <h3><?= e($w['title']) ?></h3>
                <p class="desc"><?= e(mb_strimwidth(strip_tags((string) ($w['description'] ?? $w['organization'] ?? '')), 0, 160, '…')) ?></p>
                <div class="pd-card-foot">
                  <div class="pd-meta">
                    <?php if ($owner !== '' && $staffId === 0): ?>
                      <span><i class="fa fa-user me-1"></i><?= e($owner) ?></span>
                    <?php endif; ?>
                    <span><i class="fa fa-calendar me-1"></i><?= e(thai_date($w['start_date'] ?? null)) ?></span>
                    <span><i class="fa fa-eye me-1"></i><?= number_format((int)($w['views'] ?? 0)) ?> ครั้ง</span>
                  </div>
                  <a class="pd-btn-detail" href="<?= e($href) ?>">ดูรายละเอียด</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php elseif ($staffId === 0 && $activeType === '' && $q === '' && $year === 0 && !empty($teachers)): ?>
        <div class="pd-teacher-grid">
          <?php foreach ($teachers as $t):
            $name = trim(($t['title'] ?? '') . $t['first_name'] . ' ' . $t['last_name']);
            $photo = $t['photo'] ?? $t['avatar'] ?? null;
            $photoUrl = $photo ? upload_url($photo) : asset('img/default-avatar.svg');
          ?>
            <a class="pd-teacher-card" href="<?= url('/portfolios?staff=' . (int)$t['id']) ?>">
              <img src="<?= e($photoUrl) ?>" alt="<?= e($name) ?>">
              <h3><?= e($name) ?></h3>
              <p><?= e($t['position_name'] ?? role_name($t['role'] ?? 'teacher')) ?></p>
              <p class="mt-2" style="color:#fb7185;font-weight:700"><?= (int) $t['works_count'] ?> ผลงาน</p>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="pd-empty"><i class="fa fa-folder-open d-block"></i>ไม่พบผลงานที่ค้นหา</div>
      <?php endif; ?>
    </div>
    <?php if ($showSidebar): ?>
      <?php partial('partials/portfolio-sidebar', [
        'teacher' => $sidebarTeacher,
        'modules' => $modules,
        'counts' => $typeCounts,
      ]); ?>
    <?php endif; ?>
  </div>
</section>
<button type="button" id="pd-top" aria-label="ขึ้นบน"><i class="fa fa-arrow-up"></i></button>
<script>
(function () {
  const form = document.getElementById('pd-filter-form');
  const viewInput = document.getElementById('pd-view-input');
  form?.querySelectorAll('[data-view]').forEach((btn) => {
    btn.addEventListener('click', () => {
      viewInput.value = btn.getAttribute('data-view');
      form.submit();
    });
  });
  const topBtn = document.getElementById('pd-top');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 400) topBtn.classList.add('show'); else topBtn.classList.remove('show');
  });
  topBtn?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
})();
</script>
