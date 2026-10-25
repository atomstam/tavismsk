<style>
.admin-plan-media{display:block !important}
.admin-plan-cover{position:relative !important;display:block !important;width:100% !important;height:0 !important;padding-bottom:56.25% !important;overflow:hidden !important;background:#4c1d95}
.admin-plan-cover img{position:absolute !important;inset:0 !important;width:100% !important;height:100% !important;max-width:none !important;object-fit:cover !important}
.admin-plan-badge{position:absolute !important;left:12px !important;top:12px !important;z-index:3 !important}
.admin-plan-cover-foot{position:absolute !important;right:12px !important;bottom:12px !important;left:auto !important;z-index:3 !important}
.admin-plan-cover .learn-plan-track{position:static !important}
.admin-plan-actions{display:flex !important;gap:.6rem;padding:.9rem 1.1rem 1.05rem !important;border-top:1px solid #efeaff;background:#fcfbff}
.admin-plan-actions .btn{flex:1 1 0 !important;display:inline-flex !important;align-items:center;justify-content:center;gap:.4rem;min-height:42px !important;border-radius:12px !important;font-weight:700 !important;font-size:.92rem !important;padding:.5rem .9rem !important}
.admin-plan-actions .admin-plan-detail{background:#fff !important;color:#5b21b6 !important;border:1.5px solid #7c3aed !important}
.admin-plan-actions .admin-plan-detail:hover{background:#5b21b6 !important;color:#fff !important}
.curriculum-view-switch{display:inline-flex;border:1px solid #ddd6fe;border-radius:12px;overflow:hidden;background:#fff}
.curriculum-view-switch button{border:0;background:transparent;color:#5b21b6;font-weight:700;padding:.48rem .9rem;display:inline-flex;align-items:center;gap:.4rem}
.curriculum-view-switch button.is-on{background:#5b21b6;color:#fff}
#curriculum-views[data-view="card"] .curriculum-list{display:none}
#curriculum-views[data-view="list"] .curriculum-cards{display:none}
</style>
<div class="page-head">
  <div>
    <p class="page-kicker">CURRICULUM</p>
    <h1>หลักสูตร 14 ชั่วโมง</h1>
  </div>
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <div class="curriculum-view-switch" role="group" aria-label="รูปแบบการแสดง">
      <button type="button" data-curriculum-view="card" class="is-on"><i class="fa fa-grip"></i> การ์ด</button>
      <button type="button" data-curriculum-view="list"><i class="fa fa-list"></i> ตาราง</button>
    </div>
    <?php if (can_perm('curriculum','export')): ?>
      <a class="btn btn-outline-primary" href="<?= url('/admin/curriculum/export') ?>"><i class="fa fa-download"></i> ส่งออก CSV</a>
    <?php endif; ?>
  </div>
</div>

<div id="curriculum-views" data-view="card">
<div class="row g-4 mb-4 curriculum-cards">
  <?php
  $typeLabel = [
    'teacher' => 'ครู',
    'police' => 'ตำรวจ',
    'hospital' => 'สาธารณสุข',
    'honda' => 'Honda / TSY',
    'transport' => 'ขนส่ง',
  ];
  foreach (($plans ?? []) as $p):
    $no = (int) $p['plan_no'];
    $db = $dbPlans[$no] ?? null;
    $planId = (int) ($db['id'] ?? 0);
    $href = $planId > 0 ? url('/admin/curriculum/' . $planId) : url('/admin/curriculum/' . $no);
    $cover = sprintf('img/plans/plan-%02d.png', $no);
    $hasCover = is_file(PUBLIC_PATH . '/assets/' . $cover);
    $required = $no <= 12;
  ?>
  <div class="col-md-6">
    <article class="admin-plan-card h-100">
      <a href="<?= e($href) ?>" class="admin-plan-media">
        <div class="admin-plan-cover">
          <?php if ($hasCover): ?>
            <img src="<?= asset($cover) ?>?v=4" alt="หลักสูตรที่ <?= $no ?>" loading="lazy" decoding="async">
          <?php else: ?>
            <div class="admin-plan-cover-fallback"><i class="fa fa-shield-halved"></i></div>
          <?php endif; ?>
          <span class="admin-plan-badge">หลักสูตรที่ <?= $no ?></span>
          <div class="admin-plan-cover-foot">
            <span class="learn-plan-track <?= $required ? 'is-required' : 'is-elective' ?>"><?= $required ? 'บังคับ' : 'ทั่วไป' ?></span>
          </div>
        </div>
        <div class="admin-plan-body">
          <div class="admin-plan-top">
            <span class="admin-plan-pill"><?= e($p['topic_group'] ?? 'โมดูลอบรม') ?></span>
            <span class="admin-plan-hours"><i class="fa fa-clock"></i> <?= (float) $p['hours'] ?> ชม.</span>
          </div>
          <h2><?= e($p['title']) ?></h2>
          <p><?= e($typeLabel[$p['instructor_type']] ?? ($p['instructor_label'] ?? $p['instructor_type'])) ?></p>
        </div>
      </a>
      <div class="admin-plan-actions">
        <?php if ($planId > 0 && can_perm('assessments', 'create')): ?>
          <a class="btn btn-learn-primary" href="<?= url('/admin/training/' . $planId) ?>"><i class="fa fa-chalkboard-user"></i> จัดการบทเรียน</a>
        <?php endif; ?>
        <a class="btn admin-plan-detail" href="<?= e($href) ?>"><i class="fa fa-circle-info"></i> รายละเอียด</a>
      </div>
    </article>
  </div>
  <?php endforeach; ?>
</div>

<div class="card-soft curriculum-list"><div class="card-body">
  <form id="curriculum-filter" class="dt-filter-bar">
    <input name="q" class="form-control" placeholder="ค้นหาหลักสูตร / กลุ่มหัวข้อ / วิทยากร">
  </form>
  <table id="curriculum-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/curriculum/datatable')) ?>">
    <thead><tr>
      <th>หลักสูตรที่</th><th>หัวข้อ</th><th>ชม.</th><th>วิทยากร</th><th>กลุ่มหัวข้อ</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
</div>
<script>
(function () {
  var root = document.getElementById('curriculum-views');
  if (!root) return;
  var key = 'tavis-curriculum-view';
  function apply(mode) {
    if (mode !== 'list') mode = 'card';
    root.setAttribute('data-view', mode);
    try { localStorage.setItem(key, mode); } catch (e) {}
    document.querySelectorAll('[data-curriculum-view]').forEach(function (btn) {
      btn.classList.toggle('is-on', btn.getAttribute('data-curriculum-view') === mode);
    });
    if (mode === 'list' && window.jQuery && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable('#curriculum-datatable')) {
      var api = jQuery('#curriculum-datatable').DataTable();
      api.columns.adjust();
      if (api.responsive) api.responsive.recalc();
    }
  }
  document.querySelectorAll('[data-curriculum-view]').forEach(function (btn) {
    btn.addEventListener('click', function () { apply(btn.getAttribute('data-curriculum-view')); });
  });
  var saved = 'card';
  try { saved = localStorage.getItem(key) || 'card'; } catch (e) {}
  apply(saved);
})();
</script>
