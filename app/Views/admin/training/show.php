<?php
$plan = $plan ?? [];
$tab = $tab ?? 'content';
$settings = $settings ?? [];
$pid = (int) ($plan['id'] ?? 0);
$tabs = [
  'content' => 'เนื้อหา',
  'pretest' => 'ทดสอบก่อน',
  'tasks' => 'ภาระงาน',
  'posttest' => 'สอบหลัง',
  'satisfaction' => 'ความพึงพอใจ',
  'submissions' => 'ตรวจงาน',
  'settings' => 'น้ำหนัก %',
];
$typeLabel = [
  'pdf' => 'PDF', 'word' => 'Word', 'excel' => 'Excel',
  'gdrive' => 'Google Drive', 'youtube' => 'YouTube', 'link' => 'ลิงก์', 'text' => 'ข้อความ',
];
?>
<div class="page-head">
  <div>
    <p class="page-kicker">โมดูลที่ <?= (int) ($plan['plan_no'] ?? 0) ?></p>
    <h1><?= e($plan['title'] ?? '') ?></h1>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('/admin/training') ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
    <a class="btn btn-outline-primary" href="<?= url('/learn/' . $pid) ?>" target="_blank"><i class="fa fa-eye"></i> ดูแบบผู้เรียน (เว็บไซต์)</a>
  </div>
</div>

<ul class="nav nav-pills flex-wrap gap-1 mb-3">
  <?php foreach ($tabs as $k => $label): ?>
    <li class="nav-item">
      <a class="nav-link <?= $tab === $k ? 'active' : '' ?>" href="<?= url('/admin/training/' . $pid . '?tab=' . $k) ?>"><?= e($label) ?></a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="alert alert-info py-2 small">
  เนื้อหา / ข้อสอบ / ภาระงานที่บันทึกที่นี่ จะแสดงในการเรียนฝั่งเว็บไซต์ที่
  <a href="<?= url('/learn/' . $pid) ?>" target="_blank">/learn/<?= $pid ?></a>
  ด้วยลำดับเดียวกัน: ทดสอบก่อน → เนื้อหา → ภาระงาน → สอบหลัง → ความพึงพอใจ
</div>

<?php if ($tab === 'content'): ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card-soft"><div class="card-body">
      <h2 class="h6 mb-3">เพิ่มเนื้อหา / สื่อ</h2>
      <form method="post" action="<?= url('/admin/training/' . $pid . '/content') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-2">
          <label class="form-label">ชื่อเนื้อหา</label>
          <input name="title" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">ประเภท</label>
          <select name="content_type" class="form-select" id="content-type">
            <?php foreach ($typeLabel as $k => $v): ?>
              <option value="<?= e($k) ?>"><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">ไฟล์ (PDF / Word / Excel)</label>
          <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv">
        </div>
        <div class="mb-2">
          <label class="form-label">ลิงก์ (Google Drive / YouTube / อื่นๆ)</label>
          <input name="external_url" class="form-control" placeholder="https://...">
        </div>
        <div class="mb-2">
          <label class="form-label">ข้อความประกอบ</label>
          <textarea name="body_text" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">ลำดับ</label>
          <input type="number" name="sort_order" class="form-control" value="0">
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="is_required" value="1" id="c-req" checked>
          <label class="form-check-label" for="c-req">บังคับเรียน (นับใน progress)</label>
        </div>
        <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i> เพิ่มเนื้อหา</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="card-soft"><div class="card-body">
      <table class="table datatable mb-0 align-middle">
        <thead><tr><th>เนื้อหา</th><th>ประเภท</th><th>บังคับ</th><th></th></tr></thead>
        <tbody>
        <?php foreach (($contents ?? []) as $c): ?>
          <tr>
            <td>
              <strong><?= e($c['title']) ?></strong>
              <?php if ($c['external_url']): ?><div class="small"><a href="<?= e($c['external_url']) ?>" target="_blank" rel="noopener">เปิดลิงก์</a></div><?php endif; ?>
              <?php if ($c['file_path']): ?><div class="small text-muted">ไฟล์แนบ</div><?php endif; ?>
            </td>
            <td><span class="badge text-bg-light border"><?= e($typeLabel[$c['content_type']] ?? $c['content_type']) ?></span></td>
            <td><?= (int)$c['is_required'] ? 'ใช่' : 'ไม่' ?></td>
            <td class="text-end">
              <?php if (can_perm('assessments','delete')): ?>
              <form method="post" action="<?= url('/admin/training/' . $pid . '/content/' . (int)$c['id'] . '/delete') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger" type="submit"
                        data-confirm-delete="ลบเนื้อหานี้?"
                        data-confirm-title="ลบเนื้อหา"
                        data-confirm-ok="ลบ"><i class="fa fa-trash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($contents)): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">ยังไม่มีเนื้อหา</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div></div>
  </div>
</div>

<?php elseif ($tab === 'pretest' || $tab === 'posttest' || $tab === 'satisfaction'):
  $quiz = match ($tab) {
    'pretest' => ($preQuiz ?? null),
    'posttest' => ($postQuiz ?? null),
    default => ($satQuiz ?? null),
  };
  $questions = match ($tab) {
    'pretest' => ($preQuestions ?? []),
    'posttest' => ($postQuestions ?? []),
    default => ($satQuestions ?? []),
  };
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-soft mb-3"><div class="card-body">
      <h2 class="h6 mb-3">ตั้งค่า <?= e(strtoupper($tab)) ?></h2>
      <form method="post" action="<?= url('/admin/training/' . $pid . '/quiz-meta') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="quiz_type" value="<?= e($tab) ?>">
        <div class="mb-2"><label class="form-label">ชื่อ</label><input name="title" class="form-control" value="<?= e($quiz['title'] ?? '') ?>"></div>
        <div class="mb-2"><label class="form-label">คำอธิบาย</label><textarea name="description" class="form-control" rows="2"><?= e($quiz['description'] ?? '') ?></textarea></div>
        <div class="mb-2"><label class="form-label">คะแนนผ่าน (%)</label><input type="number" step="0.01" name="pass_score" class="form-control" value="<?= e((string)($quiz['pass_score'] ?? 60)) ?>"></div>
        <div class="mb-2"><label class="form-label">จำนวนครั้งสูงสุด</label><input type="number" name="max_attempts" class="form-control" value="<?= e((string)($quiz['max_attempts'] ?? ($tab==='pretest'?1:3))) ?>" min="1"></div>
        <div class="mb-3"><label class="form-label">จำกัดเวลา (นาที, ว่าง=ไม่จำกัด)</label><input type="number" name="time_limit_minutes" class="form-control" value="<?= e((string)($quiz['time_limit_minutes'] ?? '')) ?>"></div>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </form>
    </div></div>

    <div class="card-soft"><div class="card-body">
      <h2 class="h6 mb-3">เพิ่มข้อสอบ</h2>
      <form method="post" action="<?= url('/admin/training/' . $pid . '/question') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="quiz_type" value="<?= e($tab) ?>">
        <div class="mb-2"><label class="form-label">คำถาม</label><textarea name="question_text" class="form-control" rows="3" required></textarea></div>
        <div class="mb-2"><label class="form-label">คะแนนข้อนี้</label><input type="number" step="0.01" name="points" class="form-control" value="1"></div>
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="input-group mb-2">
            <div class="input-group-text">
              <input class="form-check-input mt-0" type="radio" name="correct_index" value="<?= $i ?>" <?= $i===0?'checked':'' ?> title="คำตอบถูก">
            </div>
            <input name="choices[]" class="form-control" placeholder="ตัวเลือก <?= $i+1 ?>">
          </div>
        <?php endfor; ?>
        <p class="small text-muted">เลือกจุดกลมหน้าตัวเลือกที่เป็นคำตอบถูก</p>
        <button class="btn btn-success" type="submit"><i class="fa fa-plus"></i> เพิ่มข้อ</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card-soft"><div class="card-body">
      <h2 class="h6 mb-3">คลังข้อสอบ (<?= count($questions) ?> ข้อ)</h2>
      <?php foreach ($questions as $qi => $q): ?>
        <div class="border rounded p-3 mb-2">
          <div class="d-flex justify-content-between gap-2">
            <div>
              <strong><?= ($qi+1) ?>. <?= e($q['question_text']) ?></strong>
              <span class="badge text-bg-secondary ms-1"><?= e((string)$q['points']) ?> คะแนน</span>
            </div>
            <?php if (can_perm('assessments','delete')): ?>
            <form method="post" action="<?= url('/admin/training/' . $pid . '/question/' . (int)$q['id'] . '/delete?tab=' . urlencode($tab)) ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger" type="submit"
                      data-confirm-delete="ลบข้อนี้?"
                      data-confirm-title="ลบคำถาม"
                      data-confirm-ok="ลบ"><i class="fa fa-trash"></i></button>
            </form>
            <?php endif; ?>
          </div>
          <ul class="mb-0 mt-2 small">
            <?php foreach (($q['choices'] ?? []) as $ch): ?>
              <li class="<?= (int)($ch['is_correct']??0) ? 'text-success fw-semibold' : '' ?>">
                <?= e($ch['choice_text']) ?><?= (int)($ch['is_correct']??0) ? ' ✓' : '' ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
      <?php if (!$questions): ?><p class="text-muted mb-0">ยังไม่มีข้อสอบ</p><?php endif; ?>
    </div></div>
  </div>
</div>

<?php elseif ($tab === 'tasks'): ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card-soft"><div class="card-body">
      <h2 class="h6 mb-3">เพิ่มภาระงาน (Task)</h2>
      <form method="post" action="<?= url('/admin/training/' . $pid . '/task') ?>">
        <?= csrf_field() ?>
        <div class="mb-2"><label class="form-label">ชื่องาน</label><input name="title" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">รายละเอียด</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="mb-2"><label class="form-label">คำชี้แจงการส่งงาน</label><textarea name="instructions" class="form-control" rows="2"></textarea></div>
        <div class="row g-2 mb-2">
          <div class="col"><label class="form-label">คะแนนเต็ม</label><input type="number" name="max_score" class="form-control" value="100"></div>
          <div class="col"><label class="form-label">ผ่าน</label><input type="number" name="pass_score" class="form-control" value="60"></div>
        </div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="require_file" value="1" id="tf"><label class="form-check-label" for="tf">บังคับแนบไฟล์/ลิงก์</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="allow_link" value="1" id="tl" checked><label class="form-check-label" for="tl">อนุญาตลิงก์</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="require_review" value="1" id="tr" checked><label class="form-check-label" for="tr">ต้องให้วิทยากรตรวจ</label></div>
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_required" value="1" id="treq" checked><label class="form-check-label" for="treq">บังคับ (นับใน progress)</label></div>
        <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i> เพิ่มภาระงาน</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="card-soft"><div class="card-body">
      <table class="table datatable mb-0">
        <thead><tr><th>ภาระงาน</th><th>ตรวจ</th><th></th></tr></thead>
        <tbody>
        <?php foreach (($tasks ?? []) as $t): ?>
          <tr>
            <td>
              <strong><?= e($t['title']) ?></strong>
              <div class="small text-muted"><?= e(mb_strimwidth((string)($t['description']??''), 0, 80, '…')) ?></div>
            </td>
            <td class="small"><?= (int)$t['require_review'] ? 'ต้องตรวจ' : 'ส่งแล้วผ่าน' ?></td>
            <td class="text-end">
              <?php if (can_perm('assessments','delete')): ?>
              <form method="post" action="<?= url('/admin/training/' . $pid . '/task/' . (int)$t['id'] . '/delete') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger" type="submit"
                        data-confirm-delete="ลบภาระงานนี้?"
                        data-confirm-title="ลบภาระงาน"
                        data-confirm-ok="ลบ"><i class="fa fa-trash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($tasks)): ?><tr><td colspan="3" class="text-center text-muted py-4">ยังไม่มีภาระงาน</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div></div>
  </div>
</div>

<?php elseif ($tab === 'submissions'): ?>
<div class="card-soft"><div class="card-body">
  <table class="table datatable table-hover mb-0 align-middle">
    <thead><tr><th>ผู้เรียน</th><th>ภาระงาน</th><th>สถานะ</th><th>ส่งเมื่อ</th><th>ตรวจ</th></tr></thead>
    <tbody>
    <?php foreach (($submissions ?? []) as $s): ?>
      <tr>
        <td><?= e(trim(($s['first_name']??'').' '.($s['last_name']??'')) ?: $s['username']) ?><div class="small text-muted"><?= e($s['enrollment_no']) ?></div></td>
        <td><?= e($s['task_title']) ?>
          <?php if ($s['link_url']): ?><div class="small"><a href="<?= e($s['link_url']) ?>" target="_blank">ลิงก์</a></div><?php endif; ?>
          <?php if ($s['file_path']): ?><div class="small"><a href="<?= e(upload_url($s['file_path'])) ?>" target="_blank">ไฟล์</a></div><?php endif; ?>
          <?php if ($s['answer_text']): ?><div class="small text-muted"><?= e(mb_strimwidth($s['answer_text'],0,100,'…')) ?></div><?php endif; ?>
        </td>
        <td><span class="badge text-bg-<?= $s['status']==='approved'?'success':($s['status']==='rejected'?'danger':'warning') ?>"><?= e($s['status']) ?></span></td>
        <td class="small"><?= e($s['submitted_at'] ?? '-') ?></td>
        <td style="min-width:220px">
          <?php if (can_perm('assessments','edit') && in_array($s['status'], ['submitted','revision'], true)): ?>
          <form method="post" action="<?= url('/admin/training/' . $pid . '/submission/' . (int)$s['id'] . '/review') ?>" class="d-flex flex-column gap-1">
            <?= csrf_field() ?>
            <input type="number" name="score" class="form-control form-control-sm" placeholder="คะแนน" step="0.01">
            <input name="feedback" class="form-control form-control-sm" placeholder="ความเห็น">
            <div class="btn-group btn-group-sm">
              <button name="status" value="approved" class="btn btn-success" type="submit">ผ่าน</button>
              <button name="status" value="revision" class="btn btn-warning" type="submit">แก้</button>
              <button name="status" value="rejected" class="btn btn-outline-danger" type="submit">ไม่ผ่าน</button>
            </div>
          </form>
          <?php else: ?>
            <?= e((string)($s['score'] ?? '-')) ?> · <?= e(mb_strimwidth((string)($s['feedback']??''),0,40,'…')) ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($submissions)): ?><tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีการส่งงาน</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></div>

<?php else: /* settings */ ?>
<div class="card-soft" style="max-width:640px"><div class="card-body">
  <h2 class="h6 mb-3">น้ำหนักความก้าวหน้า (รวมควรได้ 100)</h2>
  <form method="post" action="<?= url('/admin/training/' . $pid . '/settings') ?>">
    <?= csrf_field() ?>
    <div class="row g-2 mb-3">
      <div class="col-6"><label class="form-label">ทดสอบก่อน %</label><input type="number" step="0.01" name="weight_pretest" class="form-control" value="<?= e((string)($settings['weight_pretest'] ?? 10)) ?>"></div>
      <div class="col-6"><label class="form-label">เนื้อหา %</label><input type="number" step="0.01" name="weight_content" class="form-control" value="<?= e((string)($settings['weight_content'] ?? 35)) ?>"></div>
      <div class="col-6"><label class="form-label">ภาระงาน %</label><input type="number" step="0.01" name="weight_task" class="form-control" value="<?= e((string)($settings['weight_task'] ?? 15)) ?>"></div>
      <div class="col-6"><label class="form-label">สอบหลัง %</label><input type="number" step="0.01" name="weight_posttest" class="form-control" value="<?= e((string)($settings['weight_posttest'] ?? 25)) ?>"></div>
      <div class="col-6"><label class="form-label">ความพึงพอใจ %</label><input type="number" step="0.01" name="weight_satisfaction" class="form-control" value="<?= e((string)($settings['weight_satisfaction'] ?? 15)) ?>"></div>
    </div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="require_pretest" value="1" id="rp" <?= (int)($settings['require_pretest'] ?? 1)?'checked':'' ?>><label class="form-check-label" for="rp">บังคับทำทดสอบก่อนเรียน</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="gate_content_after_pretest" value="1" id="gc" <?= (int)($settings['gate_content_after_pretest'] ?? 1)?'checked':'' ?>><label class="form-check-label" for="gc">ปลดล็อกเนื้อหาหลังทดสอบก่อน</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="gate_posttest_after_content" value="1" id="gp" <?= (int)($settings['gate_posttest_after_content'] ?? 1)?'checked':'' ?>><label class="form-check-label" for="gp">ปลดล็อกสอบหลังหลังเนื้อหา+ภาระงานครบ</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="require_pass_posttest" value="1" id="rpp" <?= (int)($settings['require_pass_posttest'] ?? 1)?'checked':'' ?>><label class="form-check-label" for="rpp">ต้องผ่านสอบหลังถึงจะนับขั้นนี้ครบ</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="gate_satisfaction_after_posttest" value="1" id="gs" <?= (int)($settings['gate_satisfaction_after_posttest'] ?? 1)?'checked':'' ?>><label class="form-check-label" for="gs">ปลดล็อกประเมินความพึงพอใจหลังสอบหลัง</label></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="require_satisfaction" value="1" id="rs" <?= (int)($settings['require_satisfaction'] ?? 1)?'checked':'' ?>><label class="form-check-label" for="rs">บังคับทำแบบประเมินความพึงพอใจ</label></div>
    <button class="btn btn-primary" type="submit">บันทึก</button>
  </form>
</div></div>
<?php endif; ?>
