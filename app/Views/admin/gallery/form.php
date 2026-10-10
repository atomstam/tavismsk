<?php $isEdit = !empty($album); ?>
<div class="page-head">
  <div><h1><?= $isEdit ? 'แก้ไขอัลบั้ม' : 'เพิ่มอัลบั้ม' ?></h1></div>
  <a class="btn btn-light" href="<?= url('/admin/gallery') ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
</div>
<div class="card-soft"><div class="card-body">
  <form method="post" enctype="multipart/form-data" action="<?= $isEdit ? url('/admin/gallery/' . $album['id']) : url('/admin/gallery') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">ชื่ออัลบั้ม *</label>
        <input class="form-control" name="title" required value="<?= e($album['title'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">หมวดหมู่</label>
        <input class="form-control" name="category" value="<?= e($album['category'] ?? '') ?>" placeholder="เช่น รางวัล, อบรม">
      </div>
      <div class="col-md-4">
        <label class="form-label">วันที่จัดกิจกรรม</label>
        <input type="date" class="form-control" name="event_date" value="<?= e($album['event_date'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">ปีการศึกษา</label>
        <select class="form-select" name="academic_year">
          <option value="">—</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= (int) $y ?>" <?= ((int) ($album['academic_year'] ?? 0) === (int) $y) ? 'selected' : '' ?>><?= (int) $y ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">สถานที่</label>
        <input class="form-control" name="location" value="<?= e($album['location'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">รายละเอียด</label>
        <textarea class="form-control" name="description" rows="3"><?= e($album['description'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">รูปปก</label>
        <input type="file" class="form-control" name="cover" accept="image/*">
        <?php if (!empty($album['cover_image'])): ?>
          <img src="<?= e(upload_url($album['cover_image'])) ?>" class="mt-2 rounded" style="max-height:100px" alt="">
        <?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">ไฟล์สื่อ (หลายไฟล์)</label>
        <input type="file" class="form-control" name="media[]" multiple accept="image/*,video/mp4">
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_public" value="1" id="is_public" <?= empty($album) || !empty($album['is_public']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="is_public">เผยแพร่สาธารณะ</label>
        </div>
      </div>
      <?php if (!empty($media)): ?>
        <div class="col-12">
          <label class="form-label">ไฟล์ที่มีอยู่</label>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($media as $m): ?>
              <a href="<?= e(upload_url($m['file_path'])) ?>" target="_blank" class="badge bg-light text-dark border"><?= e($m['original_name'] ?: 'ไฟล์') ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <div class="col-12">
        <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> บันทึก</button>
      </div>
    </div>
  </form>
</div></div>
