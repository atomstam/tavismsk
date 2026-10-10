<?php $recent = $recent ?? []; ?>
<div class="page-head">
  <div>
    <p class="page-kicker">LOGS</p>
    <h1>Activity / Audit Log</h1>
  </div>
  <a class="btn btn-outline-primary" href="<?= url('/admin/logs/login') ?>">Log Login</a>
</div>

<div data-stats-root
     data-card-url=""
     data-chart-url="<?= e(url('/admin/logs/chart')) ?>">
  <div class="row g-3 mb-3">
    <div class="col-12">
      <div class="card-soft"><div class="card-body">
        <h5 class="mb-1">สถิติการเข้าสู่ระบบ 14 วัน</h5>
        <p class="text-muted small mb-3">กราฟเส้นจำนวนครั้งสำเร็จและไม่สำเร็จต่อวัน</p>
        <div class="chart-box chart-box-lg" data-chart="login">
          <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
          <canvas id="loginChart"></canvas>
        </div>
      </div></div>
    </div>

    <div class="col-lg-4">
      <div class="card-soft h-100"><div class="card-body">
        <h5 class="mb-1">สถานะเข้าสู่ระบบ 14 วัน</h5>
        <p class="text-muted small mb-3">โดนัทผลลัพธ์ Login</p>
        <div class="chart-box" data-chart="loginStatus">
          <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
          <canvas id="loginStatusChart"></canvas>
        </div>
      </div></div>
    </div>

    <div class="col-lg-8">
      <div class="card-soft h-100"><div class="card-body">
        <h5 class="mb-3">กิจกรรมล่าสุด</h5>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr><th>เวลา</th><th>ผู้ใช้</th><th>การกระทำ</th><th>รายละเอียด</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
              <tr>
                <td><?= e(time_ago($r['created_at'])) ?></td>
                <td><?= e($r['user_name'] ?? '-') ?></td>
                <td><?= e($r['action']) ?></td>
                <td><?= e($r['description']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?>
              <tr><td colspan="4" class="text-muted">ยังไม่มีกิจกรรม</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div></div>
    </div>
  </div>
</div>

<div class="card-soft"><div class="card-body">
  <h5 class="mb-3">รายการ Activity Log ทั้งหมด</h5>
  <table id="activity-logs-datatable" class="table ab-datatable w-100" data-ajax-url="<?= e(url('/admin/logs/activity/datatable')) ?>">
    <thead><tr>
      <th>เวลา</th><th>ผู้ใช้</th><th>การกระทำ</th><th>โมดูล</th><th>รายละเอียด</th><th>IP</th>
    </tr></thead>
  </table>
</div></div>
<?php
$scripts = '<script src="'.e(asset('js/dashboard-charts.js')).'?v=3"></script>
<script src="'.e(asset('js/dashboard-async.js')).'?v=2"></script>';
?>
