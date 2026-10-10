<?php
/**
 * AJAX unique check for username / email.
 * Params: $checkUrl, $excludeId (int, edit mode), $scope (CSS selector for form, optional)
 */
$checkUrl = $checkUrl ?? url('/api/users/check');
$excludeId = (int) ($excludeId ?? 0);
$scope = $scope ?? '';
?>
<style>
.user-unique-err { display:block; color:#dc2626; font-size:.875rem; margin-top:.35rem; font-weight:500; }
.user-unique-ok { display:block; color:#16a34a; font-size:.875rem; margin-top:.35rem; }
.user-unique-invalid { border-color:#dc2626 !important; }
</style>
<script>
(function () {
  const checkUrl = <?= json_encode($checkUrl) ?>;
  const excludeId = <?= (int) $excludeId ?>;
  const root = <?= $scope !== '' ? 'document.querySelector(' . json_encode($scope) . ')' : 'document' ?>;
  if (!root) return;

  function ensureMsg(input) {
    let el = input.parentElement?.querySelector('.js-unique-msg');
    if (!el) {
      el = document.createElement('span');
      el.className = 'js-unique-msg';
      input.parentElement?.appendChild(el);
    }
    return el;
  }

  function setError(input, msg, clear) {
    input.classList.add('user-unique-invalid');
    const el = ensureMsg(input);
    el.className = 'js-unique-msg user-unique-err';
    el.textContent = msg || '';
    if (clear) {
      input.value = '';
      try { input.focus(); } catch (e) {}
    }
  }

  function setOk(input, msg) {
    input.classList.remove('user-unique-invalid');
    const el = ensureMsg(input);
    if (msg) {
      el.className = 'js-unique-msg user-unique-ok';
      el.textContent = msg;
    } else {
      el.className = 'js-unique-msg';
      el.textContent = '';
    }
  }

  function clearMsg(input) {
    input.classList.remove('user-unique-invalid');
    const el = input.parentElement?.querySelector('.js-unique-msg');
    if (el) { el.className = 'js-unique-msg'; el.textContent = ''; }
  }

  const timers = {};
  function schedule(field, input) {
    clearTimeout(timers[field]);
    timers[field] = setTimeout(() => check(field, input), 400);
  }

  async function check(field, input) {
    if (input.readOnly || input.disabled) return;
    const value = (input.value || '').trim();
    if (value === '') { clearMsg(input); return; }
    if (field === 'username' && value.length < 3) {
      setError(input, 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร', false);
      return;
    }
    try {
      const qs = new URLSearchParams({ field, value, exclude_id: String(excludeId || 0) });
      const res = await fetch(checkUrl + '?' + qs.toString(), {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (data.available) {
        setOk(input, data.message || '');
      } else {
        setError(input, data.message || 'ข้อมูลซ้ำในระบบ', data.clear !== false);
      }
    } catch (e) {
      setError(input, 'ตรวจสอบไม่สำเร็จ กรุณาลองใหม่', false);
    }
  }

  function bind(name, field) {
    const input = root.querySelector('input[name="' + name + '"]');
    if (!input) return;
    input.addEventListener('blur', () => check(field, input));
    input.addEventListener('change', () => check(field, input));
    input.addEventListener('input', () => {
      clearMsg(input);
      schedule(field, input);
    });
  }

  bind('username', 'username');
  bind('email', 'email');
})();
</script>
