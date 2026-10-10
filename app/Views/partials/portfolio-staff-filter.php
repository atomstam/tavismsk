<?php
/** @var list<array> $teachers */
/** @var int $staffId */
/** @var string $mode index|works */
$teachers = $teachers ?? [];
$staffId = (int) ($staffId ?? 0);
$mode = $mode ?? 'index';
$activeType = $activeType ?? '';
$q = $q ?? '';
$year = (int) ($year ?? 0);
$sort = $sort ?? 'latest';
$viewMode = $viewMode ?? 'list';
$name = $mode === 'works' ? 'staff_nav' : 'staff';
$onchange = $mode === 'works'
  ? "if(this.value){ var u='" . e(url('/portfolios')) . "/'+this.value+'/works'; var p=new URLSearchParams();"
    . ($activeType !== '' ? "p.set('type','" . e($activeType) . "');" : '')
    . ($q !== '' ? 'p.set(\'q\',' . json_encode($q) . ');' : '')
    . ($year > 0 ? "p.set('year','" . (int) $year . "');" : '')
    . "p.set('sort','" . e($sort) . "'); p.set('view','" . e($viewMode) . "'); var qs=p.toString(); location.href=u+(qs?'?'+qs:''); } else { location.href='" . e(url('/portfolios')) . "'; }"
  : 'this.form.submit()';
?>
<label class="pd-select pd-select-staff">
  <span class="pd-select-ico" aria-hidden="true"><i class="fa fa-user-tie"></i></span>
  <select name="<?= e($name) ?>" aria-label="กรองตามบุคลากร" onchange="<?= e($onchange) ?>">
    <?php if ($mode === 'index'): ?>
      <option value="0">บุคลากร — ทั้งหมด</option>
    <?php else: ?>
      <option value="0">ดูผลงานรวมทั้งโรงเรียน</option>
    <?php endif; ?>
    <?php foreach ($teachers as $t):
      $label = trim(($t['title'] ?? '') . ($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
      $pos = $t['position_name'] ?? '';
    ?>
      <option value="<?= (int) $t['id'] ?>" <?= $staffId === (int) $t['id'] ? 'selected' : '' ?>>
        <?= e($label) ?><?= $pos !== '' ? ' · ' . e($pos) : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
  <span class="pd-select-caret" aria-hidden="true"><i class="fa fa-chevron-down"></i></span>
</label>
