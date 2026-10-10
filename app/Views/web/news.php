<div class="container py-4">
  <div class="ws-page-hero">
    <div class="crumb"><a href="<?= url('/') ?>">หน้าแรก</a> › ข่าวสาร</div>
    <h1>ข่าวสารและประกาศ</h1>
    <p>อัปเดตกิจกรรมและข่าวประชาสัมพันธ์ของสำนักงานเขตฯ</p>
  </div>

  <div class="ws-news-list">
    <?php if ($rows): foreach ($rows as $n):
      $ts = strtotime((string)($n['published_at'] ?? $n['created_at'] ?? 'now')) ?: time();
      $day = date('j', $ts);
      $months = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
      $mon = $months[(int)date('n', $ts)] ?? '';
    ?>
      <a class="ws-news-item" href="<?= url('/news/' . $n['slug']) ?>">
        <div class="ws-news-date"><strong><?= e($day) ?></strong><span><?= e($mon) ?></span></div>
        <div class="flex-grow-1">
          <h3><?= e($n['title']) ?></h3>
          <p><?= e($n['excerpt'] ?: thai_date($n['published_at'] ?? null)) ?></p>
        </div>
      </a>
    <?php endforeach; else: ?>
      <div class="ws-empty">ยังไม่มีข่าวสาร</div>
    <?php endif; ?>
  </div>
</div>
