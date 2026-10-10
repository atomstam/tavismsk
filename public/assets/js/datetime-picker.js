/**
 * AntiBully — date / datetime / time pickers (Flatpickr)
 * - พิมพ์ตัวเลขได้โดยตรง
 * - ไอคอนปฏิทินอยู่ใน textbox
 * - รองรับมือถือ (ไม่สลับไป native picker)
 */
(function () {
  'use strict';

  if (typeof flatpickr === 'undefined') return;

  if (flatpickr.l10ns && flatpickr.l10ns.th) {
    flatpickr.localize(flatpickr.l10ns.th);
  }

  function isMobile() {
    return window.matchMedia('(max-width: 767.98px)').matches
      || (('ontouchstart' in window) && window.innerWidth < 992);
  }

  function parseExistingValue(raw, mode) {
    var v = String(raw || '').trim();
    if (!v) return null;

    if (mode === 'time') {
      var tm = v.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
      if (tm) {
        var d = new Date();
        d.setHours(parseInt(tm[1], 10), parseInt(tm[2], 10), 0, 0);
        return d;
      }
      return v;
    }

    v = v.replace('T', ' ');
    var dm = v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2})(?::\d{2})?)?$/);
    if (dm) {
      return new Date(
        parseInt(dm[3], 10),
        parseInt(dm[2], 10) - 1,
        parseInt(dm[1], 10),
        dm[4] !== undefined ? parseInt(dm[4], 10) : 0,
        dm[5] !== undefined ? parseInt(dm[5], 10) : 0,
        0,
        0
      );
    }

    var iso = v.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/);
    if (iso) {
      return new Date(
        parseInt(iso[1], 10),
        parseInt(iso[2], 10) - 1,
        parseInt(iso[3], 10),
        iso[4] !== undefined ? parseInt(iso[4], 10) : 0,
        iso[5] !== undefined ? parseInt(iso[5], 10) : 0,
        0,
        0
      );
    }

    var ts = Date.parse(v);
    return isNaN(ts) ? null : new Date(ts);
  }

  function modeFor(input) {
    var type = (input.getAttribute('type') || '').toLowerCase();
    var picker = (input.dataset.picker || '').toLowerCase();
    if (picker === 'datetime' || type === 'datetime-local' || input.classList.contains('js-datetime')) {
      return 'datetime';
    }
    if (picker === 'time' || type === 'time' || input.classList.contains('js-time')) {
      return 'time';
    }
    return 'date';
  }

  function wrapInput(input) {
    if (input.closest('.ab-date-wrap')) {
      return input.closest('.ab-date-wrap');
    }

    var wrap = document.createElement('div');
    wrap.className = 'ab-date-wrap';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);

    input.classList.add('form-control', 'ab-date-field');
    input.setAttribute('data-input', '');

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ab-date-toggle';
    btn.setAttribute('tabindex', '-1');
    btn.setAttribute('aria-label', 'เปิดปฏิทิน');
    btn.innerHTML = '<i class="fa-regular fa-calendar-days" aria-hidden="true"></i>';
    wrap.appendChild(btn);

    return wrap;
  }

  function placeToggleLast(wrap) {
    var toggle = wrap.querySelector('.ab-date-toggle');
    if (toggle && toggle.parentNode === wrap) {
      wrap.appendChild(toggle);
    }
  }

  /**
   * แทนที่ช่องปี (input + ลูกศรเล็ก) ด้วย <select> ให้เลือกปีชัดเจนบนมือถือ
   */
  function enhanceYearDropdown(instance) {
    if (!instance || !instance.calendarContainer) return;
    if (instance._abYearSelectReady) return;

    var yearWrap = instance.calendarContainer.querySelector('.flatpickr-current-month .numInputWrapper');
    if (!yearWrap) return;

    var yearInput = yearWrap.querySelector('input.cur-year, input.numInput');
    if (!yearInput) return;

    var select = document.createElement('select');
    select.className = 'ab-year-dropdown';
    select.setAttribute('aria-label', 'เลือกปี');
    select.title = 'เลือกปี';

    function yearBounds() {
      var nowY = new Date().getFullYear();
      var minY = nowY - 80;
      var maxY = nowY + 10;

      if (instance.config.minDate instanceof Date) {
        minY = instance.config.minDate.getFullYear();
      }
      if (instance.config.maxDate instanceof Date) {
        maxY = instance.config.maxDate.getFullYear();
      }

      // รองรับปี พ.ศ. ในกรณีที่ผู้ใช้พิมพ์ปีไทยไว้ — clamp เป็น ค.ศ. ช่วงใช้งานจริง
      if (minY > 2400) minY -= 543;
      if (maxY > 2400) maxY -= 543;

      if (minY > maxY) {
        var tmp = minY;
        minY = maxY;
        maxY = tmp;
      }
      return { minY: minY, maxY: maxY };
    }

    function rebuildOptions(selectedYear) {
      var bounds = yearBounds();
      var minY = bounds.minY;
      var maxY = bounds.maxY;
      var y = parseInt(selectedYear, 10);
      if (isNaN(y)) y = instance.currentYear || new Date().getFullYear();
      if (y < minY) minY = y;
      if (y > maxY) maxY = y;

      select.innerHTML = '';
      for (var yr = maxY; yr >= minY; yr--) {
        var opt = document.createElement('option');
        opt.value = String(yr);
        opt.textContent = String(yr);
        if (yr === y) opt.selected = true;
        select.appendChild(opt);
      }
      select.value = String(y);
    }

    rebuildOptions(instance.currentYear || yearInput.value);

    select.addEventListener('change', function () {
      var y = parseInt(select.value, 10);
      if (isNaN(y)) return;
      try {
        instance.changeYear(y);
      } catch (e) {
        instance.currentYear = y;
        if (typeof instance.redraw === 'function') instance.redraw();
      }
    });

    // ซ่อน input ปีเดิม แต่คงไว้ให้ flatpickr อัปเดตค่าได้
    yearWrap.classList.add('ab-year-wrap');
    yearInput.classList.add('ab-year-input-hidden');
    yearInput.setAttribute('tabindex', '-1');
    yearInput.setAttribute('aria-hidden', 'true');
    yearWrap.appendChild(select);

    // sync เมื่อเปลี่ยนเดือน/ปีจากปุ่มลูกศร
    var sync = function () {
      rebuildOptions(instance.currentYear);
    };
    if (typeof instance.config.onYearChange === 'undefined') {
      instance.config.onYearChange = [];
    } else if (!Array.isArray(instance.config.onYearChange)) {
      instance.config.onYearChange = [instance.config.onYearChange];
    }
    instance.config.onYearChange.push(sync);

    if (typeof instance.config.onMonthChange === 'undefined') {
      instance.config.onMonthChange = [];
    } else if (!Array.isArray(instance.config.onMonthChange)) {
      instance.config.onMonthChange = [instance.config.onMonthChange];
    }
    instance.config.onMonthChange.push(sync);

    if (typeof instance.config.onOpen === 'undefined') {
      instance.config.onOpen = [];
    } else if (!Array.isArray(instance.config.onOpen)) {
      instance.config.onOpen = [instance.config.onOpen];
    }
    instance.config.onOpen.push(sync);

    instance._abYearSelectReady = true;
    instance._abYearSelect = select;
  }

  function initInput(input) {
    if (!input || input.dataset.fpReady === '1') return;
    if (input.disabled || input.readOnly) return;

    var mode = modeFor(input);
    var existing = input.value;
    var defaultDate = parseExistingValue(existing, mode);
    var mobile = isMobile();

    input.type = 'text';
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.setAttribute('inputmode', mode === 'time' ? 'numeric' : 'decimal');

    if (mode === 'time') {
      input.placeholder = input.placeholder || 'ชม:นาที เช่น 14:30';
    } else if (mode === 'datetime') {
      input.placeholder = input.placeholder || 'วว/ดด/ปปปป ชม:นาที';
    } else {
      input.placeholder = input.placeholder || 'วว/ดด/ปปปป';
    }

    var wrap = wrapInput(input);
    var isTimeOnly = mode === 'time';
    var toggleBtn = wrap.querySelector('[data-toggle]');
    if (toggleBtn) {
      toggleBtn.innerHTML = isTimeOnly
        ? '<i class="fa-regular fa-clock" aria-hidden="true"></i>'
        : '<i class="fa-regular fa-calendar-days" aria-hidden="true"></i>';
      toggleBtn.setAttribute('aria-label', isTimeOnly ? 'เลือกเวลา' : 'เปิดปฏิทิน');
    }

    var opts = {
      wrap: true,
      allowInput: true,
      // บังคับใช้ Flatpickr บนมือถือ ไม่สลับไป native date picker
      disableMobile: true,
      // เปิดจากช่องกรอกได้ แต่ไอคอนใช้ capture handler ด้านล่าง (กันชนกับ wrap toggle)
      clickOpens: true,
      animate: !mobile,
      locale: 'th',
      time_24hr: true,
      defaultDate: defaultDate || undefined,
      // บนมือถือให้ปฏิทินอยู่ใน flow ใต้ช่องกรอก อ่านง่ายและไม่หลุดจอ
      static: mobile,
      monthSelectorType: 'dropdown',
      appendTo: mobile ? wrap : undefined,
      onReady: function (selectedDates, dateStr, instance) {
        placeToggleLast(wrap);
        if (instance.altInput) {
          instance.altInput.classList.add('form-control', 'ab-date-alt', 'ab-date-field');
          instance.altInput.setAttribute('inputmode', mode === 'time' ? 'numeric' : 'decimal');
          instance.altInput.setAttribute('autocomplete', 'off');
        }
        if (mode !== 'time') {
          enhanceYearDropdown(instance);
        }
      },
      onOpen: function (selectedDates, dateStr, instance) {
        placeToggleLast(wrap);
        if (mode !== 'time') {
          enhanceYearDropdown(instance);
        }
        if (!mobile || !instance.calendarContainer) return;
        // เลื่อนให้เห็นปฏิทินบนมือถือ
        setTimeout(function () {
          try {
            instance.calendarContainer.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
          } catch (e) { /* ignore */ }
        }, 50);
      },
    };

    if (mode === 'datetime') {
      opts.enableTime = true;
      opts.dateFormat = 'Y-m-d H:i';
      opts.altInput = true;
      opts.altFormat = 'd/m/Y H:i';
      opts.altInputClass = 'form-control ab-date-alt ab-date-field';
    } else if (mode === 'time') {
      opts.enableTime = true;
      opts.noCalendar = true;
      opts.dateFormat = 'H:i';
      opts.altInput = true;
      opts.altFormat = 'H:i';
      opts.altInputClass = 'form-control ab-date-alt ab-date-field';
    } else {
      opts.enableTime = false;
      opts.dateFormat = 'Y-m-d';
      opts.altInput = true;
      opts.altFormat = 'd/m/Y';
      opts.altInputClass = 'form-control ab-date-alt ab-date-field';
    }

    var fp = flatpickr(wrap, opts);
    input.dataset.fpReady = '1';
    placeToggleLast(wrap);

    // ไม่ใช้ data-toggle ของ Flatpickr (กันเปิดแล้วปิดทันที) — เปิดด้วย handler ด้านล่างเท่านั้น
    if (toggleBtn) {
      toggleBtn.removeAttribute('data-toggle');
      toggleBtn.type = 'button';
    }

    // ให้คลิกไอคอนเปิดปฏิทินได้แน่นอน
    wrap.addEventListener('click', function (e) {
      var t = e.target;
      if (!(t instanceof Element)) return;
      var btn = t.closest('.ab-date-toggle');
      if (!btn || !wrap.contains(btn)) return;
      e.preventDefault();
      e.stopPropagation();
      if (fp.isOpen) {
        fp.close();
      } else {
        fp.open();
      }
    });
  }

  function initAll(root) {
    var scope = root || document;
    scope.querySelectorAll(
      'input[type="date"], input[type="datetime-local"], input[type="time"], ' +
      'input.js-date, input.js-datetime, input.js-time, ' +
      'input[data-picker="date"], input[data-picker="datetime"], input[data-picker="time"]'
    ).forEach(initInput);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initAll(); });
  } else {
    initAll();
  }

  window.AbDatePicker = { init: initAll, initInput: initInput };
})();
