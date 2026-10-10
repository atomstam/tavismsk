<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<pre class="small"><?php foreach ($rows as $r) { echo e($r['created_at']).' '.e($r['payload'])."\n"; } ?></pre>
</div></div>
