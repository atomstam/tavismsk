<div class="page-head"><h1>Log การเข้าสู่ระบบ</h1>
  <a class="btn btn-outline-primary" href="<?= url('/admin/logs/activity') ?>">Activity</a>
</div>
<div class="card-soft"><div class="card-body">
  <table id="login-logs-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/logs/login/datatable')) ?>">
    <thead><tr>
      <th>เวลา</th><th>ผู้ใช้</th><th>สถานะ</th><th>ข้อความ</th><th>IP</th>
    </tr></thead>
  </table>
</div></div>
