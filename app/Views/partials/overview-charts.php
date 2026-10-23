<?php
$isLearner = $isLearner ?? false;
$isAdmin = $isAdmin ?? is_admin_like();
?>
<?php /* แถว 1: สถานะ + บทบาท/สัดส่วน — เต็มแถว 7/5 */ ?>
<div class="col-12 col-lg-7">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">สถานะการลงทะเบียนอบรม</h5>
    <p class="text-muted small mb-3">จำนวนรายการตามสถานะในระบบ</p>
    <div class="chart-box chart-box-lg" data-chart="countBar">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="countBarChart"></canvas>
    </div>
  </div></div>
</div>
<?php if (!$isLearner): ?>
<div class="col-12 col-lg-5">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">ผู้ใช้ตามบทบาท</h5>
    <p class="text-muted small mb-3">Admin · Trainer · User</p>
    <div class="chart-box chart-box-lg" data-chart="role">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="roleChart"></canvas>
    </div>
  </div></div>
</div>
<?php else: ?>
<div class="col-12 col-lg-5">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">สัดส่วนสถานะของฉัน</h5>
    <p class="text-muted small mb-3">สถานะการลงทะเบียนอบรม</p>
    <div class="chart-box chart-box-lg" data-chart="share">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="sharePieChart"></canvas>
    </div>
  </div></div>
</div>
<?php endif; ?>

<?php if (!$isLearner): ?>
<?php /* แถว 2: สัดส่วน + ความครอบคลุม — เต็มแถว 6/6 */ ?>
<div class="col-12 col-lg-6">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">สัดส่วนสถานะลงทะเบียน</h5>
    <p class="text-muted small mb-3">เฉพาะสถานะที่มีข้อมูล</p>
    <div class="chart-box chart-box-lg" data-chart="share">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="sharePieChart"></canvas>
    </div>
  </div></div>
</div>
<div class="col-12 col-lg-6" data-chart-wrap="coverage">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">ความครอบคลุมผู้เรียน</h5>
    <p class="text-muted small mb-3">ลงทะเบียนแล้ว vs ยังไม่ลงทะเบียน</p>
    <div class="chart-box chart-box-lg" data-chart="coverage">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="coverageChart"></canvas>
    </div>
  </div></div>
</div>

<?php /* แถว 3: เรดาร์ + โพลาร์ — เต็มแถว 6/6 */ ?>
<div class="col-12 col-lg-6">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">ภาพรวมสถานะ (เรดาร์)</h5>
    <p class="text-muted small mb-3">เปรียบเทียบจำนวนตามสถานะลงทะเบียน</p>
    <div class="chart-box chart-box-lg" data-chart="radar">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="radarChart"></canvas>
    </div>
  </div></div>
</div>
<div class="col-12 col-lg-6">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">สัดส่วนแบบโพลาร์</h5>
    <p class="text-muted small mb-3">สถานะการลงทะเบียนอบรม</p>
    <div class="chart-box chart-box-lg" data-chart="polar">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="polarChart"></canvas>
    </div>
  </div></div>
</div>

<?php if ($isAdmin && can_perm('personnel', 'view')): ?>
<?php /* แถวบุคลากร — เต็มแถว 6/6 */ ?>
<div class="col-12 col-lg-6" data-chart-wrap="position">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">บุคลากรตามตำแหน่ง</h5>
    <p class="text-muted small mb-3">จากทะเบียนบุคลากร</p>
    <div class="chart-box chart-box-lg" data-chart="position">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="positionChart"></canvas>
    </div>
  </div></div>
</div>
<div class="col-12 col-lg-6" data-chart-wrap="dept">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">บุคลากรตามกลุ่มงาน</h5>
    <p class="text-muted small mb-3">โดนัทหน่วยงาน</p>
    <div class="chart-box chart-box-lg" data-chart="dept">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="deptChart"></canvas>
    </div>
  </div></div>
</div>
<?php endif; ?>

<?php /* แถวท้าย: ปีการศึกษา + แนวโน้ม 6 เดือน — เต็มแถว 6/6 */ ?>
<div class="col-12 col-lg-6">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">ลงทะเบียนตามปีการศึกษา</h5>
    <p class="text-muted small mb-3">แยกตามสถานะ</p>
    <div class="chart-box chart-box-lg" data-chart="year">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="yearBarChart"></canvas>
    </div>
  </div></div>
</div>
<div class="col-12 col-lg-6">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">แนวโน้มการลงทะเบียน 6 เดือน</h5>
    <p class="text-muted small mb-3">จำนวนครั้งที่ลงทะเบียนใหม่</p>
    <div class="chart-box chart-box-lg" data-chart="month">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="monthLineChart"></canvas>
    </div>
  </div></div>
</div>

<?php else: ?>
<?php /* ผู้เรียน: แนวโน้มเต็มแถว */ ?>
<div class="col-12">
  <div class="card-soft h-100"><div class="card-body">
    <h5 class="mb-1">แนวโน้มการลงทะเบียน 6 เดือน</h5>
    <p class="text-muted small mb-3">จำนวนครั้งที่ลงทะเบียน</p>
    <div class="chart-box chart-box-lg" data-chart="month">
      <div class="chart-loading"><span class="spinner"></span><span>กำลังโหลด...</span></div>
      <canvas id="monthLineChart"></canvas>
    </div>
  </div></div>
</div>
<?php endif; ?>
