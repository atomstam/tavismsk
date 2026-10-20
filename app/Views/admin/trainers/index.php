<div class="page-head">
  <div>
    <p class="page-kicker">ADMIN</p>
    <h1>จัดการวิทยากร</h1>
    <p class="text-muted mb-0">เพิ่มบทบาทวิทยากรให้ผู้ใช้เดิมได้ โดยบัญชียังเข้าโหมดผู้เรียนได้ตามปกติ</p>
  </div>
</div>
<form class="dt-filter-bar mb-3" method="get" action="<?= url('/admin/trainers') ?>">
  <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="ค้นหาชื่อผู้ใช้ / ชื่อ / อีเมล">
  <button class="btn btn-primary" type="submit">ค้นหา</button>
</form>
<div class="card-soft"><div class="card-body table-responsive">
  <table class="table lms-table align-middle">
    <thead><tr><th>ผู้ใช้</th><th>ชื่อ</th><th>บทบาท</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r):
        $roles = array_filter(explode(',', (string) ($r['roles'] ?? $r['role'])));
        $isTrainer = in_array('trainer', $roles, true);
    ?>
      <tr>
        <td><?= e($r['username']) ?></td>
        <td><?= e(trim(($r['title'] ?? '') . ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) ?></td>
        <td><?= e(implode(', ', $roles)) ?></td>
        <td class="text-end">
          <form method="post" action="<?= url($isTrainer ? '/admin/trainers/revoke' : '/admin/trainers/grant') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn-sm <?= $isTrainer ? 'btn-outline-danger' : 'btn-primary' ?>" type="submit">
              <?= $isTrainer ? 'ถอนวิทยากร' : 'ตั้งเป็นวิทยากร' ?>
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
