<?php
/** @var list<array<string,mixed>> $forms */
$forms = $forms ?? [];
?>
<div class="container py-4">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › แบบฟอร์ม</div>
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
      <div>
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="bg-white bg-opacity-25 rounded-3 p-3"><i class="fa fa-file-lines fa-lg"></i></div>
          <div>
            <h1>แบบฟอร์มออนไลน์</h1>
            <p>กรอกแบบฟอร์มที่หน่วยงานเปิดรับ เพื่อส่งข้อมูลผ่านเว็บไซต์ได้โดยตรง</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!$forms): ?>
    <div class="card-soft">
      <div class="card-body p-4 text-center text-muted">
        <i class="fa fa-inbox fa-2x mb-3 d-block opacity-50"></i>
        ยังไม่มีแบบฟอร์มที่เปิดรับในขณะนี้
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($forms as $f): ?>
        <div class="col-md-6 col-lg-4">
          <a href="<?= url('/forms/' . rawurlencode((string) $f['slug'])) ?>" class="card-soft h-100 text-decoration-none d-block">
            <div class="card-body p-4">
              <div class="d-flex align-items-start gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3"><i class="fa fa-file-pen"></i></div>
                <div>
                  <h2 class="h5 mb-1 text-body"><?= e((string) $f['title']) ?></h2>
                  <?php if (!empty($f['description'])): ?>
                    <p class="text-muted small mb-2"><?= e(mb_strimwidth((string) $f['description'], 0, 120, '…')) ?></p>
                  <?php endif; ?>
                  <span class="small text-primary">กรอกแบบฟอร์ม <i class="fa fa-arrow-right"></i></span>
                </div>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
