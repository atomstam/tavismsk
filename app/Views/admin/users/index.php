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
    <?php partial('partials/registration_list_filters', [
      'showRole' => true,
      'roles' => $roles ?? [],
      'memberTypes' => $memberTypes ?? [],
      'personalTypes' => $personalTypes ?? [],
      'schools' => $schools ?? [],
    ]); ?>
  </form>
  <table id="users-datatable" class="table ab-datatable dt-list-fit w-100" data-ajax-url="<?= e(url('/admin/users/datatable')) ?>">
    <thead><tr>
      <th>ผู้ใช้</th><th>อีเมล</th><th>บทบาท</th><th>ประเภท / ตำแหน่ง</th><th>โรงเรียน / หน่วยงาน</th><th>จังหวัด</th><th>สถานะ</th><th class="col-actions">จัดการ</th>
    </tr></thead>
  </table>
</div></div>
