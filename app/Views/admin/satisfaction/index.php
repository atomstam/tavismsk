<?php
$quiz = $quiz ?? [];
$questions = $questions ?? [];
$planCount = (int) ($planCount ?? 0);
?>
<div class="page-head">
  <div>
    <p class="page-kicker">ผู้ดูแลระบบ</p>
    <h1>แบบประเมินความพึงพอใจ</h1>
  </div>
</div>

<div class="alert alert-info py-2 small">
  ชุดนี้เป็นแบบประเมินกลาง ใช้ร่วมกันทุกหลักสูตรที่เปิดเรียนอยู่ <?= $planCount ?> หลักสูตร
  คะแนนคงที่ ข้อละ 1 คะแนน รวม <?= count($questions) ?> คะแนน
  แต่ละหลักสูตรแปลงคะแนนชุดนี้เป็นร้อยละตามน้ำหนักความพึงพอใจของหลักสูตรนั้น ไม่เปลี่ยนตามจำนวนเนื้อหาหรือภาระงาน
  <?php if (count($questions) > 0): ?>
    หลักสูตรที่ตั้งน้ำหนัก 15% จะได้ 1 คะแนน = <?= e(number_format(15 / count($questions), 2)) ?>%
  <?php endif; ?>
</div>

<div class="lms-board">
  <div class="card-soft lms-panel"><div class="card-body">
    <h2>ตั้งค่าแบบประเมิน</h2>
    <form method="post" action="<?= url('/admin/satisfaction/meta') ?>" class="lms-meta">
      <?= csrf_field() ?>
      <div class="lms-field lms-field-wide"><label class="form-label">ชื่อ</label><input name="title" class="form-control" value="<?= e($quiz['title'] ?? 'แบบประเมินความพึงพอใจ') ?>"></div>
      <div class="lms-field lms-field-wide"><label class="form-label">คำอธิบาย</label><input name="description" class="form-control" value="<?= e($quiz['description'] ?? '') ?>"></div>
      <div class="lms-field"><label class="form-label">จำนวนครั้งสูงสุด</label><input type="number" name="max_attempts" class="form-control" value="<?= e((string)($quiz['max_attempts'] ?? 1)) ?>" min="1"></div>
      <div class="lms-field"><label class="form-label">จำกัดเวลา (นาที)</label><input type="number" name="time_limit_minutes" class="form-control" value="<?= e((string)($quiz['time_limit_minutes'] ?? '')) ?>" placeholder="ว่าง = ไม่จำกัด"></div>
      <div class="lms-field lms-field-action"><button class="btn btn-primary" type="submit">บันทึก</button></div>
    </form>
  </div></div>

  <div class="lms-split">
    <div class="card-soft lms-panel" id="quiz-question-panel"><div class="card-body">
      <h2 id="quiz-form-title">เพิ่มข้อ</h2>
      <form method="post" action="<?= url('/admin/satisfaction/question') ?>" enctype="multipart/form-data" id="quiz-question-form">
        <?= csrf_field() ?>
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
        <?php for ($i = 0; $i < 5; $i++): ?>
          <div class="mb-2" data-choice-slot="<?= $i ?>">
            <input type="hidden" name="choice_ids[]" value="">
            <input type="hidden" name="choice_image_paths[]" value="">
            <input name="choices[]" class="form-control" placeholder="ตัวเลือก <?= $i + 1 ?>">
            <input type="file" name="choice_images[]" class="form-control form-control-sm mt-1" accept="image/jpeg,image/png,image/webp,image/gif" aria-label="รูปตัวเลือก <?= $i + 1 ?>">
            <div class="quiz-edit-preview" data-preview="choice" hidden>
              <img class="quiz-figure quiz-figure-sm" alt="">
              <label class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_choice_image[<?= $i ?>]" value="1"> ลบรูปนี้</label>
            </div>
          </div>
        <?php endfor; ?>
        <p class="small text-muted">เว้นช่องที่ไม่ได้ใช้ได้ ทุกตัวเลือกนับเป็นการตอบแบบประเมิน</p>
        <div class="d-flex gap-2">
          <button class="btn btn-success" type="submit" id="quiz-form-submit"><i class="fa fa-plus"></i> เพิ่มข้อ</button>
          <button class="btn btn-outline-secondary" type="button" id="quiz-form-cancel" hidden>ยกเลิกแก้ไข</button>
        </div>
      </form>
    </div></div>
    <div class="card-soft lms-panel"><div class="card-body">
      <h2>ข้อประเมิน (<?= count($questions) ?> ข้อ)</h2>
      <?php foreach ($questions as $qi => $q): ?>
        <div class="lms-q">
          <div class="lms-q-head">
            <div class="lms-q-text">
              <?= ($qi + 1) ?>. <?= e($q['question_text']) ?>
              <?php if (!empty($q['image_path'])): ?><img class="quiz-figure" src="<?= e(upload_url($q['image_path'])) ?>" alt=""><?php endif; ?>
            </div>
            <div class="lms-q-actions">
              <?php
                $editPayload = [
                  'id' => (int) $q['id'],
                  'question_text' => (string) $q['question_text'],
                  'image_url' => !empty($q['image_path']) ? upload_url((string) $q['image_path']) : '',
                  'image_path' => (string) ($q['image_path'] ?? ''),
                  'choices' => array_map(static function (array $ch): array {
                    return [
                      'id' => (int) $ch['id'],
                      'text' => trim((string) ($ch['choice_text'] ?? '')),
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
              <form method="post" action="<?= url('/admin/satisfaction/question/' . (int) $q['id'] . '/delete') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger" type="submit"
                        data-confirm-delete="ลบข้อนี้?"
                        data-confirm-title="ลบคำถาม"
                        data-confirm-ok="ลบ"><i class="fa fa-trash"></i></button>
              </form>
            </div>
          </div>
          <ul class="lms-choices">
            <?php foreach (($q['choices'] ?? []) as $ch): ?>
              <li>
                <?php if (!empty($ch['image_path'])): ?><img class="quiz-figure quiz-figure-sm" src="<?= e(upload_url($ch['image_path'])) ?>" alt=""><?php endif; ?>
                <?= e(trim((string) ($ch['choice_text'] ?? ''))) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
      <?php if (!$questions): ?><p class="text-muted mb-0">ยังไม่มีข้อประเมิน</p><?php endif; ?>
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
    title.textContent = 'เพิ่มข้อ';
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
    slots.forEach((slot, index) => {
      const choice = choices[index] || {};
      slot.querySelector('[name="choices[]"]').value = choice.text || '';
      slot.querySelector('[name="choice_ids[]"]').value = choice.id || '';
      slot.querySelector('[name="choice_image_paths[]"]').value = choice.image_path || '';
      showPreview(slot.querySelector('[data-preview="choice"]'), choice.image_url || '');
    });
    title.textContent = 'แก้ไขข้อ';
    submit.className = 'btn btn-primary';
    submit.innerHTML = '<i class="fa fa-check"></i> บันทึกการแก้ไข';
    cancel.hidden = false;
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.question_text.focus();
  });

  cancel.addEventListener('click', resetForm);
})();
</script>
