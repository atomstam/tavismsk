<?php
/** Read-only registration profile, same fields and order as the public register form. */
$profileView = $profileView ?? [];
$unbound = !empty($profileView['unbound']);
?>
<dl class="record-grid">
  <?php
  $cells = [
      ['ประเภทผู้ใช้งาน', $profileView['member_type'] ?? '—'],
      [$profileView['position_label'] ?? 'ตำแหน่ง', $profileView['position'] ?? '—'],
      ['คำนำหน้า', $profileView['title'] ?? '—'],
      ['ชื่อ', $profileView['first_name'] ?? '—'],
      ['นามสกุล', $profileView['last_name'] ?? '—'],
      ['โทรศัพท์', $profileView['phone'] ?? '—'],
      ['เขตพื้นที่', $profileView['area'] ?? '—'],
  ];
  if (!$unbound) {
      $cells[] = [$profileView['school_label'] ?? 'โรงเรียน', $profileView['school'] ?? '—'];
      if (!empty($profileView['show_affiliation'])) {
          $cells[] = ['สังกัด', $profileView['affiliation'] ?? '—'];
      }
      $cells[] = ['จังหวัด', $profileView['province'] ?? '—'];
      $cells[] = ['อำเภอ', $profileView['amphur'] ?? '—'];
      $cells[] = ['ตำบล', $profileView['tumbon'] ?? '—'];
      $cells[] = ['รหัสไปรษณีย์', $profileView['zipcode'] ?? '—'];
  }
  foreach ($cells as [$label, $value]):
      $value = (string) $value;
      $empty = $value === '' || $value === '—';
  ?>
    <div class="record-cell<?= $empty ? ' is-empty' : '' ?>">
      <dt><?= e((string) $label) ?></dt>
      <dd><?= e($empty ? '—' : $value) ?></dd>
    </div>
  <?php endforeach; ?>
</dl>
