<?php
$preview = $preview ?? ['kind' => 'text', 'frame_src' => '', 'html' => '', 'note' => ''];
$kind = (string) ($preview['kind'] ?? 'text');
$frame = (string) ($preview['frame_src'] ?? '');
$html = (string) ($preview['html'] ?? '');
$note = (string) ($preview['note'] ?? '');
$fileName = (string) ($preview['file_name'] ?? '');
$infoText = (string) ($preview['info_text'] ?? '');
$backUrl = (string) ($preview['back_url'] ?? ($backUrl ?? ''));
$contentTitle = (string) ($preview['title'] ?? ($contentTitle ?? ''));
if ($fileName === '' && $frame !== '') {
    $fileName = basename(parse_url($frame, PHP_URL_PATH) ?: 'document.pdf') ?: 'document.pdf';
}
if ($infoText === '') {
    $infoText = $contentTitle !== ''
        ? 'ไฟล์เนื้อหา: ' . $contentTitle . ' — ' . $fileName
        : 'ไฟล์เนื้อหา: ' . $fileName;
}
?>
<div class="content-stage">
  <?php if ($html !== ''): ?>
    <div class="content-viewer-body"><?= $html ?></div>
  <?php endif; ?>

  <?php if ($kind === 'pdf' && $frame !== ''): ?>
    <?php partial('partials/pdf_viewer', [
      'fileUrl' => $frame,
      'fileName' => $fileName,
      'infoText' => $infoText,
      'downloadUrl' => $frame,
      'backUrl' => $backUrl,
      'title' => '',
    ]); ?>
  <?php elseif ($kind === 'video' && $frame !== ''): ?>
    <div class="content-video">
      <iframe src="<?= e($frame) ?>" title="วิดีโอ" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
    </div>
  <?php elseif ($frame !== ''): ?>
    <iframe class="content-viewer-frame" src="<?= e($frame) ?>" title="เนื้อหา" loading="lazy" allowfullscreen></iframe>
  <?php endif; ?>

  <?php if ($note !== ''): ?>
    <p class="text-muted mb-0 mt-2"><?= e($note) ?></p>
  <?php endif; ?>
</div>
