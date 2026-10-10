<?php
$sys = (string) setting('site_short_name');
$org = (string) setting('org_name');
$site = (string) setting('site_name');
$fav = setting('favicon');
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $site) ?> | <?= e($org) ?></title>
  <meta name="description" content="<?= e($org) ?> — TAVIS ชมรมวัคซีนจราจร">
  <?php if ($fav): ?><link rel="icon" href="<?= e(upload_url((string) $fav)) ?>"><?php endif; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>?v=6.5.2">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=20">
  <link rel="stylesheet" href="<?= asset('css/portfolio-clean.css') ?>?v=3">
</head>
<body class="pf-clean-body">
  <div class="pf-clean-actions no-print">
    <button type="button" class="btn btn-pf-print" onclick="window.print()">
      <i class="fa fa-print"></i> พิมพ์ / บันทึก PDF
    </button>
    <a class="btn btn-pf-back" href="<?= e($back_url ?? url('/portfolios')) ?>">
      <i class="fa fa-arrow-left"></i> กลับ
    </a>
  </div>
  <main class="pf-clean-sheet">
    <?= $content ?>
  </main>
  <?php if (!empty($scripts)) echo $scripts; ?>
</body>
</html>
