<?php
$isAdmin = is_admin_like();
$isTrainer = is_trainer_like();
$isLearner = auth()->hasRole(['user']) && !is_trainer_like();
?>
<div class="ov-grid mb-4">
<?php if ($isLearner): ?>
  <div class="ov-card" data-stat-card="my_progress" style="background:linear-gradient(135deg,#7c3aed,#6b21a8)">
    <div class="l">ความก้าวหน้าเฉลี่ย (%)</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-chart-line bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="my_modules_done" style="background:linear-gradient(135deg,#2563eb,#38bdf8)">
    <div class="l">โมดูลที่เรียนจบ</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-book-open-reader bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="my_hours" style="background:linear-gradient(135deg,#0d9488,#14b8a6)">
    <div class="l">ชั่วโมงสะสม</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-clock bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="my_certificates" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
    <div class="l">เกียรติบัตรของฉัน</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-certificate bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="enrollments" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
    <div class="l">การลงทะเบียน</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-clipboard-list bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="modules" style="background:linear-gradient(135deg,#ec4899,#f472b6)">
    <div class="l">โมดูลทั้งหมด</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-layer-group bg-ico"></i>
  </div>
<?php else: ?>
  <div class="ov-card" data-stat-card="learners" style="background:linear-gradient(135deg,#2563eb,#38bdf8)">
    <div class="l">ผู้เข้ารับการพัฒนา</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-user-graduate bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="enrollments" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6)">
    <div class="l">ลงทะเบียนอบรม</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-clipboard-list bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="pending" style="background:linear-gradient(135deg,#f59e0b,#f97316)">
    <div class="l">รออนุมัติ</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-hourglass-half bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="modules" style="background:linear-gradient(135deg,#0d9488,#14b8a6)">
    <div class="l">โมดูลอบรม</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-book-open bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="certificates" style="background:linear-gradient(135deg,#e11d48,#fb7185)">
    <div class="l">เกียรติบัตร</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-certificate bg-ico"></i>
  </div>
  <div class="ov-card" data-stat-card="hours_total" style="background:linear-gradient(135deg,#6366f1,#818cf8)">
    <div class="l">ชั่วโมงสะสม</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-clock bg-ico"></i>
  </div>
  <?php if ($isTrainer): ?>
  <div class="ov-card" data-stat-card="task_pending" style="background:linear-gradient(135deg,#ec4899,#f472b6)">
    <div class="l">งานรอตรวจ</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-list-check bg-ico"></i>
  </div>
  <?php endif; ?>
  <?php if ($isAdmin): ?>
  <div class="ov-card" data-stat-card="org_units" style="background:linear-gradient(135deg,#64748b,#94a3b8)">
    <div class="l">หน่วยงาน</div>
    <div class="n is-loading">&nbsp;</div>
    <i class="fa fa-sitemap bg-ico"></i>
  </div>
  <?php endif; ?>
<?php endif; ?>
</div>
