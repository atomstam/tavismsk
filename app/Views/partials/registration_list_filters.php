<?php
/** List filters aligned with the public registration form. */
$showRole = !empty($showRole);
$memberTypes = $memberTypes ?? [];
$personalTypes = $personalTypes ?? [];
$schools = $schools ?? [];
$roles = $roles ?? [];
?>
<input name="q" class="form-control" placeholder="<?= e($searchPlaceholder ?? 'ค้นหาชื่อ / อีเมล / โทร / หน่วยงาน') ?>">
<?php if ($showRole): ?>
<select name="role" class="form-select">
  <option value="">ทุกบทบาท</option>
  <?php foreach ($roles as $k => $label): ?>
    <option value="<?= e(is_array($label) ? ($label['key'] ?? $k) : $k) ?>"><?= e(is_array($label) ? ($label['label'] ?? $k) : (string) $label) ?></option>
  <?php endforeach; ?>
</select>
<?php endif; ?>
<select name="member_type" class="form-select">
  <option value="">ทุกประเภทผู้ใช้งาน</option>
  <?php foreach ($memberTypes as $k => $label): ?>
    <option value="<?= e((string) $k) ?>"><?= e((string) $label) ?></option>
  <?php endforeach; ?>
</select>
<select name="personal_type_id" class="form-select">
  <option value="">ทุกตำแหน่ง / ระดับชั้น</option>
  <?php foreach ($personalTypes as $p): ?>
    <option value="<?= (int) $p['id'] ?>" data-kind="<?= e((string) ($p['kind'] ?? '')) ?>"><?= e($p['title']) ?></option>
  <?php endforeach; ?>
</select>
<select name="area_choice" class="form-select">
  <option value="">ทุกเขตพื้นที่</option>
  <option value="spmmsk">สพม.มหาสารคาม</option>
  <option value="other">อื่นๆ</option>
</select>
<select name="school_id" class="form-select">
  <option value="">ทุกโรงเรียน</option>
  <?php foreach ($schools as $s): ?>
    <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
  <?php endforeach; ?>
</select>
