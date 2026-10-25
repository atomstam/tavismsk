<?php
/** @var array<string,mixed> $form */
/** @var list<array<string,mixed>> $fields */
$form = $form ?? [];
$fields = $fields ?? [];
?>
<div class="container py-4">
  <div class="ws-page-hero">
    <div class="crumb">
      <a href="<?= url('/') ?>">หน้าแรก</a> ›
      <a href="<?= url('/forms') ?>">แบบฟอร์ม</a> ›
      <?= e((string) ($form['title'] ?? '')) ?>
    </div>
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
      <div>
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="bg-white bg-opacity-25 rounded-3 p-3"><i class="fa fa-file-pen fa-lg"></i></div>
          <div>
            <h1><?= e((string) ($form['title'] ?? 'แบบฟอร์ม')) ?></h1>
            <?php if (!empty($form['description'])): ?>
              <p><?= e((string) $form['description']) ?></p>
            <?php else: ?>
              <p>กรอกข้อมูลด้านล่างแล้วกดส่งแบบฟอร์ม</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card-soft">
        <div class="card-body p-4">
          <form method="post" action="<?= url('/forms/' . rawurlencode((string) ($form['slug'] ?? ''))) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php foreach ($fields as $f):
              $name = (string) ($f['name'] ?? '');
              $label = (string) ($f['label'] ?? $name);
              $type = (string) ($f['type'] ?? 'text');
              $required = !empty($f['required']);
              $opts = array_values(array_filter(array_map('trim', explode(',', (string) ($f['options'] ?? '')))));
              ?>
              <div class="mb-3">
                <label class="form-label">
                  <?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?>
                </label>
                <?php if ($type === 'textarea'): ?>
                  <textarea name="<?= e($name) ?>" class="form-control" rows="4" <?= $required ? 'required' : '' ?>></textarea>
                <?php elseif ($type === 'dropdown'): ?>
                  <select name="<?= e($name) ?>" class="form-select" <?= $required ? 'required' : '' ?>>
                    <option value="">— เลือก —</option>
                    <?php foreach ($opts as $o): ?><option value="<?= e($o) ?>"><?= e($o) ?></option><?php endforeach; ?>
                  </select>
                <?php elseif ($type === 'radio'): ?>
                  <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($opts as $i => $o): ?>
                      <label class="form-check">
                        <input class="form-check-input" type="radio" name="<?= e($name) ?>" value="<?= e($o) ?>"
                          <?= $required && $i === 0 ? 'required' : '' ?>>
                        <?= e($o) ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                <?php elseif ($type === 'checkbox'): ?>
                  <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($opts as $o): ?>
                      <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="<?= e($name) ?>[]" value="<?= e($o) ?>">
                        <?= e($o) ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                <?php elseif ($type === 'file' || $type === 'signature'): ?>
                  <input class="form-control" type="file" name="<?= e($name) ?>" <?= $required ? 'required' : '' ?>>
                <?php else: ?>
                  <input class="form-control"
                         name="<?= e($name) ?>"
                         type="<?= $type === 'number' ? 'number' : ($type === 'date' ? 'date' : ($type === 'time' ? 'time' : 'text')) ?>"
                         <?= $required ? 'required' : '' ?>>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>

            <?php if (!$fields): ?>
              <p class="text-muted">แบบฟอร์มนี้ยังไม่มีฟิลด์ กรุณาติดต่อผู้ดูแลระบบ</p>
            <?php else: ?>
              <button type="submit" class="btn-add"><i class="fa fa-paper-plane"></i> ส่งแบบฟอร์ม</button>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
