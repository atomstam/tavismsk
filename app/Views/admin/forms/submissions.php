<?php
$form = $form ?? [];
$rows = $rows ?? [];
?>
<div class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="text-muted small mb-0">
      ข้อมูลที่ส่งจากหน้าเว็บ
      <?php if (!empty($form['slug'])): ?>
        · <a href="<?= e(url('/forms/' . $form['slug'])) ?>" target="_blank" rel="noopener">เปิดแบบฟอร์มสาธารณะ</a>
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-light" href="<?= url('/admin/forms') ?>">กลับ</a>
    <?php if (!empty($form['id'])): ?>
      <a class="btn-add" href="<?= url('/admin/forms/' . (int) $form['id'] . '/export') ?>"><i class="fa fa-file-excel"></i> Export Excel</a>
    <?php endif; ?>
  </div>
</div>
<div class="card-soft"><div class="card-body">
  <?php if (!$rows): ?>
    <p class="text-muted mb-0">ยังไม่มีคำตอบ</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width:70px">#</th>
            <th style="width:160px">วันเวลา</th>
            <th>ข้อมูลที่ส่ง</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r):
            $payload = json_decode((string) ($r['payload'] ?? ''), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            ?>
            <tr>
              <td><?= (int) $r['id'] ?></td>
              <td class="small"><?= e(thai_date($r['created_at'] ?? null, true)) ?></td>
              <td>
                <dl class="row mb-0 small">
                  <?php
                  $shown = [];
                  foreach ($payload as $k => $v):
                    if (str_ends_with((string) $k, '_label')) {
                        continue;
                    }
                    $labelKey = $k . '_label';
                    $label = (string) ($payload[$labelKey] ?? $k);
                    if (isset($shown[$label])) {
                        continue;
                    }
                    $shown[$label] = true;
                    $val = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
                    ?>
                    <dt class="col-sm-3 text-muted"><?= e($label) ?></dt>
                    <dd class="col-sm-9">
                      <?php if ($val !== '' && preg_match('#^(form-submissions|uploads)/#', $val)): ?>
                        <a href="<?= e(upload_url($val)) ?>" target="_blank" rel="noopener"><?= e(basename($val)) ?></a>
                      <?php else: ?>
                        <?= e($val !== '' ? $val : '—') ?>
                      <?php endif; ?>
                    </dd>
                  <?php endforeach; ?>
                </dl>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div></div>
