<?php
$plan = $plan ?? [];
$progress = $progress ?? [];
$settings = $settings ?? [];
$pid = (int)($plan['id'] ?? 0);
$pct = (float)($progress['overall_percent'] ?? 0);
$canContent = !empty($canContent);
$canPosttest = !empty($canPosttest);
$canSatisfaction = !empty($canSatisfaction);
$stage = (string)($progress['stage'] ?? 'pretest');
$learnBase = rtrim((string)($learnBase ?? '/learn'), '/') ?: '/learn';
$webShell = !empty($webShell);
$typeIcon = [
  'pdf' => 'fa-file-pdf', 'word' => 'fa-file-word', 'excel' => 'fa-file-excel',
  'gdrive' => 'fa-google-drive', 'youtube' => 'fa-youtube', 'link' => 'fa-link', 'text' => 'fa-align-left',
];
$steps = [
  ['key' => 'pretest', 'label' => 'ทดสอบก่อน', 'done' => (int)($progress['pretest_done'] ?? 0) === 1],
  ['key' => 'content', 'label' => 'เนื้อหา', 'done' => (int)($progress['content_done'] ?? 0) === 1],
  ['key' => 'task', 'label' => 'ภาระงาน', 'done' => (int)($progress['task_done'] ?? 0) === 1],
  ['key' => 'posttest', 'label' => 'สอบหลัง', 'done' => (int)($progress['posttest_done'] ?? 0) === 1],
  ['key' => 'satisfaction', 'label' => 'ความพึงพอใจ', 'done' => (int)($progress['satisfaction_done'] ?? 0) === 1],
];
$stageOrder = ['pretest' => 0, 'content' => 1, 'task' => 2, 'posttest' => 3, 'satisfaction' => 4, 'completed' => 5];
$activeIdx = $stageOrder[$stage] ?? 0;
?>
<?php if ($webShell): ?><div class="container py-4 ws-page-stack learn-web"><?php endif; ?>
<div class="page-head">
  <div>
    <p class="page-kicker">พื้นที่เรียน · แผนที่ <?= (int)($plan['plan_no'] ?? 0) ?></p>
    <h1><?= e($plan['title'] ?? '') ?></h1>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('/curriculum/' . (int)($plan['plan_no'] ?? 0)) ?>"><i class="fa fa-info-circle"></i> รายละเอียดแผน</a>
    <a class="btn btn-outline-secondary" href="<?= url($learnBase) ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
  </div>
</div>

<div class="learn-hero">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
      <div class="small opacity-75 mb-1">ความก้าวหน้าโมดูลนี้</div>
      <h1 class="h4 mb-1 text-white">เรียนตามลำดับ · บันทึกผลอัตโนมัติ</h1>
      <p>ทดสอบก่อน → เนื้อหา → ภาระงาน → สอบหลัง → ประเมินความพึงพอใจ</p>
    </div>
    <div class="text-end">
      <div class="learn-pct"><?= number_format($pct, 1) ?>%</div>
      <div class="small opacity-75">เสร็จสมบูรณ์</div>
    </div>
  </div>
  <div class="learn-progress-track"><span style="width:<?= min(100, $pct) ?>%"></span></div>
</div>

<nav class="learn-stepper" aria-label="ลำดับการเรียน">
  <?php foreach ($steps as $i => $st):
    $cls = $st['done'] ? 'is-done' : ($i === $activeIdx ? 'is-active' : '');
  ?>
    <div class="learn-step <?= $cls ?>">
      <div class="learn-step-num"><?= $st['done'] ? '<i class="fa fa-check"></i>' : ($i + 1) ?></div>
      <span class="learn-step-label"><?= e($st['label']) ?></span>
    </div>
  <?php endforeach; ?>
</nav>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="learn-module-card <?= (int)($progress['pretest_done']??0)?'is-done':($stage==='pretest'?'is-active':'') ?>">
      <div class="card-body">
        <h2><i class="fa fa-clipboard-question"></i> 1) ทดสอบก่อนเรียน
          <?php if ((int)($progress['pretest_done']??0)): ?><span class="learn-pill ok">เสร็จแล้ว</span><?php endif; ?>
        </h2>
        <?php if ($preAttempt): ?>
          <p class="mb-2 small">คะแนน <?= number_format((float)$preAttempt['percent'],1) ?>%
            <?= (int)$preAttempt['is_passed'] ? '<span class="learn-pill ok">ผ่าน</span>' : '<span class="learn-pill">ส่งแล้ว</span>' ?>
          </p>
        <?php elseif ($preQuiz): ?>
          <p class="text-muted small">ทำแบบทดสอบก่อนเรียนเพื่อปลดล็อกเนื้อหา</p>
          <a class="btn btn-learn-primary" href="<?= url($learnBase . '/' . $pid . '/quiz/pretest') ?>">เริ่มทดสอบก่อน</a>
        <?php else: ?>
          <p class="text-muted mb-0 small">ยังไม่มีข้อสอบ pretest (ผ่านขั้นตอนนี้โดยอัตโนมัติ)</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="learn-module-card <?= !$canContent?'is-locked':((int)($progress['content_done']??0)?'is-done':($stage==='content'?'is-active':'')) ?>">
      <div class="card-body">
        <h2><i class="fa fa-book-open"></i> 2) เนื้อหา
          <?php if (!$canContent): ?><span class="learn-pill lock"><i class="fa fa-lock"></i> ล็อก</span>
          <?php elseif ((int)($progress['content_done']??0)): ?><span class="learn-pill ok">ครบแล้ว</span><?php endif; ?>
        </h2>
        <?php if (!$canContent): ?>
          <div class="alert alert-warning py-2 small mb-0">กรุณาทำทดสอบก่อนเรียนก่อนเข้าเรียนเนื้อหา</div>
        <?php elseif (empty($contents)): ?>
          <p class="text-muted mb-0 small">ยังไม่มีเนื้อหา — ผู้สอนเพิ่มได้ที่จัดการบทเรียน LMS</p>
        <?php else: ?>
          <ul class="list-group list-group-flush">
          <?php foreach ($contents as $c):
            $done = !empty($contentViews[(int)$c['id']]['is_completed']);
          ?>
            <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
              <div>
                <i class="fa <?= e($typeIcon[$c['content_type']] ?? 'fa-file') ?> me-1 text-primary"></i>
                <?= e($c['title']) ?>
                <?= $done ? '<span class="learn-pill ok">เรียนแล้ว</span>' : '' ?>
              </div>
              <div class="d-flex gap-1">
                <a class="btn btn-sm btn-outline-primary" href="<?= url($learnBase . '/' . $pid . '/content/' . (int)$c['id']) ?>">เปิด</a>
                <?php if (!$done): ?>
                <form method="post" action="<?= url($learnBase . '/' . $pid . '/content/' . (int)$c['id'] . '/complete') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-success" type="submit">อ่านแล้ว</button>
                </form>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="learn-module-card <?= !$canContent?'is-locked':((int)($progress['task_done']??0)?'is-done':($stage==='task'?'is-active':'')) ?>">
      <div class="card-body">
        <h2><i class="fa fa-list-check"></i> 3) ภาระงาน
          <?php if (!$canContent): ?><span class="learn-pill lock"><i class="fa fa-lock"></i> ล็อก</span>
          <?php elseif ((int)($progress['task_done']??0)): ?><span class="learn-pill ok">ครบแล้ว</span><?php endif; ?>
        </h2>
        <?php if (!$canContent): ?>
          <div class="alert alert-warning py-2 small mb-0">ปลดล็อกหลังทดสอบก่อนเรียน</div>
        <?php elseif (empty($tasks)): ?>
          <p class="text-muted mb-0 small">ไม่มีภาระงานในโมดูลนี้</p>
        <?php else: ?>
          <?php foreach ($tasks as $t):
            $sub = $taskSubs[(int)$t['id']] ?? null;
            $st = $sub['status'] ?? null;
          ?>
            <div class="border rounded-3 p-3 mb-2">
              <strong><?= e($t['title']) ?></strong>
              <?php if ($t['description']): ?><div class="small text-muted"><?= nl2br(e($t['description'])) ?></div><?php endif; ?>
              <?php if ($st): ?>
                <div class="small mb-2 mt-1">สถานะ: <span class="badge text-bg-<?= $st==='approved'?'success':($st==='rejected'?'danger':'warning') ?>"><?= e($st) ?></span>
                  <?php if ($sub['feedback'] ?? null): ?> · <?= e($sub['feedback']) ?><?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if (!$st || in_array($st, ['rejected','revision'], true)): ?>
              <form method="post" action="<?= url($learnBase . '/' . $pid . '/task/' . (int)$t['id'] . '/submit') ?>" enctype="multipart/form-data" class="mt-2">
                <?= csrf_field() ?>
                <textarea name="answer_text" class="form-control form-control-sm mb-1" rows="2" placeholder="คำตอบ / บันทึก"><?= e($sub['answer_text'] ?? '') ?></textarea>
                <?php if ((int)$t['allow_link']): ?>
                  <input name="link_url" class="form-control form-control-sm mb-1" placeholder="ลิงก์ Google Drive / อื่นๆ" value="<?= e($sub['link_url'] ?? '') ?>">
                <?php endif; ?>
                <input type="file" name="file" class="form-control form-control-sm mb-2">
                <button class="btn btn-sm btn-learn-primary" type="submit">ส่งงาน</button>
              </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="learn-module-card <?= !$canPosttest?'is-locked':((int)($progress['posttest_done']??0)?'is-done':($stage==='posttest'?'is-active':'')) ?>">
      <div class="card-body">
        <h2><i class="fa fa-award"></i> 4) สอบหลังเรียน
          <?php if (!$canPosttest): ?><span class="learn-pill lock"><i class="fa fa-lock"></i> ล็อก</span>
          <?php elseif ((int)($progress['posttest_done']??0)): ?><span class="learn-pill ok">ผ่านแล้ว</span><?php endif; ?>
        </h2>
        <?php if ($postAttempt): ?>
          <p class="mb-2 small">คะแนนล่าสุด <?= number_format((float)$postAttempt['percent'],1) ?>%
            <?= (int)$postAttempt['is_passed'] ? '<span class="learn-pill ok">ผ่าน</span>' : '<span class="badge text-bg-danger">ยังไม่ผ่าน</span>' ?>
          </p>
        <?php endif; ?>
        <?php if (!$canPosttest): ?>
          <p class="text-muted small mb-0">ล็อกอยู่ — เรียนเนื้อหาและส่งภาระงานให้ครบก่อน</p>
        <?php elseif ($postQuiz): ?>
          <a class="btn btn-learn-primary" href="<?= url($learnBase . '/' . $pid . '/quiz/posttest') ?>">ทำสอบหลังเรียน</a>
        <?php else: ?>
          <p class="text-muted mb-0 small">ยังไม่มีข้อสอบ posttest (ผ่านขั้นตอนนี้โดยอัตโนมัติ)</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="learn-module-card <?= !$canSatisfaction?'is-locked':((int)($progress['satisfaction_done']??0)?'is-done':($stage==='satisfaction'?'is-active':'')) ?>">
      <div class="card-body">
        <h2><i class="fa fa-face-smile"></i> 5) แบบประเมินความพึงพอใจ
          <?php if (!$canSatisfaction): ?><span class="learn-pill lock"><i class="fa fa-lock"></i> ล็อก</span>
          <?php elseif ((int)($progress['satisfaction_done']??0)): ?><span class="learn-pill ok">ส่งแล้ว</span><?php endif; ?>
        </h2>
        <?php if ($satAttempt): ?>
          <p class="mb-2 small text-success"><i class="fa fa-check-circle"></i> ส่งแบบประเมินแล้ว ขอบคุณสำหรับความคิดเห็น</p>
        <?php elseif (!$canSatisfaction): ?>
          <p class="text-muted small mb-0">ปลดล็อกหลังสอบหลังเรียนเสร็จ</p>
        <?php elseif ($satQuiz): ?>
          <p class="text-muted small">ช่วยประเมินความพึงพอใจต่อการเรียนในแผนนี้</p>
          <a class="btn btn-learn-primary" href="<?= url($learnBase . '/' . $pid . '/quiz/satisfaction') ?>">ทำแบบประเมิน</a>
        <?php else: ?>
          <p class="text-muted mb-0 small">ยังไม่มีแบบประเมินในโมดูลนี้ (ผ่านขั้นตอนนี้โดยอัตโนมัติ) — ผู้สอนสามารถเพิ่มได้ที่จัดการบทเรียน LMS</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php if ($webShell): ?></div><?php endif; ?>
