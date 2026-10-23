<?php
$fileUrl = (string) ($fileUrl ?? '');
$downloadUrl = (string) ($downloadUrl ?? $fileUrl);
$fileName = (string) ($fileName ?? 'certificate.pdf');
$infoText = (string) ($infoText ?? $fileName);
$backUrl = (string) ($backUrl ?? '/admin/certificates');
$pageTitle = (string) ($pageTitle ?? 'ดูไฟล์ PDF');
?>
<?php partial('partials/pdf_viewer', [
  'title' => $pageTitle,
  'fileUrl' => $fileUrl,
  'downloadUrl' => $downloadUrl,
  'fileName' => $fileName,
  'infoText' => $infoText,
  'backUrl' => $backUrl,
]); ?>
