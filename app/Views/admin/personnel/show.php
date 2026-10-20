<?php
$r = $row ?? [];
$name = trim((string) ($r['title'] ?? '') . (string) ($r['first_name'] ?? '') . ' ' . (string) ($r['last_name'] ?? ''));
$gender = match ((string) ($r['gender'] ?? '')) {
    'male' => 'ชาย',
    'female' => 'หญิง',
    default => '—',
};
$code = trim((string) ($r['code'] ?? ''));
$idCard = trim((string) ($r['id_card'] ?? ''));
$email = trim((string) ($r['email'] ?? ''));
$notes = [
    'ที่อยู่' => trim((string) ($r['address'] ?? '')),
    'วุฒิการศึกษา' => trim((string) ($r['education'] ?? '')),
    'ประวัติการทำงาน' => trim((string) ($r['work_history'] ?? '')),
    'ประวัติการอบรม' => trim((string) ($r['training_history'] ?? '')),
];
?>
<div class="page-head">
  <div>
    <p class="page-kicker">บุคลากร</p>
    <h1><?= e($name !== '' ? $name : 'บุคลากร') ?></h1>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('/admin/personnel') ?>">กลับหน้ารายการ</a>
    <?php if (can_perm('personnel', 'edit')): ?>
      <a class="btn btn-primary" href="<?= url('/admin/personnel/' . (int) $r['id'] . '/edit') ?>">แก้ไข</a>
    <?php endif; ?>
  </div>
</div>
<div class="card-soft record-card"><div class="card-body">
  <div class="record-hero">
    <img src="<?= e(personnel_photo_url($r['photo'] ?? null) ?? avatar_url(null)) ?>" width="84" height="84" alt="">
    <div>
      <div class="record-name"><?= e($name !== '' ? $name : '—') ?></div>
      <div class="record-chips">
        <?php if ($code !== ''): ?><span class="record-chip"><?= e($code) ?></span><?php endif; ?>
        <span class="record-chip"><?= e((string) ($profileView['position'] ?? '—')) ?></span>
        <span class="record-chip"><?= e((string) ($profileView['member_type'] ?? '—')) ?></span>
      </div>
    </div>
  </div>

  <section class="record-section">
    <h2>ข้อมูลติดต่อ</h2>
    <dl class="record-grid">
      <div class="record-cell<?= $code === '' ? ' is-empty' : '' ?>"><dt>รหัส</dt><dd><?= e($code !== '' ? $code : '—') ?></dd></div>
      <div class="record-cell<?= $idCard === '' ? ' is-empty' : '' ?>"><dt>เลขบัตรประชาชน</dt><dd><?= e($idCard !== '' ? $idCard : '—') ?></dd></div>
      <div class="record-cell<?= $email === '' ? ' is-empty' : '' ?>"><dt>อีเมล</dt><dd><?= e($email !== '' ? $email : '—') ?></dd></div>
    </dl>
  </section>

  <section class="record-section">
    <h2>ข้อมูลสมัครสมาชิก</h2>
    <?php partial('partials/registration_profile_summary', ['profileView' => $profileView ?? []]); ?>
  </section>

  <section class="record-section">
    <h2>ข้อมูลเพิ่มเติม</h2>
    <dl class="record-grid">
      <div class="record-cell<?= $gender === '—' ? ' is-empty' : '' ?>"><dt>เพศ</dt><dd><?= e($gender) ?></dd></div>
      <div class="record-cell<?= trim((string) ($r['birth_date'] ?? '')) === '' ? ' is-empty' : '' ?>"><dt>วันเกิด</dt><dd><?= e(trim((string) ($r['birth_date'] ?? '')) !== '' ? (string) $r['birth_date'] : '—') ?></dd></div>
      <div class="record-cell<?= trim((string) ($r['line_id'] ?? '')) === '' ? ' is-empty' : '' ?>"><dt>Line ID</dt><dd><?= e(trim((string) ($r['line_id'] ?? '')) !== '' ? (string) $r['line_id'] : '—') ?></dd></div>
      <div class="record-cell<?= trim((string) ($r['facebook'] ?? '')) === '' ? ' is-empty' : '' ?>"><dt>Facebook</dt><dd><?= e(trim((string) ($r['facebook'] ?? '')) !== '' ? (string) $r['facebook'] : '—') ?></dd></div>
    </dl>
    <div class="record-notes">
      <?php foreach ($notes as $label => $text): ?>
        <article class="record-note<?= $text === '' ? ' is-empty' : '' ?>">
          <h3><?= e($label) ?></h3>
          <p><?= $text !== '' ? nl2br(e($text)) : '—' ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div></div>
