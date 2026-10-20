<div class="page-head">
  <div>
    <p class="page-kicker">TRAINER</p>
    <h1>สมัครเป็นเจ้าของหลักสูตร</h1>
    <p class="text-muted mb-0">ส่งคำร้องเพื่อเป็นวิทยากรเจ้าของแผน Admin เป็นผู้อนุมัติ และหนึ่งแผนมีเจ้าของได้หลายคน</p>
  </div>
</div>
<div class="card-soft"><div class="card-body table-responsive">
  <table class="table lms-table align-middle">
    <thead><tr><th>แผน</th><th>หลักสูตร</th><th>สถานะ</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($plans as $p):
        $status = (string) ($p['status'] ?? '');
        $label = $status === 'approved' ? 'เป็นเจ้าของแล้ว' : ($status === 'pending' ? 'รออนุมัติ' : ($status === 'rejected' ? 'ไม่ผ่าน' : 'ยังไม่ขอ'));
    ?>
      <tr>
        <td><?= (int) $p['plan_no'] ?></td>
        <td><?= e($p['title']) ?></td>
        <td><?= e($label) ?></td>
        <td class="text-end">
          <?php if ($status !== 'approved' && $status !== 'pending'): ?>
            <form method="post" action="<?= url('/admin/trainer-apply') ?>" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>">
              <button class="btn btn-sm btn-primary" type="submit">ส่งคำร้อง</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
