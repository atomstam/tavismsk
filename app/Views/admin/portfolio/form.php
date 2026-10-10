<?php $it = $item ?? []; $fields = $mod['fields'] ?? []; ?>
<div class="page-head">
  <div><p class="page-kicker">PORTFOLIO MODULE</p><h1><?= e($title) ?></h1></div>
  <a class="btn btn-light" href="<?= url('/admin/portfolio/'.$type) ?>">กลับหน้ารายการ</a>
</div>
<div class="card-soft"><div class="card-body">
<form method="post" enctype="multipart/form-data" action="<?= $item ? url('/admin/portfolio/'.$type.'/'.$item['id']) : url('/admin/portfolio/'.$type) ?>" data-loading>
  <?= csrf_field() ?>
  <div class="row g-3">
    <?php if (!empty($people)): ?>
    <div class="col-md-6">
      <label class="form-label">บุคลากร</label>
      <select name="user_id" class="form-select" required>
        <?php foreach ($people as $p): $id=(int)$p['id']; ?>
          <option value="<?= $id ?>" <?= ((int)($it['user_id'] ?? auth()->id())===$id)?'selected':'' ?>><?= e(trim(($p['title']??'').$p['first_name'].' '.$p['last_name'])) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-md-12">
      <label class="form-label">ชื่อรายการ / หัวข้อ *</label>
      <input class="form-control" name="title" required value="<?= e($it['title'] ?? '') ?>">
    </div>
    <?php if (in_array('organization', $fields, true)): ?>
    <div class="col-md-6"><label class="form-label">หน่วยงาน</label><input class="form-control" name="organization" value="<?= e($it['organization'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if (in_array('location', $fields, true)): ?>
    <div class="col-md-6"><label class="form-label">สถานที่</label><input class="form-control" name="location" value="<?= e($it['location'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if (in_array('role_name', $fields, true)): ?>
    <div class="col-md-6"><label class="form-label">บทบาทในคณะทำงาน</label><input class="form-control" name="role_name" value="<?= e($it['role_name'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if (in_array('category', $fields, true)): ?>
    <div class="col-md-6">
      <label class="form-label">ประเภท</label>
      <?php if (!empty($mod['categories'])): ?>
      <select name="category" class="form-select">
        <option value="">- เลือก -</option>
        <?php foreach ($mod['categories'] as $c): ?>
          <option value="<?= e($c) ?>" <?= (($it['category']??'')===$c)?'selected':'' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
      <?php else: ?><input class="form-control" name="category" value="<?= e($it['category'] ?? '') ?>"><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (in_array('status_name', $fields, true)): ?>
    <div class="col-md-6">
      <label class="form-label">สถานะ</label>
      <select name="status_name" class="form-select">
        <option value="">- เลือก -</option>
        <?php foreach (($mod['statuses'] ?? []) as $c): ?>
          <option value="<?= e($c) ?>" <?= (($it['status_name']??'')===$c)?'selected':'' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <?php if (in_array('level_name', $fields, true)): ?>
    <div class="col-md-4"><label class="form-label">ระดับรางวัล</label><input class="form-control" name="level_name" value="<?= e($it['level_name'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if (in_array('group_name', $fields, true)): ?>
    <div class="col-md-4"><label class="form-label">กลุ่มกิจกรรม</label><input class="form-control" name="group_name" value="<?= e($it['group_name'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if (in_array('instance_no', $fields, true)): ?>
    <div class="col-md-4"><label class="form-label">ครั้งที่</label><input class="form-control" name="instance_no" value="<?= e($it['instance_no'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if (in_array('start_date', $fields, true)): ?>
    <div class="col-md-4"><label class="form-label">วันที่เริ่ม</label><input type="date" class="form-control" name="start_date" value="<?= e($it['start_date'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if (in_array('end_date', $fields, true)): ?>
    <div class="col-md-4"><label class="form-label">วันที่สิ้นสุด</label><input type="date" class="form-control" name="end_date" value="<?= e($it['end_date'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if (in_array('hours', $fields, true)): ?>
    <div class="col-md-4"><label class="form-label">ชั่วโมง</label><input type="number" step="0.5" class="form-control" name="hours" value="<?= e((string)($it['hours'] ?? '0')) ?>"></div>
    <?php endif; ?>
    <div class="col-md-4"><label class="form-label">ปีการศึกษา</label>
      <select name="academic_year" class="form-select">
        <?php foreach (academic_years_list() as $y): ?>
          <option value="<?= $y ?>" <?= ((int)($it['academic_year'] ?? current_academic_year())===$y)?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (in_array('year_be', $fields, true)): ?>
    <div class="col-md-4"><label class="form-label">ปี พ.ศ.</label><input type="number" class="form-control" name="year_be" value="<?= e((string)($it['year_be'] ?? current_academic_year())) ?>"></div>
    <?php endif; ?>
    <div class="col-12"><label class="form-label">รายละเอียด</label><textarea class="form-control" name="description" rows="4"><?= e($it['description'] ?? '') ?></textarea></div>
    <div class="col-md-6">
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" <?= !empty($it['is_featured']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_featured">ผลงานดีเด่น / Featured</label>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" name="is_public" value="1" id="is_public" <?= empty($it) || !isset($it['is_public']) || !empty($it['is_public']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_public">เผยแพร่สาธารณะ</label>
      </div>
    </div>
    <div class="col-md-6"><label class="form-label">แนบไฟล์ (PDF/DOCX/XLSX/ZIP)</label><input type="file" name="files[]" class="form-control" multiple></div>
    <div class="col-md-6"><label class="form-label">รูปภาพประกอบ (สูงสุด 6 ภาพ)</label><input type="file" name="photos[]" class="form-control" accept="image/*" multiple></div>
  </div>
  <?php if (!empty($files)): ?>
    <div class="mt-3"><strong>ไฟล์เดิม:</strong>
      <?php foreach ($files as $f): ?><div><a href="<?= e(upload_url($f['file_path'])) ?>" target="_blank"><?= e($f['original_name']) ?></a></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="mt-4"><button class="btn-add" type="submit"><i class="fa fa-save"></i> บันทึกข้อมูล</button></div>
</form>
</div></div>
