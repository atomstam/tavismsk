<div class="page-head">
  <div>
    <p class="page-kicker">CERTIFICATES</p>
    <h1>เกียรติบัตรออนไลน์</h1>
  </div>
</div>

<?php if (!empty($canIssue)): ?>
<div class="card-soft mb-3"><div class="card-body">
  <h2 class="h6 mb-3">พร้อมออกเกียรติบัตร (ผ่านเกณฑ์แล้ว)</h2>
  <table id="certificates-ready-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/certificates/ready-datatable')) ?>">
    <thead>
      <tr>
        <th>เลขลงทะเบียน</th>
        <th>ชื่อ</th>
        <th>หน่วยงาน</th>
        <th>ชม.</th>
        <th></th>
      </tr>
    </thead>
  </table>
</div></div>
<?php endif; ?>

<div class="card-soft"><div class="card-body">
  <h2 class="h6 mb-3">เกียรติบัตรที่ออกแล้ว</h2>
  <table id="certificates-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/certificates/datatable')) ?>">
    <thead>
      <tr>
        <th>เลขที่เกียรติบัตร</th>
        <th>ผู้รับ</th>
        <th>หน่วยงาน</th>
        <th>ชั่วโมง</th>
        <th>วันที่ออก</th>
        <th></th>
      </tr>
    </thead>
  </table>
</div></div>
