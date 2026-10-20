/* AntiBully CYP — App JS */
(function ($) {
  'use strict';

  // Theme
  const root = document.documentElement;
  const savedTheme = localStorage.getItem('ab-theme') || 'light';
  root.setAttribute('data-theme', savedTheme);

  // Public navbar: mobile toggle + homepage scroll solid state
  const $nav = $('#ab-nav');
  const $menu = $('#ab-menu');
  $('#ab-menu-toggle').on('click', function () {
    $menu.toggleClass('open');
  });
  if ($('body').hasClass('page-home') && $nav.length) {
    const onScroll = function () {
      $nav.toggleClass('is-scrolled', window.scrollY > 40);
    };
    onScroll();
    $(window).on('scroll', onScroll);
  }

  $(document).on('click', '[data-toggle-theme]', function () {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('ab-theme', next);
  });

  // Sidebar mobile open / close + desktop collapse (AdminLTE pushmenu-style)
  function isMobileNav() {
    return window.matchMedia('(max-width: 991.98px)').matches;
  }
  function openSidebar() {
    const $sidebar = $('#sidebar');
    if (!$sidebar.length) return;
    // Mobile drawer must not keep desktop-collapse styles
    $('body').removeClass('sidebar-collapse');
    $sidebar.addClass('open');
    $('#sidebar-backdrop').addClass('show').prop('hidden', false).attr('aria-hidden', 'false');
    $('body').addClass('sidebar-open');
  }
  function closeSidebar() {
    $('#sidebar').removeClass('open');
    $('#sidebar-backdrop').removeClass('show').prop('hidden', true).attr('aria-hidden', 'true');
    $('body').removeClass('sidebar-open');
  }
  // Desktop collapse: set title tooltips so icons still show labels
  function syncSidebarTooltips() {
    const collapsed = !isMobileNav() && document.body.classList.contains('sidebar-collapse');
    document.querySelectorAll('.sidebar-nav a, .nav-group-toggle, .sidebar-logout button').forEach(function (el) {
      if (!collapsed) {
        if (el.dataset.tipSet === '1') {
          el.removeAttribute('title');
          delete el.dataset.tipSet;
        }
        return;
      }
      if (el.dataset.tipSet === '1') return;
      const clone = el.cloneNode(true);
      clone.querySelectorAll('i, .badge, .chevron').forEach(function (n) { n.remove(); });
      const text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
      if (text) {
        el.setAttribute('title', text);
        el.dataset.tipSet = '1';
      }
    });
  }

  function toggleSidebar() {
    if (isMobileNav()) {
      if ($('#sidebar').hasClass('open')) closeSidebar();
      else openSidebar();
      return;
    }
    // Desktop: collapse / expand like AdminLTE pushmenu
    closeSidebar();
    $('body').toggleClass('sidebar-collapse');
    try {
      localStorage.setItem('cyp-sidebar-collapse', $('body').hasClass('sidebar-collapse') ? '1' : '0');
    } catch (e) { /* ignore */ }
    syncSidebarTooltips();
    setTimeout(sidebarScrollUpdate, 220);
  }

  // Restore desktop collapse preference
  try {
    if (!isMobileNav() && localStorage.getItem('cyp-sidebar-collapse') === '1') {
      $('body').addClass('sidebar-collapse');
    }
  } catch (e) { /* ignore */ }
  syncSidebarTooltips();

  $(document).on('click', '[data-toggle-sidebar]', function (e) {
    e.preventDefault();
    e.stopPropagation();
    toggleSidebar();
  });
  $(document).on('click', '[data-close-sidebar]', function (e) {
    e.preventDefault();
    closeSidebar();
  });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });
  // Keep JS mode in sync with CSS breakpoint
  const mqNav = window.matchMedia('(max-width: 991.98px)');
  function onNavBreakpointChange() {
    if (mqNav.matches) {
      // Enter mobile: drop desktop collapse so drawer can show full labels
      $('body').removeClass('sidebar-collapse');
      closeSidebar();
    } else {
      closeSidebar();
      try {
        if (localStorage.getItem('cyp-sidebar-collapse') === '1') {
          $('body').addClass('sidebar-collapse');
        }
      } catch (e) { /* ignore */ }
    }
    syncSidebarTooltips();
    setTimeout(sidebarScrollUpdate, 220);
  }
  if (typeof mqNav.addEventListener === 'function') {
    mqNav.addEventListener('change', onNavBreakpointChange);
  } else if (typeof mqNav.addListener === 'function') {
    mqNav.addListener(onNavBreakpointChange);
  }
  $(window).on('resize', function () {
    syncSidebarTooltips();
  });

  // Sidebar scroll — OverlayScrollbars like AdminLTE (auto-hide until scroll)
  (function () {
    const $scroll = $('#sidebar-scroll');
    if (!$scroll.length) return;

    if (typeof $.fn.overlayScrollbars === 'function') {
      $scroll.overlayScrollbars({
        className: 'os-theme-light',
        sizeAutoCapable: true,
        scrollbars: {
          autoHide: 'scroll',
          autoHideDelay: 400,
          clickScrolling: true
        }
      });
      return;
    }

    // Native fallback: show thumb only while scrolling
    const scroller = $scroll.get(0);
    let timer;
    scroller.addEventListener('scroll', function () {
      scroller.classList.add('is-scrolling');
      clearTimeout(timer);
      timer = setTimeout(function () {
        scroller.classList.remove('is-scrolling');
      }, 700);
    }, { passive: true });
  })();

  function sidebarScrollInstance() {
    const $scroll = $('#sidebar-scroll');
    if (!$scroll.length || typeof $.fn.overlayScrollbars !== 'function') return null;
    try {
      return $scroll.overlayScrollbars() || null;
    } catch (e) {
      return null;
    }
  }

  function sidebarScrollUpdate() {
    const inst = sidebarScrollInstance();
    if (inst && typeof inst.update === 'function') {
      inst.update(true);
    }
  }

  // Sidebar submenu toggle (AdminLTE treeview-style accordion)
  $(document).on('click', '[data-nav-toggle]', function (e) {
    e.preventDefault();
    // Desktop collapsed: expand first so submenu can show
    if (!isMobileNav() && document.body.classList.contains('sidebar-collapse')) {
      $('body').removeClass('sidebar-collapse');
      try { localStorage.setItem('cyp-sidebar-collapse', '0'); } catch (err) { /* ignore */ }
      syncSidebarTooltips();
    }
    const $group = $(this).closest('[data-nav-group]');
    const willOpen = !$group.hasClass('open');
    if (willOpen) {
      $('[data-nav-group].open').not($group).each(function () {
        $(this).removeClass('open');
        $(this).find('[data-nav-toggle]').attr('aria-expanded', 'false');
      });
    }
    $group.toggleClass('open', willOpen);
    $(this).attr('aria-expanded', willOpen ? 'true' : 'false');
    setTimeout(sidebarScrollUpdate, 280);
  });

  // Keep active sidebar menu centered in the scroll viewport
  (function () {
    function getActiveMenuLink() {
      return document.querySelector('.sidebar-nav a.active');
    }

    function ensureActiveGroupOpen(active) {
      const group = active && active.closest('[data-nav-group]');
      if (!group) return;
      group.classList.add('open');
      const toggle = group.querySelector('[data-nav-toggle]');
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
    }

    function centerActiveMenuNative(active) {
      const host = document.querySelector('.sidebar-scroll');
      if (!host || !active) return false;
      const scroller = host.querySelector('.os-viewport') || host;
      const scrollerRect = scroller.getBoundingClientRect();
      const activeRect = active.getBoundingClientRect();
      if (activeRect.height < 1) return false;

      const activeCenter = (activeRect.top - scrollerRect.top) + scroller.scrollTop + (activeRect.height / 2);
      const target = activeCenter - (scroller.clientHeight / 2);
      const max = Math.max(0, scroller.scrollHeight - scroller.clientHeight);
      const next = Math.max(0, Math.min(max, target));

      if (typeof scroller.scrollTo === 'function') {
        scroller.scrollTo({ top: next, behavior: 'auto' });
      } else {
        scroller.scrollTop = next;
      }
      return true;
    }

    function centerActiveMenu() {
      const active = getActiveMenuLink();
      if (!active) return;

      ensureActiveGroupOpen(active);
      sidebarScrollUpdate();

      const inst = sidebarScrollInstance();
      if (inst && typeof inst.scroll === 'function') {
        try {
          // OverlayScrollbars v1 API (same stack as AdminLTE)
          inst.scroll({
            el: $(active),
            block: 'center',
            scroll: 'always'
          }, 0);
          return;
        } catch (e) {
          /* fall through to native */
        }
      }

      centerActiveMenuNative(active);
    }

    function centerActiveMenuSoon() {
      // Retry after OS/layout/fonts settle so lower items (e.g. Log/Audit) land in center
      [0, 50, 150, 350, 700].forEach(function (ms) {
        setTimeout(function () {
          requestAnimationFrame(centerActiveMenu);
        }, ms);
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', centerActiveMenuSoon);
    } else {
      centerActiveMenuSoon();
    }
    window.addEventListener('load', centerActiveMenuSoon);
    window.addEventListener('resize', function () {
      clearTimeout(window.__abSidebarCenterTimer);
      window.__abSidebarCenterTimer = setTimeout(centerActiveMenu, 120);
    });
  })();

  // Clock widget
  function tickClock() {
    const el = document.getElementById('live-clock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const dateEl = document.getElementById('live-date');
    if (dateEl) {
      dateEl.textContent = now.toLocaleDateString('th-TH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
  }
  tickClock();
  setInterval(tickClock, 1000);

  /** แปลงวันที่สำหรับแสดงผลภาษาไทย (พ.ศ.) — ไม่เปลี่ยนค่าที่เก็บในระบบ */
  window.CypThaiDate = (function () {
    var MONTHS = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    function parse(value) {
      if (value == null || value === '') return null;
      if (value instanceof Date && !isNaN(value.getTime())) return value;
      var s = String(value).trim().replace(' ', 'T');
      var d = new Date(s);
      return isNaN(d.getTime()) ? null : d;
    }
    function format(value, withTime) {
      var d = parse(value);
      if (!d) return value ? String(value) : '-';
      var out = d.getDate() + ' ' + MONTHS[d.getMonth() + 1] + ' ' + (d.getFullYear() + 543);
      if (withTime) {
        var hh = String(d.getHours()).padStart(2, '0');
        var mm = String(d.getMinutes()).padStart(2, '0');
        out += ' ' + hh + ':' + mm + ' น.';
      }
      return out;
    }
    return { format: format, months: MONTHS };
  })();

  function cssVar(name, fallback) {
    try {
      const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return v || fallback;
    } catch (e) {
      return fallback;
    }
  }

  // SweetAlert helpers — success / error / warning / danger ตามสี default ของ SweetAlert2 + ธีมหลัก
  window.AbNotify = {
    success: function (title, text) {
      if (!window.Swal) { return Promise.resolve(); }
      return Swal.fire({
        icon: 'success',
        title: title || 'สำเร็จ',
        text: text || undefined,
        timer: 2200,
        showConfirmButton: true,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: cssVar('--p-success', '#28a745'),
        customClass: { confirmButton: 'swal-btn-success' }
      });
    },
    error: function (title, text) {
      if (!window.Swal) { return Promise.resolve(); }
      return Swal.fire({
        icon: 'error',
        title: title || 'เกิดข้อผิดพลาด',
        text: text || undefined,
        confirmButtonText: 'ปิด',
        confirmButtonColor: cssVar('--p-error', '#f27474'),
        customClass: { confirmButton: 'swal-btn-error' }
      });
    },
    warning: function (title, text) {
      if (!window.Swal) { return Promise.resolve(); }
      return Swal.fire({
        icon: 'warning',
        title: title || 'แจ้งเตือน',
        text: text || undefined,
        confirmButtonText: 'ปิด',
        confirmButtonColor: cssVar('--p-warning', '#f8bb86'),
        customClass: { confirmButton: 'swal-btn-warning' }
      });
    },
    info: function (title, text) {
      if (!window.Swal) { return Promise.resolve(); }
      return Swal.fire({
        icon: 'info',
        title: title || 'แจ้งเตือน',
        text: text || undefined,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: cssVar('--p-primary', '#7c3aed'),
        customClass: { confirmButton: 'swal-btn-primary' }
      });
    },
    confirm: function (options) {
      const opts = options || {};
      const isDelete = !!opts.isDelete;
      if (!window.Swal) {
        return Promise.resolve({ isConfirmed: false });
      }
      const tone = opts.tone || (isDelete ? 'danger' : (opts.icon === 'warning' ? 'warning' : (opts.icon === 'error' ? 'error' : (opts.icon === 'success' ? 'success' : 'primary'))));
      const btnClass = 'swal-btn-' + tone;
      const colors = {
        success: cssVar('--p-success', '#28a745'),
        error: cssVar('--p-error', '#f27474'),
        warning: cssVar('--p-warning', '#f8bb86'),
        danger: cssVar('--p-danger', '#dc3741'),
        primary: cssVar('--p-primary', '#2563eb')
      };
      return Swal.fire({
        icon: opts.icon || (isDelete ? 'warning' : 'question'),
        title: opts.title || (isDelete ? 'ยืนยันการลบ?' : 'ยืนยัน?'),
        text: opts.text || '',
        showCancelButton: true,
        focusCancel: true,
        reverseButtons: true,
        confirmButtonColor: colors[tone] || colors.primary,
        cancelButtonColor: cssVar('--p-swal-cancel', '#6e7881'),
        confirmButtonText: opts.confirmText || (isDelete ? 'ลบ' : 'ยืนยัน'),
        cancelButtonText: opts.cancelText || 'ยกเลิก',
        customClass: { confirmButton: btnClass }
      });
    },
    confirmDelete: function (text) {
      return window.AbNotify.confirm({
        isDelete: true,
        title: 'ยืนยันการลบ?',
        text: text || 'เมื่อลบแล้วจะไม่สามารถกู้คืนได้',
        confirmText: 'ลบ',
        cancelText: 'ยกเลิก'
      });
    }
  };

  // Confirm delete / danger actions (SweetAlert — ยืนยันหรือยกเลิก)
  $(document).on('click', '[data-confirm], [data-confirm-delete]', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $btn = $(this);
    if ($btn.data('abConfirmBusy')) return;
    $btn.data('abConfirmBusy', true);

    const deleteMsg = $btn.attr('data-confirm-delete');
    const confirmMsg = $btn.attr('data-confirm') || deleteMsg || 'ยืนยันการดำเนินการ?';
    const isDelete = deleteMsg != null || /ลบ|delete|remove/i.test(confirmMsg);
    const form = $btn.closest('form').get(0);
    const href = $btn.is('a[href]') ? $btn.attr('href') : null;

    const finish = function () { $btn.removeData('abConfirmBusy'); };

    window.AbNotify.confirm({
      isDelete: isDelete,
      title: $btn.attr('data-confirm-title') || (isDelete ? 'ยืนยันการลบ?' : 'ยืนยัน?'),
      text: confirmMsg,
      confirmText: isDelete ? ($btn.attr('data-confirm-ok') || 'ลบ') : ($btn.attr('data-confirm-ok') || 'ยืนยัน'),
      cancelText: 'ยกเลิก'
    }).then(function (result) {
      finish();
      if (!result || !result.isConfirmed) return;
      if (form) {
        // Native submit — ไม่กระตุ้น click ซ้ำของปุ่ม confirm
        HTMLFormElement.prototype.submit.call(form);
        return;
      }
      if (href && href !== '#') {
        window.location.href = href;
      }
    }).catch(finish);
  });

  // Flash to SweetAlert (success / error / warning / info)
  (function showFlashAlerts() {
    if (!window.AbNotify || !window.Swal) return;
    const map = [
      ['flash-success', 'success'],
      ['flash-error', 'error'],
      ['flash-warning', 'warning'],
      ['flash-info', 'info']
    ];
    map.forEach(function (pair) {
      const el = document.getElementById(pair[0]);
      if (el && el.dataset.msg) {
        const fn = window.AbNotify[pair[1]];
        if (typeof fn === 'function') fn(el.dataset.msg);
      }
    });
  })();

  // DataTables — project-wide responsive (+ AJAX where marked)
  if (window.AbDataTable) {
    AbDataTable.initAllClient('table.datatable');
  } else if ($.fn.DataTable) {
    $('.datatable').not('.datatable-ajax').each(function () {
      if (!$.fn.DataTable.isDataTable(this)) {
        $(this).DataTable({
          responsive: true,
          pageLength: 15,
          order: [[0, 'desc']],
          language: {
            emptyTable: 'ไม่พบข้อมูล',
            zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
            info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ แถว',
            infoEmpty: 'แสดง 0 ถึง 0 จาก 0 แถว',
            paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' }
          }
        });
      }
    });
  }

  // Global search autocomplete (public pages only — admin layout binds its own handler)
  const $search = $('#global-search');
  if ($search.length && String($search.data('url') || '').indexOf('/admin/search') === -1) {
    let timer;
    $search.on('input', function () {
      clearTimeout(timer);
      const q = $(this).val();
      timer = setTimeout(function () {
        if (q.length < 2) {
          $('#global-search-results').hide().empty();
          return;
        }
        $.getJSON($search.data('url'), { q: q }, function (res) {
          const box = $('#global-search-results').empty().show();
          const news = (res.data && res.data.news) || [];
          if (!news.length) {
            box.append('<div class="p-2 text-muted">ไม่พบผลลัพธ์</div>');
            return;
          }
          news.slice(0, 6).forEach(function (n) {
            box.append('<a class="dropdown-item py-2" href="#">' + $('<div>').text(n.title).html() + '</a>');
          });
        }).fail(function () {
          if (window.AbNotify) window.AbNotify.error('ค้นหาไม่สำเร็จ');
        });
      }, 300);
    });
  }

  // Loading overlay for heavy forms
  $('form[data-loading]').on('submit', function () {
    $('#loading-overlay').addClass('show');
  });

  // AI chat FAB
  $('#aiFab').on('click', function () {
    const $panel = $('#aiPanel');
    $panel.prop('hidden', false).css('display', 'flex');
    $('#aiInput').trigger('focus');
  });
  $('#aiClose').on('click', function () {
    $('#aiPanel').prop('hidden', true).hide();
  });

  // AI chat
  $('#ai-chat-form').on('submit', async function (e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"], button:not([type])').prop('disabled', true);
    const msg = String($form.find('[name=message]').val() || '').trim();
    if (!msg) {
      $btn.prop('disabled', false);
      return;
    }

    const token = $form.find('[name=_token]').val() || $('meta[name="csrf-token"]').attr('content') || '';
    const $box = $('#ai-chat-box');
    $box.append(
      '<div class="ai-msg-row ai-msg-row--user">' +
        '<div class="ai-msg ai-msg--user">' + $('<div>').text(msg).html() + '</div>' +
      '</div>'
    );
    $form.find('[name=message]').val('');
    $box.scrollTop($box[0].scrollHeight);

    const pendingId = 'ai-pending-' + Date.now();
    $box.append(
      '<div class="ai-msg-row" id="' + pendingId + '">' +
        '<div class="ai-msg ai-msg--bot text-muted">กำลังคิด...</div>' +
      '</div>'
    );
    $box.scrollTop($box[0].scrollHeight);

    try {
      const res = await fetch($form.attr('action'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json'
        },
        credentials: 'same-origin',
        body: new URLSearchParams({ _token: token, message: msg })
      });
      const data = await res.json();
      const reply = (data && data.reply) ? String(data.reply) : 'ไม่สามารถตอบได้ในขณะนี้';
      $('#' + pendingId).html('<div class="ai-msg ai-msg--bot">' + $('<div>').text(reply).html() + '</div>');
    } catch (err) {
      console.error('AI chat error:', err);
      $('#' + pendingId).html('<div class="ai-msg ai-msg--bot text-danger">เกิดข้อผิดพลาดในการเชื่อมต่อ AI</div>');
      if (window.AbNotify) window.AbNotify.error('เชื่อมต่อ AI ไม่สำเร็จ');
    } finally {
      $btn.prop('disabled', false);
      $box.scrollTop($box[0].scrollHeight);
      $form.find('[name=message]').focus();
    }
  });

  // Mark notification read
  $(document).on('click', '[data-noti-read]', function () {
    const id = $(this).data('noti-read');
    const url = $(this).data('url');
    $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') });
  });

})(jQuery);
