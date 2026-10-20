<?php
$preview = $preview ?? ['kind' => 'text', 'frame_src' => '', 'html' => '', 'note' => ''];
$kind = (string) ($preview['kind'] ?? 'text');
$frame = (string) ($preview['frame_src'] ?? '');
$html = (string) ($preview['html'] ?? '');
$note = (string) ($preview['note'] ?? '');
$pdfId = 'pdf_' . substr(md5($frame . $kind), 0, 8);
?>
<div class="content-stage">
  <?php if ($html !== ''): ?>
    <div class="content-viewer-body"><?= $html ?></div>
  <?php endif; ?>

  <?php if ($kind === 'pdf' && $frame !== ''): ?>
    <div class="tavis-pdf" id="<?= e($pdfId) ?>" data-file="<?= e($frame) ?>">
      <div class="tavis-pdf-toolbar">
        <div class="tavis-pdf-group">
          <button type="button" data-act="sidebar" title="แสดงหรือซ่อนหน้ารายการ"><i class="fa fa-bars"></i></button>
          <button type="button" data-act="prev" title="หน้าก่อนหน้า"><i class="fa fa-chevron-left"></i></button>
          <span class="tavis-pdf-pages"><span data-role="current">1</span> / <span data-role="total">-</span></span>
          <button type="button" data-act="next" title="หน้าถัดไป"><i class="fa fa-chevron-right"></i></button>
        </div>
        <div class="tavis-pdf-group">
          <button type="button" data-act="zoom-out" title="ย่อ"><i class="fa fa-minus"></i></button>
          <span class="tavis-pdf-pages"><span data-role="zoom">100</span>%</span>
          <button type="button" data-act="zoom-in" title="ขยาย"><i class="fa fa-plus"></i></button>
          <button type="button" data-act="fit-width" title="พอดีความกว้าง"><i class="fa fa-arrows-left-right"></i></button>
          <button type="button" data-act="rotate" title="หมุน"><i class="fa fa-rotate-right"></i></button>
          <a href="<?= e($frame) ?>" target="_blank" rel="noopener" title="ดาวน์โหลด"><i class="fa fa-download"></i></a>
        </div>
      </div>
      <div class="tavis-pdf-main">
        <aside class="tavis-pdf-side" data-role="side"></aside>
        <div class="tavis-pdf-canvas" data-role="canvas"></div>
      </div>
    </div>
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
<?php if ($kind === 'pdf' && $frame !== ''): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
  const root = document.getElementById(<?= json_encode($pdfId) ?>);
  if (!root || !window.pdfjsLib) return;
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
  const fileUrl = root.getAttribute('data-file');
  const canvasHost = root.querySelector('[data-role="canvas"]');
  const side = root.querySelector('[data-role="side"]');
  const currentEl = root.querySelector('[data-role="current"]');
  const totalEl = root.querySelector('[data-role="total"]');
  const zoomEl = root.querySelector('[data-role="zoom"]');
  let pdfDoc = null;
  let pageNum = 1;
  let zoom = 1;
  let rotation = 0;

  const paint = (num) => {
    pdfDoc.getPage(num).then((page) => {
      const viewport = page.getViewport({ scale: zoom, rotation: rotation });
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      canvas.width = viewport.width;
      canvas.height = viewport.height;
      page.render({ canvasContext: ctx, viewport: viewport }).promise.then(() => {
        canvasHost.innerHTML = '';
        const pageBox = document.createElement('div');
        pageBox.className = 'tavis-pdf-page';
        pageBox.appendChild(canvas);
        canvasHost.appendChild(pageBox);
        currentEl.textContent = String(num);
      });
    });
  };

  const thumbs = () => {
    side.innerHTML = '';
    for (let i = 1; i <= pdfDoc.numPages; i++) {
      pdfDoc.getPage(i).then((page) => {
        const viewport = page.getViewport({ scale: 0.22 });
        const canvas = document.createElement('canvas');
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise.then(() => {
          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'tavis-pdf-thumb' + (i === pageNum ? ' is-on' : '');
          item.appendChild(canvas);
          const label = document.createElement('span');
          label.textContent = String(i);
          item.appendChild(label);
          item.addEventListener('click', () => {
            pageNum = i;
            paint(pageNum);
            side.querySelectorAll('.tavis-pdf-thumb').forEach((el, idx) => {
              el.classList.toggle('is-on', idx + 1 === pageNum);
            });
          });
          side.appendChild(item);
        });
      });
    }
  };

  pdfjsLib.getDocument(fileUrl).promise.then((pdf) => {
    pdfDoc = pdf;
    totalEl.textContent = String(pdf.numPages);
    paint(1);
    thumbs();
  }).catch(() => {
    canvasHost.innerHTML = '<p class="text-white p-4 mb-0">เปิดไฟล์ PDF ไม่สำเร็จ</p>';
  });

  root.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-act]');
    if (!btn || !pdfDoc) return;
    const act = btn.getAttribute('data-act');
    if (act === 'prev' && pageNum > 1) pageNum -= 1;
    if (act === 'next' && pageNum < pdfDoc.numPages) pageNum += 1;
    if (act === 'zoom-in') zoom = Math.min(3, zoom + 0.2);
    if (act === 'zoom-out') zoom = Math.max(0.4, zoom - 0.2);
    if (act === 'fit-width') zoom = 1;
    if (act === 'rotate') rotation = (rotation + 90) % 360;
    if (act === 'sidebar') side.classList.toggle('is-hidden');
    zoomEl.textContent = String(Math.round(zoom * 100));
    if (act !== 'sidebar') paint(pageNum);
  });
})();
</script>
<?php endif; ?>
