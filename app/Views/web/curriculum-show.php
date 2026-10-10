<?php
$cfg = $cfg ?? [];
$no = (int) ($cfg['plan_no'] ?? 0);
$row = $row ?? null;
$planId = (int) ($row['id'] ?? 0);
$progress = $progress ?? null;
$lmsContents = $lmsContents ?? [];
$lmsTasks = $lmsTasks ?? [];
$pct = (float) ($progress['overall_percent'] ?? 0);
$stage = (string) ($progress['stage'] ?? '');
$typeLabel = [
  'teacher' => 'ครูที่ปรึกษาชมรม',
  'police' => 'เจ้าหน้าที่ตำรวจ',
  'hospital' => 'บุคลากรสาธารณสุข / โรงพยาบาล',
  'honda' => 'วิทยากร Honda Safety / TSY',
  'transport' => 'เจ้าหน้าที่กรมการขนส่งทางบก',
];
$itype = (string) ($cfg['instructor_type'] ?? '');
$instructor = $cfg['instructor_label'] ?? ($typeLabel[$itype] ?? $itype);
$knowledge = $cfg['knowledge_sheets'] ?? [];
$worksheetsCfg = $cfg['worksheets'] ?? [];
$media = $cfg['resources'] ?? [];
$coverRel = sprintf('img/plans/plan-%02d.png', $no);
$coverFile = PUBLIC_PATH . '/assets/' . $coverRel;
$hasCover = is_file($coverFile);
$loggedIn = auth()->check();
$learnUrl = $planId > 0 ? url('/learn/' . $planId) : '';
$loginUrl = $planId > 0 ? url('/login?redirect=' . rawurlencode('/learn/' . $planId)) : url('/login');
$stageLabel = match ($stage) {
  'completed' => 'เรียนครบแล้ว',
  'satisfaction' => 'ประเมินความพึงพอใจ',
  'posttest' => 'สอบหลังเรียน',
  'task' => 'ภาระงาน',
  'content' => 'เรียนเนื้อหา',
  'pretest' => 'ทดสอบก่อนเรียน',
  default => '',
};
?>
<div class="container ws-page-stack">
  <div class="ws-page-hero<?= $hasCover ? ' ws-page-hero--with-media' : '' ?>">
    <div class="ws-page-hero-copy">
      <div class="crumb">
        <a href="<?= url('/') ?>">หน้าแรก</a> ›
        <a href="<?= url('/curriculum') ?>">แผนการเรียนรู้</a> ›
        แผนที่ <?= $no ?>
      </div>
      <div class="ws-plan-hero-badge">แผนที่ <?= $no ?></div>
      <h1><?= e($cfg['title'] ?? ('แผนที่ ' . $no)) ?></h1>
      <p><?= e($cfg['topic_group'] ?? '') ?><?= !empty($cfg['topic_group']) ? ' · ' : '' ?><?= e((string) ($cfg['hours'] ?? 1)) ?> ชั่วโมง</p>
      <?php if ($planId > 0): ?>
        <div class="ws-plan-learn-cta">
          <?php if ($loggedIn): ?>
            <a class="btn btn-learn-primary btn-lg" href="<?= e($learnUrl) ?>">
              <i class="fa fa-play"></i>
              <?= $pct > 0 ? 'เข้าเรียนต่อ' : 'เริ่มเรียนแผนนี้' ?>
            </a>
            <?php if ($progress): ?>
              <span class="ws-plan-learn-meta"><?= number_format($pct, 0) ?>%<?= $stageLabel ? ' · ' . e($stageLabel) : '' ?></span>
            <?php endif; ?>
          <?php else: ?>
            <a class="btn btn-learn-primary btn-lg" href="<?= e($loginUrl) ?>">
              <i class="fa fa-right-to-bracket"></i> เข้าสู่ระบบเพื่อเริ่มเรียน
            </a>
          <?php endif; ?>
          <p class="ws-plan-learn-flow mb-0">ลำดับการเรียน: ทดสอบก่อน → เนื้อหา → ภาระงาน → สอบหลัง → ประเมินความพึงพอใจ</p>
        </div>
      <?php else: ?>
        <p class="ws-plan-learn-flow mt-3 mb-0">แผนนี้ยังไม่เปิดให้เรียนในระบบ LMS</p>
      <?php endif; ?>
    </div>
    <?php if ($hasCover): ?>
      <div class="ws-page-hero-media">
        <img src="<?= asset($coverRel) ?>?v=4" alt="แผนที่ <?= $no ?> — <?= e($cfg['title'] ?? '') ?>" loading="eager" decoding="async">
      </div>
    <?php endif; ?>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <article class="card-soft ws-plan-detail">
        <div class="ws-plan-meta">
          <div class="ws-plan-meta-item">
            <span class="ws-plan-meta-icon"><i class="fa fa-clock"></i></span>
            <div>
              <span class="label">ชั่วโมง</span>
              <strong><?= e((string) ($cfg['hours'] ?? 1)) ?> ชม.</strong>
            </div>
          </div>
          <div class="ws-plan-meta-item">
            <span class="ws-plan-meta-icon"><i class="fa fa-chalkboard-user"></i></span>
            <div>
              <span class="label">วิทยากร</span>
              <strong><?= e($instructor) ?></strong>
            </div>
          </div>
          <div class="ws-plan-meta-item">
            <span class="ws-plan-meta-icon"><i class="fa fa-layer-group"></i></span>
            <div>
              <span class="label">กลุ่มหัวข้อ</span>
              <strong><?= e($cfg['topic_group'] ?? '-') ?></strong>
            </div>
          </div>
        </div>

        <?php if ($lmsContents || $lmsTasks): ?>
          <section class="ws-plan-section">
            <h2><i class="fa fa-graduation-cap"></i> เนื้อหาในระบบเรียน (LMS)</h2>
            <?php if ($lmsContents): ?>
              <ul class="ws-plan-list">
                <?php foreach ($lmsContents as $c): ?>
                  <li><i class="fa fa-book-open me-1 text-primary"></i><?= e($c['title']) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php if ($lmsTasks): ?>
              <h3 class="h6 mt-3">ภาระงาน</h3>
              <ul class="ws-plan-list">
                <?php foreach ($lmsTasks as $t): ?>
                  <li><i class="fa fa-list-check me-1 text-primary"></i><?= e($t['title']) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </section>
        <?php endif; ?>

        <section class="ws-plan-section">
          <h2><i class="fa fa-book-open"></i> ใบความรู้</h2>
          <?php if ($knowledge): ?>
            <ul class="ws-plan-list">
              <?php foreach ($knowledge as $ks): ?><li><?= e($ks) ?></li><?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="ws-plan-empty">ยังไม่มีใบความรู้ในแผนนี้</p>
          <?php endif; ?>
        </section>

        <section class="ws-plan-section">
          <h2><i class="fa fa-pen-to-square"></i> ใบงาน</h2>
          <?php if ($worksheetsCfg): ?>
            <ul class="ws-plan-list">
              <?php foreach ($worksheetsCfg as $ws): ?><li><?= e($ws) ?></li><?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="ws-plan-empty">ยังไม่มีใบงานในแผนนี้</p>
          <?php endif; ?>
        </section>

        <section class="ws-plan-section">
          <h2><i class="fa fa-link"></i> สื่อ / แหล่งเรียนรู้</h2>
          <?php if ($media): ?>
            <ul class="ws-plan-list ws-plan-links">
              <?php foreach ($media as $res): ?>
                <li>
                  <?php if (!empty($res['url'])): ?>
                    <a href="<?= e($res['url']) ?>" target="_blank" rel="noopener">
                      <?= e($res['title'] ?? 'ลิงก์') ?>
                      <i class="fa fa-arrow-up-right-from-square"></i>
                    </a>
                  <?php else: ?>
                    <?= e($res['title'] ?? '') ?>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="ws-plan-empty">ยังไม่มีสื่อในแผนนี้</p>
          <?php endif; ?>
        </section>

        <?php if (!empty($resources)): ?>
          <section class="ws-plan-section">
            <h2><i class="fa fa-folder-open"></i> ทรัพยากรเพิ่มเติม</h2>
            <ul class="ws-plan-list ws-plan-links">
              <?php foreach ($resources as $r): ?>
                <li>
                  <?= e($r['title']) ?>
                  <?php if (!empty($r['url'])): ?>
                    — <a href="<?= e($r['url']) ?>" target="_blank" rel="noopener">เปิด <i class="fa fa-arrow-up-right-from-square"></i></a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <?php if (!empty($worksheets)): ?>
          <section class="ws-plan-section">
            <h2><i class="fa fa-file-lines"></i> ใบงานในระบบ</h2>
            <ul class="ws-plan-list">
              <?php foreach ($worksheets as $w): ?><li><?= e($w['title']) ?></li><?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>
      </article>
    </div>

    <div class="col-lg-4">
      <aside class="card-soft ws-plan-aside mb-3">
        <h2 class="ws-plan-aside-title">ขั้นตอนการเรียน</h2>
        <ol class="ws-plan-steps-list">
          <li>ทดสอบก่อนเรียน</li>
          <li>เรียนเนื้อหา</li>
          <li>ส่งภาระงาน</li>
          <li>สอบหลังเรียน</li>
          <li>ประเมินความพึงพอใจ</li>
        </ol>
        <?php if ($planId > 0): ?>
          <a class="btn btn-learn-primary w-100" href="<?= e($loggedIn ? $learnUrl : $loginUrl) ?>">
            <?= $loggedIn ? ($pct > 0 ? 'เข้าเรียนต่อ' : 'เริ่มเรียน') : 'เข้าสู่ระบบเพื่อเรียน' ?>
          </a>
        <?php endif; ?>
      </aside>

      <aside class="card-soft ws-plan-aside">
        <h2 class="ws-plan-aside-title">แผนอื่นในหลักสูตร</h2>
        <nav class="ws-plan-nav" aria-label="แผนการเรียนรู้">
          <?php foreach (($plans ?? []) as $p):
            $pn = (int) $p['plan_no'];
            $active = $pn === $no;
          ?>
            <a class="ws-plan-nav-item<?= $active ? ' is-active' : '' ?>"
               href="<?= url('/curriculum/' . $pn) ?>"
               <?= $active ? 'aria-current="page"' : '' ?>>
              <span class="ws-plan-nav-num"><?= $pn ?></span>
              <span class="ws-plan-nav-text">
                <strong>แผนที่ <?= $pn ?></strong>
                <small><?= e($p['title']) ?></small>
              </span>
            </a>
          <?php endforeach; ?>
        </nav>
      </aside>
    </div>
  </div>
</div>
