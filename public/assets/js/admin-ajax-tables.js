/**
 * TAVIS DataTables — มาตรฐานเดียวกันทั้งระบบ
 * AbDataTable.initAjax: responsive + serverSide + ปุ่ม Copy/Excel/PDF/Print/CSV เหนือตาราง
 */
(function () {
  'use strict';
  if (!window.AbDataTable || !window.jQuery) return;

  const esc = AbDataTable.esc;
  const raw = (html) => (html == null ? '' : String(html));

  function bindFilter(formId, table) {
    const form = document.getElementById(formId);
    if (!form || !table) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      table.ajax.reload();
    });
    const q = form.querySelector('input[name="q"], input[type="search"]');
    let t;
    q?.addEventListener('input', function () {
      clearTimeout(t);
      t = setTimeout(function () { table.ajax.reload(); }, 350);
    });
    form.querySelectorAll('select').forEach(function (sel) {
      sel.addEventListener('change', function () { table.ajax.reload(); });
    });
  }

  function val(sel) {
    return document.querySelector(sel)?.value?.trim() || '';
  }

  function registrationFilterParams(formSel) {
    return {
      q: val(formSel + ' [name="q"]'),
      role: val(formSel + ' [name="role"]'),
      member_type: val(formSel + ' [name="member_type"]'),
      personal_type_id: val(formSel + ' [name="personal_type_id"]'),
      area_choice: val(formSel + ' [name="area_choice"]'),
      school_id: val(formSel + ' [name="school_id"]')
    };
  }

  function bindRegistrationFilters(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    const member = form.querySelector('[name="member_type"]');
    const personal = form.querySelector('[name="personal_type_id"]');
    const area = form.querySelector('[name="area_choice"]');
    const school = form.querySelector('[name="school_id"]');
    const positionCatalog = personal
      ? Array.from(personal.options).filter(function (opt) { return opt.value; }).map(function (opt) {
          return { value: opt.value, label: opt.textContent, kind: opt.getAttribute('data-kind') || '' };
        })
      : [];
    function syncPersonal() {
      if (!personal) return;
      const kind = member?.value || '';
      const general = kind === 'general';
      const current = personal.value;
      const placeholder = !kind
        ? 'เลือกประเภทก่อน'
        : (general ? 'ตำแหน่งกรอกเอง — ไม่มีรายการ' : 'ทุกตำแหน่ง / ระดับชั้น');
      personal.innerHTML = '';
      const empty = document.createElement('option');
      empty.value = '';
      empty.textContent = placeholder;
      personal.appendChild(empty);
      if (kind === 'staff' || kind === 'student') {
        positionCatalog.forEach(function (item) {
          if (item.kind !== kind) return;
          const opt = document.createElement('option');
          opt.value = item.value;
          opt.textContent = item.label;
          opt.setAttribute('data-kind', item.kind);
          if (item.value === current) opt.selected = true;
          personal.appendChild(opt);
        });
      }
      personal.disabled = !kind || general;
      personal.hidden = general;
    }
    function syncArea() {
      if (!school) return;
      const other = area?.value === 'other';
      school.hidden = other;
      school.disabled = other;
      if (other) school.value = '';
    }
    member?.addEventListener('change', syncPersonal);
    area?.addEventListener('change', syncArea);
    syncPersonal();
    syncArea();
  }

  // Extra registration columns stay out of the desktop row. The expand control
  // is only useful once those columns are shown again on a phone.
  function bindListLayout(table) {
    if (!table) return;
    const mq = window.matchMedia('(max-width: 991.98px)');
    const apply = function () {
      const mobile = mq.matches;
      table.columns('.col-mobile-detail').visible(mobile, false);
      table.columns.adjust();
      if (table.responsive && typeof table.responsive.recalc === 'function') {
        table.responsive.recalc();
      }
    };
    table.one('draw', apply);
    if (mq.addEventListener) mq.addEventListener('change', apply);
    else if (mq.addListener) mq.addListener(apply);
  }

  function initUsers() {
    const el = document.getElementById('users-datatable');
    if (!el) return;
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[0, 'asc']],
      getParams: function () {
        return registrationFilterParams('#users-filter');
      },
      columns: [
        { data: 'user_html', className: 'col-primary', responsivePriority: 1, render: raw },
        { data: 'email', className: 'col-mobile-detail', responsivePriority: 100, render: (d) => esc(d) },
        { data: 'role_label', responsivePriority: 3, render: (d) => `<span class="badge text-bg-secondary">${esc(d)}</span>` },
        { data: 'member_html', className: 'col-wrap', responsivePriority: 4, render: raw },
        { data: 'org_name', className: 'col-wrap', responsivePriority: 5, render: raw },
        { data: 'province_name', className: 'col-mobile-detail', responsivePriority: 100, render: (d) => esc(d) },
        { data: 'status_html', responsivePriority: 2, render: raw },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', responsivePriority: 1, render: raw }
      ]
    });
    bindListLayout(table);
    bindRegistrationFilters('users-filter');
    bindFilter('users-filter', table);
  }

  function initPersonnel() {
    const el = document.getElementById('personnel-datatable');
    if (!el) return;
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'asc']],
      getParams: function () {
        return registrationFilterParams('#personnel-filter');
      },
      columns: [
        { data: 'code_html', className: 'col-nowrap col-mobile-detail', responsivePriority: 100, render: raw },
        { data: 'name_html', className: 'col-primary', responsivePriority: 1, render: raw },
        { data: 'member_label', className: 'col-wrap', responsivePriority: 4, render: (d) => esc(d) },
        { data: 'position', className: 'col-wrap', responsivePriority: 3, render: (d) => esc(d) },
        { data: 'org_html', className: 'col-wrap', responsivePriority: 5, render: raw },
        { data: 'province_name', className: 'col-mobile-detail', responsivePriority: 100, render: (d) => esc(d) },
        { data: 'phone', className: 'col-nowrap', responsivePriority: 2, render: (d) => esc(d) },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', responsivePriority: 1, render: raw }
      ]
    });
    bindListLayout(table);
    bindRegistrationFilters('personnel-filter');
    bindFilter('personnel-filter', table);
  }

  function initPortfolio() {
    const el = document.getElementById('pf-datatable');
    if (!el) return;
    const mode = el.dataset.mode || 'hours';
    let mid = [
      { data: 'date', className: 'col-nowrap', render: (d) => esc(d) },
      { data: 'hours', render: (d) => esc(d) }
    ];
    if (mode === 'innovation' || mode === 'research') {
      mid = [
        { data: 'year', render: (d) => esc(d) },
        { data: 'status', render: (d) => esc(d) }
      ];
    } else if (mode === 'plc') {
      mid = [
        { data: 'status', render: (d) => esc(d) },
        { data: 'year', render: (d) => esc(d) }
      ];
    }
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[3, 'desc']],
      getParams: function () {
        return {
          q: val('#pf-filter [name="q"]'),
          user_id: val('#pf-filter [name="user_id"]'),
          year: val('#pf-filter [name="year"]')
        };
      },
      columns: [
        { data: 'person', className: 'col-nowrap', render: raw },
        { data: 'item_html', className: 'col-primary col-wrap', render: raw },
        ...mid,
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
    bindFilter('pf-filter', table);
  }

  function initReports() {
    const el = document.getElementById('reports-datatable');
    if (!el) return;
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[3, 'desc']],
      getParams: function () {
        return {
          q: val('#reports-filter [name="q"]'),
          user_id: val('#reports-filter [name="user_id"]'),
          year: val('#reports-filter [name="year"]'),
          report: 1
        };
      },
      columns: [
        { data: 'person', className: 'col-nowrap', render: raw },
        { data: 'item_html', className: 'col-primary col-wrap', render: raw },
        { data: 'date', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
    bindFilter('reports-filter', table);
  }

  function initTickets() {
    const el = document.getElementById('tickets-datatable');
    if (!el) return;
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[5, 'desc']],
      getParams: function () {
        return { q: val('#tickets-filter [name="q"]'), status: val('#tickets-filter [name="status"]') };
      },
      columns: [
        { data: 'ticket_no', className: 'col-nowrap', render: raw },
        { data: 'subject', className: 'col-primary col-wrap', render: (d) => esc(d) },
        { data: 'user_name', render: (d) => esc(d) },
        { data: 'status_html', render: raw },
        { data: 'created_at', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
    bindFilter('tickets-filter', table);
  }

  function initForms() {
    const el = document.getElementById('forms-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'asc']],
      columns: [
        { data: 'title', className: 'col-primary', render: (d) => esc(d) },
        { data: 'slug_html', render: raw },
        { data: 'status_html', render: raw },
        { data: 'updated_at', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
  }

  function initCatalog() {
    const el = document.getElementById('catalog-datatable');
    if (!el) return;
    let cols = [];
    try { cols = JSON.parse(el.dataset.colKeys || '[]'); } catch (e) { cols = []; }
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'asc']],
      columns: cols.map(function (c) {
        return {
          data: c.key,
          render: c.raw ? raw : function (d) { return esc(d == null ? '' : d); }
        };
      }).concat([
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ])
    });
  }

  function initDepartments() {
    const el = document.getElementById('departments-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'asc']],
      columns: [
        { data: 'sort_order', render: (d) => esc(d) },
        { data: 'name', className: 'col-primary', render: (d) => esc(d) },
        { data: 'head_name', render: (d) => esc(d) },
        { data: 'color_html', render: raw },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
  }

  function initActivityLogs() {
    const el = document.getElementById('activity-logs-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'desc']],
      columns: [
        { data: 'created_at', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'user_name', render: (d) => esc(d) },
        { data: 'action', render: (d) => `<span class="badge bg-secondary">${esc(d)}</span>` },
        { data: 'module', render: (d) => esc(d) },
        { data: 'description', className: 'col-wrap', render: (d) => esc(d) },
        { data: 'ip_address', render: (d) => esc(d) }
      ]
    });
  }

  function initLoginLogs() {
    const el = document.getElementById('login-logs-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'desc']],
      columns: [
        { data: 'created_at', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'username', render: (d) => esc(d) },
        { data: 'status_html', render: raw },
        { data: 'message', className: 'col-wrap', render: (d) => esc(d) },
        { data: 'ip_address', render: (d) => esc(d) }
      ]
    });
  }

  function initNotifications() {
    const el = document.getElementById('notifications-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[3, 'desc']],
      columns: [
        { data: 'title_html', className: 'col-primary', render: raw },
        { data: 'message', className: 'col-wrap', render: (d) => esc(d) },
        { data: 'created_at', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'status_html', render: raw },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
  }

  function initGallery() {
    const el = document.getElementById('gallery-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'desc']],
      columns: [
        { data: 'event_date', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'title', className: 'col-primary', render: (d) => esc(d) },
        { data: 'category', render: (d) => esc(d) },
        { data: 'academic_year', render: (d) => esc(d) },
        { data: 'media_count', render: (d) => esc(d) },
        { data: 'status_html', render: raw },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
  }

  function initCurriculum() {
    const el = document.getElementById('curriculum-datatable');
    if (!el) return;
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[0, 'asc']],
      getParams: function () {
        return { q: val('#curriculum-filter [name="q"]') };
      },
      columns: [
        { data: 'plan_no', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'title', className: 'col-primary col-wrap', render: (d) => esc(d) },
        { data: 'hours', render: (d) => esc(d) },
        { data: 'instructor', render: (d) => esc(d) },
        { data: 'topic_group', render: (d) => esc(d) },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
    bindFilter('curriculum-filter', table);
  }

  function initMembers() {
    const el = document.getElementById('members-datatable');
    if (!el) return;
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'asc']],
      getParams: function () {
        return { q: val('#members-filter [name="q"]') };
      },
      columns: [
        { data: 'student_code', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'name', className: 'col-primary', render: (d) => esc(d) },
        { data: 'grade_level', render: (d) => esc(d) },
        { data: 'room', render: (d) => esc(d) },
        { data: 'academic_year', render: (d) => esc(d) },
        { data: 'phone', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'status_html', render: raw },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
    bindFilter('members-filter', table);
  }

  function initOrgUnits() {
    const el = document.getElementById('org-units-datatable');
    if (!el) return;
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'asc']],
      getParams: function () {
        return { org_type: val('#org-units-filter [name="org_type"]') };
      },
      columns: [
        { data: 'code_html', className: 'col-nowrap', render: raw },
        { data: 'name_html', className: 'col-primary col-wrap', render: raw },
        { data: 'type_html', render: raw },
        { data: 'parent_name', className: 'col-wrap', render: (d) => esc(d) },
        { data: 'province', render: (d) => esc(d) },
        { data: 'user_count', className: 'col-nowrap text-end', render: (d) => esc(d) }
      ]
    });
    bindFilter('org-units-filter', table);
  }

  function initEnrollments() {
    const el = document.getElementById('enrollments-datatable');
    if (!el) return;
    const table = AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'desc']],
      getParams: function () {
        return { status: val('#enrollments-filter [name="status"]') };
      },
      columns: [
        { data: 'enrollment_no_html', className: 'col-nowrap', render: raw },
        { data: 'user_name', className: 'col-primary', render: (d) => esc(d) },
        { data: 'org_name', className: 'col-wrap', render: (d) => esc(d) },
        { data: 'hours', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'score', render: (d) => esc(d) },
        { data: 'status_html', render: raw },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
    bindFilter('enrollments-filter', table);
  }

  function initCertificates() {
    const el = document.getElementById('certificates-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'desc']],
      columns: [
        { data: 'cert_no_html', className: 'col-nowrap', render: raw },
        { data: 'recipient_name', className: 'col-primary', render: (d) => esc(d) },
        { data: 'org_name', className: 'col-wrap', render: (d) => esc(d) },
        { data: 'hours', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'issued_at', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
  }

  function initCertificatesReady() {
    const el = document.getElementById('certificates-ready-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'desc']],
      columns: [
        { data: 'enrollment_no_html', className: 'col-nowrap', render: raw },
        { data: 'user_name', className: 'col-primary', render: (d) => esc(d) },
        { data: 'org_name', className: 'col-wrap', render: (d) => esc(d) },
        { data: 'hours', className: 'col-nowrap', render: (d) => esc(d) },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
  }

  function initTraining() {
    const el = document.getElementById('training-datatable');
    if (!el) return;
    AbDataTable.initAjax(el, {
      ajaxUrl: el.dataset.ajaxUrl,
      order: [[1, 'asc']],
      columns: [
        { data: 'plan_no_html', className: 'col-nowrap', render: raw },
        { data: 'title_html', className: 'col-primary col-wrap', render: raw },
        { data: 'pretest_q', className: 'text-center', render: (d) => esc(d) },
        { data: 'content_count', className: 'text-center', render: (d) => esc(d) },
        { data: 'task_count', className: 'text-center', render: (d) => esc(d) },
        { data: 'posttest_q', className: 'text-center', render: (d) => esc(d) },
        { data: 'actions', orderable: false, searchable: false, className: 'col-actions', render: raw }
      ]
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initUsers();
    initPersonnel();
    initPortfolio();
    initReports();
    initTickets();
    initForms();
    initDepartments();
    initCatalog();
    initActivityLogs();
    initLoginLogs();
    initNotifications();
    initGallery();
    initCurriculum();
    initMembers();
    initOrgUnits();
    initEnrollments();
    initCertificates();
    initCertificatesReady();
    initTraining();
  });
})();
