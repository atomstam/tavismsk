<?php
declare(strict_types=1);
?>
<div id="abImageCropModal" class="ab-cover-crop-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="abImageCropTitle">
  <div class="ab-cover-crop-modal__backdrop" data-image-crop-close></div>
  <div class="ab-cover-crop-modal__panel">
    <div class="ab-cover-crop-modal__head">
      <h3 id="abImageCropTitle" class="ab-cover-crop-modal__title">ตัดรูปภาพ</h3>
      <button type="button" class="ab-cover-crop-modal__close" data-image-crop-close aria-label="ปิด">&times;</button>
    </div>
    <div class="ab-cover-crop-modal__body">
      <div class="ab-cover-crop-modal__grid">
        <div class="ab-cover-crop-modal__stage">
          <div id="abImageCropPreviewWrap" class="ab-cover-crop-modal__preview-wrap">
            <p class="ab-cover-crop-modal__placeholder">เลือกรูปภาพเพื่อเริ่มตัด</p>
          </div>
          <div id="abImageCropToolbar" class="ab-cover-crop-modal__toolbar" hidden>
            <button type="button" class="ab-cover-crop-modal__tool-btn" data-image-crop-transform="rotate_left" title="หมุนซ้าย">
              <span aria-hidden="true">↺</span><span>หมุนซ้าย</span>
            </button>
            <button type="button" class="ab-cover-crop-modal__tool-btn" data-image-crop-transform="rotate_right" title="หมุนขวา">
              <span aria-hidden="true">↻</span><span>หมุนขวา</span>
            </button>
            <button type="button" class="ab-cover-crop-modal__tool-btn" data-image-crop-transform="flip_h" title="พลิกแนวนอน">
              <span aria-hidden="true">↔</span><span>พลิกแนวนอน</span>
            </button>
            <button type="button" class="ab-cover-crop-modal__tool-btn" data-image-crop-transform="flip_v" title="พลิกแนวตั้ง">
              <span aria-hidden="true">↕</span><span>พลิกแนวตั้ง</span>
            </button>
            <button type="button" class="ab-cover-crop-modal__tool-btn ab-cover-crop-modal__tool-btn--reset" data-image-crop-transform="reset" title="รีเซ็ต">
              <span aria-hidden="true">⟲</span><span>รีเซ็ต</span>
            </button>
          </div>
          <p id="abImageCropHint" class="ab-cover-crop-modal__hint">ลากกรอบเพื่อเลือกพื้นที่</p>
        </div>
        <div class="ab-cover-crop-modal__aside">
          <p class="ab-cover-crop-modal__aside-label">ตัวอย่าง:</p>
          <div id="abImageCropAsidePreview" class="ab-cover-crop-modal__rect" aria-hidden="true">
            <span class="ab-cover-crop-modal__rect-placeholder">?</span>
          </div>
          <p id="abImageCropAsideNote" class="ab-cover-crop-modal__aside-note"></p>
        </div>
      </div>
    </div>
    <div class="ab-cover-crop-modal__foot">
      <button type="button" class="btn btn-light" data-image-crop-close>ยกเลิก</button>
      <button type="button" id="abImageCropSaveBtn" class="btn btn-primary" disabled>บันทึกรูป</button>
    </div>
  </div>
</div>
