<div class="page-head">
  <div>
    <p class="page-kicker">ADMIN</p>
    <h1>อนุมัติเจ้าของหลักสูตร</h1>
  </div>
</div>

<div class="card-soft mb-4"><div class="card-body">
  <h2 class="h5">มอบหมายเจ้าของหลักสูตร</h2>
  <form method="post" action="<?= url('/admin/plan-owners/assign') ?>" class="row g-2 align-items-end">
    <?= csrf_field() ?>
    <div class="col-md-5">
      <label class="form-label">หลักสูตร</label>
      <select class="form-select" name="plan_id" required>
        <?php foreach ($plans as $p): ?>
          <option value="<?= (int) $p['id'] ?>">หลักสูตรที่ <?= (int) $p['plan_no'] ?> — <?= e($p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5">
      <label class="form-label">วิทยากร</label>
      <select class="form-select" name="user_id" required>
        <?php foreach ($trainers as $t): ?>
          <option value="<?= (int) $t['id'] ?>"><?= e(trim(($t['title'] ?? '') . ($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''))) ?> (<?= e($t['username']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">มอบหมาย</button></div>
  </form>
</div></div>

<div class="card-soft mb-4"><div class="card-body table-responsive">
  <h2 class="h5">คำร้องรออนุมัติ</h2>
  <table class="table lms-table">
    <thead><tr><th>หลักสูตร</th><th>ผู้ขอ</th><th></th></tr></thead>
    <tbody>
    <?php if ($pending === []): ?><tr><td colspan="3" class="text-muted">ไม่มีคำร้อง</td></tr><?php endif; ?>
    <?php foreach ($pending as $row): ?>
      <tr>
        <td>หลักสูตรที่ <?= (int) $row['plan_no'] ?> <?= e($row['plan_title']) ?></td>
        <td><?= e(trim(($row['title'] ?? '') . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?></td>
        <td class="text-end">
          <form method="post" action="<?= url('/admin/plan-owners/review') ?>" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <input type="hidden" name="status" value="approved">
            <button class="btn btn-sm btn-primary" type="submit">อนุมัติ</button>
          </form>
          <form method="post" action="<?= url('/admin/plan-owners/review') ?>" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <input type="hidden" name="status" value="rejected">
            <button class="btn btn-sm btn-outline-danger" type="submit">ไม่อนุมัติ</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>

<div class="card-soft"><div class="card-body table-responsive">
  <h2 class="h5">เจ้าของหลักสูตรปัจจุบัน</h2>
  <table class="table lms-table">
    <thead><tr><th>หลักสูตร</th><th>เจ้าของ</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($owners as $row): ?>
      <tr>
        <td>หลักสูตรที่ <?= (int) $row['plan_no'] ?> <?= e($row['plan_title']) ?></td>
        <td><?= e(trim(($row['title'] ?? '') . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?> (<?= e($row['username']) ?>)</td>
        <td class="text-end">
          <form method="post" action="<?= url('/admin/plan-owners/remove') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">นำออก</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
