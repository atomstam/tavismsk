(function (global) {
  if (typeof Chart === "undefined") return;
  Chart.defaults.font.family = "Kanit, Sarabun, sans-serif";
  Chart.defaults.color = "#64748b";
  Chart.defaults.plugins.legend.labels.boxWidth = 12;
  const commonOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } };

  function cssVar(name, fallback) {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback;
  }

  function cssList(prefix, count, fallbacks) {
    const out = [];
    for (let i = 1; i <= count; i++) {
      out.push(cssVar(prefix + "-" + i, fallbacks[i - 1] || fallbacks[0] || "#94a3b8"));
    }
    return out;
  }

  function chartColors() {
    return {
      pal: cssList("--chart-pal", 10, ["#0d9488", "#f59e0b", "#2563eb", "#db2777", "#16a34a", "#ea580c", "#7c3aed", "#0891b2", "#dc2626", "#64748b"]),
      hours: [cssVar("--chart-hours-earned", "#0f766e"), cssVar("--chart-hours-required", "#d97706")],
      share: cssList("--chart-share", 6, ["#f59e0b", "#2563eb", "#a855f7", "#059669", "#e11d48", "#94a3b8"]),
      polar: cssList("--chart-polar", 6, ["#ea580c", "#0891b2", "#c026d3", "#65a30d", "#e11d48", "#78716c"]),
      status: cssList("--chart-status", 6, ["#f59e0b", "#3b82f6", "#7c3aed", "#10b981", "#ef4444", "#94a3b8"]),
      role: cssList("--chart-role", 4, ["#7c3aed", "#0ea5e9", "#22c55e", "#f97316"]),
      radar: cssVar("--chart-radar", "#0891b2"),
      radarFill: cssVar("--chart-radar-fill", "rgba(8,145,178,.2)"),
      month: cssVar("--chart-month", "#16a34a"),
      monthFill: cssVar("--chart-month-fill", "rgba(22,163,74,.16)"),
      loginOk: cssVar("--chart-login-ok", "#2563eb"),
      loginOkFill: cssVar("--chart-login-ok-fill", "rgba(37,99,235,.16)"),
      loginFail: cssVar("--chart-login-fail", "#e11d48"),
      loginFailFill: cssVar("--chart-login-fail-fill", "rgba(225,29,72,.08)"),
      coverage: [cssVar("--chart-coverage-done", "#059669"), cssVar("--chart-coverage-todo", "#cbd5e1")],
      empty: cssVar("--chart-empty", "#e2e8f0")
    };
  }

  function take(colors, n) {
    if (!n || n <= 0) return colors.slice();
    const out = [];
    for (let i = 0; i < n; i++) out.push(colors[i % colors.length]);
    return out;
  }

  function make(id, cfg) {
    const el = document.getElementById(id);
    if (!el) return null;
    const existing = Chart.getChart(el);
    if (existing) existing.destroy();
    return new Chart(el, cfg);
  }

  function pieOrEmpty(labels, data, colors, emptyColor) {
    if (data && data.some(function (v) { return Number(v) > 0; })) {
      return { labels: labels, data: data, colors: colors };
    }
    return { labels: ["ยังไม่มีข้อมูล"], data: [1], colors: [emptyColor || "#e2e8f0"] };
  }

  const renderers = {
    login: function (C) {
      const col = chartColors();
      make("loginChart", {
        type: "line",
        data: {
          labels: C.loginLabels || [],
          datasets: [
            { label: "สำเร็จ", data: C.loginData || [], borderColor: col.loginOk, backgroundColor: col.loginOkFill, fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: col.loginOk },
            { label: "ไม่สำเร็จ", data: C.loginFail || [], borderColor: col.loginFail, backgroundColor: col.loginFailFill, fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: col.loginFail }
          ]
        },
        options: Object.assign({}, commonOpts, { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } })
      });
    },
    countBar: function (C) {
      const col = chartColors();
      const n = (C.moduleCounts || []).length;
      make("countBarChart", {
        type: "bar",
        data: {
          labels: C.moduleLabels || [],
          datasets: [{
            label: "จำนวนรายการ",
            data: C.moduleCounts || [],
            backgroundColor: take(col.status, n),
            borderRadius: 8,
            maxBarThickness: 42
          }]
        },
        options: Object.assign({}, commonOpts, { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } })
      });
    },
    role: function (C) {
      const col = chartColors();
      const n = (C.roleData || []).length;
      make("roleChart", {
        type: "doughnut",
        data: {
          labels: C.roleLabels || [],
          datasets: [{ data: C.roleData || [], backgroundColor: take(col.role, n), borderWidth: 2, borderColor: "#fff" }]
        },
        options: Object.assign({}, commonOpts, { cutout: "62%" })
      });
    },
    hours: function (C) {
      const col = chartColors();
      const data = (C.hourBars && C.hourBars.length) ? C.hourBars : (C.moduleHours || []);
      make("hoursBarChart", {
        type: "bar",
        data: {
          labels: (C.hourLabels && C.hourLabels.length) ? C.hourLabels : (C.moduleLabels || []),
          datasets: [{
            label: "ชั่วโมง",
            data: data,
            backgroundColor: take(col.hours, data.length || 2),
            borderRadius: 8,
            maxBarThickness: 28
          }]
        },
        options: Object.assign({}, commonOpts, { indexAxis: "y", plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } })
      });
    },
    share: function (C) {
      const col = chartColors();
      const n = (C.shareData || []).length || (C.shareLabels || []).length;
      const share = pieOrEmpty(C.shareLabels, C.shareData, take(col.share, n || 6), col.empty);
      make("sharePieChart", {
        type: "pie",
        data: { labels: share.labels, datasets: [{ data: share.data, backgroundColor: share.colors, borderWidth: 2, borderColor: "#fff" }] },
        options: commonOpts
      });
    },
    radar: function (C) {
      const col = chartColors();
      const n = (C.moduleCounts || []).length;
      make("radarChart", {
        type: "radar",
        data: {
          labels: C.moduleLabels || [],
          datasets: [{
            label: "จำนวนรายการ",
            data: C.moduleCounts || [],
            borderColor: col.radar,
            backgroundColor: col.radarFill,
            pointBackgroundColor: take(col.status, n),
            pointBorderColor: "#fff",
            pointRadius: 4
          }]
        },
        options: Object.assign({}, commonOpts, { scales: { r: { beginAtZero: true, ticks: { precision: 0, backdropColor: "transparent" } } } })
      });
    },
    polar: function (C) {
      const col = chartColors();
      const labels = (C.shareLabels && C.shareLabels.length) ? C.shareLabels : C.moduleLabels;
      const data = (C.shareData && C.shareData.length) ? C.shareData : C.moduleCounts;
      const n = (data || []).length || (labels || []).length;
      const polar = pieOrEmpty(labels, data, take(col.polar, n || 6), col.empty);
      make("polarChart", {
        type: "polarArea",
        data: { labels: polar.labels, datasets: [{ data: polar.data, backgroundColor: polar.colors }] },
        options: Object.assign({}, commonOpts, { scales: { r: { ticks: { backdropColor: "transparent", precision: 0 } } } })
      });
    },
    position: function (C) {
      const col = chartColors();
      const n = (C.positionData || []).length;
      make("positionChart", {
        type: "pie",
        data: { labels: C.positionLabels || [], datasets: [{ data: C.positionData || [], backgroundColor: take(col.pal, n), borderWidth: 2, borderColor: "#fff" }] },
        options: commonOpts
      });
    },
    dept: function (C) {
      const col = chartColors();
      const n = (C.deptData || []).length;
      make("deptChart", {
        type: "doughnut",
        data: { labels: C.deptLabels || [], datasets: [{ data: C.deptData || [], backgroundColor: take(col.pal.slice().reverse(), n), borderWidth: 2, borderColor: "#fff" }] },
        options: Object.assign({}, commonOpts, { cutout: "58%" })
      });
    },
    coverage: function (C) {
      const col = chartColors();
      make("coverageChart", {
        type: "doughnut",
        data: { labels: C.coverageLabels || [], datasets: [{ data: C.coverageData || [], backgroundColor: col.coverage, borderWidth: 2, borderColor: "#fff" }] },
        options: Object.assign({}, commonOpts, { cutout: "62%" })
      });
    },
    year: function (C) {
      const col = chartColors();
      const yearColors = {
        "รออนุมัติ": col.status[0],
        "อนุมัติ": col.status[1],
        "กำลังเรียน": col.status[2],
        "ผ่านหลักสูตร": col.status[3]
      };
      make("yearBarChart", {
        type: "bar",
        data: {
          labels: C.yearLabels || [],
          datasets: (C.yearSets || []).map(function (s, i) {
            return Object.assign({}, s, {
              backgroundColor: yearColors[s.label] || col.status[i % col.status.length],
              borderRadius: 6,
              maxBarThickness: 28
            });
          })
        },
        options: Object.assign({}, commonOpts, { scales: { x: { stacked: false }, y: { beginAtZero: true, ticks: { precision: 0 } } } })
      });
    },
    month: function (C) {
      const col = chartColors();
      make("monthLineChart", {
        type: "line",
        data: {
          labels: C.monthLabels || [],
          datasets: [{
            label: "รายการใหม่",
            data: C.monthData || [],
            borderColor: col.month,
            backgroundColor: col.monthFill,
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointBackgroundColor: col.month
          }]
        },
        options: Object.assign({}, commonOpts, { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } })
      });
    },
    loginStatus: function (C) {
      const col = chartColors();
      make("loginStatusChart", {
        type: "doughnut",
        data: {
          labels: C.loginStatusLabels || [],
          datasets: [{
            data: C.loginStatusData || [],
            backgroundColor: (C.loginStatusColors && C.loginStatusColors.length) ? C.loginStatusColors : [col.coverage[0], col.loginFail, col.empty],
            borderWidth: 2,
            borderColor: "#fff"
          }]
        },
        options: Object.assign({}, commonOpts, { cutout: "58%" })
      });
    }
  };

  global.EPCharts = global.PDRSCharts = {
    render: function (key, payload) {
      const fn = renderers[key];
      if (typeof fn === "function") fn(payload || {});
    }
  };
})(window);
