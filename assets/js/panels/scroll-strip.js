/* assets/js/panels/search.js
   Scroll Strip: renders a horizontal article carousel from DB-injected JSON or RSS fallback.
*/

(function () {
  // --- Config injected by PHP ---
  const dbArticlesRaw = Array.isArray(window.SN_SCROLL_STRIP_DB) ? window.SN_SCROLL_STRIP_DB : [];

  // --- Helpers ---
  function fetchRSSArticlesForScrollStrip(feedUrl) {
    // Requires jQuery ($) since you're using $.ajax
    if (typeof window.$ === "undefined") {
      console.warn("Scroll Strip: jQuery not found; cannot fetch RSS fallback.");
      return;
    }

    $.ajax({
      url: "rss_proxy.php",
      method: "POST",
      cache: false,
      data: { feed: feedUrl },
      dataType: "json",
      success: function (response) {
        const articles = (response && response.items) ? response.items : [];
        renderNewsStrip("newsStrip", articles);
      },
      error: function () {
        console.warn("RSS fetch failed for Scroll Strip");
      },
    });
  }

  function normalizePubDate(value) {
    if (!value) return "";

    let str = String(value).trim();

    // Convert MySQL datetime to ISO
    str = str.replace(" ", "T");

    const timestamp = Date.parse(str);

    if (isNaN(timestamp)) {
      console.warn("Invalid pubDate:", value);
      return "";
    }

    return new Date(timestamp).toISOString();
  }

  function timeAgo(iso) {
    const t = new Date(iso).getTime();
    if (!isFinite(t)) return "";

    const diff = (Date.now() - t) / 1000;
    const units = [
      ["day", 86400],
      ["hour", 3600],
      ["min", 60],
    ];

    for (const [u, s] of units) {
      const v = Math.floor(diff / s);
      if (v >= 1) return `${v} ${u}${v > 1 ? "s" : ""} ago`;
    }
    return "just now";
  }

  function extractDomain(url) {
    try {
      const u = new URL(url);
      let host = u.hostname || "";
      if (host.startsWith("www.")) host = host.slice(4);
      return host || "Unknown";
    } catch (e) {
      return "Unknown";
    }
  }

  function safeText(s) {
    // minimal HTML escaping for template literals
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function renderNewsStrip(elId, articles) {
    const strip = document.getElementById(elId);
    if (!strip) return;

    strip.innerHTML = (articles || []).map((a) => {
      const link = a.link || a.url || "#";

      const image =
        a.image ||
        a.image_url ||
        "assets/img/news-placeholder.jpg";

      const rawPubDate =
        a.pub_date_iso ||
        a.pubDate ||
        a.pub_date ||
        "";

      const pubDate = normalizePubDate(rawPubDate);

      const pubForLink =
        a.pub_date_ts ||
        a.pubDateForLink ||
        a.pub_date_ts ||
        a.pub_date ||
        pubDate;

      // Category: RSS uses a.category, DB uses a.source_slug
      const rawCategory = a.category || a.source_slug || "Politics";
      const category = rawCategory
        ? String(rawCategory).charAt(0).toUpperCase() + String(rawCategory).slice(1)
        : "Politics";

      // Publisher/domain
      const domainOrSource = a.publisher || a.source || extractDomain(link);

      const encodedUrl = encodeURIComponent(link);
      const encodedCategory = encodeURIComponent(category);

      let newsroomLink = `newsroom.php?url=${encodedUrl}&category=${encodedCategory}&pub_date=${encodeURIComponent(pubForLink)}`;

      if (a.fromDb) {
        newsroomLink += "&db=1";
      }

      const faviconUrl =
        "https://t0.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=" +
        encodeURIComponent("http://" + domainOrSource) +
        "&size=64";

      const safeTitle = safeText(a.title || "");

      // NLP bits (DB only)
      const hashtags = Array.isArray(a.hashtags) ? a.hashtags : [];
      const sentimentScoreRaw = a.sentiment_score ?? a.sentimentScore ?? null;
      const emotions = Array.isArray(a.emotions) ? a.emotions : [];

      // Badges (DB only)
      const badges = Array.isArray(a.badges) ? a.badges : [];

      // Sentiment (score → bucket → emoji) — keep consistent with site thresholds
      const SENT_POS = 0.30;   // match SN_SENT_POS
      const SENT_NEG = -0.02;  // match SN_SENT_NEG (or -0.30 if you kept symmetric)

      let sentimentBucket = "unknown";
      const s = Number(sentimentScoreRaw);

      if (Number.isFinite(s)) {
        if (s >= SENT_POS) sentimentBucket = "positive";
        else if (s <= SENT_NEG) sentimentBucket = "negative";
        else sentimentBucket = "neutral";
      }

      let sentimentEmoji = "🤷";
      if (sentimentBucket === "positive") sentimentEmoji = "🙂";
      else if (sentimentBucket === "negative") sentimentEmoji = "☹️";
      else if (sentimentBucket === "neutral") sentimentEmoji = "😐";

      // Hashtag chips
      let hashtagsHtml = "";
      if (hashtags.length) {
        hashtagsHtml = `
          <div class="sn-tags">
            ${hashtags.map(tag => `<span class="sn-tag">${safeText(tag)}</span>`).join("")}
          </div>
        `;
      }

      // Emotion bars
      let emotionsHtml = "";
      if (emotions.length) {
        emotionsHtml = `
          <div class="sn-emotions">
            ${emotions.map((e) => {
              const rawVal = typeof e.value === "number" ? e.value : parseFloat(e.value) || 0;
              const width = Math.min(100, Math.max(5, rawVal)); // clamp 5–100
              const label = safeText(e.label || "");
              const displayVal = Math.round(rawVal);

              return `
                <div class="sn-emobar-row">
                  <span class="sn-emobar-label">${label}</span>
                  <div class="sn-emobar-track">
                    <div class="sn-emobar-fill" style="width: ${width}%;"></div>
                  </div>
                  <span class="sn-emobar-value">${displayVal}%</span>
                </div>
              `;
            }).join("")}
          </div>
        `;
      }

      // Badge pills (non-clickable)
      let badgesHtml = "";
      if (badges.length) {
        badgesHtml = `
          <div class="sn-badges scroll-article-badges">
            ${badges.map((badge) => {
              const slug = safeText(badge.slug || "");
              const label = safeText(badge.label || "");
              const tooltip = safeText(badge.tooltip || "");
              return `<span class="scroll-badge scroll-badge-${slug}" title="${tooltip}">${label}</span>`;
            }).join("")}
          </div>
        `;
      }

      return `
        <a class="sn-card" role="listitem"
           href="${newsroomLink}"
           rel="noopener noreferrer"
           aria-label="Open article: ${safeTitle}"
           data-loading>
          <div class="sn-media">
            <img src="${image}" alt="${safeTitle}"
                 onerror="this.src = 'assets/img/news-placeholder.jpg';"
                 loading="lazy" decoding="async">
            <span class="sn-badge">
              <img src="${faviconUrl}" alt="${safeText(domainOrSource)} logo" class="sn-favicon">
              ${safeText(domainOrSource)}
            </span>
          </div>
          <div class="sn-body">
            <div class="sn-title">${safeTitle} ▶️</div>
            <div class="sn-meta">
              <span>${safeText(domainOrSource)}</span>
              ${sentimentEmoji ? `<span class="sn-dot" aria-hidden="true"></span><span>${sentimentEmoji}</span>` : ""}
              <span class="sn-dot" aria-hidden="true"></span>
              <time datetime="${safeText(pubDate)}">${pubDate ? safeText(timeAgo(pubDate)) : "Recent"}</time>
            </div>
            ${badgesHtml}
            ${hashtagsHtml}
            ${emotionsHtml}
          </div>
        </a>
      `;
    }).join("");

    // Horizontal wheel scroll (desktop)
    strip.addEventListener("wheel", (e) => {
      if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
        strip.scrollLeft += e.deltaY * 0.9;
        e.preventDefault();
      }
    }, { passive: false });

    // Keyboard left/right when focused
    strip.tabIndex = 0;
    strip.addEventListener("keydown", (e) => {
      if (e.key === "ArrowRight") strip.scrollBy({ left: strip.clientWidth * 0.9, behavior: "smooth" });
      if (e.key === "ArrowLeft") strip.scrollBy({ left: -strip.clientWidth * 0.9, behavior: "smooth" });
    });

    // Arrow buttons
    const section = strip.closest(".sn-section");
    if (!section) return;

    section.querySelectorAll(".sn-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const dir = btn.dataset.dir === "right" ? 1 : -1;
        strip.scrollBy({ left: dir * strip.clientWidth * 0.9, behavior: "smooth" });
      });
    });
  }

  // --- INIT ---
  function initScrollStrip() {
    const stripEl = document.getElementById("newsStrip");
    if (!stripEl) return; // not on this page

    if (Array.isArray(dbArticlesRaw) && dbArticlesRaw.length > 0) {
      const mapped = dbArticlesRaw.map((row) => {
        // Prefer PHP-provided ts/iso if you add them later
        const pubDate = row.pub_date_iso || row.pub_date || new Date().toISOString();

        // If pub_date_ts exists, use it; otherwise try to derive it
        let ts = row.pub_date_ts;
        if (!ts) {
          const t = new Date(pubDate).getTime();
          ts = isFinite(t) ? Math.floor(t / 1000) : "";
        }

        return {
          ...row,
          category: row.source_slug,
          pub_date: pubDate,
          pubDateForLink: ts,
          fromDb: true,
        };
      });

      renderNewsStrip("newsStrip", mapped);
      return;
    }

    const rssUrl = "https://rss.app/feeds/tahaOzLGHPxMD9OC.xml";
    fetchRSSArticlesForScrollStrip(rssUrl);
  }

  // DOM-ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initScrollStrip);
  } else {
    initScrollStrip();
  }
})();