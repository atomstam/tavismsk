<?php
$u = $user ?? [];
$isEdit = !empty($user['id']);
$personnelList = $personnelList ?? [];
$lookupUrl = $lookupUrl ?? url('/admin/users/lookup');
?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" enctype="multipart/form-data" id="user-form" class="sheet-form"
      action="<?= $isEdit ? url('/admin/users/' . (int)$user['id']) : url('/admin/users') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">ชื่อผู้ใช้ *</label>
      <input name="username" id="uf-username" class="form-control" required autocomplete="off"
             value="<?= e($u['username'] ?? '') ?>" <?= $isEdit ? 'readonly' : '' ?>>
      <?php if ($isEdit): ?><div class="form-text">ชื่อผู้ใช้ไม่สามารถเปลี่ยนได้</div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">อีเมล</label>
      <input name="email" id="uf-email" type="email" class="form-control" value="<?= e($u['email'] ?? '') ?>" autocomplete="email">
    </div>
    <div class="col-md-4"><label class="form-label">รหัสผ่าน <?= $isEdit ? '(เว้นว่างหากไม่เปลี่ยน)' : '' ?></label>
      <input name="password" type="password" class="form-control" <?= $isEdit ? '' : 'required minlength="8"' ?> autocomplete="new-password">
    </div>
    <div class="col-md-6">
      <label class="form-label">เชื่อมโยงบุคลากร (Personnel)</label>
      <select name="personnel_id" class="form-select">
        <option value="">— ไม่เชื่อมโยง —</option>
        <?php foreach ($personnelList as $p):
          $pname = trim(($p['title'] ?? '') . ($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
          $plabel = ($p['code'] ? $p['code'] . ' · ' : '') . $pname;
        ?>
          <option value="<?= (int)$p['id'] ?>" <?= ((int)($u['personnel_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
            <?= e($plabel) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">บทบาท *</label>
      <select name="role" id="uf-role" class="form-select js-user-role">
        <?php foreach ($roles as $k=>$meta): ?><option value="<?= e($k) ?>" <?= (($u['role']??'')===$k)?'selected':'' ?>><?= e($meta['label']??$k) ?></option><?php endforeach; ?>
      </select>
      <div class="form-text">Admin / วิทยากร ไม่ต้องสังกัดหน่วยงาน — ผู้ใช้งานใช้ชุดข้อมูลเดียวกับหน้าสมัครสมาชิก</div>
    </div>
    <div class="col-md-4">
      <label class="form-label">ยืนยันรหัสผ่าน <?= $isEdit ? '(กรอกเมื่อเปลี่ยนรหัสผ่าน)' : '*' ?></label>
      <input name="password_confirmation" type="password" class="form-control" <?= $isEdit ? '' : 'required minlength="8"' ?> autocomplete="new-password">
    </div>
    <div class="col-12 form-section"><h2>ข้อมูลสมัครสมาชิก</h2></div>
    <div id="uf-org-block" style="display:contents">
      <?php partial('partials/registration_profile_fields', [
        'idPrefix' => 'uf',
        'withIdentity' => true,
        'required' => (($u['role'] ?? 'user') !== 'admin' && ($u['role'] ?? 'user') !== 'trainer'),
        'profile' => $profile ?? [],
        'memberTypes' => $memberTypes ?? [],
        'personalTypes' => $personalTypes ?? [],
        'prefixes' => $prefixes ?? [],
        'schools' => $schools ?? [],
        'provinces' => $provinces ?? [],
        'amphurs' => $amphurs ?? [],
        'tumbons' => $tumbons ?? [],
      ]); ?>
    </div>
    <script>
    (function () {
      const roleSel = document.getElementById('uf-role');
      const block = document.getElementById('uf-org-block');
      const staff = <?= json_encode(\App\Services\OrgMembershipService::STAFF_ROLES) ?>;
      function sync() {
        const role = roleSel?.value || 'user';
        const needOrg = !staff.includes(role);
        if (block) block.style.display = 'contents';
        if (window.tavisProfileSetActive) window.tavisProfileSetActive('uf', needOrg);
      }
      roleSel?.addEventListener('change', sync);
      sync();
    })();
    </script>
    <div class="col-12 form-section"><h2>การใช้งาน</h2></div>
    <div class="col-md-4"><label class="form-label">สถานะ</label>
      <select name="status" class="form-select"><option value="1">ใช้งาน</option><option value="0" <?= isset($u['status']) && !(int)$u['status']?'selected':'' ?>>ระงับ</option></select>
    </div>
    <div class="col-md-4"><label class="form-label">2FA อีเมล</label>
      <select name="two_factor_enabled" class="form-select"><option value="0">ปิด</option><option value="1" <?= !empty($u['two_factor_enabled'])?'selected':'' ?>>เปิด</option></select>
    </div>
    <?php partial('partials/image_crop_field', [
      'type' => 'avatar',
      'name' => 'avatar',
      'tokenName' => 'avatar_crop_token',
      'label' => 'รูปโปรไฟล์',
      'existing' => $u['avatar'] ?? '',
      'colClass' => 'col-md-4',
    ]); ?>
  </div>
  <button class="btn-add mt-3" type="submit">บันทึก</button>
</form>
</div></div>
<?php partial('partials/user_unique_check', [
  'checkUrl' => url('/api/users/check'),
  'excludeId' => (int) ($u['id'] ?? 0),
  'scope' => '#user-form',
]); ?>
