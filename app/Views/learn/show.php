<?php
$plan = $plan ?? [];
$progress = $progress ?? [];
$settings = $settings ?? [];
$pid = (int)($plan['id'] ?? 0);
$pct = (float)($progress['overall_percent'] ?? 0);
$canContent = !empty($canContent);
$canTask = !empty($canTask);
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
    <p class="page-kicker">พื้นที่เรียน · แผนที่ <?= (int)($plan['plan_no'] ?? 0) ?> · <?= e(\App\Services\PlanProgress::trackLabel($plan)) ?></p>
    <h1><?= e($plan['title'] ?? '') ?></h1>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php if (!$webShell && can_perm('plan_chat', 'view')): ?>
      <a class="btn btn-learn-primary" href="<?= url('/admin/plan-chat/plan/' . $pid) ?>"><i class="fa fa-comments"></i> กระดานสนทนา</a>
    <?php endif; ?>
    <a class="btn <?= $webShell ? 'btn-outline-secondary' : 'btn-learn-outline' ?>" href="<?= url($webShell ? '/curriculum/' . (int)($plan['plan_no'] ?? 0) : '/admin/curriculum/' . $pid) ?>"><i class="fa fa-circle-info"></i> รายละเอียดแผน</a>
    <a class="btn <?= $webShell ? 'btn-outline-secondary' : 'btn-learn-outline' ?>" href="<?= url($learnBase) ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
  </div>
</div>

<div class="learn-hero">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
      <div class="small opacity-75 mb-1">ความก้าวหน้าการเรียน (ครบองค์ประกอบ)</div>
      <h1 class="h4 mb-1 text-white">เรียนตามลำดับ · บันทึกผลอัตโนมัติ</h1>
      <p>ทดสอบก่อน → เนื้อหา → ภาระงาน → สอบหลัง → ประเมินความพึงพอใจ</p>
    </div>
    <div class="text-end">
      <div class="learn-pct"><?= number_format($pct, 1) ?>%</div>
      <div class="small opacity-75">
        <?php if ((int) ($progress['is_passed'] ?? 0) === 1): ?>
          ผ่านแผนแล้ว · คะแนนรวม <?= number_format((float) ($progress['score_percent'] ?? 0), 1) ?>%
        <?php else: ?>
          ดำเนินการเรียนครบองค์ประกอบ → 100%
        <?php endif; ?>
      </div>
      <?php if ((int)($progress['is_passed'] ?? 0) === 1): ?>
        <a class="btn btn-light btn-sm mt-2" href="<?= url('/admin/certificates/plan/' . $pid . '/view') ?>">
          <i class="fa fa-file-pdf"></i> พิมพ์เกียรติบัตร PDF
        </a>
      <?php else: ?>
        <div class="small opacity-75 mt-1">ผ่านเกณฑ์แล้วจึงพิมพ์เกียรติบัตรของแผนนี้ได้ (ครบทุกขั้น · ภาระงาน ≥70% · สอบหลัง ≥70%)</div>
      <?php endif; ?>
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

<div class="row g-3 learn-steps-stack">
  <div class="col-12">
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

  <div class="col-12">
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
          <ul class="list-group list-group-flush" id="learn-content-list"
              data-csrf="<?= e(csrf_token()) ?>">
          <?php foreach ($contents as $c):
            $cid = (int) $c['id'];
            $done = !empty($contentViews[$cid]['is_completed']);
            $openOutside = in_array((string) $c['content_type'], ['gdrive', 'link'], true);
            $outsideUrl = trim((string) ($c['external_url'] ?? ''));
            $openHref = ($openOutside && $outsideUrl !== '')
              ? $outsideUrl
              : url($learnBase . '/' . $pid . '/content/' . $cid);
            $completeUrl = url($learnBase . '/' . $pid . '/content/' . $cid . '/complete');
          ?>
            <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2 learn-content-item<?= $done ? ' is-done' : '' ?>"
                data-content-id="<?= $cid ?>"
                data-complete-url="<?= e($completeUrl) ?>"
                data-done="<?= $done ? '1' : '0' ?>">
              <div class="learn-content-title">
                <i class="fa <?= e($typeIcon[$c['content_type']] ?? 'fa-file') ?> me-1 text-primary"></i>
                <?= e($c['title']) ?>
                <span class="learn-pill ok learn-content-done-pill<?= $done ? '' : ' is-pending' ?>"<?= $done ? '' : ' hidden' ?>>เรียนแล้ว</span>
              </div>
              <div class="d-flex gap-1">
                <a class="btn btn-sm btn-outline-primary learn-content-open"
                   href="<?= e($openHref) ?>"
                   data-content-id="<?= $cid ?>"
                   data-outside="<?= $openOutside ? '1' : '0' ?>"
                   <?= $openOutside ? ' target="_blank" rel="noopener"' : '' ?>>เปิด</a>
              </div>
            </li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php
    $taskStatusLabel = static function (?string $st): string {
      return match ((string) $st) {
        'submitted' => 'ส่งแล้ว · รอวิทยากรตรวจ',
        'approved' => 'ตรวจผ่าน',
        'rejected' => 'ไม่ผ่าน',
        'revision' => 'ต้องแก้ไข',
        default => (string) $st,
      };
    };
    $posttestUrl = $postQuiz ? url($learnBase . '/' . $pid . '/quiz/posttest') : '';
    $satisfactionUrl = $satQuiz ? url($learnBase . '/' . $pid . '/quiz/satisfaction') : '';
  ?>
  <div class="col-12">
    <div id="learn-card-task"
         class="learn-module-card <?= !$canTask?'is-locked':((int)($progress['task_done']??0)?'is-done':($stage==='task'?'is-active':'')) ?>"
         data-can="<?= $canTask ? '1' : '0' ?>"
         data-done="<?= (int)($progress['task_done']??0) ? '1' : '0' ?>">
      <div class="card-body">
        <h2><i class="fa fa-list-check"></i> 3) ภาระงาน
          <span class="learn-pill lock learn-gate-lock"<?= $canTask ? ' hidden' : '' ?>><i class="fa fa-lock"></i> ล็อก</span>
          <span class="learn-pill ok learn-gate-done"<?= ((int)($progress['task_done']??0) && $canTask) ? '' : ' hidden' ?>>ตรวจครบแล้ว</span>
        </h2>
        <?php if ($canTask): ?>
          <p class="small text-muted mb-2">
            คะแนนภาระงาน
            <?php if ((int)($progress['task_done']??0) === 1): ?>
              <strong><?= number_format((float)($progress['task_score_earned']??0), 1) ?></strong>
              / <?= number_format((float)($progress['task_score_max']??0), 0) ?>
              (<?= number_format((float)($progress['task_percent']??0), 0) ?>%)
            <?php else: ?>
              <span class="text-warning">รอวิทยากรตรวจให้คะแนนก่อน จึงคิดร้อยละ</span>
              · ขณะนี้ <?= number_format((float)($progress['task_percent']??0), 0) ?>%
            <?php endif; ?>
          </p>
        <?php endif; ?>
        <div class="learn-task-locked alert alert-warning py-2 small mb-0"<?= $canTask ? ' hidden' : '' ?>>
          <?= !$canContent ? 'ปลดล็อกหลังทดสอบก่อนเรียน' : 'กรุณาเปิดดูเนื้อหาให้ครบทุกรายการก่อนทำภาระงาน' ?>
        </div>
        <div class="learn-task-body"<?= $canTask ? '' : ' hidden' ?>>
        <?php if (empty($tasks)): ?>
          <p class="text-muted mb-0 small">ไม่มีภาระงานในโมดูลนี้</p>
        <?php else: ?>
          <?php foreach ($tasks as $t):
            $sub = $taskSubs[(int)$t['id']] ?? null;
            $st = $sub['status'] ?? null;
            $tid = (int) $t['id'];
          ?>
            <div class="border rounded-3 p-3 mb-2 learn-task-item" data-task-id="<?= $tid ?>" data-status="<?= e((string) $st) ?>">
              <strong><?= e($t['title']) ?></strong>
              <?php if ($t['description']): ?><div class="small text-muted"><?= nl2br(e($t['description'])) ?></div><?php endif; ?>
              <div class="learn-task-status small mb-2 mt-1"<?= $st ? '' : ' hidden' ?>>
                สถานะ: <span class="badge text-bg-<?= $st==='approved'?'success':($st==='rejected'?'danger':'warning') ?> learn-task-status-badge"><?= e($taskStatusLabel($st)) ?></span>
                <?php
                  $tmax = (float) ($t['max_score'] ?? 100);
                  if ($st === 'approved' && isset($sub['score']) && $sub['score'] !== null && $sub['score'] !== ''):
                    $tscore = (float) $sub['score'];
                    $tpct = $tmax > 0 ? round(($tscore / $tmax) * 100, 0) : 0;
                ?>
                  · คะแนน <?= number_format($tscore, 1) ?>/<?= number_format($tmax, 0) ?> (<?= $tpct ?>%)
                <?php elseif ($st === 'submitted'): ?>
                  · เต็ม <?= number_format($tmax, 0) ?> คะแนน
                <?php endif; ?>
                <?php if ($sub['feedback'] ?? null): ?> · <?= e($sub['feedback']) ?><?php endif; ?>
              </div>
              <?php if (!$st || in_array($st, ['rejected','revision'], true)): ?>
              <form method="post" action="<?= url($learnBase . '/' . $pid . '/task/' . $tid . '/submit') ?>" enctype="multipart/form-data" class="mt-2 learn-task-form">
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
  </div>

  <div class="col-12">
    <div id="learn-card-posttest"
         class="learn-module-card <?= !$canPosttest?'is-locked':((int)($progress['posttest_done']??0)?'is-done':($stage==='posttest'?'is-active':'')) ?>"
         data-can="<?= $canPosttest ? '1' : '0' ?>"
         data-quiz-url="<?= e($posttestUrl) ?>"
         data-has-quiz="<?= $postQuiz ? '1' : '0' ?>">
      <div class="card-body">
        <h2><i class="fa fa-award"></i> 4) สอบหลังเรียน
          <span class="learn-pill lock learn-gate-lock"<?= $canPosttest ? ' hidden' : '' ?>><i class="fa fa-lock"></i> ล็อก</span>
          <span class="learn-pill ok learn-gate-done"<?= ((int)($progress['posttest_done']??0) && $canPosttest) ? '' : ' hidden' ?>>ผ่านแล้ว</span>
        </h2>
        <?php if ($postAttempt): ?>
          <p class="mb-2 small learn-post-score">คะแนนล่าสุด <?= number_format((float)$postAttempt['percent'],1) ?>%
            <?= (int)$postAttempt['is_passed'] ? '<span class="learn-pill ok">ผ่าน</span>' : '<span class="badge text-bg-danger">ยังไม่ผ่าน</span>' ?>
          </p>
        <?php endif; ?>
        <div class="learn-gate-locked-msg text-muted small mb-0"<?= $canPosttest ? ' hidden' : '' ?>>ล็อกอยู่ — เปิดดูเนื้อหาและส่งภาระงานให้ครบก่อนจึงสอบหลังได้</div>
        <div class="learn-gate-open"<?= $canPosttest ? '' : ' hidden' ?>>
          <?php if ($postQuiz): ?>
            <a class="btn btn-learn-primary learn-posttest-btn" href="<?= e($posttestUrl) ?>">ทำสอบหลังเรียน</a>
          <?php else: ?>
            <p class="text-muted mb-0 small">ยังไม่มีข้อสอบ posttest (ผ่านขั้นตอนนี้โดยอัตโนมัติ)</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div id="learn-card-satisfaction"
         class="learn-module-card <?= !$canSatisfaction?'is-locked':((int)($progress['satisfaction_done']??0)?'is-done':($stage==='satisfaction'?'is-active':'')) ?>"
         data-can="<?= $canSatisfaction ? '1' : '0' ?>"
         data-quiz-url="<?= e($satisfactionUrl) ?>"
         data-has-quiz="<?= $satQuiz ? '1' : '0' ?>">
      <div class="card-body">
        <h2><i class="fa fa-face-smile"></i> 5) แบบประเมินความพึงพอใจ
          <span class="learn-pill lock learn-gate-lock"<?= $canSatisfaction ? ' hidden' : '' ?>><i class="fa fa-lock"></i> ล็อก</span>
          <span class="learn-pill ok learn-gate-done"<?= ((int)($progress['satisfaction_done']??0) && $canSatisfaction) ? '' : ' hidden' ?>>ส่งแล้ว</span>
        </h2>
        <?php if ($satAttempt): ?>
          <p class="mb-2 small text-success learn-sat-done-msg"><i class="fa fa-check-circle"></i> ส่งแบบประเมินแล้ว ขอบคุณสำหรับความคิดเห็น</p>
        <?php endif; ?>
        <div class="learn-gate-locked-msg text-muted small mb-0"<?= ($canSatisfaction || $satAttempt) ? ' hidden' : '' ?>>ปลดล็อกหลังทำแบบทดสอบหลังเรียนเสร็จ</div>
        <div class="learn-gate-open"<?= ($canSatisfaction && !$satAttempt) ? '' : ' hidden' ?>>
          <?php if ($satQuiz): ?>
            <p class="text-muted small">ช่วยประเมินความพึงพอใจต่อการเรียนในแผนนี้</p>
            <a class="btn btn-learn-primary learn-satisfaction-btn" href="<?= e($satisfactionUrl) ?>">ทำแบบประเมิน</a>
          <?php else: ?>
            <p class="text-muted mb-0 small">ยังไม่มีแบบประเมินในโมดูลนี้ (ผ่านขั้นตอนนี้โดยอัตโนมัติ)</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!$webShell && can_perm('plan_chat', 'view')): ?>
<div class="card-soft mt-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
  <div>
    <h2 class="h6 mb-1"><i class="fa fa-comments text-primary"></i> กระดานสนทนาของแผนนี้</h2>
    <p class="small text-muted mb-0">เลือกห้องรวมของแผน หรือคุยตัวต่อตัวกับวิทยากร · เก็บประวัติย้อนหลังได้</p>
  </div>
  <a class="btn btn-learn-primary" href="<?= url('/admin/plan-chat/plan/' . $pid) ?>">เลือกห้องสนทนา</a>
</div></div>
<?php endif; ?>
<?php if ($webShell): ?></div><?php endif; ?>
<script>
(function () {
  var list = document.getElementById('learn-content-list');
  var csrf = (list && list.getAttribute('data-csrf')) || (document.querySelector('input[name="_token"]') || {}).value || '';
  var cardTask = document.getElementById('learn-card-task');
  var cardPost = document.getElementById('learn-card-posttest');
  var cardSat = document.getElementById('learn-card-satisfaction');

  function setHidden(el, hide) {
    if (!el) return;
    el.hidden = !!hide;
  }

  function unlockGate(card, opts) {
    if (!card) return;
    opts = opts || {};
    card.setAttribute('data-can', '1');
    card.classList.remove('is-locked');
    if (opts.done) {
      card.classList.add('is-done');
      card.setAttribute('data-done', '1');
    } else {
      card.classList.add('is-active');
    }
    setHidden(card.querySelector('.learn-gate-lock'), true);
    setHidden(card.querySelector('.learn-gate-done'), !opts.done);
    setHidden(card.querySelector('.learn-gate-locked-msg'), true);
    setHidden(card.querySelector('.learn-gate-open'), !!opts.hideOpen);
  }

  function unlockTasks() {
    if (!cardTask) return;
    cardTask.setAttribute('data-can', '1');
    cardTask.classList.remove('is-locked');
    cardTask.classList.add('is-active');
    setHidden(cardTask.querySelector('.learn-gate-lock'), true);
    setHidden(cardTask.querySelector('.learn-task-locked'), true);
    setHidden(cardTask.querySelector('.learn-task-body'), false);
  }

  function unlockPosttest() {
    if (!cardPost) return;
    unlockGate(cardPost, { hideOpen: false });
    if (cardTask) {
      cardTask.classList.remove('is-active');
      cardTask.classList.add('is-done');
      cardTask.setAttribute('data-done', '1');
      setHidden(cardTask.querySelector('.learn-gate-done'), false);
    }
  }

  function unlockSatisfaction() {
    unlockGate(cardSat, { hideOpen: false });
  }

  function applyProgress(data) {
    if (!data) return;
    if (Number(data.can_task) === 1 || Number(data.content_done) === 1) unlockTasks();
    if (Number(data.can_posttest) === 1 || Number(data.task_done) === 1) unlockPosttest();
    if (Number(data.can_satisfaction) === 1) unlockSatisfaction();
    var pctEl = document.querySelector('.learn-pct');
    if (pctEl && data.overall_percent != null) {
      pctEl.textContent = Number(data.overall_percent).toFixed(1) + '%';
    }
  }

  function findItem(contentId) {
    if (!list || !contentId) return null;
    return list.querySelector('.learn-content-item[data-content-id="' + contentId + '"]');
  }

  function markUiDone(contentId) {
    var item = findItem(contentId);
    if (!item) return;
    item.setAttribute('data-done', '1');
    item.classList.add('is-done');
    var pill = item.querySelector('.learn-content-done-pill');
    if (pill) {
      pill.hidden = false;
      pill.classList.remove('is-pending');
    }
  }

  function completeContent(item) {
    if (!item) return Promise.resolve(false);
    var contentId = String(item.getAttribute('data-content-id') || '');
    if (!contentId) return Promise.resolve(false);
    if (item.getAttribute('data-done') === '1') {
      return Promise.resolve(true);
    }
    var url = item.getAttribute('data-complete-url');
    if (!url) return Promise.resolve(false);
    var body = new URLSearchParams();
    body.set('_token', csrf);
    return fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': csrf
      },
      body: body.toString(),
      credentials: 'same-origin'
    }).then(function (res) {
      return res.json().catch(function () { return { ok: res.ok, content_id: Number(contentId) }; });
    }).then(function (data) {
      if (!data || !data.ok) return false;
      var doneId = String(data.content_id != null ? data.content_id : contentId);
      if (doneId !== contentId) return false;
      markUiDone(doneId);
      applyProgress(data);
      return true;
    }).catch(function () { return false; });
  }

  if (list) {
    list.addEventListener('click', function (e) {
      var openBtn = e.target.closest('.learn-content-open');
      if (!openBtn || !list.contains(openBtn)) return;
      var item = openBtn.closest('.learn-content-item');
      if (!item) return;
      var contentId = openBtn.getAttribute('data-content-id') || item.getAttribute('data-content-id');
      if (contentId && String(item.getAttribute('data-content-id')) !== String(contentId)) {
        item = findItem(contentId);
        if (!item) return;
      }
      var outside = openBtn.getAttribute('data-outside') === '1';
      if (outside) {
        completeContent(item);
        return;
      }
      e.preventDefault();
      var href = openBtn.getAttribute('href');
      completeContent(item).finally(function () {
        if (href) window.location.href = href;
      });
    });
  }

  document.querySelectorAll('.learn-task-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var item = form.closest('.learn-task-item');
      var btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'กำลังส่ง...';
      }
      var fd = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: fd,
        credentials: 'same-origin'
      }).then(function (res) {
        return res.json().catch(function () { return { ok: res.ok }; });
      }).then(function (data) {
        if (!data || !data.ok) {
          alert((data && data.error) || 'ส่งภาระงานไม่สำเร็จ');
          if (btn) {
            btn.disabled = false;
            btn.textContent = 'ส่งงาน';
          }
          return;
        }
        if (item) {
          item.setAttribute('data-status', 'submitted');
          var statusWrap = item.querySelector('.learn-task-status');
          var badge = item.querySelector('.learn-task-status-badge');
          if (statusWrap) statusWrap.hidden = false;
          if (badge) {
            badge.className = 'badge text-bg-warning learn-task-status-badge';
            badge.textContent = 'ส่งแล้ว · รอวิทยากรตรวจ';
          }
          form.remove();
        }
        applyProgress(data);
        if (Number(data.can_posttest) === 1 || Number(data.task_done) === 1) {
          unlockPosttest();
        }
      }).catch(function () {
        alert('ส่งภาระงานไม่สำเร็จ');
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'ส่งงาน';
        }
      });
    });
  });

  // ถ้าส่งงานครบแล้วตั้งแต่โหลดหน้า — แสดงปุ่มสอบหลังทันที
  if (cardPost && cardPost.getAttribute('data-can') === '1') {
    unlockPosttest();
  }
  if (cardSat && cardSat.getAttribute('data-can') === '1') {
    unlockSatisfaction();
  }
})();
</script>
