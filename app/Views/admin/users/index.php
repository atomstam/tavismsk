<?php
$clusters = $clusters ?? (new \App\Services\OrgMasterService())->clusters();
?>
<div class="page-head">
  <div>
    <h1>จัดการผู้ใช้งาน</h1>
    <?php if (!empty($isAdmin)): ?>
      <p class="text-muted mb-0 small">Admin สร้าง/แก้ไขบัญชี <strong>ผู้ดูแลระบบ</strong> และ <strong>วิทยากร</strong> ได้ที่นี่ — ผู้ใช้งานทั่วไปสมัครเองที่หน้าเว็บ หรือเพิ่มจากเมนูนี้</p>
    <?php endif; ?>
  </div>
  <?php if (can_perm('users','create')): ?><a class="btn-add" href="<?= url('/admin/users/create') ?>"><i class="fa fa-plus"></i> เพิ่มผู้ใช้</a><?php endif; ?>
</div>
<div class="card-soft"><div class="card-body">
  <form id="users-filter" class="dt-filter-bar flex-wrap">
    <input name="q" class="form-control" placeholder="ค้นหาชื่อ / อีเมล / ผู้ใช้ / เลขบัตร">
    <select name="role" class="form-select">
      <option value="">ทุกบทบาท</option>
      <?php foreach (($roles ?? []) as $k => $label): ?>
        <option value="<?= e(is_array($label)?($label['key']??$k):$k) ?>"><?= e(is_array($label)?($label['label']??$k):(string)$label) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="cluster" class="form-select" id="users-flt-cluster">
      <option value="">ทุกเขตตรวจ (ผู้ใช้งาน)</option>
      <?php foreach ($clusters as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="area_id" class="form-select" id="users-flt-area">
      <option value="">ทุก สพม.</option>
    </select>
    <select name="school_id" class="form-select" id="users-flt-school">
      <option value="">ทุกโรงเรียน</option>
    </select>
  </form>
  <table id="users-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/users/datatable')) ?>">
    <thead><tr>
      <th>ผู้ใช้</th><th>อีเมล</th><th>บทบาท</th><th>สพม.</th><th>โรงเรียน</th><th>สถานะ</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
<script>
(function () {
  const areaUrl = <?= json_encode(url('/api/org/areas')) ?>;
  const schoolUrl = <?= json_encode(url('/api/org/schools')) ?>;
  const cluster = document.getElementById('users-flt-cluster');
  const area = document.getElementById('users-flt-area');
  const school = document.getElementById('users-flt-school');
  cluster?.addEventListener('change', async () => {
    area.innerHTML = '<option value="">ทุก สพม.</option>';
    school.innerHTML = '<option value="">ทุกโรงเรียน</option>';
    if (!cluster.value) return;
    const res = await fetch(areaUrl + '?cluster=' + encodeURIComponent(cluster.value));
    const json = await res.json();
    (json.data || []).forEach((a) => {
      const o = document.createElement('option');
      o.value = a.id; o.textContent = a.name; area.appendChild(o);
    });
  });
  area?.addEventListener('change', async () => {
    school.innerHTML = '<option value="">ทุกโรงเรียน</option>';
    if (!area.value) return;
    const res = await fetch(schoolUrl + '?area_id=' + encodeURIComponent(area.value));
    const json = await res.json();
    (json.data || []).forEach((s) => {
      const o = document.createElement('option');
      o.value = s.id; o.textContent = s.name; school.appendChild(o);
    });
  });
})();
</script>
