<?php
$plan = $plan ?? [];
$quiz = $quiz ?? [];
$questions = $questions ?? [];
$type = $type ?? 'pretest';
$pid = (int)($plan['id'] ?? 0);
$learnBase = rtrim((string)($learnBase ?? '/learn'), '/') ?: '/learn';
$webShell = !empty($webShell);
$typeLabel = match ($type) {
  'pretest' => 'PRETEST',
  'posttest' => 'POSTTEST',
  default => 'ความพึงพอใจ',
};
?>
<?php if ($webShell): ?><div class="container py-4 ws-page-stack learn-web"><?php endif; ?>
<div class="page-head">
  <div>
    <p class="page-kicker"><?= e($typeLabel) ?></p>
    <h1><?= e($quiz['title'] ?? '') ?></h1>
  </div>
  <a class="btn <?= $webShell ? 'btn-outline-secondary' : 'btn-learn-outline' ?>" href="<?= url($learnBase . '/' . $pid) ?>">ยกเลิก</a>
</div>

<div class="card-soft mb-3"><div class="card-body small">
  <div>เกณฑ์ผ่าน <?= number_format((float)($quiz['pass_score']??60),0) ?>% · เหลือ <?= (int)($attemptsLeft ?? 0) ?> ครั้ง</div>
  <?php if (!empty($quiz['description'])): ?><div class="text-muted mt-1"><?= nl2br(e($quiz['description'])) ?></div><?php endif; ?>
</div></div>

<form method="post" action="<?= url($learnBase . '/' . $pid . '/quiz/' . $type) ?>" id="quiz-form">
  <?= csrf_field() ?>
  <?php foreach ($questions as $i => $q): ?>
    <div class="card-soft mb-3"><div class="card-body">
      <div class="fw-semibold mb-2"><?= ($i+1) ?>. <?= e($q['question_text']) ?>
        <span class="badge text-bg-light border"><?= e((string)$q['points']) ?> คะแนน</span>
      </div>
      <?php if (!empty($q['image_path'])): ?><img class="quiz-figure" src="<?= e(upload_url($q['image_path'])) ?>" alt=""><?php endif; ?>
      <?php foreach (($q['choices'] ?? []) as $ch): ?>
        <div class="form-check quiz-choice mb-2">
          <input class="form-check-input" type="radio" name="answers[<?= (int)$q['id'] ?>]" id="c<?= (int)$ch['id'] ?>" value="<?= (int)$ch['id'] ?>" required>
          <label class="form-check-label" for="c<?= (int)$ch['id'] ?>">
            <?php if (!empty($ch['image_path'])): ?><img class="quiz-figure quiz-figure-sm" src="<?= e(upload_url($ch['image_path'])) ?>" alt=""><?php endif; ?>
            <?= e(trim((string)($ch['choice_text'] ?? ''))) ?>
          </label>
        </div>
      <?php endforeach; ?>
    </div></div>
  <?php endforeach; ?>

  <?php if (!$questions): ?>
    <div class="alert alert-warning">ยังไม่มีข้อสอบ</div>
  <?php else: ?>
    <button class="btn btn-primary btn-lg" type="submit"
            data-confirm="ยืนยันส่งคำตอบ?"
            data-confirm-title="ส่งแบบทดสอบ"
            data-confirm-ok="ส่งคำตอบ">ส่งคำตอบ</button>
  <?php endif; ?>
</form>
<?php if ($webShell): ?></div><?php endif; ?>
