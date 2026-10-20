<?php
/** Registration profile fields shared by admin user and personnel forms. */
$profile = $profile ?? [];
$idPrefix = preg_replace('/[^a-z0-9_-]/i', '', (string) ($idPrefix ?? 'regp')) ?: 'regp';
$required = !empty($required);
$memberTypes = $memberTypes ?? [];
$personalTypes = $personalTypes ?? [];
$schools = $schools ?? [];
$provinces = $provinces ?? [];
$amphurs = $amphurs ?? [];
$tumbons = $tumbons ?? [];
$memberType = (string) ($profile['member_type'] ?? 'staff');
$isGeneral = $memberType === 'general';
$areaChoice = (string) ($profile['area_choice'] ?? '');
$selectedPersonal = (int) ($profile['personal_type_id'] ?? 0);
$selectedSchool = (int) ($profile['org_unit_id'] ?? 0);
$selectedProvince = (int) ($profile['province_id'] ?? 0);
$selectedAmphur = (int) ($profile['amphur_id'] ?? 0);
$selectedTumbon = (int) ($profile['tumbon_id'] ?? 0);
$star = $required ? ' *' : '';
$withIdentity = !empty($withIdentity);
?>
<div class="col-12 profile-sheet" id="<?= e($idPrefix) ?>-profile">
<div class="row g-3">
  <div class="col-md-4">
    <label class="form-label">ประเภทผู้ใช้งาน<?= e($star) ?></label>
    <select class="form-select" name="member_type" id="<?= e($idPrefix) ?>-member-type" <?= $required ? 'required' : '' ?>>
      <option value="">— เลือก —</option>
      <?php foreach ($memberTypes as $k => $label): ?>
        <option value="<?= e((string) $k) ?>" <?= $memberType === (string) $k ? 'selected' : '' ?>><?= e((string) $label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" id="<?= e($idPrefix) ?>-position-label"><?= $memberType === 'student' ? 'ระดับชั้น' : 'ตำแหน่ง' ?><?= e($star) ?></label>
    <select class="form-select" name="personal_type_id" id="<?= e($idPrefix) ?>-personal-type" <?= $isGeneral ? 'disabled hidden' : ($required ? 'required' : '') ?>>
      <option value="">— เลือก —</option>
      <?php foreach ($personalTypes as $p): ?>
        <?php if ((string) ($p['kind'] ?? '') !== $memberType) { continue; } ?>
        <option value="<?= (int) $p['id'] ?>" <?= $selectedPersonal === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="form-control" type="text" name="position_text" id="<?= e($idPrefix) ?>-position-text" maxlength="120"
           value="<?= e((string) ($profile['position_text'] ?? '')) ?>" placeholder="กรอกตำแหน่งเอง"
           <?= $isGeneral ? ($required ? 'required' : '') : 'disabled hidden' ?>>
    <?php if (!$isGeneral && $selectedPersonal === 0 && trim((string) ($profile['position_text'] ?? '')) !== ''): ?>
      <div class="form-text">ตำแหน่งเดิม: <?= e((string) $profile['position_text']) ?> — เลือกจากรายการให้ตรงกับแบบฟอร์มสมัคร</div>
    <?php endif; ?>
  </div>
  <?php if ($withIdentity): ?>
  <div class="col-md-3">
    <label class="form-label">คำนำหน้า<?= e($star) ?></label>
    <select class="form-select" name="title" id="<?= e($idPrefix) ?>-title" data-keep="1" <?= $required ? 'required' : '' ?>>
      <option value="">—</option>
      <?php foreach ($prefixes as $p): ?>
        <?php $audience = (string) ($p['audience'] ?? 'both'); ?>
        <?php if ($audience !== $memberType && $audience !== 'both' && (string) ($p['title'] ?? '') !== (string) ($profile['title'] ?? '')) { continue; } ?>
        <option value="<?= e($p['title']) ?>" <?= (string) ($profile['title'] ?? '') === (string) $p['title'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">ชื่อ *</label>
    <input class="form-control" name="first_name" data-keep="1" required value="<?= e((string) ($profile['first_name'] ?? '')) ?>">
  </div>
  <div class="col-md-5">
    <label class="form-label">นามสกุล *</label>
    <input class="form-control" name="last_name" data-keep="1" required value="<?= e((string) ($profile['last_name'] ?? '')) ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">โทรศัพท์</label>
    <input class="form-control" name="phone" data-keep="1" value="<?= e((string) ($profile['phone'] ?? '')) ?>" autocomplete="tel">
  </div>
  <?php endif; ?>
  <div class="col-md-4">
    <label class="form-label">เขตพื้นที่<?= e($star) ?></label>
    <select class="form-select" name="area_choice" id="<?= e($idPrefix) ?>-area" <?= $required ? 'required' : '' ?>>
      <option value="">— เลือก —</option>
      <option value="spmmsk" <?= $areaChoice === 'spmmsk' ? 'selected' : '' ?>>สพม.มหาสารคาม</option>
      <option value="other" <?= $areaChoice === 'other' ? 'selected' : '' ?>>อื่นๆ</option>
    </select>
  </div>
  <div class="col-12" id="<?= e($idPrefix) ?>-school-wrap" <?= $areaChoice === 'spmmsk' ? '' : 'hidden' ?>>
    <label class="form-label">โรงเรียน<?= e($star) ?></label>
    <select class="form-select" name="org_unit_id" id="<?= e($idPrefix) ?>-school" <?= $areaChoice === 'spmmsk' && $required ? 'required' : '' ?>>
      <option value="">— เลือกโรงเรียน —</option>
      <?php foreach ($schools as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= $selectedSchool === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6" id="<?= e($idPrefix) ?>-org-wrap" <?= $areaChoice === 'other' ? '' : 'hidden' ?>>
    <label class="form-label">หน่วยงาน<?= e($star) ?></label>
    <input class="form-control" name="org_name" id="<?= e($idPrefix) ?>-org" maxlength="255"
           value="<?= e((string) ($profile['org_name'] ?? '')) ?>" placeholder="เช่น ชื่อโรงเรียน หรือหน่วยงาน"
           <?= $areaChoice === 'other' && $required ? 'required' : '' ?>>
  </div>
  <div class="col-md-6" id="<?= e($idPrefix) ?>-aff-wrap" <?= $areaChoice === 'other' ? '' : 'hidden' ?>>
    <label class="form-label">สังกัด<?= e($star) ?></label>
    <input class="form-control" name="affiliation" id="<?= e($idPrefix) ?>-aff" maxlength="255"
           value="<?= e((string) ($profile['affiliation'] ?? '')) ?>" placeholder="เช่น สพป. / หน่วยงานต้นสังกัด"
           <?= $areaChoice === 'other' && $required ? 'required' : '' ?>>
  </div>
  <div class="col-md-6">
    <label class="form-label">จังหวัด<?= e($star) ?></label>
    <select class="form-select" name="province_id" id="<?= e($idPrefix) ?>-province" <?= $required ? 'required' : '' ?>>
      <option value="">— เลือกจังหวัด —</option>
      <?php foreach ($provinces as $p): ?>
        <option value="<?= (int) $p['id'] ?>" <?= $selectedProvince === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">อำเภอ<?= e($star) ?></label>
    <select class="form-select" name="amphur_id" id="<?= e($idPrefix) ?>-amphur" <?= $required ? 'required' : '' ?> <?= $selectedProvince > 0 ? '' : 'disabled' ?>>
      <option value="">— เลือกอำเภอ —</option>
      <?php foreach ($amphurs as $a): ?>
        <option value="<?= (int) $a['id'] ?>" <?= $selectedAmphur === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">ตำบล<?= e($star) ?></label>
    <select class="form-select" name="tumbon_id" id="<?= e($idPrefix) ?>-tumbon" <?= $required ? 'required' : '' ?> <?= $selectedAmphur > 0 ? '' : 'disabled' ?>>
      <option value="">— เลือกตำบล —</option>
      <?php foreach ($tumbons as $t): ?>
        <option value="<?= (int) $t['id'] ?>" data-zipcode="<?= e($t['zipcode']) ?>" <?= $selectedTumbon === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">รหัสไปรษณีย์<?= e($star) ?></label>
    <input class="form-control" type="text" name="zipcode" id="<?= e($idPrefix) ?>-zipcode" maxlength="10" readonly
           value="<?= e((string) ($profile['zipcode'] ?? '')) ?>" placeholder="เลือกตำบลแล้วจะแสดงอัตโนมัติ" autocomplete="postal-code"
           <?= $required ? 'required' : '' ?>>
  </div>
</div>
</div>
<script>
(function (prefix) {
  var root = document.getElementById(prefix + '-profile');
  if (!root || root.dataset.bound === '1') return;
  root.dataset.bound = '1';
  var memberType = document.getElementById(prefix + '-member-type');
  var personalType = document.getElementById(prefix + '-personal-type');
  var positionText = document.getElementById(prefix + '-position-text');
  var positionLabel = document.getElementById(prefix + '-position-label');
  var area = document.getElementById(prefix + '-area');
  var schoolWrap = document.getElementById(prefix + '-school-wrap');
  var school = document.getElementById(prefix + '-school');
  var orgWrap = document.getElementById(prefix + '-org-wrap');
  var affWrap = document.getElementById(prefix + '-aff-wrap');
  var org = document.getElementById(prefix + '-org');
  var aff = document.getElementById(prefix + '-aff');
  var province = document.getElementById(prefix + '-province');
  var amphur = document.getElementById(prefix + '-amphur');
  var tumbon = document.getElementById(prefix + '-tumbon');
  var zip = document.getElementById(prefix + '-zipcode');
  var form = root.closest('form');
  var titleSel = document.getElementById(prefix + '-title') || (form ? form.querySelector('select[name="title"]') : null);
  var staffPositions = <?= json_encode(array_values(array_filter($personalTypes, static fn ($p) => ($p['kind'] ?? '') === 'staff')), JSON_UNESCAPED_UNICODE) ?>;
  var studentGrades = <?= json_encode(array_values(array_filter($personalTypes, static fn ($p) => ($p['kind'] ?? '') === 'student')), JSON_UNESCAPED_UNICODE) ?>;
  var allPrefixes = <?= json_encode($prefixes ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var amphurUrl = <?= json_encode(url('/api/geo/amphurs')) ?>;
  var tumbonUrl = <?= json_encode(url('/api/geo/tumbons')) ?>;
  var active = true;

  function fillSelect(el, placeholder, items, valueKey, labelKey, selected, withZip) {
    if (!el) return;
    var keep = selected != null ? String(selected) : (el.value || '');
    el.innerHTML = '';
    var opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = placeholder;
    el.appendChild(opt0);
    (items || []).forEach(function (item) {
      var opt = document.createElement('option');
      opt.value = String(item[valueKey]);
      opt.textContent = item[labelKey];
      if (withZip && item.zipcode != null) opt.setAttribute('data-zipcode', item.zipcode);
      if (keep && keep === String(item[valueKey])) opt.selected = true;
      el.appendChild(opt);
    });
  }

  function setField(el, on, required) {
    if (!el) return;
    el.hidden = !on;
    el.disabled = !on || !active;
    if (on && active && required) el.setAttribute('required', 'required');
    else el.removeAttribute('required');
  }

  function prefixesFor(kind) {
    return (allPrefixes || []).filter(function (p) {
      return p.audience === kind || p.audience === 'both';
    });
  }

  function syncMember(reset) {
    var kind = memberType ? memberType.value : '';
    var isStudent = kind === 'student';
    var isStaff = kind === 'staff';
    var isGeneral = kind === 'general';
    if (positionLabel) positionLabel.textContent = (isStudent ? 'ระดับชั้น' : 'ตำแหน่ง') + (active ? ' *' : '');
    setField(personalType, isStaff || isStudent, true);
    setField(positionText, isGeneral, true);
    if (!(isStaff || isStudent) && personalType) personalType.value = '';
    if (!isGeneral && positionText) positionText.value = '';
    var items = isStudent ? studentGrades : (isStaff ? staffPositions : []);
    if (isStaff || isStudent) {
      fillSelect(personalType, '— เลือก —', items, 'id', 'title', reset ? personalType.value : '', false);
    }
    if (titleSel) {
      var keepTitle = reset ? titleSel.value : '';
      fillSelect(titleSel, '—', prefixesFor(kind), 'title', 'title', keepTitle, false);
      if (titleSel.dataset.keep === '1') {
        titleSel.disabled = false;
        if (active) titleSel.setAttribute('required', 'required');
        else titleSel.removeAttribute('required');
      } else {
        titleSel.disabled = !active || !(isStaff || isStudent || isGeneral);
        if (active && (isStaff || isStudent || isGeneral)) titleSel.setAttribute('required', 'required');
      }
    }
  }

  function syncArea() {
    var v = area ? area.value : '';
    var spm = v === 'spmmsk';
    var other = v === 'other';
    if (schoolWrap) schoolWrap.hidden = !spm;
    if (orgWrap) orgWrap.hidden = !other;
    if (affWrap) affWrap.hidden = !other;
    setField(school, spm, true);
    setField(org, other, true);
    setField(aff, other, true);
  }

  function syncZip() {
    if (!tumbon || !zip) return;
    var opt = tumbon.options[tumbon.selectedIndex];
    zip.value = opt && opt.getAttribute('data-zipcode') ? opt.getAttribute('data-zipcode') : '';
  }

  function loadAmphurs(pid) {
    fillSelect(amphur, '— เลือกอำเภอ —', [], 'id', 'name', '', false);
    fillSelect(tumbon, '— เลือกตำบล —', [], 'id', 'name', '', true);
    if (zip) zip.value = '';
    amphur.disabled = !pid || !active;
    tumbon.disabled = true;
    if (!pid) return;
    fetch(amphurUrl + '?province_id=' + encodeURIComponent(pid), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        amphur.disabled = !active;
        fillSelect(amphur, '— เลือกอำเภอ —', json.data || [], 'id', 'name', '', false);
      });
  }

  function loadTumbons(aid) {
    fillSelect(tumbon, '— เลือกตำบล —', [], 'id', 'name', '', true);
    if (zip) zip.value = '';
    tumbon.disabled = !aid || !active;
    if (!aid) return;
    fetch(tumbonUrl + '?amphur_id=' + encodeURIComponent(aid), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        tumbon.disabled = !active;
        fillSelect(tumbon, '— เลือกตำบล —', json.data || [], 'id', 'name', '', true);
      });
  }

  window.tavisProfileSetActive = window.tavisProfileSetActive || function (which, on) {
    if (which !== prefix) return;
    active = !!on;
    root.querySelectorAll('input, select').forEach(function (el) {
      if (el.dataset.keep === '1') return;
      if (el.id === prefix + '-zipcode') {
        el.readOnly = true;
      }
      el.disabled = !active || el.hidden;
    });
    if (active) {
      syncMember(true);
      syncArea();
      if (province) province.disabled = false;
      if (amphur && amphur.value) amphur.disabled = false;
      if (tumbon && tumbon.value) tumbon.disabled = false;
      if (zip) zip.disabled = false;
    } else {
      root.querySelectorAll('[required]').forEach(function (el) { el.removeAttribute('required'); });
      if (titleSel) titleSel.removeAttribute('required');
    }
  };

  memberType?.addEventListener('change', function () { syncMember(false); });
  area?.addEventListener('change', syncArea);
  province?.addEventListener('change', function () { loadAmphurs(province.value); });
  amphur?.addEventListener('change', function () { loadTumbons(amphur.value); });
  tumbon?.addEventListener('change', syncZip);
  syncMember(true);
  syncArea();
})(<?= json_encode($idPrefix) ?>);
</script>
