<?php
$u = $user ?? [];
$isEdit = !empty($user['id']);
$personnelList = $personnelList ?? [];
$lookupUrl = $lookupUrl ?? url('/admin/users/lookup');
?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="card-soft"><div class="card-body">
<form method="post" enctype="multipart/form-data" id="user-form"
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
    <div class="col-md-2"><label class="form-label">คำนำหน้า</label>
      <?php if (!empty($prefixes)): ?>
        <select name="title" class="form-select">
          <option value="">—</option>
          <?php foreach ($prefixes as $p): ?>
            <option value="<?= e($p['title']) ?>" <?= (($u['title'] ?? '') === $p['title']) ? 'selected' : '' ?>><?= e($p['title']) ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input name="title" class="form-control" value="<?= e($u['title'] ?? '') ?>">
      <?php endif; ?>
    </div>
    <div class="col-md-4"><label class="form-label">ชื่อ *</label><input name="first_name" class="form-control" required value="<?= e($u['first_name'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">นามสกุล *</label><input name="last_name" class="form-control" required value="<?= e($u['last_name'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">โทร</label><input name="phone" class="form-control" value="<?= e($u['phone'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">เลขบัตรประชาชน</label><input name="id_card" class="form-control" maxlength="13" pattern="[0-9]*" inputmode="numeric" value="<?= e($u['id_card'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">บทบาท *</label>
      <select name="role" id="uf-role" class="form-select js-user-role">
        <?php foreach ($roles as $k=>$meta): ?><option value="<?= e($k) ?>" <?= (($u['role']??'')===$k)?'selected':'' ?>><?= e($meta['label']??$k) ?></option><?php endforeach; ?>
      </select>
      <div class="form-text">Admin / วิทยากร ไม่ต้องสังกัดหน่วยงาน (สร้างโดย Admin เท่านั้น) — ผู้ใช้งานต้องเลือก เขตตรวจ → สพม. → โรงเรียน</div>
    </div>
    <div class="col-md-4"><label class="form-label">กลุ่มงาน</label>
      <select name="department_id" class="form-select"><option value="">-</option>
        <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>" <?= ((int)($u['department_id']??0)===(int)$d['id'])?'selected':'' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div id="uf-org-block" class="row g-3 w-100" style="display:none">
      <?php partial('partials/org_cascade_fields', [
        'org' => $orgSelected ?? [],
        'clusters' => $clusters ?? [],
        'areas' => $areas ?? [],
        'schools' => $schools ?? [],
        'personalTypes' => $personalTypes ?? [],
        'prefixes' => $prefixes ?? [],
        'memberTypes' => $memberTypes ?? [],
        'showOrgLevel' => false,
        'requireSchool' => true,
        'col' => 'col-md-4',
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
        if (block) block.style.display = needOrg ? '' : 'none';
        block?.querySelectorAll('select[name="cluster_code"], select[name="area_id"], select[name="org_unit_id"]').forEach((el) => {
          if (needOrg && el.name === 'org_unit_id') el.setAttribute('required', 'required');
          else if (needOrg && (el.name === 'area_id' || el.name === 'cluster_code')) el.setAttribute('required', 'required');
          else el.removeAttribute('required');
        });
      }
      roleSel?.addEventListener('change', sync);
      sync();
    })();
    </script>
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
