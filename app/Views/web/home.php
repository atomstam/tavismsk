<?php
$sys = (string) app_brand('short_name');
$org = (string) setting('org_name');
$plans = function_exists('curriculum_plans') ? curriculum_plans() : [];
$planCount = count($plans) ?: 14;
$members = (int) ($stats['members'] ?? 0);
$activities = (int) ($stats['works'] ?? 0);
$teachers = (int) ($stats['teachers'] ?? 0);
$modules = $modules ?? (function_exists('portfolio_modules') ? portfolio_modules() : []);
$fullName = app_brand('full_name');
$tagline = app_brand('tagline');
?>
<section class="home-hero">
  <div class="container">
    <div class="home-hero-grid">
      <div>
        <span class="home-badge"><i class="fa fa-shield-halved"></i> <?= e($sys) ?> · <?= e($tagline) ?></span>
        <h1>อบรม · ประเมิน · ผ่านหลักสูตร<br><em>44444555ffff66666777ออกเกียรติบัตรออนไลน์</em></h1>
        <p class="home-quote"><?= e(app_brand('name')) ?> — <?= e(app_brand('description')) ?></p>
        <div class="home-ctas">
          <?php if (auth()->check()): ?>
            <a class="ws-btn-primary" href="<?= url('/learn') ?>">เข้าเรียนต่อ <i class="fa fa-arrow-right"></i></a>
            <a class="ws-btn-ghost" href="<?= url('/admin/enrollments') ?>">ลงทะเบียนอบรม</a>
          <?php else: ?>
            <a class="ws-btn-primary" href="<?= url('/login') ?>">เข้าสู่ระบบ <i class="fa fa-arrow-right"></i></a>
            <a class="ws-btn-ghost" href="<?= url('/curriculum') ?>">ดูโมดูลการอบรม</a>
          <?php endif; ?>
          <a class="ws-btn-ghost" href="<?= url('/certificate/verify') ?>">ตรวจสอบเกียรติบัตร</a>
        </div>
        <div class="home-stats">
          <div class="home-stat">
            <i class="fa fa-book-open" style="background:#7c3aed"></i>
            <strong><?= (int) $planCount ?></strong>
            <span>โมดูลอบรม</span>
          </div>
          <div class="home-stat">
            <i class="fa fa-clock" style="background:#f59e0b"></i>
            <strong>14</strong>
            <span>ชั่วโมงหลักสูตร</span>
          </div>
          <div class="home-stat">
            <i class="fa fa-certificate" style="background:#6b21a8"></i>
            <strong>e-Cert</strong>
            <span>เกียรติบัตรออนไลน์</span>
          </div>
          <div class="home-stat">
            <i class="fa fa-sitemap" style="background:#0d9488"></i>
            <strong>4</strong>
            <span>ระดับหน่วยงาน</span>
          </div>
        </div>
      </div>
      <div class="home-visual">
        <div class="tavis-hero-frame" aria-hidden="false">
          <img
            src="<?= asset('img/tavis-hero.png') ?>?v=3"
            alt="<?= e(app_brand('name')) ?>"
            class="tavis-hero-photo"
            loading="eager"
            decoding="async"
          >
        </div>
      </div>
    </div>
  </div>
</section>

<nav class="home-quick container" aria-label="ทางลัด">
  <a href="<?= url('/curriculum') ?>"><i class="fa fa-book"></i><span>แผนการเรียนรู้</span></a>
  <a href="<?= url('/about') ?>"><i class="fa fa-info-circle"></i><span>เกี่ยวกับชมรม</span></a>
  <a href="<?= url('/news') ?>"><i class="fa fa-newspaper"></i><span>กิจกรรม</span></a>
  <a href="<?= url('/portfolios/gallery') ?>"><i class="fa fa-images"></i><span>แกลเลอรี่</span></a>
  <a href="<?= url('/contact') ?>"><i class="fa fa-envelope"></i><span>ติดต่อเรา</span></a>
</nav>

<section class="ws-section">
  <div class="container">
    <div class="ws-section-head">
      <div>
        <h2>หมวดการเรียนรู้หลัก</h2>
        <p>โครงสร้างเนื้อหา<?= e($fullName) ?></p>
      </div>
      <a class="ws-link-all" href="<?= url('/curriculum') ?>">ดูทั้งหมด <i class="fa fa-arrow-right"></i></a>
    </div>
    <div class="ws-cat-grid">
      <?php
      $cats = [
        ['', 'ความปลอดภัยพื้นฐาน', 'อุบัติเหตุ มารยาท และการเตรียมความพร้อม', 'fa-car-burst', '#7c3aed', '/curriculum/1'],
        ['', 'กฎหมายจราจร', 'กฎหมายรถยนต์ ทางหลวง สัญญาณและเครื่องหมาย', 'fa-scale-balanced', '#6b21a8', '/curriculum/3'],
        ['', 'ทักษะคิดเอาชีวิตรอด (TSY)', 'ความเสี่ยง สมรรถนะ มนุษย์ และการตระหนักรู้', 'fa-brain', '#f59e0b', '/curriculum/5'],
        ['', 'การตัดสินใจและทัศนคติ', 'DECIDE · KYT · Hazardous Attitude', 'fa-lightbulb', '#0d9488', '/curriculum/8'],
        ['', 'สุขภาพและความเสี่ยง', 'เหนื่อยล้า ยา โรค และ AMISAFE', 'fa-heart-pulse', '#db2777', '/curriculum/10'],
        ['', 'ฉุกเฉินและใบขับขี่', 'ปฐมพยาบาล ขับขี่ปลอดภัย ขอใบอนุญาต', 'fa-id-card', '#4f46e5', '/curriculum/11'],
      ];
      foreach ($cats as [$key, $catTitle, $desc, $icon, $color, $href]):
      ?>
        <a class="ws-cat-card" href="<?= url($href) ?>">
          <div class="ws-cat-icon" style="background:<?= e($color) ?>"><i class="fa <?= e($icon) ?>"></i></div>
          <h3><?= e($catTitle) ?></h3>
          <p><?= e($desc) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="ws-section">
  <div class="container">
    <div class="ws-section-head">
      <div>
        <h2>แผนการจัดการเรียนรู้ 14 ชั่วโมง</h2>
        <p>กิจกรรมพัฒนาผู้เรียน 1 ชั่วโมง/สัปดาห์</p>
      </div>
      <a class="ws-link-all" href="<?= url('/curriculum') ?>">ดูทั้งหมด <i class="fa fa-arrow-right"></i></a>
    </div>
    <div class="ws-work-grid">
      <?php foreach (array_slice($plans, 0, 6, true) as $no => $plan): ?>
        <a class="ws-work-card" href="<?= url('/curriculum/' . (int) $no) ?>">
          <?php
            $homeCover = sprintf('img/plans/plan-%02d.png', (int) $no);
            $homeCoverFile = PUBLIC_PATH . '/assets/' . $homeCover;
          ?>
          <div class="thumb">
            <?php if (is_file($homeCoverFile)): ?>
              <img src="<?= asset($homeCover) ?>?v=4" alt="แผนที่ <?= (int) $no ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center h-100" style="background:linear-gradient(135deg,#4c1d95,#7c3aed);color:#fff">
                <div class="text-center">
                  <div class="small opacity-75">แผนที่</div>
                  <div style="font-size:2rem;font-weight:700;font-family:var(--w-display)"><?= (int) $no ?></div>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <div class="body">
            <div class="meta">
              <span class="ws-pill"><?= e($plan['topic_group'] ?? 'หลักสูตร') ?></span>
              <span class="ws-pill" style="background:#fffbeb;color:#b45309"><?= (int) ($plan['hours'] ?? 1) ?> ชม.</span>
            </div>
            <h3><?= e($plan['title'] ?? '') ?></h3>
            <div class="foot">
              <span><i class="fa fa-chalkboard-user me-1"></i><?= e($plan['instructor_label'] ?? 'วิทยากร') ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="ws-section">
  <div class="container">
    <div class="ws-section-head">
      <div>
        <h2>แกลเลอรี่กิจกรรม</h2>
        <p>ภาพกิจกรรม การอบรม และการเรียนรู้ของชมรม</p>
      </div>
      <a class="ws-link-all" href="<?= url('/portfolios/gallery') ?>">ดูทั้งหมด <i class="fa fa-arrow-right"></i></a>
    </div>
    <?php if (!empty($albums)): ?>
    <div class="ws-gallery-grid">
      <?php foreach ($albums as $a): ?>
        <a class="ws-gallery-item" href="<?= url('/portfolios/gallery') ?>">
          <?php if (!empty($a['cover_image'])): ?>
            <img src="<?= e(upload_url($a['cover_image'])) ?>" alt="<?= e($a['title']) ?>">
          <?php else: ?>
            <div class="w-100 h-100 d-flex align-items-center justify-content-center"><i class="fa fa-image fa-2x text-muted"></i></div>
          <?php endif; ?>
          <span class="cap"><?= e($a['title']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="ws-empty">ยังไม่มีอัลบั้มในแกลเลอรี่</div>
    <?php endif; ?>
  </div>
</section>

<section class="ws-section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="ws-section-head">
          <div>
            <h2>ข่าวสารและประกาศ</h2>
            <p>อัปเดตล่าสุดจาก<?= e(app_brand('name')) ?></p>
          </div>
          <a class="ws-link-all" href="<?= url('/news') ?>">ดูทั้งหมด <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="ws-news-list">
          <?php if (!empty($news)): foreach ($news as $n):
            $ts = strtotime((string)($n['published_at'] ?? $n['created_at'] ?? 'now')) ?: time();
            $day = date('j', $ts);
            $months = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
            $mon = $months[(int)date('n', $ts)] ?? '';
          ?>
            <a class="ws-news-item" href="<?= url('/news/' . $n['slug']) ?>">
              <div class="ws-news-date"><strong><?= e($day) ?></strong><span><?= e($mon) ?></span></div>
              <div>
                <h3><?= e($n['title']) ?></h3>
                <p><?= e($n['excerpt'] ?: $org) ?></p>
              </div>
            </a>
          <?php endforeach; else: ?>
            <div class="ws-empty">ยังไม่มีข่าวสาร</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="ws-section-head">
          <div>
            <h2>กิจกรรมชมรม</h2>
            <p>หมวดบันทึกกิจกรรมในระบบ</p>
          </div>
          <a class="ws-link-all" href="<?= url('/portfolios') ?>">ทั้งหมด</a>
        </div>
        <div class="pf-teacher-grid" style="grid-template-columns:1fr 1fr">
          <?php
          $clubCards = [
            ['activities', 'กิจกรรมชมรม', 'fa-calendar-check'],
            ['training', 'การอบรม', 'fa-book-open'],
            ['assessments', 'แบบทดสอบ', 'fa-clipboard-check'],
            ['licenses', 'ใบขับขี่', 'fa-id-card'],
          ];
          foreach ($clubCards as [$k, $label, $icon]):
            $m = $modules[$k] ?? null;
          ?>
            <a class="pf-teacher-card text-decoration-none" href="<?= url('/portfolios?type=' . urlencode($k)) ?>">
              <div class="d-grid place-items-center rounded-circle mx-auto mb-2" style="width:56px;height:56px;background:linear-gradient(135deg,#7c3aed,#f59e0b);color:#fff">
                <i class="fa <?= e($m['icon'] ?? $icon) ?>"></i>
              </div>
              <h3><?= e($m['title'] ?? $label) ?></h3>
              <p><?= e($m['short'] ?? app_brand('full_name')) ?></p>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ws-section">
  <div class="container">
    <div class="ws-contact-band">
      <div class="row g-4 align-items-center">
        <div class="col-lg-5">
          <h2 class="h3 mb-3" style="font-family:var(--w-display)">แผนที่และการติดต่อ</h2>
          <p class="mb-3 opacity-90"><?= e($org) ?><br><?= e((string) setting('org_affiliation')) ?></p>
          <p class="mb-2"><i class="fa fa-phone me-2"></i><?= e((string) setting('org_phone')) ?></p>
          <p class="mb-2"><i class="fa fa-envelope me-2"></i><?= e((string) setting('org_email')) ?></p>
          <p class="mb-4"><i class="fa fa-location-dot me-2"></i><?= e((string) setting('org_address')) ?></p>
          <a class="ws-btn-on-band" href="<?= url('/contact') ?>"><i class="fa fa-envelope"></i> ติดต่อสอบถาม</a>
        </div>
        <div class="col-lg-7">
          <div class="map-wrap">
            <?php $map = setting('maps_embed'); if ($map): ?>
              <?= $map ?>
            <?php else: ?>
              <iframe title="แผนที่" src="https://www.google.com/maps?q=<?= rawurlencode('สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม 134 ถนนเลี่ยงเมืองมหาสารคาม-ร้อยเอ็ด') ?>&output=embed" loading="lazy"></iframe>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
