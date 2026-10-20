<?php
$r = $row ?? [];
$isEdit = !empty($row['id']);
$nextCode = $nextCode ?? ($r['code'] ?? '');
$users = $users ?? [];
$lookupUrl = $lookupUrl ?? url('/admin/personnel/lookup');
?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" enctype="multipart/form-data" id="personnel-form" class="sheet-form"
      action="<?= $isEdit ? url('/admin/personnel/' . (int)$row['id']) : url('/admin/personnel') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">รหัส</label>
      <input type="text" id="pf-code" name="code" class="form-control bg-light" readonly
             value="<?= e($nextCode) ?>" title="สร้างอัตโนมัติจากระบบ">
      <div class="form-text">สร้างอัตโนมัติ (MAX id + 1) — แก้ไขไม่ได้</div>
    </div>
    <div class="col-md-3">
      <label class="form-label">เลขบัตรประชาชน</label>
      <input type="text" id="pf-id-card" name="id_card" class="form-control" inputmode="numeric"
             maxlength="13" pattern="[0-9]*" autocomplete="off"
             value="<?= e($r['id_card'] ?? '') ?>" placeholder="กรอกเฉพาะตัวเลข 13 หลัก">
      <div class="form-text" id="pf-id-hint">ถ้ามีในระบบแล้วจะโหลดข้อมูลเพื่อแก้ไขอัตโนมัติ</div>
    </div>
    <div class="col-md-6">
      <label class="form-label">เชื่อมโยงบัญชีผู้ใช้ (Users)</label>
      <select name="user_id" id="pf-user-id" class="form-select">
        <option value="">— ไม่เชื่อมโยง —</option>
        <?php foreach ($users as $u):
          $uname = trim(($u['title'] ?? '') . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
          $label = $u['username'] . ($uname !== '' ? ' · ' . $uname : '') . ' (' . role_name((string)$u['role']) . ')';
        ?>
          <option value="<?= (int)$u['id'] ?>" <?= ((int)($r['user_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>>
            <?= e($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 form-section"><h2>ข้อมูลสมัครสมาชิก</h2></div>
    <?php partial('partials/registration_profile_fields', [
      'idPrefix' => 'pf',
      'withIdentity' => true,
      'required' => true,
      'profile' => $profile ?? [],
      'memberTypes' => $memberTypes ?? [],
      'personalTypes' => $personalTypes ?? [],
      'prefixes' => $prefixes ?? [],
      'schools' => $schools ?? [],
      'provinces' => $provinces ?? [],
      'amphurs' => $amphurs ?? [],
      'tumbons' => $tumbons ?? [],
    ]); ?>
    <div class="col-12 form-section"><h2>ข้อมูลเพิ่มเติม</h2></div>
    <div class="col-md-3"><label class="form-label">เพศ</label>
      <select name="gender" class="form-select"><option value="">-</option>
        <option value="male" <?= (($r['gender']??'')==='male')?'selected':'' ?>>ชาย</option>
        <option value="female" <?= (($r['gender']??'')==='female')?'selected':'' ?>>หญิง</option>
      </select></div>
    <div class="col-md-3"><label class="form-label">วันเกิด</label><input type="date" name="birth_date" class="form-control" value="<?= e($r['birth_date']??'') ?>"></div>
    <div class="col-md-3"><label class="form-label">Email</label><input name="email" class="form-control" value="<?= e($r['email']??'') ?>"></div>
    <div class="col-md-3"><label class="form-label">Line ID</label><input name="line_id" class="form-control" value="<?= e($r['line_id']??'') ?>"></div>
    <div class="col-md-3"><label class="form-label">Facebook</label><input name="facebook" class="form-control" value="<?= e($r['facebook']??'') ?>"></div>
    <div class="col-12"><label class="form-label">ที่อยู่</label><textarea name="address" class="form-control"><?= e($r['address']??'') ?></textarea></div>
    <div class="col-12"><label class="form-label">วุฒิการศึกษา</label><textarea name="education" class="form-control"><?= e($r['education']??'') ?></textarea></div>
    <div class="col-12"><label class="form-label">ประวัติการทำงาน</label><textarea name="work_history" class="form-control"><?= e($r['work_history']??'') ?></textarea></div>
    <div class="col-12"><label class="form-label">ประวัติการอบรม</label><textarea name="training_history" class="form-control"><?= e($r['training_history']??'') ?></textarea></div>
    <?php partial('partials/image_crop_field', [
      'type' => 'photo',
      'name' => 'photo',
      'tokenName' => 'photo_crop_token',
      'label' => 'รูปภาพบุคลากร',
      'existing' => $r['photo'] ?? '',
      'colClass' => 'col-md-4',
    ]); ?>
  </div>
  <button class="btn-add mt-3" type="submit">บันทึก</button>
</form>
</div></div>
<script>
(function () {
  const input = document.getElementById('pf-id-card');
  const hint = document.getElementById('pf-id-hint');
  const lookupUrl = <?= json_encode($lookupUrl) ?>;
  const currentEditId = <?= (int)($row['id'] ?? 0) ?>;
  const enableLookup = <?= $isEdit ? 'true' : 'true' ?>;
  let timer = null;
  let lastChecked = '';

  input?.addEventListener('input', () => {
    input.value = input.value.replace(/\D/g, '').slice(0, 13);
    if (!enableLookup) return;
    clearTimeout(timer);
    if (input.value.length === 13) {
      timer = setTimeout(checkIdCard, 280);
    }
  });
  input?.addEventListener('blur', () => { if (enableLookup) checkIdCard(); });

  function checkIdCard() {
    const v = (input?.value || '').replace(/\D/g, '');
    if (v.length < 13 || v === lastChecked) return;
    lastChecked = v;
    if (hint) hint.textContent = 'กำลังตรวจสอบเลขบัตร...';
    fetch(lookupUrl + '?id_card=' + encodeURIComponent(v), { headers: { 'Accept': 'application/json' } })
      .then((r) => r.json())
      .then((data) => {
        if (data.found && data.edit_url && Number(data.id) !== currentEditId) {
          if (hint) hint.textContent = 'พบข้อมูลในระบบ — กำลังเปิดโหมดแก้ไข...';
          if (window.Swal) {
            Swal.fire({ icon: 'info', title: 'พบเลขบัตรในระบบ', text: 'โหลดข้อมูลเพื่อแก้ไข', timer: 1200, showConfirmButton: false });
          }
          setTimeout(() => { location.href = data.edit_url; }, 600);
        } else if (data.found && Number(data.id) === currentEditId) {
          if (hint) hint.textContent = 'ข้อมูลรายการนี้';
        } else if (!data.found) {
          if (hint) hint.textContent = currentEditId ? 'เลขบัตรนี้ยังไม่ซ้ำกับรายการอื่น' : 'ยังไม่มีในระบบ — จะสร้างรายการใหม่';
        }
      })
      .catch(() => { if (hint) hint.textContent = 'ตรวจสอบไม่สำเร็จ ลองใหม่'; });
  }
})();
</script>
