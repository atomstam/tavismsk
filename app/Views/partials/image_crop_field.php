<?php
/**
 * ช่องอัปโหลดรูป + crop
 *
 * @var string $type       preset: avatar|photo|logo|site_logo|favicon|cover
 * @var string $name       input name เช่น photo, avatar
 * @var string $label
 * @var string|null $existing relative path
 * @var string|null $tokenName default {$name}_crop_token
 * @var string|null $colClass
 */
use App\Services\ImageCropService;

$type = (string) ($type ?? 'photo');
$allPresets = ImageCropService::presets();
$preset = $allPresets[$type] ?? $allPresets['photo'];
$name = (string) ($name ?? 'photo');
$label = (string) ($label ?? $preset['label']);
$existing = trim((string) ($existing ?? ''));
$tokenName = (string) ($tokenName ?? ($name . '_crop_token'));
$colClass = (string) ($colClass ?? 'col-md-4');
$existingUrl = $existing !== '' ? upload_url($existing) : '';
$shape = (string) ($preset['shape'] ?? 'square');
$outW = (int) $preset['width'];
$outH = (int) $preset['height'];
?>
<div class="<?= e($colClass) ?> ab-image-crop-field"
     data-image-crop-field
     data-crop-type="<?= e($type) ?>"
     data-crop-width="<?= $outW ?>"
     data-crop-height="<?= $outH ?>"
     data-crop-shape="<?= e($shape) ?>"
     data-crop-label="<?= e($label) ?>">
  <label class="form-label"><?= e($label) ?></label>
  <input type="hidden" name="<?= e($tokenName) ?>" value="" data-image-crop-token>
  <input type="file" name="<?= e($name) ?>" accept="image/*" class="form-control" data-image-crop-input>
  <div class="form-text mb-2">
    ค่าเริ่มต้น <?= $outW ?>×<?= $outH ?> px
    <?php if (!empty($preset['accept_sizes'])): ?>
      <?php foreach ($preset['accept_sizes'] as $sz): ?>
        <?php if ((int)$sz[0] !== $outW || (int)$sz[1] !== $outH): ?>
          หรือ <?= (int)$sz[0] ?>×<?= (int)$sz[1] ?>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
    — หากขนาดไม่ตรง ระบบจะเปิดหน้าต่างตัดรูป
  </div>
  <div class="ab-image-crop-preview ab-image-crop-preview--<?= e($shape) ?><?= $existingUrl !== '' ? ' has-image' : '' ?>"
       data-image-crop-preview
       <?= $existingUrl !== '' ? 'data-existing="' . e($existingUrl) . '"' : '' ?>>
    <img src="<?= $existingUrl !== '' ? e($existingUrl) : '' ?>" alt="ตัวอย่าง<?= e($label) ?>"
         <?= $existingUrl === '' ? 'hidden' : '' ?>>
    <div class="ab-image-crop-empty" data-image-crop-empty <?= $existingUrl !== '' ? 'hidden' : '' ?>>
      <i class="fa-regular fa-image"></i>
      <span>ยังไม่มีรูป</span>
    </div>
  </div>
</div>
