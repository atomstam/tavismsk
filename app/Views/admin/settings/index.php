<?php $s = array_merge(setting_defaults(), $s ?? []); ?>
<div class="page-head"><h1>SETTINGS การตั้งค่าระบบ</h1></div>
<form method="post" enctype="multipart/form-data" action="<?= url('/admin/settings') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card-soft h-100"><div class="card-body">
        <h5>ข้อมูลหน่วยงาน / ชื่อระบบ</h5>
        <div class="mb-2"><label class="form-label">ชื่อระบบ</label><input name="site_name" class="form-control" value="<?= e($s['site_name']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ชื่อย่อ</label><input name="site_short_name" class="form-control" value="<?= e($s['site_short_name']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ชื่อเต็มระบบ</label><input name="site_full_name" class="form-control" value="<?= e($s['site_full_name']??'') ?>" placeholder="วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน"></div>
        <div class="mb-2"><label class="form-label">คำโปรย (Tagline)</label><input name="site_tagline" class="form-control" value="<?= e($s['site_tagline']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ชื่อระบบ (อังกฤษ)</label><input name="site_name_en" class="form-control" value="<?= e($s['site_name_en']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ชื่อบนเมนูนำทาง</label><input name="site_nav_title" class="form-control" value="<?= e($s['site_nav_title']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ข้อความรองใต้ชื่อเมนู</label><input name="site_nav_tag" class="form-control" value="<?= e($s['site_nav_tag']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">คำอธิบายระบบ</label><textarea name="system_purpose" class="form-control" rows="3"><?= e($s['system_purpose']??'') ?></textarea></div>
        <div class="mb-2"><label class="form-label">ชื่อหน่วยงาน</label><input name="org_name" class="form-control" value="<?= e($s['org_name']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ชื่อหน่วยงาน (อังกฤษ)</label><input name="org_name_en" class="form-control" value="<?= e($s['org_name_en']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">สังกัด</label><input name="org_affiliation" class="form-control" value="<?= e($s['org_affiliation']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ที่อยู่</label><input name="org_address" class="form-control" value="<?= e($s['org_address']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">โทรศัพท์</label><input name="org_phone" class="form-control" value="<?= e($s['org_phone']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">Email</label><input name="org_email" class="form-control" value="<?= e($s['org_email']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ปีการศึกษาเริ่มต้น</label>
          <select name="academic_year" class="form-select"><?php foreach ($years as $y): ?><option value="<?= (int)$y ?>" <?= ((int)($s['academic_year']??0)===$y)?'selected':'' ?>><?= $y ?></option><?php endforeach; ?></select>
        </div>
        <?php partial('partials/image_crop_field', [
          'type' => 'site_logo',
          'name' => 'logo',
          'tokenName' => 'logo_crop_token',
          'label' => 'โลโก้เว็บไซต์',
          'existing' => $s['logo'] ?? '',
          'colClass' => 'mb-2',
        ]); ?>
        <?php partial('partials/image_crop_field', [
          'type' => 'favicon',
          'name' => 'favicon',
          'tokenName' => 'favicon_crop_token',
          'label' => 'Favicon',
          'existing' => $s['favicon'] ?? '',
          'colClass' => 'mb-2',
        ]); ?>
        <div class="mb-2"><label class="form-label">โหมดปิดปรับปรุง</label>
          <select name="maintenance_mode" class="form-select"><option value="0">ปิด</option><option value="1" <?= (($s['maintenance_mode']??'')==='1')?'selected':'' ?>>เปิด</option></select>
        </div>
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card-soft h-100"><div class="card-body">
        <h5>ข้อมูลผู้รับรอง</h5>
        <div class="mb-2"><label class="form-label">ชื่อ-สกุลผู้อำนวยการ</label><input name="director_name" class="form-control" value="<?= e($s['director_name']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ตำแหน่ง</label><input name="director_position" class="form-control" value="<?= e($s['director_position']??'') ?>"></div>
        <h5 class="mt-4">SMTP</h5>
        <div class="mb-2"><label class="form-label">Host</label><input name="smtp_host" class="form-control" value="<?= e($s['smtp_host']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">Port</label><input name="smtp_port" class="form-control" value="<?= e($s['smtp_port']??'587') ?>"></div>
        <div class="mb-2"><label class="form-label">ผู้ใช้</label><input name="smtp_user" class="form-control" value="<?= e($s['smtp_user']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">Google Maps Embed</label><textarea name="maps_embed" class="form-control" rows="3"><?= e($s['maps_embed']??'') ?></textarea></div>
        <h5 class="mt-4">เครดิตท้ายหน้า</h5>
        <div class="mb-2"><label class="form-label">ผู้พัฒนา</label><input name="developer_name" class="form-control" value="<?= e($s['developer_name']??'') ?>"></div>
        <div class="mb-2"><label class="form-label">ข้อความท้ายหน้า (Footer)</label><input name="footer_credit" class="form-control" value="<?= e($s['footer_credit']??'') ?>" placeholder="พัฒนาโดย : Jacky_Nawa"></div>
        <p class="small text-muted mb-3">แสดงทั้งหน้าเว็บไซต์ หน้าเข้าสู่ระบบ และท้ายหน้าหลังบ้าน</p>
        <button class="btn-add mt-2">บันทึกข้อมูล</button>
      </div></div>
    </div>
  </div>
</form>
<div class="card-soft mt-3"><div class="card-body">
  <h5>สำรองฐานข้อมูล</h5>
  <form method="post" action="<?= url('/admin/settings/backup') ?>" class="mb-3"><?= csrf_field() ?><button class="btn btn-outline-primary">สำรองทันที</button></form>
  <table class="table datatable"><thead><tr><th>ไฟล์</th><th>ขนาด</th><th>วันที่</th><th></th></tr></thead><tbody>
  <?php foreach ($backups as $b): ?>
    <tr>
      <td><?= e($b['file_name']) ?></td>
      <td><?= e(format_bytes((int)$b['file_size'])) ?></td>
      <td><?= e(thai_date($b['created_at'], true)) ?></td>
      <td>
        <a class="btn btn-sm btn-outline-success" href="<?= url('/admin/settings/backup/'.$b['id']) ?>">ดาวน์โหลด</a>
        <form class="d-inline" method="post" action="<?= url('/admin/settings/backup/'.$b['id'].'/restore') ?>"><?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger" data-confirm="กู้คืนข้อมูลนี้?">Restore</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody></table>
</div></div>
