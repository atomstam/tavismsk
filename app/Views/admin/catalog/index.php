<div class="page-head"><div><h1><?= e($catalog['title']) ?></h1></div>
  <?php if ((!array_key_exists('creatable', $catalog) || !empty($catalog['creatable'])) && can_perm((string) $catalog['module'], 'create')): ?>
    <a class="btn-add" href="<?= url('/admin/catalog/' . $key . '/create') ?>"><i class="fa fa-plus"></i> เพิ่มรายการ</a>
  <?php endif; ?>
</div>
<div class="card-soft"><div class="card-body">
  <table id="catalog-datatable" class="table ab-datatable w-100"
         data-ajax-url="<?= e(url('/admin/catalog/' . $key . '/datatable')) ?>"
         data-col-keys="<?= e(json_encode($jsColumns, JSON_UNESCAPED_UNICODE)) ?>">
    <thead><tr>
      <?php foreach ($catalog['columns'] as $col): ?>
        <th><?= e((string) ($col['label'] ?? '')) ?></th>
      <?php endforeach; ?>
      <th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
