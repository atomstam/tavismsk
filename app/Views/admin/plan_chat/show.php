<?php
$thread = $thread ?? [];
$messages = $messages ?? [];
$meId = (int) ($meId ?? 0);
$tid = (int) ($thread['id'] ?? 0);
$trainerId = (int) ($thread['trainer_id'] ?? 0);
$isGroup = !empty($isGroup) || (($thread['thread_type'] ?? '') === 'group');
?>
<div class="page-head">
  <div>
    <p class="page-kicker">
      <span class="plan-chat-type-badge <?= $isGroup ? 'is-group' : 'is-direct' ?>"><?= $isGroup ? 'ห้องรวม' : 'ตัวต่อตัว' ?></span>
      สนทนา · แผนที่ <?= (int) ($thread['plan_no'] ?? 0) ?>
    </p>
    <h1><?= e($thread['plan_title'] ?? '') ?></h1>
    <p class="text-muted mb-0 small">
      <?php if ($isGroup): ?>
        ห้องสนทนารวมของแผนนี้ · วิทยากรและผู้เรียนเข้าร่วมได้
      <?php else: ?>
        ผู้เรียน: <?= e($thread['learner_name'] ?? '-') ?>
        · วิทยากร: <?= e($thread['trainer_name'] ?? '-') ?>
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-learn-outline" href="<?= url('/admin/plan-chat/plan/' . (int) ($thread['plan_id'] ?? 0)) ?>">เปลี่ยนห้อง</a>
    <a class="btn btn-learn-outline" href="<?= url('/admin/learn/' . (int) ($thread['plan_id'] ?? 0)) ?>">บทเรียน</a>
    <a class="btn btn-learn-outline" href="<?= url('/admin/plan-chat') ?>">รายการสนทนา</a>
  </div>
</div>

<div class="plan-chat-shell" id="plan-chat"
     data-thread="<?= $tid ?>"
     data-me="<?= $meId ?>"
     data-trainer="<?= $trainerId ?>"
     data-group="<?= $isGroup ? '1' : '0' ?>"
     data-poll-url="<?= e(url('/admin/plan-chat/' . $tid . '/messages')) ?>"
     data-post-url="<?= e(url('/admin/plan-chat/' . $tid . '/messages')) ?>">
  <div class="plan-chat-timeline" id="plan-chat-timeline" aria-live="polite">
    <?php if ($messages === []): ?>
      <div class="plan-chat-empty" id="plan-chat-empty">ยังไม่มีข้อความ · เริ่มสนทนาได้ด้านล่าง</div>
    <?php endif; ?>
    <?php foreach ($messages as $m):
      $mine = (int) $m['sender_id'] === $meId;
      $isTrainer = $trainerId > 0
        ? (int) $m['sender_id'] === $trainerId
        : in_array((string) ($m['sender_role'] ?? ''), ['trainer', 'admin'], true);
    ?>
      <article class="plan-chat-msg <?= $mine ? 'is-mine' : 'is-theirs' ?>" data-id="<?= (int) $m['id'] ?>">
        <div class="plan-chat-avatar" aria-hidden="true"><?= e(mb_substr((string) $m['sender_name'], 0, 1)) ?></div>
        <div class="plan-chat-bubble">
          <div class="plan-chat-meta">
            <strong><?= e($m['sender_name']) ?></strong>
            <span class="plan-chat-role <?= $isTrainer ? 'is-trainer' : 'is-learner' ?>"><?= $isTrainer ? 'วิทยากร' : 'ผู้เรียน' ?></span>
            <time><?= e(thai_date($m['created_at'], true)) ?></time>
          </div>
          <div class="plan-chat-body"><?= nl2br(e($m['body'])) ?></div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <form class="plan-chat-composer" id="plan-chat-form">
    <?= csrf_field() ?>
    <textarea name="body" id="plan-chat-body" rows="2" maxlength="4000" placeholder="พิมพ์ข้อความ..." required></textarea>
    <button type="submit" class="btn btn-primary" id="plan-chat-send"><i class="fa fa-paper-plane"></i> ส่ง</button>
  </form>
</div>
<script src="<?= asset('js/plan-chat.js') ?>?v=2"></script>
