<?php
$plan = $plan ?? [];
$trainers = $trainers ?? [];
$learners = $learners ?? [];
$role = $role ?? 'user';
$pid = (int) ($plan['id'] ?? 0);
?>
<div class="page-head">
  <div>
    <p class="page-kicker">PLAN CHAT</p>
    <h1>เลือกห้องสนทนา</h1>
    <p class="text-muted mb-0">แผนที่ <?= (int) ($plan['plan_no'] ?? 0) ?> · <?= e($plan['title'] ?? '') ?></p>
  </div>
  <a class="btn btn-learn-outline" href="<?= url('/admin/learn/' . $pid) ?>">กลับบทเรียน</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <form method="get" action="<?= url('/admin/plan-chat/plan/' . $pid) ?>" class="card-soft h-100 plan-chat-pick-card">
      <input type="hidden" name="room" value="group">
      <div class="card-body d-flex flex-column">
        <span class="plan-chat-type-badge is-group mb-2">ห้องรวม</span>
        <strong class="mb-1">ห้องสนทนารวมของแผนนี้</strong>
        <p class="small text-muted flex-grow-1">วิทยากรและผู้เรียนพูดคุยร่วมกันในห้องเดียว · ประวัติเก็บรวมของแผน</p>
        <button class="btn btn-primary" type="submit"><i class="fa fa-users"></i> เข้าห้องรวม</button>
      </div>
    </form>
  </div>
  <div class="col-md-6">
    <div class="card-soft h-100 plan-chat-pick-card">
      <div class="card-body">
        <span class="plan-chat-type-badge is-direct mb-2">ตัวต่อตัว</span>
        <strong class="mb-1 d-block">สนทนาตัวต่อตัว</strong>
        <p class="small text-muted">คุยส่วนตัวระหว่างวิทยากรกับผู้เรียนคนนั้น · แยกประวัติชัดเจน</p>
        <?php if ($role === 'trainer'): ?>
          <?php if ($learners === []): ?>
            <p class="small text-warning mb-0">ยังไม่มีผู้เรียนสำหรับเริ่มคุย</p>
          <?php else: ?>
            <form method="post" action="<?= url('/admin/plan-chat/start') ?>" class="row g-2">
              <?= csrf_field() ?>
              <input type="hidden" name="room" value="direct">
              <input type="hidden" name="plan_id" value="<?= $pid ?>">
              <div class="col-12">
                <select name="learner_id" class="form-select" required>
                  <option value="">— เลือกผู้เรียน —</option>
                  <?php foreach ($learners as $u): ?>
                    <option value="<?= (int) $u['id'] ?>"><?= e(trim(($u['title'] ?? '') . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <button class="btn btn-outline-primary w-100" type="submit"><i class="fa fa-user"></i> เริ่มคุยตัวต่อตัว</button>
              </div>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($trainers === []): ?>
            <p class="small text-warning mb-0">แผนนี้ยังไม่มีวิทยากรที่ได้รับมอบหมาย (เจ้าของหลักสูตรที่อนุมัติแล้ว)</p>
          <?php else: ?>
            <form method="post" action="<?= url('/admin/plan-chat/start') ?>" class="row g-2">
              <?= csrf_field() ?>
              <input type="hidden" name="room" value="direct">
              <input type="hidden" name="plan_id" value="<?= $pid ?>">
              <div class="col-12">
                <label class="form-label small mb-1">วิทยากรของแผนนี้</label>
                <select name="trainer_id" class="form-select" required>
                  <option value="">— เลือกวิทยากร —</option>
                  <?php foreach ($trainers as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= e(trim(($t['title'] ?? '') . ($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''))) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <button class="btn btn-outline-primary w-100" type="submit"><i class="fa fa-user"></i> เริ่มคุยตัวต่อตัว</button>
              </div>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
