(function () {
  const root = document.querySelector("[data-stats-root]");
  if (!root) return;

  const cardBase = (root.getAttribute("data-card-url") || "").replace(/\/$/, "");
  const chartBase = (root.getAttribute("data-chart-url") || "").replace(/\/$/, "");
  const qs = root.getAttribute("data-query") || "";

  async function fetchJson(url) {
    const res = await fetch(url, {
      credentials: "same-origin",
      headers: { Accept: "application/json" }
    });
    const data = await res.json().catch(function () { return null; });
    if (!res.ok || !data || data.ok === false) {
      throw new Error((data && data.message) || ("HTTP " + res.status));
    }
    return data;
  }

  function formatCount(n) {
    const v = Number(n);
    if (!Number.isFinite(v)) return "–";
    return v.toLocaleString("th-TH");
  }

  async function loadCard(el) {
    const key = el.getAttribute("data-stat-card");
    const num = el.querySelector(".n");
    if (!key || !num) return;
    try {
      const data = await fetchJson(cardBase + "/" + encodeURIComponent(key) + qs);
      num.textContent = formatCount(data.value);
      num.classList.remove("is-loading");
    } catch (err) {
      num.textContent = "–";
      num.classList.remove("is-loading");
      num.classList.add("is-error");
    }
  }

  function setChartState(box, state, message) {
    const overlay = box.querySelector(".chart-loading");
    box.classList.remove("is-loading", "is-error", "is-ready");
    box.classList.add("is-" + state);
    if (!overlay) return;
    if (state === "ready") {
      overlay.hidden = true;
      return;
    }
    overlay.hidden = false;
    const label = overlay.querySelector("span:last-child");
    if (label) label.textContent = message || (state === "error" ? "โหลดไม่สำเร็จ" : "กำลังโหลด...");
  }

  async function loadChart(box) {
    const key = box.getAttribute("data-chart");
    if (!key) return;
    setChartState(box, "loading", "กำลังโหลด...");
    try {
      const data = await fetchJson(chartBase + "/" + encodeURIComponent(key) + qs);
      const wrap = box.closest("[data-chart-wrap]");
      if (data.hide && wrap) {
        wrap.style.display = "none";
        return;
      }
      if (window.EPCharts && typeof window.EPCharts.render === "function") {
        window.EPCharts.render(key, data);
      }
      setChartState(box, "ready");
    } catch (err) {
      setChartState(box, "error", "โหลดไม่สำเร็จ");
    }
  }

  root.querySelectorAll("[data-stat-card]").forEach(function (el) {
    if (!cardBase) return;
    void loadCard(el);
  });
  root.querySelectorAll("[data-chart]").forEach(function (box) {
    if (!chartBase) return;
    void loadChart(box);
  });
})();
