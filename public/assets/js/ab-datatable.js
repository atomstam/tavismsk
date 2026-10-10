/**
 * AntiBully shared DataTables — responsive + client/AJAX
 * - Toolbar: Excel / PDF / Print / CSV + search
 * - Mobile: expand/collapse in-table
 * - Desktop: hide expand when not collapsed
 * - Server-side AJAX via async/await fetch (initAjax)
 */
(function (window, $) {
  'use strict';

  if (!$) return;

  const TH_LANG = 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/th.json';

  const DOM =
    "<'dt-toolbar'<'dt-buttons-wrap'B><'dt-search-wrap'f>>" +
    "<'dt-table'tr>" +
    "<'dt-footer'<'dt-info'i><'dt-paginate'p>>";

  const EXPORT_OPTS = {
    columns: ':visible:not(.dt-expand-col):not(.col-actions)',
    format: {
      body: function (data) {
        if (data == null) return '';
        const tmp = document.createElement('div');
        tmp.innerHTML = String(data);
        return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
      }
    }
  };

  const BUTTONS = [
    { extend: 'copyHtml5', className: 'btn btn-sm btn-dark', text: '<i class="fa fa-copy"></i> Copy', exportOptions: EXPORT_OPTS },
    { extend: 'excelHtml5', className: 'btn btn-sm btn-success', text: '<i class="fa fa-file-excel"></i> Excel', exportOptions: EXPORT_OPTS },
    {
      extend: 'pdfHtml5',
      className: 'btn btn-sm btn-danger',
      text: '<i class="fa fa-file-pdf"></i> PDF',
      pageSize: 'A4',
      exportOptions: EXPORT_OPTS,
      customize: function (doc) {
        // THSarabun via vfs_fonts_thsarabun.js (same pattern as sim-cyp)
        if (window.pdfMake) {
          window.pdfMake.fonts = window.pdfMake.fonts || {};
          window.pdfMake.fonts.THSarabun = {
            normal: 'THSarabun.ttf',
            bold: 'THSarabun-Bold.ttf',
            italics: 'THSarabun-Italic.ttf',
            bolditalics: 'THSarabun-BoldItalic.ttf'
          };
        }
        doc.defaultStyle = doc.defaultStyle || {};
        doc.defaultStyle.font = 'THSarabun';
        doc.defaultStyle.fontSize = 14;
        if (doc.styles) {
          if (doc.styles.tableHeader) {
            doc.styles.tableHeader.font = 'THSarabun';
            doc.styles.tableHeader.fontSize = 14;
            doc.styles.tableHeader.bold = true;
          }
          if (doc.styles.title) {
            doc.styles.title.font = 'THSarabun';
            doc.styles.title.fontSize = 18;
          }
        }
      }
    },
    { extend: 'print', className: 'btn btn-sm btn-secondary', text: '<i class="fa fa-print"></i> Print', exportOptions: EXPORT_OPTS },
    { extend: 'csvHtml5', className: 'btn btn-sm btn-info', text: '<i class="fa fa-file-csv"></i> CSV', charset: 'utf-8', bom: true, exportOptions: EXPORT_OPTS }
  ];

  const esc = (s) => $('<div>').text(s ?? '').html();

  const toggleHtml = (expanded) =>
    expanded
      ? '<button type="button" class="btn-dt-toggle is-expanded" title="ย่อ" aria-label="ย่อ"><i class="fa fa-compress"></i><span>ย่อ</span></button>'
      : '<button type="button" class="btn-dt-toggle" title="ขยาย" aria-label="ขยาย"><i class="fa fa-expand"></i><span>ขยาย</span></button>';

  const syncToggleButtons = (api) => {
    api.rows({ page: 'current' }).every(function () {
      const tr = this.node();
      if (!tr) return;
      const cell = tr.querySelector('td.dtr-control, td.dt-expand-col');
      if (!cell) return;
      const expanded = tr.classList.contains('parent');
      const current = cell.querySelector('.btn-dt-toggle');
      if (current && current.classList.contains('is-expanded') === expanded) return;
      cell.innerHTML = toggleHtml(expanded);
    });
  };

  const ensureExpandColumn = (tableEl) => {
    const $table = $(tableEl);
    // Remove empty-state colspan rows (break expand column / responsive)
    $table.find('tbody tr').each(function () {
      const $cells = $(this).children('td');
      if ($cells.length === 1 && $cells.first().attr('colspan')) {
        $(this).remove();
      }
    });
    if ($table.find('thead th.dt-expand-col').length) return;
    $table.find('thead tr').prepend('<th class="dt-expand-col"></th>');
    $table.find('tbody tr').each(function () {
      $(this).prepend('<td class="dtr-control dt-expand-col"></td>');
    });
  };

  const prepareTableContainer = (tableEl) => {
    // Bootstrap .table-responsive causes horizontal scroll instead of column collapse
    const $wrap = $(tableEl).closest('.table-responsive');
    if ($wrap.length) {
      $wrap.css({ overflow: 'visible', overflowX: 'visible', width: '100%' });
      $wrap.removeClass('table-responsive').addClass('ab-dt-host');
    }
  };

  const markActionColumns = (tableEl) => {
    const $table = $(tableEl);
    $table.find('thead tr th').each(function () {
      const text = String($(this).text() || '').trim();
      if (text === '' || text === '#' || $(this).hasClass('col-actions')) {
        $(this).addClass('col-actions');
      }
    });
    $table.find('tbody tr').each(function () {
      const $last = $(this).children('td').last();
      if ($last.find('a.btn, button, form').length) {
        $last.addClass('col-actions');
      }
    });
  };

  const baseOptions = () => ({
    pageLength: 15,
    autoWidth: false,
    deferRender: true,
    scrollX: false,
    language: { url: TH_LANG },
    dom: DOM,
    buttons: BUTTONS,
    responsive: {
      details: {
        type: 'column',
        target: 0
      }
    }
  });

  const bindResponsiveUi = (table, tableEl) => {
    $(tableEl).on('click', 'td.dtr-control, td.dt-expand-col', function () {
      setTimeout(function () {
        syncToggleButtons(table);
      }, 0);
    });
    table.on('responsive-display draw responsive-resize', function () {
      syncToggleButtons(table);
    });

    const recalc = function () {
      if (!$.fn.DataTable.isDataTable(tableEl)) return;
      table.columns.adjust();
      if (table.responsive && typeof table.responsive.recalc === 'function') {
        table.responsive.recalc();
      }
      syncToggleButtons(table);
    };
    recalc();
    setTimeout(recalc, 200);
    setTimeout(recalc, 600);

    let resizeTimer;
    const resizeNs = 'resize.abdt-' + (tableEl.id || ('t' + Math.random().toString(36).slice(2)));
    $(window).off(resizeNs).on(resizeNs, function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(recalc, 150);
    });
  };

  const parseOrderAttr = (tableEl, fallback) => {
    const orderAttr = tableEl.getAttribute('data-order');
    if (!orderAttr) return fallback;
    try {
      return JSON.parse(orderAttr);
    } catch (e) {
      return fallback;
    }
  };

  /**
   * Client-side DataTable (HTML rows)
   */
  const initClient = (tableEl, extra = {}) => {
    if (!tableEl || !$.fn.DataTable || $.fn.DataTable.isDataTable(tableEl)) return null;

    prepareTableContainer(tableEl);
    ensureExpandColumn(tableEl);
    markActionColumns(tableEl);
    $(tableEl).addClass('ab-datatable w-100');

    const opts = $.extend(true, {}, baseOptions(), {
      order: parseOrderAttr(tableEl, [[1, 'desc']]),
      columnDefs: [
        {
          targets: 0,
          orderable: false,
          searchable: false,
          className: 'dtr-control dt-expand-col',
          defaultContent: '',
          responsivePriority: 1,
          render: () => toggleHtml(false)
        },
        {
          targets: 'th.col-actions',
          orderable: false,
          searchable: false,
          responsivePriority: 2
        },
        {
          targets: 'th.col-primary',
          responsivePriority: 3
        }
      ],
      drawCallback: function () {
        syncToggleButtons(this.api());
      }
    }, extra);

    const table = $(tableEl).DataTable(opts);
    bindResponsiveUi(table, tableEl);
    return table;
  };

  /**
   * Server-side AJAX DataTable (async/await fetch)
   */
  const initAjax = (tableEl, config = {}) => {
    if (!tableEl || !$.fn.DataTable || $.fn.DataTable.isDataTable(tableEl)) return null;

    const ajaxUrl = config.ajaxUrl || tableEl.dataset.ajaxUrl;
    if (!ajaxUrl) {
      console.warn('AbDataTable.initAjax: missing ajaxUrl');
      return null;
    }

    $(tableEl).addClass('ab-datatable datatable-ajax w-100');
    prepareTableContainer(tableEl);
    // ต้องมี th.dt-expand-col ให้จำนวนคอลัมน์ตรงกับ columns (กันหัวตารางเลื่อน)
    ensureExpandColumn(tableEl);

    const columns = config.columns || [];
    if (!columns.length || !String(columns[0].className || '').includes('dtr-control')) {
      columns.unshift({
        data: null,
        orderable: false,
        searchable: false,
        className: 'dtr-control dt-expand-col',
        defaultContent: '',
        responsivePriority: 1,
        render: () => toggleHtml(false)
      });
    } else {
      columns[0].render = columns[0].render || (() => toggleHtml(false));
      columns[0].responsivePriority = columns[0].responsivePriority || 1;
    }

    const getExtraParams = typeof config.getParams === 'function' ? config.getParams : () => ({});

    const opts = $.extend(true, {}, baseOptions(), {
      processing: true,
      serverSide: true,
      searching: config.searching !== false,
      order: config.order || parseOrderAttr(tableEl, [[1, 'desc']]),
      columns,
      ajax: async function (dtParams, callback) {
        try {
          const url = new URL(ajaxUrl, window.location.origin);
          url.searchParams.set('draw', String(dtParams.draw ?? 1));
          url.searchParams.set('start', String(dtParams.start ?? 0));
          url.searchParams.set('length', String(dtParams.length ?? 15));

          const dtSearch = (dtParams.search && dtParams.search.value) ? String(dtParams.search.value).trim() : '';
          const extra = getExtraParams(dtParams) || {};
          const searchValue = dtSearch || String(extra.q || '').trim();
          url.searchParams.set('search[value]', searchValue);
          url.searchParams.set('q', searchValue);

          Object.keys(extra).forEach((key) => {
            if (key === 'q') return;
            if (extra[key] !== undefined && extra[key] !== null && extra[key] !== '') {
              url.searchParams.set(key, String(extra[key]));
            }
          });

          if (dtParams.order && dtParams.order[0]) {
            url.searchParams.set('order[0][column]', String(dtParams.order[0].column));
            url.searchParams.set('order[0][dir]', String(dtParams.order[0].dir));
          }

          const res = await fetch(url.toString(), {
            method: 'GET',
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          });

          if (!res.ok) throw new Error('HTTP ' + res.status);
          const json = await res.json();
          callback(json);
        } catch (err) {
          console.error('AbDataTable AJAX error:', err);
          if (window.AbNotify) {
            window.AbNotify.error('โหลดข้อมูลไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง');
          } else if (window.Swal) {
            Swal.fire({
              icon: 'error',
              title: 'โหลดข้อมูลไม่สำเร็จ',
              text: 'กรุณาลองใหม่อีกครั้ง',
              confirmButtonText: 'ปิด',
              customClass: { confirmButton: 'swal-btn-error' }
            });
          }
          callback({
            draw: dtParams.draw || 1,
            recordsTotal: 0,
            recordsFiltered: 0,
            data: []
          });
        }
      },
      drawCallback: function () {
        syncToggleButtons(this.api());
        if (typeof config.drawCallback === 'function') {
          config.drawCallback(this.api());
        }
      }
    }, config.extraOptions || {});

    const table = $(tableEl).DataTable(opts);
    bindResponsiveUi(table, tableEl);
    return table;
  };

  const initAllClient = (selector = 'table.datatable') => {
    if (!$.fn.DataTable) return [];
    const tables = [];
    $(selector).not('.datatable-ajax').each(function () {
      if ($.fn.DataTable.isDataTable(this)) return;
      const t = initClient(this);
      if (t) tables.push(t);
    });
    return tables;
  };

  // Recalc when Bootstrap tabs become visible
  $(document).on('shown.bs.tab', 'button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]', function () {
    const target = $(this).attr('data-bs-target') || $(this).attr('href');
    if (!target) return;
    $(target).find('table.dataTable, table.ab-datatable').each(function () {
      if (!$.fn.DataTable.isDataTable(this)) return;
      const api = $(this).DataTable();
      api.columns.adjust();
      if (api.responsive) api.responsive.recalc();
      syncToggleButtons(api);
    });
  });

  window.AbDataTable = {
    esc,
    toggleHtml,
    syncToggleButtons,
    ensureExpandColumn,
    initClient,
    initAjax,
    initAllClient,
    BUTTONS,
    DOM
  };
})(window, window.jQuery);
