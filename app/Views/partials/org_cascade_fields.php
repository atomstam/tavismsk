<?php
/** Shared cascade: cluster → area → school (+ optional member type / personal type)
 * Aligns with Safety Center user_clus / user_area / sch_id
 */
$org = $org ?? [];
$selectedCluster = (string) ($org['cluster'] ?? '');
$selectedArea = (int) ($org['area_id'] ?? 0);
$selectedSchool = (int) ($org['org_unit_id'] ?? 0);
$selectedLevel = (string) ($org['org_level'] ?? 'user');
$prefixes = $prefixes ?? [];
$personalTypes = $personalTypes ?? [];
$clusters = $clusters ?? [];
$areas = $areas ?? [];
$schools = $schools ?? [];
$showPersonal = $showPersonal ?? true;
$showOrgLevel = $showOrgLevel ?? false;
$orgLevels = $orgLevels ?? \App\Services\OrgMembershipService::levelLabels();
$requireSchool = $requireSchool ?? true;
$col = $col ?? 'col-md-4';
$schoolRequired = $requireSchool && !in_array($selectedLevel, ['admin', 'cluster', 'area'], true);
?>
<?php if ($showOrgLevel): ?>
<div class="<?= e($col) ?>">
  <label class="form-label">ระดับหน่วยงาน (org level)</label>
  <select class="form-select js-org-level" name="org_level">
    <?php foreach ($orgLevels as $k => $label): ?>
      <option value="<?= e($k) ?>" <?= $selectedLevel === $k ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php endif; ?>
<div class="<?= e($col) ?>">
  <label class="form-label">เขตตรวจราชการ</label>
  <select class="form-select js-org-cluster" name="cluster_code">
    <option value="">— เลือก —</option>
    <?php foreach ($clusters as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= $selectedCluster === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="<?= e($col) ?>">
  <label class="form-label">สพม. / เขตพื้นที่</label>
  <select class="form-select js-org-area" name="area_id">
    <option value="">— เลือก —</option>
    <?php foreach ($areas as $a): ?>
      <option value="<?= (int)$a['id'] ?>" <?= $selectedArea === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="<?= e($col) ?>">
  <label class="form-label">โรงเรียน / หน่วยงาน<?= $schoolRequired ? ' *' : '' ?></label>
  <select class="form-select js-org-school" name="org_unit_id" <?= $schoolRequired ? 'required' : '' ?>>
    <option value="">— เลือก —</option>
    <?php foreach ($schools as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= $selectedSchool === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php if ($showPersonal): ?>
<div class="<?= e($col) ?>">
  <label class="form-label">ประเภทผู้ใช้งาน</label>
  <select class="form-select js-member-type" name="member_type" id="uf-member-type">
    <?php
      $memberTypes = $memberTypes ?? ['staff' => 'ครูและบุคลากรทางการศึกษา', 'student' => 'นักเรียน', 'general' => 'ทั่วไป'];
      $selectedMemberType = (string) ($org['member_type'] ?? 'staff');
      if (!in_array($selectedMemberType, ['staff', 'student', 'general'], true)) {
          $selectedMemberType = 'staff';
      }
      $isGeneralMember = $selectedMemberType === 'general';
      $positionTextValue = (string) ($org['position_text'] ?? '');
    ?>
    <?php foreach ($memberTypes as $k => $label): ?>
      <option value="<?= e($k) ?>" <?= $selectedMemberType === $k ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="<?= e($col) ?>">
  <label class="form-label js-position-label"><?= $selectedMemberType === 'student' ? 'ระดับชั้น' : 'ตำแหน่ง' ?></label>
  <select class="form-select js-personal-type" name="personal_type_id" <?= $isGeneralMember ? 'disabled hidden' : '' ?>>
    <option value="">— เลือก —</option>
    <?php foreach ($personalTypes as $p): ?>
      <?php
        $pKind = (string) ($p['kind'] ?? 'staff');
        if ($pKind !== $selectedMemberType) {
            continue;
        }
      ?>
      <option value="<?= (int)$p['id'] ?>" data-kind="<?= e($pKind) ?>" <?= ((int)($org['personal_type_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>><?= e($p['title']) ?></option>
    <?php endforeach; ?>
  </select>
  <input class="form-control js-position-text" type="text" name="position_text" maxlength="120"
         value="<?= e($positionTextValue) ?>" placeholder="กรอกตำแหน่งเอง"
         <?= $isGeneralMember ? '' : 'disabled hidden' ?>>
</div>
<?php endif; ?>
<script>
(function () {
  if (window.__tavisOrgCascade) return;
  window.__tavisOrgCascade = true;
  const areaUrl = <?= json_encode(url('/api/org/areas')) ?>;
  const schoolUrl = <?= json_encode(url('/api/org/schools')) ?>;
  const allPersonalTypes = <?= json_encode($personalTypes ?? [], JSON_UNESCAPED_UNICODE) ?>;
  const allPrefixes = <?= json_encode($prefixes ?? [], JSON_UNESCAPED_UNICODE) ?>;

  function syncSchoolRequired(form) {
    const level = form.querySelector('.js-org-level')?.value || 'user';
    const school = form.querySelector('.js-org-school');
    if (!school) return;
    const need = !['admin', 'cluster', 'area'].includes(level);
    if (need) school.setAttribute('required', 'required');
    else school.removeAttribute('required');
  }

  function fillSelect(el, placeholder, items, selected, valueKey, labelKey, kindFilter) {
    if (!el) return;
    const keep = selected != null ? String(selected) : '';
    el.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = placeholder;
    el.appendChild(opt0);
    (items || []).forEach((item) => {
      if (kindFilter && item.kind && item.kind !== kindFilter) return;
      if (kindFilter && item.audience && item.audience !== kindFilter && item.audience !== 'both') return;
      const opt = document.createElement('option');
      opt.value = String(item[valueKey]);
      opt.textContent = item[labelKey];
      if (item.kind) opt.setAttribute('data-kind', item.kind);
      if (keep && keep === String(item[valueKey])) opt.selected = true;
      el.appendChild(opt);
    });
  }

  function setVisible(el, on) {
    if (!el) return;
    el.hidden = !on;
    el.disabled = !on;
  }

  function syncMemberType(form) {
    const kind = form.querySelector('.js-member-type')?.value || 'staff';
    const isGeneral = kind === 'general';
    const label = form.querySelector('.js-position-label');
    if (label) label.textContent = kind === 'student' ? 'ระดับชั้น' : 'ตำแหน่ง';
    const personal = form.querySelector('.js-personal-type');
    const positionText = form.querySelector('.js-position-text');
    setVisible(personal, !isGeneral);
    setVisible(positionText, isGeneral);
    if (isGeneral) {
      if (personal) personal.value = '';
    } else if (positionText) {
      positionText.value = '';
      const selectedPersonal = personal?.value || '';
      fillSelect(personal, '— เลือก —', allPersonalTypes, selectedPersonal, 'id', 'title', kind);
    }
    const title = form.querySelector('select[name="title"]');
    if (title) {
      const selectedTitle = title.value || '';
      fillSelect(title, '—', allPrefixes, selectedTitle, 'title', 'title', kind);
    }
  }

  document.addEventListener('change', async (e) => {
    const t = e.target;
    if (!(t instanceof HTMLSelectElement)) return;
    const form = t.closest('form') || document;
    if (t.classList.contains('js-member-type')) {
      syncMemberType(form);
    }
    if (t.classList.contains('js-org-level')) {
      syncSchoolRequired(form);
    }
    if (t.classList.contains('js-org-cluster')) {
      const area = form.querySelector('.js-org-area');
      const school = form.querySelector('.js-org-school');
      if (!area) return;
      area.innerHTML = '<option value="">กำลังโหลด...</option>';
      if (school) school.innerHTML = '<option value="">— เลือก —</option>';
      const res = await fetch(areaUrl + '?cluster=' + encodeURIComponent(t.value || ''));
      const json = await res.json();
      area.innerHTML = '<option value="">— เลือก —</option>';
      (json.data || []).forEach((a) => {
        const o = document.createElement('option');
        o.value = a.id; o.textContent = a.name; area.appendChild(o);
      });
    }
    if (t.classList.contains('js-org-area')) {
      const school = form.querySelector('.js-org-school');
      if (!school) return;
      school.innerHTML = '<option value="">กำลังโหลด...</option>';
      if (!t.value) { school.innerHTML = '<option value="">— เลือก —</option>'; return; }
      const res = await fetch(schoolUrl + '?area_id=' + encodeURIComponent(t.value));
      const json = await res.json();
      school.innerHTML = '<option value="">— เลือก —</option>';
      (json.data || []).forEach((s) => {
        const o = document.createElement('option');
        o.value = s.id; o.textContent = s.name; school.appendChild(o);
      });
    }
  });
  document.querySelectorAll('form').forEach((form) => {
    syncSchoolRequired(form);
    if (form.querySelector('.js-member-type')) syncMemberType(form);
  });
})();
</script>
