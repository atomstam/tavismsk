<div class="container py-5">
  <h1><?= e($form['title']) ?></h1>
  <p><?= e($form['description']??'') ?></p>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php foreach ($fields as $f): ?>
      <div class="mb-3">
        <label class="form-label"><?= e($f['label']) ?></label>
        <?php if ($f['type']==='textarea'): ?>
          <textarea name="<?= e($f['name']) ?>" class="form-control" <?= $f['required']?'required':'' ?>></textarea>
        <?php elseif ($f['type']==='dropdown'): ?>
          <select name="<?= e($f['name']) ?>" class="form-select">
            <?php foreach (array_filter(array_map('trim', explode(',', (string)$f['options']))) as $o): ?><option><?= e($o) ?></option><?php endforeach; ?>
          </select>
        <?php else: ?>
          <input class="form-control" name="<?= e($f['name']) ?>" type="<?= $f['type']==='file'?'file':($f['type']==='number'?'number':($f['type']==='date'?'date':($f['type']==='time'?'time':'text'))) ?>" <?= $f['required']?'required':'' ?>>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button class="btn-add">ส่งแบบฟอร์ม</button>
  </form>
</div>
