(function () {
  var root = document.getElementById('plan-chat');
  if (!root) return;

  var timeline = document.getElementById('plan-chat-timeline');
  var form = document.getElementById('plan-chat-form');
  var bodyEl = document.getElementById('plan-chat-body');
  var sendBtn = document.getElementById('plan-chat-send');
  var emptyEl = document.getElementById('plan-chat-empty');
  var pollUrl = root.getAttribute('data-poll-url');
  var postUrl = root.getAttribute('data-post-url');
  var meId = parseInt(root.getAttribute('data-me') || '0', 10);
  var trainerId = parseInt(root.getAttribute('data-trainer') || '0', 10);
  var lastId = 0;
  var busy = false;
  var timer = null;

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var input = form && form.querySelector('[name="_token"]');
    return (input && input.value) || (meta && meta.getAttribute('content')) || '';
  }

  function scrollBottom(force) {
    if (!timeline) return;
    var near = timeline.scrollHeight - timeline.scrollTop - timeline.clientHeight < 120;
    if (force || near) {
      timeline.scrollTop = timeline.scrollHeight;
    }
  }

  function renderMsg(m) {
    if (!m || !m.id || timeline.querySelector('[data-id="' + m.id + '"]')) return;
    if (emptyEl) {
      emptyEl.remove();
      emptyEl = null;
    }
    var mine = !!m.mine || m.sender_id === meId;
    var isTrainer = !!m.is_trainer || m.sender_id === trainerId;
    var art = document.createElement('article');
    art.className = 'plan-chat-msg ' + (mine ? 'is-mine' : 'is-theirs');
    art.setAttribute('data-id', String(m.id));
    var name = m.sender_name || '';
    art.innerHTML =
      '<div class="plan-chat-avatar" aria-hidden="true">' + esc(name.charAt(0) || '?') + '</div>' +
      '<div class="plan-chat-bubble">' +
        '<div class="plan-chat-meta">' +
          '<strong>' + esc(name) + '</strong>' +
          '<span class="plan-chat-role ' + (isTrainer ? 'is-trainer' : 'is-learner') + '">' +
            (isTrainer ? 'วิทยากร' : 'ผู้เรียน') +
          '</span>' +
          '<time>' + esc(m.created_label || m.created_at || '') + '</time>' +
        '</div>' +
        '<div class="plan-chat-body">' + esc(m.body || '').replace(/\n/g, '<br>') + '</div>' +
      '</div>';
    timeline.appendChild(art);
    lastId = Math.max(lastId, parseInt(m.id, 10) || 0);
  }

  function seedLastId() {
    timeline.querySelectorAll('.plan-chat-msg[data-id]').forEach(function (el) {
      var id = parseInt(el.getAttribute('data-id') || '0', 10);
      if (id > lastId) lastId = id;
    });
  }

  function poll() {
    if (document.hidden) return;
    var url = pollUrl + (pollUrl.indexOf('?') >= 0 ? '&' : '?') + 'after=' + encodeURIComponent(String(lastId));
    fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok || !Array.isArray(data.messages)) return;
        var added = 0;
        data.messages.forEach(function (m) {
          var before = lastId;
          renderMsg(m);
          if (lastId > before) added++;
        });
        if (added) scrollBottom(false);
      })
      .catch(function () {});
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (busy) return;
    var text = (bodyEl.value || '').trim();
    if (!text) return;
    busy = true;
    sendBtn.disabled = true;
    var fd = new FormData();
    fd.append('_token', csrf());
    fd.append('body', text);
    fetch(postUrl, {
      method: 'POST',
      body: fd,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.j || !res.j.ok) {
          alert((res.j && res.j.error) || 'ส่งข้อความไม่สำเร็จ');
          return;
        }
        bodyEl.value = '';
        renderMsg(res.j.message);
        scrollBottom(true);
      })
      .catch(function () { alert('เครือข่ายขัดข้อง'); })
      .finally(function () {
        busy = false;
        sendBtn.disabled = false;
        bodyEl.focus();
      });
  });

  bodyEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      form.requestSubmit();
    }
  });

  seedLastId();
  scrollBottom(true);
  timer = setInterval(poll, 4000);
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) poll();
  });
  window.addEventListener('beforeunload', function () {
    if (timer) clearInterval(timer);
  });
})();
