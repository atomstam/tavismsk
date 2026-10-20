<?php
$u = $user ?? [];
$name = trim((string) ($u['title'] ?? '') . (string) ($u['first_name'] ?? '') . ' ' . (string) ($u['last_name'] ?? ''));
$active = (int) ($u['status'] ?? 0) === 1;
$email = trim((string) ($u['email'] ?? ''));
?>
<div class="page-head">
  <div>
    <p class="page-kicker">ผู้ใช้งาน</p>
    <h1><?= e($name !== '' ? $name : 'ผู้ใช้งาน') ?></h1>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('/admin/users') ?>">กลับหน้ารายการ</a>
    <?php if (can_perm('users', 'edit')): ?>
      <a class="btn btn-primary" href="<?= url('/admin/users/' . (int) $u['id'] . '/edit') ?>">แก้ไข</a>
    <?php endif; ?>
  </div>
</div>
<div class="card-soft record-card"><div class="card-body">
  <div class="record-hero">
    <img src="<?= e(avatar_url($u['avatar'] ?? null)) ?>" width="84" height="84" alt="">
    <div>
      <div class="record-name"><?= e($name !== '' ? $name : '—') ?></div>
      <div class="record-chips">
        <span class="record-chip">@<?= e((string) ($u['username'] ?? '')) ?></span>
        <span class="record-chip"><?= e(role_name((string) ($u['role'] ?? ''))) ?></span>
        <span class="record-chip<?= $active ? ' is-on' : ' is-off' ?>"><?= $active ? 'ใช้งาน' : 'ระงับ' ?></span>
      </div>
    </div>
  </div>

  <section class="record-section">
    <h2>บัญชี</h2>
    <dl class="record-grid">
      <div class="record-cell"><dt>ชื่อผู้ใช้</dt><dd><?= e((string) ($u['username'] ?? '—')) ?></dd></div>
      <div class="record-cell<?= $email === '' ? ' is-empty' : '' ?>"><dt>อีเมล</dt><dd><?= e($email !== '' ? $email : '—') ?></dd></div>
      <div class="record-cell"><dt>สถานะ</dt><dd><?= $active ? 'ใช้งาน' : 'ระงับ' ?></dd></div>
    </dl>
  </section>

  <section class="record-section">
    <h2>ข้อมูลสมัครสมาชิก</h2>
    <?php partial('partials/registration_profile_summary', ['profileView' => $profileView ?? []]); ?>
  </section>
</div></div>
