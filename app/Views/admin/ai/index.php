<div class="page-head">
  <div>
    <p class="page-kicker">AI ASSISTANT</p>
    <h1>AI ผู้ช่วย <?= e(app_brand('short_name')) ?></h1>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card-soft"><div class="card-body">
      <p class="text-muted mb-3">ถามหลักสูตร<?= e(app_brand('full_name')) ?> สรุปรายงาน ร่างข่าว หรือวิเคราะห์แดชบอร์ด</p>
      <div class="mb-3">
        <label class="form-label" for="ai-message">ข้อความ / คำค้น / หัวข้อข่าว</label>
        <textarea id="ai-message" class="form-control" rows="5" placeholder="เช่น อธิบาย SHELL Model ในแผนที่ 7 หรือ ร่างข่าวอบรมขับขี่ปลอดภัย"></textarea>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn-add" data-ai-action="chat"><i class="fa fa-comments"></i> สนทนา</button>
        <button type="button" class="btn btn-outline-primary" data-ai-action="summarize"><i class="fa fa-align-left"></i> สรุปข้อความ</button>
        <button type="button" class="btn btn-outline-primary" data-ai-action="search"><i class="fa fa-search"></i> ค้นหาหลักสูตร</button>
        <button type="button" class="btn btn-outline-warning" data-ai-action="news"><i class="fa fa-newspaper"></i> ร่างข่าว</button>
        <button type="button" class="btn btn-outline-secondary" data-ai-action="dashboard"><i class="fa fa-chart-line"></i> วิเคราะห์ Dashboard</button>
      </div>
      <div id="ai-result" class="mt-3 p-3 rounded ai-result-box" style="min-height:140px;white-space:pre-wrap;background:#faf5ff;border:1px solid #ede9fe">ผลลัพธ์จะแสดงที่นี่</div>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card-soft"><div class="card-body">
      <h2 class="h6">สถานะการตั้งค่า</h2>
      <?php if (!empty($configured)): ?>
        <p class="mb-1"><span class="badge-pill-ok">พร้อมใช้งาน OpenAI</span></p>
        <p class="small text-muted mb-2">โมเดล: <code><?= e($model ?? 'gpt-4o-mini') ?></code></p>
      <?php else: ?>
        <p class="mb-1"><span class="badge-pill-off">ยังไม่มี OPENAI_API_KEY</span></p>
        <p class="small text-muted mb-2">เพิ่มในไฟล์ <code>.env</code> แล้วรีเฟรชหน้านี้</p>
        <pre class="small bg-light p-2 rounded mb-0">OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini</pre>
      <?php endif; ?>
      <hr>
      <p class="small text-muted mb-0">การค้นหาหลักสูตรใช้ข้อมูลในระบบได้แม้ยังไม่มี API key ส่วนสนทนา/สรุป/ร่างข่าว/แดชบอร์ดต้องมีคีย์</p>
    </div></div>
  </div>
</div>

<script>
(function () {
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const out = document.getElementById('ai-result');
  const input = document.getElementById('ai-message');
  const endpoints = {
    chat: <?= json_encode(url('/admin/ai/chat')) ?>,
    summarize: <?= json_encode(url('/admin/ai/summarize')) ?>,
    search: <?= json_encode(url('/admin/ai/search')) ?>,
    news: <?= json_encode(url('/admin/ai/news')) ?>,
    dashboard: <?= json_encode(url('/admin/ai/dashboard')) ?>
  };

  async function call(action) {
    const text = (input?.value || '').trim();
    if (action !== 'dashboard' && !text) {
      out.textContent = 'กรุณากรอกข้อความก่อน';
      return;
    }
    out.textContent = 'กำลังประมวลผล…';
    const fields = { _token: token };
    if (action === 'chat') fields.message = text;
    else if (action === 'summarize') fields.text = text;
    else if (action === 'search') fields.query = text;
    else if (action === 'news') fields.topic = text;

    try {
      const res = await fetch(endpoints[action], {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: new URLSearchParams(fields)
      });
      const data = await res.json();
      if (data.ok && data.content) {
        let extra = '';
        if (Array.isArray(data.matches) && data.matches.length) {
          extra += '\n\n— แผนที่เกี่ยวข้อง —\n' + data.matches.map(m =>
            `แผน ${m.plan_no}: ${m.title}`
          ).join('\n');
        }
        if (data.model) extra += `\n\n(model: ${data.model})`;
        out.textContent = data.content + extra;
      } else {
        out.textContent = data.error || JSON.stringify(data, null, 2);
      }
    } catch (e) {
      out.textContent = 'เกิดข้อผิดพลาด: ' + e;
    }
  }

  document.querySelectorAll('[data-ai-action]').forEach(btn => {
    btn.addEventListener('click', () => call(btn.getAttribute('data-ai-action')));
  });
})();
</script>
