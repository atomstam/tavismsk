<?php
$plan = $plan ?? [];
$tab = $tab ?? 'content';
$settings = $settings ?? [];
$pid = (int) ($plan['id'] ?? 0);
$tabs = [
  'content' => ['label' => 'เนื้อหา', 'icon' => 'fa-book-open'],
  'pretest' => ['label' => 'ทดสอบก่อน', 'icon' => 'fa-clipboard-question'],
  'tasks' => ['label' => 'ภาระงาน', 'icon' => 'fa-list-check'],
  'posttest' => ['label' => 'สอบหลัง', 'icon' => 'fa-file-circle-check'],
  'submissions' => ['label' => 'ตรวจงาน', 'icon' => 'fa-clipboard-check'],
  'settings' => ['label' => 'น้ำหนัก %', 'icon' => 'fa-sliders'],
];
$typeLabel = [
  'pdf' => 'PDF', 'word' => 'Word', 'excel' => 'Excel',
  'gdrive' => 'Google Drive', 'youtube' => 'YouTube', 'link' => 'ลิงก์', 'text' => 'ข้อความ',
];
$shares = $shares ?? [];
$shareLine = static function (string $key) use ($shares): string {
    $part = $shares[$key] ?? null;
    if (!is_array($part)) {
        return '';
    }
    $weight = number_format((float) ($part['weight'] ?? 0), 2);
    $count = (int) ($part['count'] ?? 0);
    $unit = (string) ($part['unit'] ?? 'รายการ');
    if ($count < 1) {
        return 'น้ำหนักแผน ' . $weight . '% ยังไม่มี' . $unit . 'ที่นำมาคิดคะแนน';
    }
    $each = number_format((float) ($part['each'] ?? 0), 2);
    $points = number_format((float) ($part['points_total'] ?? $count), 0);
    if (!empty($part['fixed'])) {
        return 'คะแนนคงที่ ' . $count . ' ' . $unit . ' ละ 1 คะแนน รวม ' . $points . ' คะแนน = น้ำหนักแผน ' . $weight . '% ดังนั้น 1 คะแนน = ' . $each . '%';
    }
    return 'คิดจากจำนวนรายการ ขณะนี้ ' . $count . ' ' . $unit . ' ละ 1 คะแนน รวม ' . $points . ' คะแนน แล้วแปลงให้เท่ากับน้ำหนักแผน ' . $weight . '% ดังนั้น 1 คะแนน = ' . $each . '%';
};
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

<nav class="lms-tabs mb-3" aria-label="แท็บจัดการบทเรียน">
  <ul class="lms-tabs-list">
    <?php foreach ($tabs as $k => $meta):
      $label = is_array($meta) ? (string) ($meta['label'] ?? $k) : (string) $meta;
      $icon = is_array($meta) ? (string) ($meta['icon'] ?? 'fa-circle') : 'fa-circle';
      $active = $tab === $k;
    ?>
      <li>
        <a class="lms-tab<?= $active ? ' is-active' : '' ?>"
           href="<?= url('/admin/training/' . $pid . '?tab=' . $k) ?>"
           <?= $active ? 'aria-current="page"' : '' ?>>
          <i class="fa <?= e($icon) ?>" aria-hidden="true"></i>
          <span><?= e($label) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>

<div class="alert alert-info py-2 small">
  <?php
    $quizDraw = (int) ($settings['quiz_draw_count'] ?? 10);
    $quizShuffle = (int) ($settings['quiz_shuffle'] ?? 1) === 1;
  ?>
  เนื้อหา / ข้อสอบ / ภาระงานที่บันทึกที่นี่ จะแสดงในการเรียนฝั่งเว็บไซต์ที่
  <a href="<?= url('/learn/' . $pid) ?>" target="_blank">/learn/<?= $pid ?></a>
  ด้วยลำดับเดียวกัน: ทดสอบก่อน → เนื้อหา → ภาระงาน → สอบหลัง → ความพึงพอใจ
  แผนนี้ดึง <?= $quizDraw ?> ข้อ<?= $quizShuffle ? ' แบบสุ่มลำดับข้อและตัวเลือก (ถ้าคลังแผนไม่พอ ระบบเติมจากคลังรวมโดยไม่ซ้ำข้อความ) ข้อสอบหลังใช้ชุดเดิมแต่สลับลำดับ' : ' ตามลำดับในคลัง ไม่สุ่ม' ?>
  จำนวนข้อและการสุ่มตั้งในแท็บน้ำหนัก %
  แบบประเมินความพึงพอใจเป็นชุดกลางของผู้ดูแลระบบ ใช้ร่วมทุกแผน
</div>

<?php if ($tab === 'content'): ?>
<div class="lms-board">
<div class="lms-split">
  <div class="card-soft lms-panel" id="content-form-panel"><div class="card-body">
      <h2 id="content-form-title">เพิ่มเนื้อหา / สื่อ</h2>
      <form method="post" action="<?= url('/admin/training/' . $pid . '/content') ?>" enctype="multipart/form-data" id="content-form">
        <?= csrf_field() ?>
        <input type="hidden" name="content_id" value="">
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
          <p class="small text-muted mb-0 mt-1" id="content-file-note" hidden></p>
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
        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit" id="content-form-submit"><i class="fa fa-plus"></i> เพิ่มเนื้อหา</button>
          <button class="btn btn-outline-secondary" type="button" id="content-form-cancel" hidden>ยกเลิกแก้ไข</button>
        </div>
      </form>
    </div></div>
  <div class="card-soft lms-panel"><div class="card-body">
      <h2>รายการเนื้อหา</h2>
      <p class="small text-muted"><?= e($shareLine('content')) ?> เพิ่มหรือลดเรื่องที่บังคับ แล้วคะแนนรวมของส่วนนี้จะเปลี่ยน แต่ยังแปลงเป็นร้อยละตามน้ำหนักเนื้อหาของแผน</p>
      <table class="table lms-table mb-0 align-middle">
        <thead><tr><th>เนื้อหา</th><th>ประเภท</th><th>บังคับ</th><th></th></tr></thead>
        <tbody>
        <?php foreach (($contents ?? []) as $c): ?>
          <tr>
            <td>
              <strong><?= e($c['title']) ?></strong>
            </td>
            <td><span class="badge text-bg-light border"><?= e($typeLabel[$c['content_type']] ?? $c['content_type']) ?></span></td>
            <td><?= (int)$c['is_required'] ? '1 คะแนน = ' . e(number_format((float)($shares['content']['each'] ?? 0), 2)) . '%' : 'ไม่นับ' ?></td>
            <td class="text-end">
              <div class="lms-q-actions justify-content-end">
                <?php
                  $openOutside = in_array((string) $c['content_type'], ['gdrive', 'link'], true);
                  $outsideUrl = trim((string) ($c['external_url'] ?? ''));
                  $openHref = ($openOutside && $outsideUrl !== '')
                    ? $outsideUrl
                    : url('/admin/training/' . $pid . '/content/' . (int) $c['id'] . '/view');
                ?>
                <a class="btn btn-sm btn-outline-secondary" title="<?= $openOutside ? 'เปิดลิงก์ภายนอก' : 'เปิดดูในระบบ' ?>"
                   href="<?= e($openHref) ?>" target="_blank" rel="noopener">
                  <i class="fa fa-up-right-from-square"></i>
                </a>
                <?php if (can_perm('assessments','edit')):
                  $contentPayload = [
                    'id' => (int) $c['id'],
                    'title' => (string) $c['title'],
                    'content_type' => (string) $c['content_type'],
                    'external_url' => (string) ($c['external_url'] ?? ''),
                    'body_text' => (string) ($c['body_text'] ?? ''),
                    'sort_order' => (int) ($c['sort_order'] ?? 0),
                    'is_required' => (int) ($c['is_required'] ?? 0) === 1,
                    'has_file' => !empty($c['file_path']),
                  ];
                ?>
                <button type="button" class="btn btn-sm btn-outline-primary" title="แก้ไข"
                        data-content-edit="<?= e(json_encode($contentPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">
                  <i class="fa fa-pen"></i>
                </button>
                <?php endif; ?>
                <?php if (can_perm('assessments','delete')): ?>
                <form method="post" action="<?= url('/admin/training/' . $pid . '/content/' . (int)$c['id'] . '/delete') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger" type="submit"
                          data-confirm-delete="ลบเนื้อหานี้?"
                          data-confirm-title="ลบเนื้อหา"
                          data-confirm-ok="ลบ"><i class="fa fa-trash"></i></button>
                </form>
                <?php endif; ?>
              </div>
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
<script>
(function () {
  const form = document.getElementById('content-form');
  if (!form) return;
  const title = document.getElementById('content-form-title');
  const submit = document.getElementById('content-form-submit');
  const cancel = document.getElementById('content-form-cancel');
  const note = document.getElementById('content-file-note');
  const panel = document.getElementById('content-form-panel');

  const resetForm = () => {
    form.reset();
    form.elements.namedItem('content_id').value = '';
    note.hidden = true;
    note.textContent = '';
    form.elements.namedItem('is_required').checked = true;
    title.textContent = 'เพิ่มเนื้อหา / สื่อ';
    submit.innerHTML = '<i class="fa fa-plus"></i> เพิ่มเนื้อหา';
    cancel.hidden = true;
  };

  document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-content-edit]');
    if (!btn) return;
    let data;
    try {
      data = JSON.parse(btn.getAttribute('data-content-edit') || '');
    } catch (err) {
      return;
    }
    resetForm();
    form.elements.namedItem('content_id').value = data.id || '';
    form.elements.namedItem('title').value = data.title || '';
    form.elements.namedItem('content_type').value = data.content_type || 'text';
    form.elements.namedItem('external_url').value = data.external_url || '';
    form.elements.namedItem('body_text').value = data.body_text || '';
    form.elements.namedItem('sort_order').value = data.sort_order || 0;
    form.elements.namedItem('is_required').checked = !!data.is_required;
    if (data.has_file) {
      note.hidden = false;
      note.textContent = 'มีไฟล์เดิมอยู่ เลือกไฟล์ใหม่เมื่อต้องการเปลี่ยน';
    }
    title.textContent = 'แก้ไขเนื้อหา';
    submit.innerHTML = '<i class="fa fa-check"></i> บันทึกการแก้ไข';
    cancel.hidden = false;
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.elements.namedItem('title').focus();
  });

  cancel.addEventListener('click', resetForm);
})();
</script>

<?php elseif ($tab === 'pretest' || $tab === 'posttest'):
  $quiz = $tab === 'pretest' ? ($preQuiz ?? null) : ($postQuiz ?? null);
  $questions = $tab === 'pretest' ? ($preQuestions ?? []) : ($postQuestions ?? []);
?>
<div class="lms-board">
  <div class="card-soft lms-panel"><div class="card-body">
      <h2>ตั้งค่า <?= e(strtoupper($tab)) ?></h2>
      <form method="post" action="<?= url('/admin/training/' . $pid . '/quiz-meta') ?>" class="lms-meta">
        <?= csrf_field() ?>
        <input type="hidden" name="quiz_type" value="<?= e($tab) ?>">
        <div class="lms-field lms-field-wide"><label class="form-label">ชื่อ</label><input name="title" class="form-control" value="<?= e($quiz['title'] ?? '') ?>"></div>
        <div class="lms-field lms-field-wide"><label class="form-label">คำอธิบาย</label><input name="description" class="form-control" value="<?= e($quiz['description'] ?? '') ?>"></div>
        <div class="lms-field"><label class="form-label">คะแนนผ่าน (%)</label><input type="number" step="0.01" name="pass_score" class="form-control" value="<?= e((string)($quiz['pass_score'] ?? ($tab==='posttest'?70:60))) ?>"></div>
        <div class="lms-field"><label class="form-label">จำนวนครั้งสูงสุด</label><input type="number" name="max_attempts" class="form-control" value="<?= e((string)($quiz['max_attempts'] ?? ($tab==='pretest'?1:3))) ?>" min="1"></div>
        <div class="lms-field"><label class="form-label">จำกัดเวลา (นาที)</label><input type="number" name="time_limit_minutes" class="form-control" value="<?= e((string)($quiz['time_limit_minutes'] ?? '')) ?>" placeholder="ว่าง = ไม่จำกัด"></div>
        <div class="lms-field lms-field-action"><button class="btn btn-primary" type="submit">บันทึก</button></div>
      </form>
    </div></div>

  <div class="lms-split">
    <div class="card-soft lms-panel" id="quiz-question-panel"><div class="card-body">
      <h2 id="quiz-form-title">เพิ่มข้อสอบ</h2>
      <form method="post" action="<?= url('/admin/training/' . $pid . '/question') ?>" enctype="multipart/form-data" id="quiz-question-form">
        <?= csrf_field() ?>
        <input type="hidden" name="quiz_type" value="<?= e($tab === 'posttest' ? 'pretest' : $tab) ?>">
        <input type="hidden" name="return_tab" value="<?= e($tab) ?>">
        <input type="hidden" name="question_id" value="">
        <input type="hidden" name="question_image_path" value="">
        <div class="mb-2"><label class="form-label">คำถาม</label><textarea name="question_text" class="form-control" rows="3" required></textarea></div>
        <div class="mb-2">
          <label class="form-label">รูปคำถาม</label>
          <input type="file" name="question_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
          <div class="quiz-edit-preview" data-preview="question" hidden>
            <img class="quiz-figure quiz-figure-sm" alt="">
            <label class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_question_image" value="1"> ลบรูปนี้</label>
          </div>
        </div>
        <p class="small text-muted">คะแนนคงที่ ข้อละ 1 คะแนน = <?= e(number_format((float)($shares[$tab === 'posttest' ? 'posttest' : 'pretest']['each'] ?? 0), 2)) ?>% ตามน้ำหนัก<?= $tab === 'posttest' ? 'สอบหลัง' : 'สอบก่อน' ?>ของแผนนี้ ไม่เปลี่ยนตามจำนวนเนื้อหาหรือภาระงาน</p>
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="mb-2" data-choice-slot="<?= $i ?>">
            <input type="hidden" name="choice_ids[]" value="">
            <input type="hidden" name="choice_image_paths[]" value="">
            <div class="input-group">
              <div class="input-group-text">
                <input class="form-check-input mt-0" type="radio" name="correct_index" value="<?= $i ?>" <?= $i===0?'checked':'' ?> title="คำตอบถูก">
              </div>
              <input name="choices[]" class="form-control" placeholder="ตัวเลือก <?= $i+1 ?>">
            </div>
            <input type="file" name="choice_images[]" class="form-control form-control-sm mt-1" accept="image/jpeg,image/png,image/webp,image/gif" aria-label="รูปตัวเลือก <?= $i+1 ?>">
            <div class="quiz-edit-preview" data-preview="choice" hidden>
              <img class="quiz-figure quiz-figure-sm" alt="">
              <label class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_choice_image[<?= $i ?>]" value="1"> ลบรูปนี้</label>
            </div>
          </div>
        <?php endfor; ?>
        <p class="small text-muted">เลือกจุดกลมหน้าตัวเลือกที่เป็นคำตอบถูก เว้นว่างได้ถ้าไม่ใช้ตัวเลือกนั้น</p>
        <div class="d-flex gap-2">
          <button class="btn btn-success" type="submit" id="quiz-form-submit"><i class="fa fa-plus"></i> เพิ่มข้อ</button>
          <button class="btn btn-outline-secondary" type="button" id="quiz-form-cancel" hidden>ยกเลิกแก้ไข</button>
        </div>
      </form>
    </div></div>
    <div class="card-soft lms-panel"><div class="card-body">
      <h2>คลังข้อสอบ (<?= count($questions) ?> ข้อ)</h2>
      <p class="small text-muted"><?= e($shareLine($tab === 'posttest' ? 'posttest' : 'pretest')) ?></p>
      <?php foreach ($questions as $qi => $q): ?>
        <div class="lms-q">
          <div class="lms-q-head">
            <div class="lms-q-text">
              <?= ($qi+1) ?>. <?= e($q['question_text']) ?>
              <span class="badge text-bg-secondary ms-1">1 คะแนนคงที่ = <?= e(number_format((float)($shares[$tab === 'posttest' ? 'posttest' : 'pretest']['each'] ?? 0), 2)) ?>%</span>
              <?php if (!empty($q['image_path'])): ?><img class="quiz-figure" src="<?= e(upload_url($q['image_path'])) ?>" alt=""><?php endif; ?>
            </div>
            <div class="lms-q-actions">
              <?php if (can_perm('assessments','edit')):
                $editPayload = [
                  'id' => (int) $q['id'],
                  'question_text' => (string) $q['question_text'],
                  'points' => (string) $q['points'],
                  'image_url' => !empty($q['image_path']) ? upload_url((string) $q['image_path']) : '',
                  'image_path' => (string) ($q['image_path'] ?? ''),
                  'choices' => array_map(static function (array $ch): array {
                    return [
                      'id' => (int) $ch['id'],
                      'text' => trim((string) ($ch['choice_text'] ?? '')),
                      'is_correct' => (int) ($ch['is_correct'] ?? 0) === 1,
                      'image_url' => !empty($ch['image_path']) ? upload_url((string) $ch['image_path']) : '',
                      'image_path' => (string) ($ch['image_path'] ?? ''),
                    ];
                  }, $q['choices'] ?? []),
                ];
              ?>
              <button type="button" class="btn btn-sm btn-outline-primary" title="แก้ไข"
                      data-quiz-edit="<?= e(json_encode($editPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">
                <i class="fa fa-pen"></i>
              </button>
              <?php endif; ?>
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
          </div>
          <ul class="lms-choices">
            <?php foreach (($q['choices'] ?? []) as $ch): ?>
              <li class="<?= (int)($ch['is_correct']??0) ? 'is-correct' : '' ?>">
                <?php if (!empty($ch['image_path'])): ?><img class="quiz-figure quiz-figure-sm" src="<?= e(upload_url($ch['image_path'])) ?>" alt=""><?php endif; ?>
                <?= e(trim((string)($ch['choice_text'] ?? ''))) ?><?= (int)($ch['is_correct']??0) ? ' ✓' : '' ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
      <?php if (!$questions): ?><p class="text-muted mb-0">ยังไม่มีข้อสอบ</p><?php endif; ?>
    </div></div>
  </div>
</div>
<script>
(function () {
  const form = document.getElementById('quiz-question-form');
  if (!form) return;
  const title = document.getElementById('quiz-form-title');
  const submit = document.getElementById('quiz-form-submit');
  const cancel = document.getElementById('quiz-form-cancel');
  const panel = document.getElementById('quiz-question-panel');
  const slots = form.querySelectorAll('[data-choice-slot]');

  const showPreview = (box, url) => {
    if (!box) return;
    const img = box.querySelector('img');
    const check = box.querySelector('input[type="checkbox"]');
    if (check) check.checked = false;
    if (!url) {
      box.hidden = true;
      if (img) img.removeAttribute('src');
      return;
    }
    if (img) img.src = url;
    box.hidden = false;
  };

  const resetForm = () => {
    form.reset();
    form.question_id.value = '';
    form.question_image_path.value = '';
    showPreview(form.querySelector('[data-preview="question"]'), '');
    slots.forEach((slot) => {
      slot.querySelector('[name="choice_ids[]"]').value = '';
      slot.querySelector('[name="choice_image_paths[]"]').value = '';
      showPreview(slot.querySelector('[data-preview="choice"]'), '');
    });
    const firstRadio = form.querySelector('[name="correct_index"]');
    if (firstRadio) firstRadio.checked = true;
    title.textContent = 'เพิ่มข้อสอบ';
    submit.className = 'btn btn-success';
    submit.innerHTML = '<i class="fa fa-plus"></i> เพิ่มข้อ';
    cancel.hidden = true;
  };

  document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-quiz-edit]');
    if (!btn) return;
    let data;
    try {
      data = JSON.parse(btn.getAttribute('data-quiz-edit') || '');
    } catch (err) {
      return;
    }
    resetForm();
    form.question_id.value = data.id || '';
    form.question_text.value = data.question_text || '';
    form.question_image_path.value = data.image_path || '';
    showPreview(form.querySelector('[data-preview="question"]'), data.image_url || '');
    const choices = Array.isArray(data.choices) ? data.choices : [];
    let correct = 0;
    slots.forEach((slot, index) => {
      const choice = choices[index] || {};
      slot.querySelector('[name="choices[]"]').value = choice.text || '';
      slot.querySelector('[name="choice_ids[]"]').value = choice.id || '';
      slot.querySelector('[name="choice_image_paths[]"]').value = choice.image_path || '';
      showPreview(slot.querySelector('[data-preview="choice"]'), choice.image_url || '');
      if (choice.is_correct) correct = index;
    });
    const radio = form.querySelector('[name="correct_index"][value="' + correct + '"]');
    if (radio) radio.checked = true;
    title.textContent = 'แก้ไขข้อสอบ';
    submit.className = 'btn btn-primary';
    submit.innerHTML = '<i class="fa fa-check"></i> บันทึกการแก้ไข';
    cancel.hidden = false;
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.question_text.focus();
  });

  cancel.addEventListener('click', resetForm);
})();
</script>

<?php elseif ($tab === 'tasks'): ?>
<div class="lms-board">
<div class="lms-split">
  <div class="card-soft lms-panel" id="task-form-panel"><div class="card-body">
      <h2 id="task-form-title">เพิ่มภาระงาน</h2>
      <form method="post" action="<?= url('/admin/training/' . $pid . '/task') ?>" id="task-form">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" value="">
        <input type="hidden" name="sort_order" value="0">
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
        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit" id="task-form-submit"><i class="fa fa-plus"></i> เพิ่มภาระงาน</button>
          <button class="btn btn-outline-secondary" type="button" id="task-form-cancel" hidden>ยกเลิกแก้ไข</button>
        </div>
      </form>
    </div></div>
  <div class="card-soft lms-panel"><div class="card-body">
      <h2>รายการภาระงาน</h2>
      <p class="small text-muted"><?= e($shareLine('task')) ?> เพิ่มหรือลดงานที่บังคับ แล้วคะแนนรวมของส่วนนี้จะเปลี่ยน แต่ยังแปลงเป็นร้อยละตามน้ำหนักภาระงานของแผน</p>
      <table class="table lms-table mb-0">
        <thead><tr><th>ภาระงาน</th><th>ตรวจ</th><th></th></tr></thead>
        <tbody>
        <?php foreach (($tasks ?? []) as $t): ?>
          <tr>
            <td>
              <strong><?= e($t['title']) ?></strong>
              <div class="small text-muted"><?= e(mb_strimwidth((string)($t['description']??''), 0, 80, '…')) ?></div>
            </td>
            <td class="small">
              <?= (int)$t['require_review'] ? 'ต้องตรวจ' : 'ส่งแล้วผ่าน' ?>
              <div class="text-muted"><?= (int)($t['is_required'] ?? 0) ? '1 คะแนน = ' . e(number_format((float)($shares['task']['each'] ?? 0), 2)) . '%' : 'ไม่นับ' ?></div>
            </td>
            <td class="text-end">
              <div class="lms-q-actions justify-content-end">
              <?php if (can_perm('assessments','edit')):
                $taskPayload = [
                  'id' => (int) $t['id'],
                  'title' => (string) $t['title'],
                  'description' => (string) ($t['description'] ?? ''),
                  'instructions' => (string) ($t['instructions'] ?? ''),
                  'max_score' => (string) $t['max_score'],
                  'pass_score' => (string) $t['pass_score'],
                  'sort_order' => (int) ($t['sort_order'] ?? 0),
                  'require_file' => (int) ($t['require_file'] ?? 0) === 1,
                  'allow_link' => (int) ($t['allow_link'] ?? 0) === 1,
                  'require_review' => (int) ($t['require_review'] ?? 0) === 1,
                  'is_required' => (int) ($t['is_required'] ?? 0) === 1,
                ];
              ?>
              <button type="button" class="btn btn-sm btn-outline-primary" title="แก้ไข"
                      data-task-edit="<?= e(json_encode($taskPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">
                <i class="fa fa-pen"></i>
              </button>
              <?php endif; ?>
              <?php if (can_perm('assessments','delete')): ?>
              <form method="post" action="<?= url('/admin/training/' . $pid . '/task/' . (int)$t['id'] . '/delete') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger" type="submit"
                        data-confirm-delete="ลบภาระงานนี้?"
                        data-confirm-title="ลบภาระงาน"
                        data-confirm-ok="ลบ"><i class="fa fa-trash"></i></button>
              </form>
              <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($tasks)): ?><tr><td colspan="3" class="text-center text-muted py-4">ยังไม่มีภาระงาน</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div></div>
</div>
</div>
<script>
(function () {
  const form = document.getElementById('task-form');
  if (!form) return;
  const title = document.getElementById('task-form-title');
  const submit = document.getElementById('task-form-submit');
  const cancel = document.getElementById('task-form-cancel');
  const panel = document.getElementById('task-form-panel');
  const setCheck = (name, on) => {
    const el = form.elements.namedItem(name);
    if (el) el.checked = !!on;
  };
  const resetForm = () => {
    form.reset();
    form.elements.namedItem('task_id').value = '';
    form.elements.namedItem('sort_order').value = '0';
    setCheck('require_file', false);
    setCheck('allow_link', true);
    setCheck('require_review', true);
    setCheck('is_required', true);
    form.elements.namedItem('max_score').value = '100';
    form.elements.namedItem('pass_score').value = '60';
    title.textContent = 'เพิ่มภาระงาน';
    submit.innerHTML = '<i class="fa fa-plus"></i> เพิ่มภาระงาน';
    cancel.hidden = true;
  };
  document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-task-edit]');
    if (!btn) return;
    let data;
    try { data = JSON.parse(btn.getAttribute('data-task-edit') || ''); } catch (err) { return; }
    resetForm();
    form.elements.namedItem('task_id').value = data.id || '';
    form.elements.namedItem('title').value = data.title || '';
    form.elements.namedItem('description').value = data.description || '';
    form.elements.namedItem('instructions').value = data.instructions || '';
    form.elements.namedItem('max_score').value = data.max_score || '100';
    form.elements.namedItem('pass_score').value = data.pass_score || '60';
    form.elements.namedItem('sort_order').value = data.sort_order || '0';
    setCheck('require_file', data.require_file);
    setCheck('allow_link', data.allow_link);
    setCheck('require_review', data.require_review);
    setCheck('is_required', data.is_required);
    title.textContent = 'แก้ไขภาระงาน';
    submit.innerHTML = '<i class="fa fa-check"></i> บันทึกการแก้ไข';
    cancel.hidden = false;
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.elements.namedItem('title').focus();
  });
  cancel.addEventListener('click', resetForm);
})();
</script>

<?php elseif ($tab === 'submissions'):
  $pendingCount = (int) ($pendingCount ?? 0);
  $statusTh = static fn (?string $st): string => match ((string) $st) {
    'submitted' => 'รอตรวจ',
    'approved' => 'ตรวจผ่าน',
    'rejected' => 'ไม่ผ่าน',
    'revision' => 'ต้องแก้ไข',
    default => (string) $st,
  };
?>
<div class="card-soft mb-3"><div class="card-body">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <div>
      <h2 class="h6 mb-1">ตรวจให้คะแนนภาระงาน</h2>
      <p class="small text-muted mb-0">ค่าเริ่มต้นของแต่ละภาระงาน = คะแนนเต็ม · รอตรวจ <?= $pendingCount ?> รายการ</p>
    </div>
    <?php if (can_perm('assessments','edit') && $pendingCount > 0): ?>
    <form method="post" action="<?= url('/admin/training/' . $pid . '/submissions/bulk-review') ?>" class="d-flex flex-wrap gap-2"
          data-confirm="ตรวจผ่านทุกคน ทุกภาระงานที่รอตรวจในแผนนี้? (คะแนน = คะแนนเต็ม)">
      <?= csrf_field() ?>
      <input type="hidden" name="scope" value="all">
      <button class="btn btn-success btn-sm" type="submit"><i class="fa fa-check-double"></i> ตรวจผ่านทั้งแผน</button>
    </form>
    <?php endif; ?>
  </div>
</div></div>

<?php if (can_perm('assessments','edit') && $pendingCount > 0): ?>
<form method="post" action="<?= url('/admin/training/' . $pid . '/submissions/bulk-review') ?>" id="bulk-selected-form" class="d-none">
  <?= csrf_field() ?>
  <input type="hidden" name="scope" value="selected">
</form>
<?php endif; ?>
<div class="card-soft"><div class="card-body">
    <?php if (can_perm('assessments','edit') && $pendingCount > 0): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button class="btn btn-outline-success btn-sm" type="submit" form="bulk-selected-form"
              data-confirm="ตรวจผ่านรายการที่เลือก? (คะแนน = คะแนนเต็มของแต่ละภาระงาน)">
        <i class="fa fa-check"></i> ตรวจผ่านที่เลือก
      </button>
    </div>
    <?php endif; ?>
  <table class="table table-hover mb-0 align-middle">
    <thead>
      <tr>
        <th style="width:36px"><?php if ($pendingCount > 0): ?><input type="checkbox" class="form-check-input" id="check-all-subs"><?php endif; ?></th>
        <th>ผู้เรียน</th>
        <th>ภาระงาน</th>
        <th>สถานะ</th>
        <th>คะแนน</th>
        <th>ส่งเมื่อ</th>
        <th>ตรวจ</th>
      </tr>
    </thead>
    <tbody>
    <?php
      $shownEnrollBulk = [];
      foreach (($submissions ?? []) as $s):
      $maxScore = (float) ($s['task_max_score'] ?? 100);
      $scoreVal = $s['score'] !== null && $s['score'] !== '' ? (float) $s['score'] : null;
      $pct = $scoreVal !== null && $maxScore > 0 ? round(($scoreVal / $maxScore) * 100, 1) : null;
      $pending = in_array((string) $s['status'], ['submitted', 'revision'], true);
      $eid = (int) $s['enrollment_id'];
    ?>
      <tr>
        <td>
          <?php if (can_perm('assessments','edit') && $pending): ?>
            <input type="checkbox" class="form-check-input sub-check" name="submission_ids[]" value="<?= (int) $s['id'] ?>" form="bulk-selected-form">
          <?php endif; ?>
        </td>
        <td>
          <?= e(trim(($s['first_name']??'').' '.($s['last_name']??'')) ?: $s['username']) ?>
          <div class="small text-muted"><?= e($s['enrollment_no']) ?></div>
          <?php if (can_perm('assessments','edit') && $pending && empty($shownEnrollBulk[$eid])):
            $shownEnrollBulk[$eid] = true;
          ?>
          <form method="post" action="<?= url('/admin/training/' . $pid . '/submissions/bulk-review') ?>" class="mt-1"
                data-confirm="ตรวจผ่านภาระงานทั้งหมดของผู้เรียนคนนี้ที่รอตรวจ?">
            <?= csrf_field() ?>
            <input type="hidden" name="scope" value="enrollment">
            <input type="hidden" name="enrollment_id" value="<?= $eid ?>">
            <button class="btn btn-link btn-sm p-0" type="submit">ตรวจผ่านทั้งคนนี้</button>
          </form>
          <?php endif; ?>
        </td>
        <td><?= e($s['task_title']) ?>
          <div class="small text-muted">เต็ม <?= number_format($maxScore, 0) ?> คะแนน</div>
          <?php if ($s['link_url']): ?><div class="small"><a href="<?= e($s['link_url']) ?>" target="_blank">ลิงก์</a></div><?php endif; ?>
          <?php if ($s['file_path']): ?><div class="small"><a href="<?= e(upload_url($s['file_path'])) ?>" target="_blank">ไฟล์</a></div><?php endif; ?>
          <?php if ($s['answer_text']): ?><div class="small text-muted"><?= e(mb_strimwidth($s['answer_text'],0,100,'…')) ?></div><?php endif; ?>
        </td>
        <td><span class="badge text-bg-<?= $s['status']==='approved'?'success':($s['status']==='rejected'?'danger':'warning') ?>"><?= e($statusTh($s['status'])) ?></span></td>
        <td class="small">
          <?php if ($scoreVal !== null): ?>
            <?= number_format($scoreVal, 1) ?> / <?= number_format($maxScore, 0) ?>
            <div class="text-muted"><?= number_format((float) $pct, 0) ?>%</div>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <td class="small"><?= e($s['submitted_at'] ?? '-') ?></td>
        <td style="min-width:220px">
          <?php if (can_perm('assessments','edit') && $pending): ?>
          <form method="post" action="<?= url('/admin/training/' . $pid . '/submission/' . (int)$s['id'] . '/review') ?>" class="d-flex flex-column gap-1">
            <?= csrf_field() ?>
            <input type="number" name="score" class="form-control form-control-sm" value="<?= e((string) $maxScore) ?>" min="0" max="<?= e((string) $maxScore) ?>" step="0.01" title="ค่าเริ่มต้น = คะแนนเต็ม">
            <input name="feedback" class="form-control form-control-sm" placeholder="ความเห็น">
            <div class="btn-group btn-group-sm">
              <button name="status" value="approved" class="btn btn-success" type="submit">ผ่าน</button>
              <button name="status" value="revision" class="btn btn-warning" type="submit">แก้</button>
              <button name="status" value="rejected" class="btn btn-outline-danger" type="submit">ไม่ผ่าน</button>
            </div>
          </form>
          <?php else: ?>
            <span class="small text-muted"><?= e(mb_strimwidth((string)($s['feedback']??''),0,40,'…')) ?></span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($submissions)): ?><tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีการส่งงาน</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></div>
<script>
(function () {
  var all = document.getElementById('check-all-subs');
  if (!all) return;
  all.addEventListener('change', function () {
    document.querySelectorAll('.sub-check').forEach(function (el) { el.checked = all.checked; });
  });
})();
</script>

<?php else: /* settings */ ?>
<div class="card-soft lms-panel" style="max-width:720px"><div class="card-body">
  <h2>น้ำหนักความก้าวหน้า (รวมควรได้ 100)</h2>
  <form method="post" action="<?= url('/admin/training/' . $pid . '/settings') ?>">
    <?= csrf_field() ?>
    <div class="row g-2 mb-3">
      <div class="col-6"><label class="form-label">ทดสอบก่อน %</label><input type="number" step="0.01" name="weight_pretest" class="form-control" value="<?= e((string)($settings['weight_pretest'] ?? 10)) ?>"></div>
      <div class="col-6"><label class="form-label">เนื้อหา %</label><input type="number" step="0.01" name="weight_content" class="form-control" value="<?= e((string)($settings['weight_content'] ?? 35)) ?>"></div>
      <div class="col-6"><label class="form-label">ภาระงาน %</label><input type="number" step="0.01" name="weight_task" class="form-control" value="<?= e((string)($settings['weight_task'] ?? 15)) ?>"></div>
      <div class="col-6"><label class="form-label">สอบหลัง %</label><input type="number" step="0.01" name="weight_posttest" class="form-control" value="<?= e((string)($settings['weight_posttest'] ?? 25)) ?>"></div>
      <div class="col-6"><label class="form-label">ความพึงพอใจ %</label><input type="number" step="0.01" name="weight_satisfaction" class="form-control" value="<?= e((string)($settings['weight_satisfaction'] ?? 15)) ?>"></div>
      <div class="col-6"><label class="form-label">เกณฑ์ผ่านความก้าวหน้า %</label><input type="number" step="0.01" min="1" max="100" name="pass_percent" class="form-control" value="<?= e((string)($settings['pass_percent'] ?? 80)) ?>"></div>
      <div class="col-6"><label class="form-label">จำนวนข้อที่ดึงจากคลัง</label><input type="number" min="1" max="100" name="quiz_draw_count" class="form-control" value="<?= e((string)($settings['quiz_draw_count'] ?? 10)) ?>"></div>
    </div>
    <p class="small text-muted">สอบก่อน สอบหลัง และแบบประเมินใช้คะแนนคงที่ ข้อละ 1 คะแนน เนื้อหาและภาระงานคิดคะแนนจากจำนวนรายการที่บังคับ แล้วแปลงคะแนนนั้นเป็นร้อยละตามน้ำหนักของแผนนี้</p>
    <table class="table lms-table mb-3">
      <thead><tr><th>ส่วน</th><th>แบบ</th><th>จำนวน</th><th>คะแนน</th><th>น้ำหนักแผน</th><th>1 คะแนน</th></tr></thead>
      <tbody>
      <?php
        $shareRows = [
          'pretest' => 'สอบก่อน',
          'posttest' => 'สอบหลัง',
          'satisfaction' => 'แบบประเมินความพึงพอใจ',
          'content' => 'เนื้อหาที่บังคับ',
          'task' => 'ภาระงานที่บังคับ',
        ];
        foreach ($shareRows as $key => $label):
          $part = $shares[$key] ?? ['weight' => 0, 'count' => 0, 'each' => 0, 'unit' => '', 'points_total' => 0, 'fixed' => false];
      ?>
        <tr>
          <td><?= e($label) ?></td>
          <td><?= !empty($part['fixed']) ? 'คงที่' : 'ตามจำนวนรายการ' ?></td>
          <td><?= (int) $part['count'] ?> <?= e((string) $part['unit']) ?></td>
          <td><?= (int) $part['count'] > 0 ? e(number_format((float) ($part['points_total'] ?? 0), 0)) . ' คะแนน' : '—' ?></td>
          <td><?= e(number_format((float) $part['weight'], 2)) ?>%</td>
          <td><?= (int) $part['count'] > 0 ? e(number_format((float) $part['each'], 2)) . '%' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
        <tr>
          <td><strong>รวม</strong></td>
          <td colspan="5"><strong><?= e(number_format((float) ($shares['total'] ?? 0), 2)) ?>%</strong><?= abs((float) ($shares['total'] ?? 0) - 100) > 0.05 ? ' ควรปรับให้รวมได้ 100' : '' ?></td>
        </tr>
      </tbody>
    </table>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="quiz_shuffle" value="1" id="qs" <?= (int)($settings['quiz_shuffle'] ?? 1)?'checked':'' ?>><label class="form-check-label" for="qs">สุ่มข้อสอบจากคลัง (ชุดก่อนเรียนกับหลังเรียนเป็นข้อเดียวกัน แต่สลับลำดับข้อและตัวเลือก)</label></div>
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
