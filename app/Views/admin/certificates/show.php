<?php /** @var array $cert */ ?>
<?php
$isDiploma = empty($cert['plan_id']);
$fileName = ($isDiploma ? 'diploma_' : 'certificate_')
    . preg_replace('/[^\p{L}\p{N}_-]+/u', '_', (string) ($cert['cert_no'] ?? 'cert')) . '.pdf';
$streamUrl = url('/admin/certificates/' . (int) $cert['id'] . '?format=inline');
$downloadUrl = url('/admin/certificates/' . (int) $cert['id'] . '?format=pdf');
$info = $isDiploma
    ? 'วุฒิบัตร: ' . (string) ($cert['recipient_name'] ?? '') . ' — ' . $fileName
    : 'เกียรติบัตร: ' . (string) ($cert['recipient_name'] ?? '') . ' — ' . (string) ($cert['title'] ?? '') . ' — ' . $fileName;
?>
<?php partial('partials/pdf_viewer', [
  'title' => $isDiploma ? 'ดูวุฒิบัตร' : 'ดูเกียรติบัตร',
  'fileUrl' => $streamUrl,
  'downloadUrl' => $downloadUrl,
  'fileName' => $fileName,
  'infoText' => $info,
  'backUrl' => $backUrl ?? '/admin/certificates',
]); ?>
