<?php
$isAdmin = is_admin_like();
$isTrainer = is_trainer_like();
$isLearner = auth()->hasRole(['user']) && !is_trainer_like();

$card = static function (
    string $key,
    string $label,
    string $hint,
    string $icon,
    string $tone,
    ?string $href = null
): void {
    $tag = $href ? 'a' : 'div';
    $hrefAttr = $href ? ' href="' . e($href) . '"' : '';
    ?>
  <<?= $tag ?> class="ov-card ov-card--<?= e($tone) ?><?= $href ? ' is-link' : '' ?>" data-stat-card="<?= e($key) ?>"<?= $hrefAttr ?>>
    <div class="ov-card-top">
      <span class="ov-card-ico"><i class="fa <?= e($icon) ?>"></i></span>
      <?php if ($href): ?><span class="ov-card-go" aria-hidden="true"><i class="fa fa-arrow-right"></i></span><?php endif; ?>
    </div>
    <div class="ov-card-label"><?= e($label) ?></div>
    <div class="ov-card-value n is-loading">&nbsp;</div>
    <div class="ov-card-hint"><?= e($hint) ?></div>
  </<?= $tag ?>>
    <?php
};
?>
<?php if ($isLearner): ?>

<div class="ov-section mb-2">
  <h2 class="ov-section-title">ความก้าวหน้าของฉัน</h2>
</div>
<div class="ov-grid ov-grid--3 mb-3">
  <?php
  $card('my_progress', 'ความก้าวหน้าเฉลี่ย', 'ร้อยละเฉลี่ยทุกแผน', 'fa-chart-line', 'violet', url('/admin/learn'));
  $card('my_modules_done', 'โมดูลที่เรียนจบ', 'แผนที่ครบขั้นตอนแล้ว', 'fa-book-open-reader', 'blue', url('/admin/learn'));
  $card('enrollments', 'การลงทะเบียน', 'สถานะหลักสูตรของฉัน', 'fa-clipboard-list', 'indigo', url('/admin/enrollments'));
  ?>
</div>
<div class="ov-section mb-2">
  <h2 class="ov-section-title">เอกสารรับรอง</h2>
</div>
<div class="ov-grid ov-grid--2 mb-4">
  <?php
  $card('my_certificates', 'เกียรติบัตร', 'เกียรติบัตรรายแผนที่ออกแล้ว', 'fa-certificate', 'rose', url('/admin/certificates'));
  $card('my_diplomas', 'วุฒิบัตร', 'เมื่อผ่านแผนบังคับครบ', 'fa-award', 'amber', url('/admin/certificates'));
  ?>
</div>

<?php else: ?>

<div class="ov-section mb-2">
  <h2 class="ov-section-title">ภาพรวมการอบรม</h2>
</div>
<div class="ov-grid ov-grid--4 mb-3">
  <?php
  $card('learners', 'ผู้เข้ารับการพัฒนา', 'บัญชีผู้เรียนในระบบ', 'fa-user-graduate', 'blue', can_perm('members', 'view') ? url('/admin/members') : null);
  $card('enrollments', 'ลงทะเบียนอบรม', 'จำนวนการลงทะเบียนทั้งหมด', 'fa-clipboard-list', 'indigo', can_perm('enrollments', 'view') ? url('/admin/plan-enrollments') : null);
  $card('pending', 'รออนุมัติ', 'คำขอลงทะเบียนที่รอตรวจ', 'fa-hourglass-half', 'amber', can_perm('enrollments', 'view') ? url('/admin/plan-enrollments') : null);
  if ($isAdmin) {
      $card('org_units', 'หน่วยงาน', 'หน่วยงานในโครงสร้าง', 'fa-sitemap', 'teal', can_perm('org_units', 'view') ? url('/admin/org-units') : null);
  } else {
      $card('plans_owned', 'แผนที่ดูแล', 'หลักสูตรที่ได้รับมอบหมาย', 'fa-chalkboard-user', 'teal', url('/admin/curriculum'));
  }
  ?>
</div>

<div class="ov-section mb-2">
  <h2 class="ov-section-title">เอกสารรับรองและภาระงาน</h2>
</div>
<div class="ov-grid ov-grid--4 mb-4">
  <?php
  $card('plan_certificates', 'เกียรติบัตร', 'ออกแล้ว · รายแผน', 'fa-certificate', 'rose', can_perm('certificates', 'view') ? url('/admin/certificates') : null);
  $card('diplomas', 'วุฒิบัตร', 'ออกแล้ว · ผ่านบังคับครบ', 'fa-award', 'emerald', can_perm('certificates', 'view') ? url('/admin/certificates') : null);
  $card('cert_ready', 'พร้อมออกเกียรติบัตร', 'ผ่านแผนแล้ว ยังไม่ออกเอกสาร', 'fa-circle-check', 'orange', can_perm('certificates', 'view') ? url('/admin/certificates') : null);
  $card('task_pending', 'งานรอตรวจ', 'ภาระงานที่รอให้คะแนน', 'fa-list-check', 'pink', can_perm('assessments', 'edit') ? url('/admin/task-reviews') : null);
  ?>
</div>

<?php endif; ?>
