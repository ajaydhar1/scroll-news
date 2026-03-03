// js/scroll-archive.js
(function () {
  "use strict";

  function updateHistoryNoResultsMessage() {
    const items = Array.from(document.querySelectorAll(".sn-history-item"));
    if (!items.length) return;

    const anyVisible = items.some((el) => !el.classList.contains("d-none"));
    const alertBox = document.getElementById("history-no-results");
    if (!alertBox) return;

    if (!anyVisible) alertBox.classList.remove("d-none");
    else alertBox.classList.add("d-none");
  }

  document.addEventListener("DOMContentLoaded", function () {
    var SCROLL_AMOUNT = 600; // px per click

    // Arrow button click scroll
    document.querySelectorAll(".scroll-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var trackId = btn.getAttribute("data-track");
        var dir = btn.getAttribute("data-direction");
        var track = document.getElementById(trackId);
        if (!track) return;

        var delta = (dir === "right" ? 1 : -1) * SCROLL_AMOUNT;

        track.scrollBy({
          left: delta,
          behavior: "smooth",
        });
      });
    });

    // Keyboard left/right navigation for each horizontal track
    document.querySelectorAll(".articles-track").forEach(function (track) {
      track.tabIndex = 0; // make focusable

      track.addEventListener("keydown", function (e) {
        if (e.key === "ArrowRight") {
          e.preventDefault();
          track.scrollBy({
            left: track.clientWidth * 0.9,
            behavior: "smooth",
          });
        } else if (e.key === "ArrowLeft") {
          e.preventDefault();
          track.scrollBy({
            left: -track.clientWidth * 0.9,
            behavior: "smooth",
          });
        }
      });
    });

    // Filter bar logic
    var keywordInput = document.getElementById("historyFilterKeyword");
    var domainInput = document.getElementById("historyFilterDomain");
    var timeSelect = document.getElementById("historyFilterTime");

    if (keywordInput && domainInput && timeSelect) {
      var items = Array.prototype.slice.call(
        document.querySelectorAll(".sn-history-item")
      );
      var dayGroups = Array.prototype.slice.call(
        document.querySelectorAll(".sn-history-day")
      );

      function applyFilters() {
        var keyword = keywordInput.value.trim().toLowerCase();
        var domain = domainInput.value.trim().toLowerCase();
        var timeVal = timeSelect.value;

        var nowSec = Math.floor(Date.now() / 1000);

        items.forEach(function (item) {
          var title = (item.dataset.title || "").toLowerCase();
          var itemDomain = (item.dataset.domain || "").toLowerCase();
          var ts = parseInt(item.dataset.timestamp || "0", 10);

          var visible = true;

          // Keyword filter
          if (keyword && !title.includes(keyword)) visible = false;

          // Domain filter
          if (visible && domain && !itemDomain.includes(domain)) visible = false;

          // Time window filter
          if (visible && timeVal !== "all" && ts > 0) {
            var diffSec = nowSec - ts;
            if (timeVal === "1d" && diffSec > 86400) visible = false;
            if (timeVal === "7d" && diffSec > 86400 * 7) visible = false;
            if (timeVal === "30d" && diffSec > 86400 * 30) visible = false;
          }

          if (visible) item.classList.remove("d-none");
          else item.classList.add("d-none");
        });

        // Hide whole day rows that have no visible items
        dayGroups.forEach(function (group) {
          var anyVisible =
            group.querySelector(".sn-history-item:not(.d-none)") !== null;
          if (anyVisible) group.classList.remove("d-none");
          else group.classList.add("d-none");
        });

        // Reset all tracks back to the start after filtering
        document.querySelectorAll(".articles-track").forEach(function (track) {
          track.scrollLeft = 0;
        });

        updateHistoryNoResultsMessage();
      }

      keywordInput.addEventListener("input", applyFilters);
      domainInput.addEventListener("input", applyFilters);
      timeSelect.addEventListener("change", applyFilters);

      // Run once on load (handles prefilled filters / autofill)
      applyFilters();
    } else {
      // Even without filter bar, make sure alert is correct if DOM changes later
      updateHistoryNoResultsMessage();
    }
  });
})();