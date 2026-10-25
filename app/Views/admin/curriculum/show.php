<?php
$row = $row ?? [];
$cfg = $cfg ?? null;
$typeLabel = [
  'teacher' => 'ครูที่ปรึกษา',
  'police' => 'ตำรวจ',
  'hospital' => 'สาธารณสุข / โรงพยาบาล',
  'honda' => 'Honda Safety / TSY',
  'transport' => 'กรมการขนส่งทางบก',
];
$itype = (string) ($row['instructor_type'] ?? ($cfg['instructor_type'] ?? ''));
$statusMap = [
  'pending' => 'ยังไม่เรียน',
  'in_progress' => 'กำลังเรียน',
  'completed' => 'ผ่านแล้ว',
  'absent' => 'ขาดเรียน',
];
?>
<div class="page-head">
  <div>
    <p class="page-kicker">หลักสูตรที่ <?= (int)($row['plan_no'] ?? 0) ?></p>
    <h1><?= e($row['title'] ?? ($cfg['title'] ?? '')) ?></h1>
  </div>
  <a class="btn btn-outline-secondary" href="<?= url('/admin/curriculum') ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card-soft"><div class="card-body">
      <div class="row g-3 small">
        <div class="col-md-4"><strong>ชั่วโมง</strong><div><?= e((string)($row['hours'] ?? $cfg['hours'] ?? 1)) ?> ชม.</div></div>
        <div class="col-md-4"><strong>วิทยากร</strong><div><?= e($row['instructor_label'] ?? ($cfg['instructor_label'] ?? ($typeLabel[$itype] ?? $itype))) ?></div></div>
        <div class="col-md-4"><strong>กลุ่มหัวข้อ</strong><div><?= e($row['topic_group'] ?? ($cfg['topic_group'] ?? '-')) ?></div></div>
      </div>
      <?php if (!empty($row['description'])): ?>
        <hr><div><?= nl2br(e($row['description'])) ?></div>
      <?php endif; ?>

      <?php
      $sections = $sections ?? [
        'contents' => [], 'tasks' => [], 'knowledge' => [], 'worksheets' => [],
        'worksheets_covered_by_tasks' => false, 'media' => [],
      ];
      ?>
      <hr>
      <h2 class="h6">เนื้อหาในระบบเรียน (LMS)</h2>
      <?php if ($sections['contents']): ?>
        <ul><?php foreach ($sections['contents'] as $c): ?><li><?= e($c['title']) ?></li><?php endforeach; ?></ul>
      <?php else: ?>
        <p class="text-muted small">ยังไม่มี</p>
      <?php endif; ?>

      <h2 class="h6">ภาระงานในระบบเรียน (LMS)</h2>
      <?php if ($sections['tasks']): ?>
        <ul><?php foreach ($sections['tasks'] as $t): ?><li><?= e($t['title']) ?></li><?php endforeach; ?></ul>
      <?php else: ?>
        <p class="text-muted small">ยังไม่มี</p>
      <?php endif; ?>

      <h2 class="h6">ใบความรู้</h2>
      <?php if ($sections['knowledge']): ?>
        <ul><?php foreach ($sections['knowledge'] as $ks): ?><li><?= e($ks) ?></li><?php endforeach; ?></ul>
      <?php else: ?>
        <p class="text-muted small">ยังไม่มี</p>
      <?php endif; ?>

      <h2 class="h6">ใบงาน</h2>
      <?php if ($sections['worksheets']): ?>
        <ul><?php foreach ($sections['worksheets'] as $ws): ?><li><?= e($ws) ?></li><?php endforeach; ?></ul>
      <?php elseif (!empty($sections['worksheets_covered_by_tasks'])): ?>
        <p class="text-muted small">ใบงานดำเนินการผ่านภาระงานในระบบด้านบนแล้ว ไม่แสดงซ้ำ</p>
      <?php else: ?>
        <p class="text-muted small">ยังไม่มี</p>
      <?php endif; ?>

      <h2 class="h6">สื่อ / แหล่งเรียนรู้</h2>
      <?php if ($sections['media']): ?>
        <ul>
          <?php foreach ($sections['media'] as $res): ?>
            <li>
              <?php if (!empty($res['url'])): ?>
                <a href="<?= e($res['url']) ?>" target="_blank" rel="noopener"><?= e($res['title'] ?? 'ลิงก์') ?></a>
                <?php if (!empty($res['type'])): ?><span class="text-muted small">(<?= e($res['type']) ?>)</span><?php endif; ?>
              <?php else: ?>
                <?= e($res['title'] ?? '') ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted small">ไม่มีสื่อเพิ่มเติม (เนื้อหาหลักอยู่ใน LMS)</p>
      <?php endif; ?>
    </div></div>
  </div>

  <div class="col-lg-4">
    <?php if (!empty($row['id']) && can_perm('curriculum','edit')): ?>
    <div class="card-soft mb-3"><div class="card-body">
      <h2 class="h6 mb-3">บันทึกความก้าวหน้า</h2>
      <form method="post" action="<?= url('/admin/curriculum/' . (int)$row['id'] . '/progress') ?>">
        <?= csrf_field() ?>
        <div class="mb-2">
          <label class="form-label">สมาชิกชมรม</label>
          <select name="member_id" class="form-select" required>
            <option value="">— เลือก —</option>
            <?php foreach ($members as $m): ?>
              <option value="<?= (int)$m['id'] ?>"><?= e(trim(($m['title']??'').($m['first_name']??'').' '.($m['last_name']??''))) ?> (<?= e($m['student_code']??'-') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">สถานะ</label>
          <select name="status" class="form-select">
            <?php foreach ($statusMap as $k => $label): ?>
              <option value="<?= e($k) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">วันที่เรียน</label>
          <input type="datetime-local" name="attended_at" class="form-control">
        </div>
        <div class="mb-2">
          <label class="form-label">คะแนน</label>
          <input type="number" step="0.01" name="score" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">หมายเหตุ</label>
          <textarea name="note" class="form-control" rows="2"></textarea>
        </div>
        <button class="btn-add w-100" type="submit"><i class="fa fa-save"></i> บันทึก</button>
      </form>
    </div></div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($row['id'])): ?>
<div class="card-soft"><div class="card-body">
  <h2 class="h6 mb-3">ความก้าวหน้าสมาชิก</h2>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead><tr><th>รหัส</th><th>ชื่อ</th><th>ชั้น/ห้อง</th><th>สถานะ</th><th>คะแนน</th><th>วันที่</th><th>หมายเหตุ</th></tr></thead>
      <tbody>
      <?php if (!$progress): ?>
        <tr><td colspan="7" class="text-muted">ยังไม่มีการบันทึก</td></tr>
      <?php else: foreach ($progress as $pr): ?>
        <tr>
          <td><?= e($pr['student_code'] ?? '-') ?></td>
          <td><?= e(trim(($pr['member_title']??'').($pr['first_name']??'').' '.($pr['last_name']??''))) ?></td>
          <td><?= e(($pr['grade_level']??'-').'/'.($pr['room']??'-')) ?></td>
          <td><?= e($statusMap[$pr['status']] ?? $pr['status']) ?></td>
          <td><?= e((string)($pr['score'] ?? '-')) ?></td>
          <td><?= e($pr['attended_at'] ? thai_date($pr['attended_at'], true) : '-') ?></td>
          <td><?= e($pr['note'] ?? '') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php endif; ?>
