<div class="container py-4">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › ติดต่อ</div>
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
      <div>
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="bg-white bg-opacity-25 rounded-3 p-3"><i class="fa fa-headset fa-lg"></i></div>
          <div>
            <h1>ติดต่อสอบถาม / ประสานงาน</h1>
            <p>ยินดีให้คำแนะนำ ตอบข้อสงสัย และร่วมแลกเปลี่ยนความรู้ทางวิชาการ</p>
          </div>
        </div>
      </div>
      <div class="d-none d-md-flex gap-3 opacity-75 fs-4">
        <i class="fa fa-envelope"></i><i class="fa fa-phone"></i><i class="fa fa-location-dot"></i><i class="fa fa-comments"></i>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card-soft h-100">
        <div class="card-body p-4">
          <h2 class="h5 mb-3"><i class="fa fa-user text-danger me-2"></i>ข้อมูลการติดต่อ</h2>
          <div class="d-flex gap-3 mb-3">
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-grid place-items-center" style="width:42px;height:42px;place-items:center;display:grid"><i class="fa fa-location-dot"></i></div>
            <div>
              <div class="fw-semibold"><?= e(setting('org_name')) ?></div>
              <div class="text-muted small"><?= e(setting('org_affiliation')) ?><br><?= e(setting('org_address')) ?></div>
            </div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <div class="rounded-circle bg-success bg-opacity-10 text-success d-grid" style="width:42px;height:42px;place-items:center;display:grid"><i class="fa fa-phone"></i></div>
            <div><div class="fw-semibold">โทรศัพท์</div><div class="text-muted small"><?= e(setting('org_phone')) ?></div></div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-grid" style="width:42px;height:42px;place-items:center;display:grid"><i class="fa fa-envelope"></i></div>
            <div><div class="fw-semibold">อีเมล</div><div class="text-muted small"><?= e(setting('org_email')) ?></div></div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <div class="rounded-circle bg-info bg-opacity-10 text-info d-grid" style="width:42px;height:42px;place-items:center;display:grid"><i class="fa fa-globe"></i></div>
            <div><div class="fw-semibold">เว็บไซต์</div><div class="text-muted small"><?= e(setting('org_website') ?: 'https://tavis.thaiedu.in.th') ?></div></div>
          </div>
          <?php $map = setting('maps_embed'); ?>
          <div class="ratio ratio-16x9 rounded-3 overflow-hidden mt-3">
            <?php if ($map): ?>
              <?= $map ?>
            <?php else: ?>
              <iframe title="แผนที่" src="https://www.google.com/maps?q=<?= rawurlencode('สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม 134 ถนนเลี่ยงเมืองมหาสารคาม-ร้อยเอ็ด') ?>&output=embed" loading="lazy"></iframe>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card-soft h-100">
        <div class="card-body p-4">
          <h2 class="h5 mb-3"><i class="fa fa-paper-plane text-danger me-2"></i>ส่งข้อความถึงสำนักงานเขตฯ</h2>
          <form method="post" action="<?= url('/contact') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input name="name" class="form-control" placeholder="กรุณากรอกชื่อ-นามสกุล" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                <input name="email" type="email" class="form-control" placeholder="กรุณากรอกอีเมล" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">เบอร์โทร</label>
                <input name="phone" class="form-control" placeholder="กรุณากรอกเบอร์โทร">
              </div>
              <div class="col-md-6">
                <label class="form-label">หัวข้อ <span class="text-danger">*</span></label>
                <input name="subject" class="form-control" placeholder="หัวข้อเรื่อง" required>
              </div>
              <div class="col-12">
                <label class="form-label">ข้อความ <span class="text-danger">*</span></label>
                <textarea name="message" class="form-control" rows="5" placeholder="รายละเอียดข้อความ" required></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">รหัส Captcha <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 align-items-center">
                  <img src="<?= url('/captcha') ?>?t=<?= time() ?>" alt="captcha" height="44" class="rounded border">
                  <input name="captcha" class="form-control" placeholder="รหัสในภาพ" required>
                </div>
              </div>
              <div class="col-12">
                <button class="ws-btn-primary border-0" type="submit"><i class="fa fa-paper-plane"></i> ส่งข้อความ</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
