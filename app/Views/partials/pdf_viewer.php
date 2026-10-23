<?php
/**
 * PDF viewer แบบ PAMS (pdf.js) — ใช้กับเนื้อหา / เกียรติบัตร / วุฒิบัตร
 *
 * @var string $fileUrl URL ของ PDF (ต้อง stream ได้ในเบราว์เซอร์)
 * @var string $fileName ชื่อไฟล์ที่แสดง
 * @var string $infoText ข้อความในแถบข้อมูล
 * @var string $downloadUrl URL ดาวน์โหลด (ว่าง = ใช้ $fileUrl)
 * @var string $backUrl URL ปุ่มกลับ (ว่าง = ไม่แสดง)
 * @var string $title หัวข้อหน้า (ว่าง = ไม่แสดง page-head)
 */
$fileUrl = (string) ($fileUrl ?? '');
$fileName = (string) ($fileName ?? 'document.pdf');
$infoText = (string) ($infoText ?? $fileName);
$downloadUrl = (string) ($downloadUrl ?? $fileUrl);
$backUrl = (string) ($backUrl ?? '');
$title = (string) ($title ?? '');
$viewerId = 'pdfv_' . substr(md5($fileUrl . $fileName), 0, 10);
?>
<?php if ($title !== ''): ?>
<div class="page-head pdf-view-head">
  <div>
    <p class="page-kicker">PDF VIEWER</p>
    <h1><?= e($title) ?></h1>
  </div>
  <?php if ($backUrl !== ''): ?>
    <a class="btn btn-outline-secondary" href="<?= e($backUrl) ?>"><i class="fa fa-arrow-left"></i> กลับ</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="pdf-view-shell" id="<?= e($viewerId) ?>" data-file="<?= e($fileUrl) ?>">
  <div class="pdf-view-banner" role="status">
    <i class="fa fa-info-circle" aria-hidden="true"></i>
    <span><?= e($infoText) ?></span>
  </div>
  <div class="pdf-view-actions">
    <button type="button" class="btn btn-primary" data-act="preview">
      <i class="fa fa-eye"></i> ดูตัวอย่าง
    </button>
    <?php if ($downloadUrl !== ''): ?>
      <a class="btn btn-success" href="<?= e($downloadUrl) ?>" target="_blank" rel="noopener">
        <i class="fa fa-download"></i> ดาวน์โหลด PDF
      </a>
    <?php endif; ?>
    <?php if ($backUrl !== '' && $title === ''): ?>
      <a class="btn btn-secondary" href="<?= e($backUrl) ?>">
        <i class="fa fa-arrow-left"></i> กลับ
      </a>
    <?php endif; ?>
  </div>

  <div class="pdf-view-container">
    <div class="pdf-view-wrapper">
      <div class="pdf-view-toolbar">
        <div class="pdf-view-toolbar-left">
          <button type="button" data-act="sidebar" title="แสดง/ซ่อน Thumbnail"><i class="fa fa-bars"></i></button>
          <span class="pdf-view-filename"><?= e($fileName) ?></span>
          <button type="button" data-act="prev" title="หน้าก่อนหน้า"><i class="fa fa-chevron-left"></i></button>
          <span class="pdf-view-pageinfo">
            <span data-role="current">1</span> / <span data-role="total">-</span>
          </span>
          <button type="button" data-act="next" title="หน้าถัดไป"><i class="fa fa-chevron-right"></i></button>
        </div>
        <div class="pdf-view-toolbar-right">
          <button type="button" data-act="zoom-out" title="ย่อ"><i class="fa fa-minus"></i></button>
          <span class="pdf-view-zoom"><span data-role="zoom">100</span>%</span>
          <button type="button" data-act="zoom-in" title="ขยาย"><i class="fa fa-plus"></i></button>
          <button type="button" data-act="fit-width" title="พอดีความกว้าง"><i class="fa fa-arrows-left-right"></i></button>
          <button type="button" data-act="fit-page" title="พอดีหน้า"><i class="fa fa-arrows-up-down"></i></button>
          <button type="button" data-act="rotate" title="หมุน"><i class="fa fa-rotate-right"></i></button>
          <?php if ($downloadUrl !== ''): ?>
            <a href="<?= e($downloadUrl) ?>" target="_blank" rel="noopener" title="ดาวน์โหลด"><i class="fa fa-download"></i></a>
          <?php endif; ?>
          <button type="button" data-act="print" title="พิมพ์"><i class="fa fa-print"></i></button>
        </div>
      </div>
      <div class="pdf-view-body">
        <aside class="pdf-view-side" data-role="side">
          <div data-role="thumbs"></div>
        </aside>
        <div class="pdf-view-canvas" data-role="canvas">
          <div data-role="pages"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
  const root = document.getElementById(<?= json_encode($viewerId) ?>);
  if (!root || !window.pdfjsLib) return;
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

  const fileUrl = root.getAttribute('data-file') || '';
  const pagesHost = root.querySelector('[data-role="pages"]');
  const canvasHost = root.querySelector('[data-role="canvas"]');
  const thumbsHost = root.querySelector('[data-role="thumbs"]');
  const side = root.querySelector('[data-role="side"]');
  const currentEl = root.querySelector('[data-role="current"]');
  const totalEl = root.querySelector('[data-role="total"]');
  const zoomEl = root.querySelector('[data-role="zoom"]');

  let pdfDoc = null;
  let currentPage = 1;
  let totalPages = 0;
  let currentZoom = 1.0;
  let currentRotation = 0;
  let sidebarVisible = true;

  function renderPage(pageNum) {
    return pdfDoc.getPage(pageNum).then(function (page) {
      const viewport = page.getViewport({ scale: currentZoom, rotation: currentRotation });
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      canvas.height = viewport.height;
      canvas.width = viewport.width;
      return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
        let pageDiv = document.getElementById(root.id + '-page-' + pageNum);
        if (!pageDiv) {
          pageDiv = document.createElement('div');
          pageDiv.id = root.id + '-page-' + pageNum;
          pageDiv.className = 'pdf-view-page';
          pagesHost.appendChild(pageDiv);
        }
        pageDiv.innerHTML = '';
        pageDiv.appendChild(canvas);
      });
    });
  }

  function renderAllPages() {
    pagesHost.innerHTML = '';
    const jobs = [];
    for (let i = 1; i <= totalPages; i++) {
      jobs.push(renderPage(i));
    }
    return Promise.all(jobs);
  }

  function renderThumbnails() {
    thumbsHost.innerHTML = '';
    const order = [];
    const jobs = [];
    for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
      jobs.push(pdfDoc.getPage(pageNum).then(function (page) {
        const viewport = page.getViewport({ scale: 0.28 });
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
          const thumb = document.createElement('button');
          thumb.type = 'button';
          thumb.className = 'pdf-view-thumb' + (pageNum === 1 ? ' is-on' : '');
          thumb.id = root.id + '-thumb-' + pageNum;
          thumb.appendChild(canvas);
          const label = document.createElement('span');
          label.className = 'pdf-view-thumb-no';
          label.textContent = String(pageNum);
          thumb.appendChild(label);
          thumb.addEventListener('click', function () {
            currentPage = pageNum;
            updatePage();
          });
          order[pageNum] = thumb;
        });
      }));
    }
    Promise.all(jobs).then(function () {
      for (let i = 1; i <= totalPages; i++) {
        if (order[i]) thumbsHost.appendChild(order[i]);
      }
    });
  }

  function updatePage() {
    currentEl.textContent = String(currentPage);
    root.querySelectorAll('.pdf-view-thumb').forEach(function (el) {
      el.classList.remove('is-on');
    });
    const active = document.getElementById(root.id + '-thumb-' + currentPage);
    if (active) active.classList.add('is-on');
    const pageDiv = document.getElementById(root.id + '-page-' + currentPage);
    if (pageDiv) pageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    renderPage(currentPage);
  }

  function updateZoom() {
    zoomEl.textContent = String(Math.round(currentZoom * 100));
    renderAllPages().then(updatePage);
  }

  pdfjsLib.getDocument(fileUrl).promise.then(function (pdf) {
    pdfDoc = pdf;
    totalPages = pdf.numPages;
    totalEl.textContent = String(totalPages);
    renderAllPages();
    renderThumbnails();
  }).catch(function () {
    pagesHost.innerHTML = '<p class="pdf-view-error">ไม่สามารถโหลด PDF ได้ กรุณาลองใหม่อีกครั้ง</p>';
  });

  root.addEventListener('click', function (event) {
    const btn = event.target.closest('[data-act]');
    if (!btn) return;
    const act = btn.getAttribute('data-act');
    if (act === 'preview') {
      canvasHost.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return;
    }
    if (!pdfDoc) return;
    if (act === 'prev' && currentPage > 1) {
      currentPage -= 1;
      updatePage();
    } else if (act === 'next' && currentPage < totalPages) {
      currentPage += 1;
      updatePage();
    } else if (act === 'zoom-in') {
      currentZoom = Math.min(currentZoom + 0.25, 3);
      updateZoom();
    } else if (act === 'zoom-out') {
      currentZoom = Math.max(currentZoom - 0.25, 0.5);
      updateZoom();
    } else if (act === 'fit-width') {
      pdfDoc.getPage(currentPage).then(function (page) {
        const viewport = page.getViewport({ scale: 1, rotation: currentRotation });
        currentZoom = Math.max(0.5, (canvasHost.clientWidth - 48) / viewport.width);
        updateZoom();
      });
    } else if (act === 'fit-page') {
      pdfDoc.getPage(currentPage).then(function (page) {
        const viewport = page.getViewport({ scale: 1, rotation: currentRotation });
        const scaleX = (canvasHost.clientWidth - 48) / viewport.width;
        const scaleY = (canvasHost.clientHeight - 40) / viewport.height;
        currentZoom = Math.max(0.5, Math.min(scaleX, scaleY));
        updateZoom();
      });
    } else if (act === 'rotate') {
      currentRotation = (currentRotation + 90) % 360;
      renderAllPages();
    } else if (act === 'sidebar') {
      sidebarVisible = !sidebarVisible;
      side.style.display = sidebarVisible ? '' : 'none';
    } else if (act === 'print') {
      window.open(fileUrl, '_blank');
    }
  });

  document.addEventListener('keydown', function (e) {
    if (!pdfDoc || !root.contains(document.activeElement) && !root.matches(':hover')) return;
    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
      if (currentPage > 1) { currentPage -= 1; updatePage(); }
    } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
      if (currentPage < totalPages) { currentPage += 1; updatePage(); }
    }
  });
})();
</script>
