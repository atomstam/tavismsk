<?php $r = $row ?? []; ?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" class="sheet-form" action="<?= $row ? url('/admin/catalog/' . $key . '/' . (int) $row['id']) : url('/admin/catalog/' . $key) ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <?php foreach ($catalog['fields'] as $field):
        $name = (string) $field['name'];
        if (!empty($field['create_only']) && $row) {
            continue;
        }
        $val = (string) ($r[$name] ?? '');
        if (($field['type'] ?? '') === 'datetime' && $val !== '') {
            $ts = strtotime($val);
            $val = $ts ? date('Y-m-d\TH:i', $ts) : '';
        }
        $readonly = !empty($field['readonly']);
        ?>
      <div class="col-md-6">
        <label class="form-label"><?= e((string) $field['label']) ?><?= !empty($field['required']) ? ' *' : '' ?></label>
        <?php if (($field['type'] ?? '') === 'textarea'): ?>
          <textarea name="<?= e($name) ?>" class="form-control" rows="4" <?= $readonly ? 'readonly' : '' ?> <?= !empty($field['required']) ? 'required' : '' ?>><?= e((string) ($r[$name] ?? '')) ?></textarea>
        <?php elseif (($field['type'] ?? '') === 'select'): ?>
          <select name="<?= e($name) ?>" class="form-select" <?= $readonly ? 'disabled' : '' ?>>
            <?php foreach ((array) ($field['options'] ?? []) as $opt): ?>
              <option value="<?= e((string) $opt[0]) ?>" <?= (string) ($r[$name] ?? ($field['options'][0][0] ?? '')) === (string) $opt[0] ? 'selected' : '' ?>><?= e((string) $opt[1]) ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input class="form-control" name="<?= e($name) ?>"
                 type="<?= ($field['type'] ?? '') === 'number' ? 'number' : (($field['type'] ?? '') === 'datetime' ? 'datetime-local' : 'text') ?>"
                 value="<?= e($val) ?>"
                 <?= !empty($field['max']) ? 'maxlength="' . (int) $field['max'] . '"' : '' ?>
                 <?= !empty($field['required']) ? 'required' : '' ?>
                 <?= $readonly ? 'readonly' : '' ?>>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn-add">บันทึก</button>
    <a class="btn btn-light" href="<?= url('/admin/catalog/' . $key) ?>">กลับ</a>
  </div>
</form>
</div></div>
