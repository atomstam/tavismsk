<?php
$ready = !empty($ready);
$prefixes = $prefixes ?? [];
$schools = $schools ?? [];
$provinces = $provinces ?? [];
$amphurs = $amphurs ?? [];
$tumbons = $tumbons ?? [];
$memberTypes = $memberTypes ?? ['staff' => 'ครูและบุคลากรทางการศึกษา', 'student' => 'นักเรียน', 'general' => 'ทั่วไป'];
$memberType = (string) ($memberType ?? old('member_type', 'staff'));
if (!in_array($memberType, ['staff', 'student', 'general'], true)) {
    $memberType = 'staff';
}
$personalTypes = $personalTypes ?? [];
$staffPositions = $staffPositions ?? [];
$studentGrades = $studentGrades ?? [];
$staffPrefixes = $staffPrefixes ?? [];
$studentPrefixes = $studentPrefixes ?? [];
$generalPrefixes = $generalPrefixes ?? [];
$choice = (string) old('area_choice', '');
$selectedSchool = (int) old('org_unit_id', 0);
$selectedProvince = (int) old('province_id', 0);
$selectedAmphur = (int) old('amphur_id', 0);
$selectedTumbon = (int) old('tumbon_id', 0);
$selectedPersonalType = (int) old('personal_type_id', 0);
$oldPositionText = (string) old('position_text', '');
$oldZip = (string) old('zipcode', '');
$positionLabel = $memberType === 'student' ? 'ระดับชั้น *' : 'ตำแหน่ง *';
$isGeneral = $memberType === 'general';
?>
<div class="auth-wrap">
  <div class="auth-visual">
    <div>
      <div class="auth-brand">
        <span class="auth-brand-icon"><i class="fa fa-shield-halved"></i></span>
        <span class="auth-brand-text">
          <span class="name"><?= e(app_brand('short_name')) ?></span>
          <span class="tag"><?= e(app_brand('nav_tag')) ?></span>
        </span>
      </div>
      <h1>สมัครสมาชิก</h1>
      <p class="mt-3">สำหรับ<strong>ผู้ใช้งานทั่วไป</strong> — เลือกสังกัด สพม.มหาสารคาม แล้วเลือกโรงเรียน หรือเลือก “อื่นๆ” แล้วกรอกหน่วยงานเอง</p>
      <p class="mt-2" style="opacity:.85;font-size:.95rem">บัญชีผู้ดูแลระบบ และวิทยากร จัดการโดย Admin ในระบบหลังบ้านเท่านั้น</p>
    </div>
  </div>
  <div class="auth-panel">
    <div class="auth-form auth-form--register">
      <p class="auth-kicker">ผู้ใช้งานทั่วไป</p>
      <h2 class="auth-title">สร้างบัญชีใหม่</h2>
      <?php if (!$ready): ?>
        <div class="alert alert-warning">ระบบกำลังเตรียมรายชื่อโรงเรียนใน สพม.มหาสารคาม</div>
      <?php endif; ?>
      <form method="post" action="<?= url('/register') ?>" class="auth-fields" id="register-form">
        <?= csrf_field() ?>
        <div class="row g-2 g-md-3">
          <div class="col-12 col-md-6">
            <label class="form-label">ชื่อผู้ใช้ *</label>
            <input class="form-control" name="username" required minlength="3" value="<?= e((string) old('username')) ?>" autocomplete="username">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">อีเมล *</label>
            <input class="form-control" type="email" name="email" required value="<?= e((string) old('email')) ?>" autocomplete="email">
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">รหัสผ่าน * (อย่างน้อย 8 ตัว)</label>
            <div class="auth-input auth-input--password">
              <i class="fa fa-lock" aria-hidden="true"></i>
              <input class="form-control" type="password" name="password" id="reg-password" required minlength="8" autocomplete="new-password">
              <button type="button" class="auth-password-toggle" data-toggle-password="reg-password" aria-label="แสดงรหัสผ่าน" aria-pressed="false" title="แสดงรหัสผ่าน">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">ยืนยันรหัสผ่าน *</label>
            <div class="auth-input auth-input--password">
              <i class="fa fa-lock" aria-hidden="true"></i>
              <input class="form-control" type="password" name="password_confirmation" id="reg-password2" required minlength="8" autocomplete="new-password">
              <button type="button" class="auth-password-toggle" data-toggle-password="reg-password2" aria-label="แสดงรหัสผ่าน" aria-pressed="false" title="แสดงรหัสผ่าน">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">ประเภทผู้ใช้งาน *</label>
            <select class="form-select js-searchable" name="member_type" id="reg-member-type" required data-placeholder="ค้นหาหรือเลือกประเภท">
              <option value="">— เลือก —</option>
              <?php foreach ($memberTypes as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $memberType === $k ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" id="reg-position-label"><?= e($positionLabel) ?></label>
            <select class="form-select js-searchable" name="personal_type_id" id="reg-personal-type" <?= $isGeneral ? 'disabled hidden' : 'required' ?> data-placeholder="ค้นหาหรือเลือกตำแหน่ง">
              <option value="">— เลือก —</option>
              <?php foreach ($personalTypes as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $selectedPersonalType === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
              <?php endforeach; ?>
            </select>
            <input class="form-control" type="text" name="position_text" id="reg-position-text" maxlength="120"
                   value="<?= e($oldPositionText) ?>" placeholder="กรอกตำแหน่งเอง"
                   <?= $isGeneral ? 'required' : 'disabled hidden' ?>>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">คำนำหน้า *</label>
            <select class="form-select js-searchable" name="title" id="reg-title" required data-placeholder="คำนำหน้า">
              <option value="">—</option>
              <?php foreach ($prefixes as $p): ?>
                <option value="<?= e($p['title']) ?>" <?= old('title') === $p['title'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">ชื่อ *</label>
            <input class="form-control" name="first_name" required value="<?= e((string) old('first_name')) ?>">
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label">นามสกุล *</label>
            <input class="form-control" name="last_name" required value="<?= e((string) old('last_name')) ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">โทรศัพท์</label>
            <input class="form-control" name="phone" value="<?= e((string) old('phone')) ?>" autocomplete="tel">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">เขตพื้นที่ *</label>
            <select class="form-select js-searchable" name="area_choice" id="reg-area" required data-placeholder="ค้นหาหรือเลือกเขตพื้นที่">
              <option value="">— เลือก —</option>
              <option value="spmmsk" <?= $choice === 'spmmsk' ? 'selected' : '' ?>>สพม.มหาสารคาม</option>
              <option value="other" <?= $choice === 'other' ? 'selected' : '' ?>>อื่นๆ</option>
            </select>
          </div>
          <div class="col-12" id="reg-school-wrap" <?= $choice === 'spmmsk' ? '' : 'hidden' ?>>
            <label class="form-label">โรงเรียน *</label>
            <select class="form-select js-searchable" name="org_unit_id" id="reg-school" data-placeholder="ค้นหาหรือเลือกโรงเรียน">
              <option value="">— เลือกโรงเรียน —</option>
              <?php foreach ($schools as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= $selectedSchool === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6" id="reg-org-wrap" <?= $choice === 'other' ? '' : 'hidden' ?>>
            <label class="form-label">หน่วยงาน *</label>
            <input class="form-control" name="org_name" id="reg-org" maxlength="255" value="<?= e((string) old('org_name')) ?>" placeholder="เช่น ชื่อโรงเรียน หรือหน่วยงาน">
          </div>
          <div class="col-12 col-md-6" id="reg-aff-wrap" <?= $choice === 'other' ? '' : 'hidden' ?>>
            <label class="form-label">สังกัด *</label>
            <input class="form-control" name="affiliation" id="reg-aff" maxlength="255" value="<?= e((string) old('affiliation')) ?>" placeholder="เช่น สพป. / หน่วยงานต้นสังกัด">
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">จังหวัด *</label>
            <select class="form-select js-searchable" name="province_id" id="reg-province" required data-placeholder="ค้นหาหรือเลือกจังหวัด">
              <option value="">— เลือกจังหวัด —</option>
              <?php foreach ($provinces as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $selectedProvince === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">อำเภอ *</label>
            <select class="form-select js-searchable" name="amphur_id" id="reg-amphur" required <?= $selectedProvince > 0 ? '' : 'disabled' ?> data-placeholder="ค้นหาหรือเลือกอำเภอ">
              <option value="">— เลือกอำเภอ —</option>
              <?php foreach ($amphurs as $a): ?>
                <option value="<?= (int) $a['id'] ?>" <?= $selectedAmphur === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">ตำบล *</label>
            <select class="form-select js-searchable" name="tumbon_id" id="reg-tumbon" required <?= $selectedAmphur > 0 ? '' : 'disabled' ?> data-placeholder="ค้นหาหรือเลือกตำบล">
              <option value="">— เลือกตำบล —</option>
              <?php foreach ($tumbons as $t): ?>
                <option value="<?= (int) $t['id'] ?>" data-zipcode="<?= e($t['zipcode']) ?>" <?= $selectedTumbon === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">รหัสไปรษณีย์ *</label>
            <input class="form-control" type="text" name="zipcode" id="reg-zipcode" maxlength="10" required readonly value="<?= e($oldZip) ?>" placeholder="เลือกตำบลแล้วจะแสดงอัตโนมัติ" autocomplete="postal-code">
          </div>
        </div>
        <button class="auth-submit mt-3" type="submit"><i class="fa fa-user-plus"></i> สมัครสมาชิก</button>
      </form>
      <p class="mt-3 mb-0 text-center"><a href="<?= url('/login') ?>">มีบัญชีแล้ว? เข้าสู่ระบบ</a></p>
      <a class="auth-back" href="<?= url('/') ?>"><i class="fa fa-arrow-left"></i> กลับหน้าเว็บไซต์</a>
    </div>
  </div>
</div>
<?php partial('partials/user_unique_check', [
  'checkUrl' => url('/api/users/check'),
  'excludeId' => 0,
  'scope' => '.auth-fields',
]); ?>

<script>
(function () {
  var area = document.getElementById('reg-area');
  var schoolWrap = document.getElementById('reg-school-wrap');
  var school = document.getElementById('reg-school');
  var orgWrap = document.getElementById('reg-org-wrap');
  var affWrap = document.getElementById('reg-aff-wrap');
  var org = document.getElementById('reg-org');
  var aff = document.getElementById('reg-aff');

  function mountSearchSelect(el) {
    if (!el || typeof TomSelect === 'undefined') return null;
    if (el.tomselect) return el.tomselect;
    return new TomSelect(el, {
      plugins: ['dropdown_input'],
      allowEmptyOption: true,
      create: false,
      maxOptions: null,
      sortField: [{ field: '$order' }],
      placeholder: el.getAttribute('data-placeholder') || 'ค้นหาหรือเลือก...',
      render: {
        no_results: function () {
          return '<div class="no-results">ไม่พบรายการ</div>';
        }
      },
      onDropdownOpen: function () {
        var input = this.control_input || this.dropdown.querySelector('.dropdown-input input, input');
        if (input) {
          input.placeholder = 'พิมพ์เพื่อค้นหา...';
          setTimeout(function () { input.focus(); }, 0);
        }
      }
    });
  }

  function remountSearchSelect(el) {
    if (!el || typeof TomSelect === 'undefined') return;
    var disabled = !!el.disabled;
    var hidden = !!el.hidden;
    var value = el.value;
    // Tom Select destroy() clears <option> nodes — preserve them
    var html = el.innerHTML;
    if (el.tomselect) el.tomselect.destroy();
    el.innerHTML = html;
    if (value) el.value = value;
    if (hidden) return;
    var ts = mountSearchSelect(el);
    if (!ts) return;
    if (value) ts.setValue(value, true);
    if (disabled) ts.disable();
    else ts.enable();
  }

  function populateSelect(el, placeholder, items, valueKey, labelKey, selected, withZip) {
    if (!el) return;
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
      if (String(selected) === String(item[valueKey])) opt.selected = true;
      el.appendChild(opt);
    });
    remountSearchSelect(el);
  }

  function setSelectState(el, on, required) {
    if (!el) return;
    el.hidden = !on;
    el.disabled = !on;
    if (on && required) el.setAttribute('required', 'required');
    else el.removeAttribute('required');
    if (!on) {
      if (el.tomselect) el.tomselect.destroy();
      return;
    }
    remountSearchSelect(el);
  }

  function syncArea() {
    var v = area ? area.value : '';
    var spm = v === 'spmmsk';
    var other = v === 'other';
    if (schoolWrap) schoolWrap.hidden = !spm;
    if (orgWrap) orgWrap.hidden = !other;
    if (affWrap) affWrap.hidden = !other;
    if (school) {
      school.required = spm;
      school.disabled = !spm;
      school.hidden = !spm;
      if (spm) remountSearchSelect(school);
      else if (school.tomselect) school.tomselect.destroy();
    }
    if (org) {
      org.required = other;
      org.disabled = !other;
    }
    if (aff) {
      aff.required = other;
      aff.disabled = !other;
    }
  }
  area?.addEventListener('change', syncArea);

  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-toggle-password');
      var input = id ? document.getElementById(id) : null;
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
      btn.title = show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน';
      var icon = btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
      }
    });
  });

  var memberType = document.getElementById('reg-member-type');
  var personalType = document.getElementById('reg-personal-type');
  var positionText = document.getElementById('reg-position-text');
  var titleSel = document.getElementById('reg-title');
  var positionLabel = document.getElementById('reg-position-label');
  var staffPositions = <?= json_encode($staffPositions, JSON_UNESCAPED_UNICODE) ?>;
  var studentGrades = <?= json_encode($studentGrades, JSON_UNESCAPED_UNICODE) ?>;
  var staffPrefixes = <?= json_encode($staffPrefixes, JSON_UNESCAPED_UNICODE) ?>;
  var studentPrefixes = <?= json_encode($studentPrefixes, JSON_UNESCAPED_UNICODE) ?>;
  var generalPrefixes = <?= json_encode($generalPrefixes, JSON_UNESCAPED_UNICODE) ?>;
  var selectedPersonalType = <?= json_encode((string) $selectedPersonalType) ?>;
  var selectedTitle = <?= json_encode((string) old('title', '')) ?>;

  function fillOptions(el, placeholder, items, selected, valueKey, labelKey) {
    populateSelect(el, placeholder, items, valueKey, labelKey, selected, false);
  }

  function setVisible(el, on, required) {
    if (!el) return;
    if (el.tagName === 'SELECT') {
      setSelectState(el, on, required);
      return;
    }
    el.hidden = !on;
    el.disabled = !on;
    if (required) el.setAttribute('required', 'required');
    else el.removeAttribute('required');
  }

  function syncMemberType(keepSelected) {
    var kind = memberType ? memberType.value : '';
    var isStudent = kind === 'student';
    var isStaff = kind === 'staff';
    var isGeneral = kind === 'general';
    if (positionLabel) {
      positionLabel.textContent = isStudent ? 'ระดับชั้น *' : 'ตำแหน่ง *';
    }
    setVisible(personalType, isStaff || isStudent, true);
    setVisible(positionText, isGeneral, true);
    if (!(isStaff || isStudent) && personalType) personalType.value = '';
    if (!isGeneral && positionText) positionText.value = '';

    var positions = isStudent ? studentGrades : (isStaff ? staffPositions : []);
    var prefixes = isStudent ? studentPrefixes : (isGeneral ? generalPrefixes : (isStaff ? staffPrefixes : []));
    var keepPos = keepSelected ? selectedPersonalType : '';
    var keepTitle = keepSelected ? selectedTitle : '';
    if (isStaff || isStudent) {
      fillOptions(personalType, '— เลือก —', positions, keepPos, 'id', 'title');
    }
    if (titleSel) titleSel.disabled = !(isStaff || isStudent || isGeneral);
    fillOptions(titleSel, '—', prefixes, keepTitle, 'title', 'title');
  }
  memberType?.addEventListener('change', function () { syncMemberType(false); });

  var province = document.getElementById('reg-province');
  var amphur = document.getElementById('reg-amphur');
  var tumbon = document.getElementById('reg-tumbon');
  var zip = document.getElementById('reg-zipcode');
  var amphurUrl = <?= json_encode(url('/api/geo/amphurs')) ?>;
  var tumbonUrl = <?= json_encode(url('/api/geo/tumbons')) ?>;

  function fillSelect(el, placeholder, items, valueKey, labelKey, selected) {
    populateSelect(el, placeholder, items, valueKey, labelKey, selected, true);
  }

  function resetZip() {
    if (zip) zip.value = '';
  }

  function syncZipFromTumbon() {
    if (!tumbon || !zip) return;
    var opt = tumbon.options[tumbon.selectedIndex];
    zip.value = opt && opt.getAttribute('data-zipcode') ? opt.getAttribute('data-zipcode') : '';
  }

  function loadAmphurs(pid) {
    amphur.disabled = true;
    tumbon.disabled = true;
    fillSelect(amphur, '— เลือกอำเภอ —', [], 'id', 'name', '');
    fillSelect(tumbon, '— เลือกตำบล —', [], 'id', 'name', '');
    resetZip();
    if (!pid) return;
    amphur.disabled = false;
    fillSelect(amphur, 'กำลังโหลด...', [], 'id', 'name', '');
    fetch(amphurUrl + '?province_id=' + encodeURIComponent(pid), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        amphur.disabled = false;
        fillSelect(amphur, '— เลือกอำเภอ —', json.data || [], 'id', 'name', '');
      })
      .catch(function () {
        amphur.disabled = false;
        fillSelect(amphur, '— โหลดอำเภอไม่สำเร็จ —', [], 'id', 'name', '');
      });
  }

  function loadTumbons(aid) {
    tumbon.disabled = true;
    fillSelect(tumbon, '— เลือกตำบล —', [], 'id', 'name', '');
    resetZip();
    if (!aid) return;
    tumbon.disabled = false;
    fillSelect(tumbon, 'กำลังโหลด...', [], 'id', 'name', '');
    fetch(tumbonUrl + '?amphur_id=' + encodeURIComponent(aid), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        tumbon.disabled = false;
        fillSelect(tumbon, '— เลือกตำบล —', json.data || [], 'id', 'name', '');
      })
      .catch(function () {
        tumbon.disabled = false;
        fillSelect(tumbon, '— โหลดตำบลไม่สำเร็จ —', [], 'id', 'name', '');
      });
  }

  province?.addEventListener('change', function () { loadAmphurs(province.value); });
  amphur?.addEventListener('change', function () { loadTumbons(amphur.value); });
  tumbon?.addEventListener('change', syncZipFromTumbon);

  document.querySelectorAll('#register-form select.js-searchable').forEach(function (el) {
    if (!el.hidden) mountSearchSelect(el);
  });
  syncMemberType(true);
  syncArea();
  if (tumbon && tumbon.value) syncZipFromTumbon();
})();
</script>
