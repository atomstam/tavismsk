<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<table class="table"><thead><tr><th>โมดูล</th><th>สิทธิ์</th></tr></thead><tbody>
<?php foreach ($modules as $k=>$lab): ?>
<tr><td><?= e($lab) ?></td><td><?= e(implode(', ', $perms[$k] ?? $perms['*'] ?? [])) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div></div>
