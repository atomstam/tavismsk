<?php
$threads = $threads ?? [];
$plans = $plans ?? [];
$trainersByPlan = $trainersByPlan ?? [];
$learners = $learners ?? [];
$role = $role ?? 'user';
$filterPlan = (int) ($_GET['plan_id'] ?? 0);
?>
<div class="page-head">
  <div>
    <p class="page-kicker">PLAN CHAT</p>
    <h1>กระดานสนทนา</h1>
    <p class="text-muted mb-0">เลือกห้องรวมของแผน หรือสนทนาตัวต่อตัวระหว่างวิทยากรกับผู้เรียน</p>
  </div>
</div>

<div class="card-soft mb-4"><div class="card-body">
  <h2 class="h6 mb-3">เริ่ม / เข้าห้องสนทนา</h2>
  <form method="post" action="<?= url('/admin/plan-chat/start') ?>" class="row g-2 align-items-end" id="pc-start-form">
    <?= csrf_field() ?>
    <div class="col-md-4">
      <label class="form-label">แผนการเรียนรู้</label>
      <select name="plan_id" id="pc-plan" class="form-select" required>
        <option value="">— เลือกแผน —</option>
        <?php foreach ($plans as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= $filterPlan === (int) $p['id'] ? 'selected' : '' ?>>
            แผนที่ <?= (int) $p['plan_no'] ?> — <?= e($p['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">ประเภทห้อง</label>
      <select name="room" id="pc-room" class="form-select" required>
        <option value="group">ห้องรวมของแผน</option>
        <option value="direct">ตัวต่อตัว</option>
      </select>
    </div>
    <div class="col-md-3 pc-direct-only">
      <?php if ($role === 'trainer'): ?>
        <label class="form-label">ผู้เรียน</label>
        <select name="learner_id" id="pc-learner" class="form-select">
          <option value="">— เลือกผู้เรียน —</option>
          <?php foreach ($learners as $u): ?>
            <option value="<?= (int) $u['id'] ?>"><?= e(trim(($u['title'] ?? '') . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <label class="form-label">วิทยากรของแผน</label>
        <select name="trainer_id" id="pc-trainer" class="form-select">
          <option value="">— เลือกวิทยากรที่ดูแลแผนนี้ —</option>
        </select>
      <?php endif; ?>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100" type="submit"><i class="fa fa-comments"></i> เข้าห้อง</button>
    </div>
  </form>
</div></div>
<script>
(function () {
  var map = <?= json_encode($trainersByPlan, JSON_UNESCAPED_UNICODE) ?>;
  var plan = document.getElementById('pc-plan');
  var room = document.getElementById('pc-room');
  var trainer = document.getElementById('pc-trainer');
  var directBlocks = document.querySelectorAll('.pc-direct-only');
  function fillTrainers() {
    if (!trainer) return;
    var pid = plan.value;
    trainer.innerHTML = '<option value="">— เลือกวิทยากรที่ดูแลแผนนี้ —</option>';
    var list = map[pid] || [];
    if (pid && list.length === 0) {
      var empty = document.createElement('option');
      empty.value = '';
      empty.disabled = true;
      empty.textContent = 'แผนนี้ยังไม่มีวิทยากรที่ได้รับมอบหมาย';
      trainer.appendChild(empty);
      return;
    }
    list.forEach(function (t) {
      var o = document.createElement('option');
      o.value = t.id;
      o.textContent = ((t.title || '') + (t.first_name || '') + ' ' + (t.last_name || '')).trim();
      trainer.appendChild(o);
    });
  }
  function toggleDirect() {
    var isDirect = room.value === 'direct';
    directBlocks.forEach(function (el) { el.style.display = isDirect ? '' : 'none'; });
    if (trainer) trainer.required = isDirect;
    var learner = document.getElementById('pc-learner');
    if (learner) learner.required = isDirect;
  }
  plan && plan.addEventListener('change', fillTrainers);
  room && room.addEventListener('change', toggleDirect);
  fillTrainers();
  toggleDirect();
})();
</script>

<div class="card-soft"><div class="card-body p-0">
  <?php
  $visible = [];
  foreach ($threads as $t) {
      if ($filterPlan > 0 && (int) $t['plan_id'] !== $filterPlan) {
          continue;
      }
      $visible[] = $t;
  }
  ?>
  <?php if ($visible === []): ?>
    <div class="p-4 text-center text-muted">ยังไม่มีการสนทนา — เริ่มจากแบบฟอร์มด้านบน</div>
  <?php else: ?>
    <ul class="plan-chat-thread-list">
      <?php foreach ($visible as $t):
        $unread = (int) ($t['unread_count'] ?? 0);
        $isGroup = ($t['thread_type'] ?? 'direct') === 'group';
      ?>
        <li>
          <a href="<?= url('/admin/plan-chat/' . (int) $t['id']) ?>" class="plan-chat-thread-item">
            <div class="plan-chat-thread-main">
              <strong>
                <span class="plan-chat-type-badge <?= $isGroup ? 'is-group' : 'is-direct' ?>">
                  <?= $isGroup ? 'ห้องรวม' : 'ตัวต่อตัว' ?>
                </span>
                แผนที่ <?= (int) $t['plan_no'] ?> · <?= e($t['plan_title']) ?>
              </strong>
              <span class="text-muted small d-block">
                <?php if ($isGroup): ?>
                  สนทนาเปิดของแผนนี้ · วิทยากรและผู้เรียนในแผนเข้าร่วมได้
                <?php else: ?>
                  ผู้เรียน: <?= e($t['learner_name'] ?: '-') ?> · วิทยากร: <?= e($t['trainer_name'] ?: '-') ?>
                <?php endif; ?>
              </span>
              <?php if (!empty($t['last_body'])): ?>
                <span class="plan-chat-preview"><?= e(mb_strimwidth((string) $t['last_body'], 0, 90, '…')) ?></span>
              <?php endif; ?>
            </div>
            <div class="plan-chat-thread-meta">
              <?php if ($unread > 0): ?><span class="plan-chat-unread"><?= $unread ?></span><?php endif; ?>
              <span class="small text-muted"><?= e(thai_date($t['last_message_at'] ?? $t['created_at'], true)) ?></span>
              <i class="fa fa-chevron-right text-muted"></i>
            </div>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div></div>
