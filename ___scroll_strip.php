<?php
// Try to pull recent articles from the DB first
$scroll_strip_articles = [];

try {
    // Make sure config/DB is loaded (safe even if already included)
    require_once "___modules.php";

    $db = _pdo_or_null();

    // Adjust $db / $pdo variable name to whatever you use in ___config.php
    // Example assumes $db is a PDO instance.
    if (!isset($db)) {
        throw new Exception("DB handle not available");
    }

    // Adjust column names to match your `articles` table:
    // url, title, publisher, image_url, pub_date are guesses.
    $sql = "
        SELECT 
            url,
            title,
            publisher,
            image_url,
            pub_date
        FROM articles
        WHERE 
            url IS NOT NULL 
            AND title IS NOT NULL
            AND image_url IS NOT NULL
        ORDER BY pub_date DESC
        LIMIT 12
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows && count($rows) > 0) {
        $scroll_strip_articles = $rows;
    }
} catch (Throwable $e) {
    // If anything goes wrong, we just fall back to RSS on the frontend
    $scroll_strip_articles = [];
}
?>

<!-- ========== Article Strip (HTML + CSS + JS) ========== -->
<section class="sn-section">
  <div class="container-fluid">
    <div class="text-center">
        <h2 class="section-heading text-uppercase">Top Stories</h2>
    </div>
    <div class="sn-header">
      <h2>Scroll Strip</h2>
      <div class="sn-controls">
        <button class="sn-btn" data-dir="left" aria-label="Scroll left">←</button>
        <button class="sn-btn" data-dir="right" aria-label="Scroll right">→</button>
      </div>
    </div>

    <div class="sn-strip" id="newsStrip" role="list" aria-label="Top stories"></div>
  </div>
</section>

<style>
  .sn-section { --gap: 16px; --card-w: 280px; --radius: 16px; --shadow: 0 6px 20px rgba(0,0,0,.12); padding: 3rem 0; }
  @media (max-width: 575.98px) {
    .sn-section {
        padding-bottom: 1.5rem;
    }
  }
  .sn-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
  .sn-header h2 { font-size:1.1rem; margin:0; letter-spacing:.2px; }
  .sn-controls { display:flex; gap:8px; }
  .sn-btn { border:1px solid rgba(0,0,0,.1); background:#fff; border-radius:999px; padding:8px 12px; cursor:pointer; }
  .sn-btn:hover { background:#f7f7f7; }
  .sn-strip {
    display:grid;
    grid-auto-flow:column;
    grid-auto-columns:minmax(var(--card-w), calc(var(--card-w) + 40px));
    gap:var(--gap);
    overflow-x:auto; overflow-y:hidden;
    scroll-snap-type:x mandatory;
    -webkit-overflow-scrolling:touch;
    padding:6px 2px 10px;
  }
  .sn-strip::-webkit-scrollbar { height:8px; }
  .sn-strip::-webkit-scrollbar-thumb { background:rgba(0,0,0,.15); border-radius:999px; }

  .sn-card {
    scroll-snap-align:start;
    display:block; width:100%;
    border-radius:var(--radius);
    background:#fff; box-shadow:var(--shadow);
    text-decoration:none; color:inherit;
    overflow:hidden; transform:translateZ(0);
    transition:transform .15s ease, box-shadow .15s ease;
  }
  .sn-card:hover, .sn-card:focus-visible { transform:translateY(-2px); box-shadow:0 10px 28px rgba(0,0,0,.18); outline:none; }
  .sn-media { position:relative; aspect-ratio:16/9; background:#eee; overflow:hidden; }
  .sn-media img:not(.sn-favicon) { width:100%; height:100%; object-fit:cover; display:block; }
  .sn-badge {
    position:absolute; left:10px; bottom:10px;
    background:rgba(0,0,0,.7); color:#fff; font-size:.75rem;
    padding:4px 8px; border-radius:999px; backdrop-filter:saturate(140%) blur(2px);
    display:flex; align-items:center; gap:6px;
  }
  .sn-body { padding:12px 14px 14px; display:grid; gap:8px; }
  .sn-title { font-size:.98rem; line-height:1.25; font-weight:600; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
  .sn-meta { font-size:.8rem; color:#666; display:flex; align-items:center; gap:8px; }
  .sn-dot { width:4px; height:4px; background:#bbb; border-radius:50%; display:inline-block; }
  .sn-favicon { width:14px; height:14px; border-radius:4px; }
  @media (max-width:480px) {
    .sn-section { --card-w: 240px; }
    .sn-header h2 { font-size:1rem; }
  }
</style>

<script>
  // DB articles injected from PHP (if any)
  const dbArticlesRaw = <?php
    echo json_encode($scroll_strip_articles, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  ?> || [];

  function fetchRSSArticlesForScrollStrip(feedUrl, category) {
    $.ajax({
      url: "rss_proxy.php", // PHP file that fetches RSS content
      method: "POST",
      cache: false,
      data: { feed: feedUrl },
      dataType: "json",
      success: function(response) {
        const articles = response.items || [];
        renderNewsStrip("newsStrip", articles);
      },
      error: function() {
        // If RSS fails too, you could optionally render nothing or a simple message.
        console.warn("RSS fetch failed for Scroll Strip");
      }
    });
  }

  function timeAgo(iso) {
    const diff = (Date.now() - new Date(iso).getTime())/1000;
    const units = [
      ["day", 86400],
      ["hour", 3600],
      ["min", 60]
    ];
    for (const [u, s] of units) {
      const v = Math.floor(diff / s);
      if (v >= 1) return `${v} ${u}${v>1?"s":""} ago`;
    }
    return "just now";
  }

  function renderNewsStrip(elId, articles) {
    const strip = document.getElementById(elId);
    if (!strip) return;

    strip.innerHTML = articles.map(a => {
      const title      = a.title || "";
      const publisher  = a.publisher || a.source || "Unknown";
      const link       = a.link || a.url || "#";
      const image      = a.image || a.image_url || "assets/img/news-placeholder.jpg";
      const pubDate    = a.pubDate || a.pub_date || new Date().toISOString();
      const pubForLink = a.pubDateForLink || a.pub_date || pubDate;

      const encodedUrl = encodeURIComponent(link);
      const encodedPub = encodeURIComponent(publisher);

      let newsroomLink = `newsroom.php?url=${encodedUrl}&category=Politics&publisher=${encodedPub}&pub_date=${encodeURIComponent(pubForLink)}`;

      // If this article came from the articles table, add db=1
      if (a.fromDb) {
        newsroomLink += '&db=1';
      }

      const faviconUrl = 'https://t0.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=http://' + encodedPub + '&size=64';

      return `
        <a class="sn-card" role="listitem" href="${newsroomLink}" rel="noopener noreferrer" aria-label="Open article: ${title}" data-loading>
          <div class="sn-media">
            <img src="${image}" alt="${title}" onerror="this.src = 'assets/img/news-placeholder.jpg';">
            <span class="sn-badge">
              <img src="${faviconUrl}" alt="${publisher} logo" class="sn-favicon">
              ${publisher}
            </span>
          </div>
          <div class="sn-body">
            <div class="sn-title">${title}</div>
            <div class="sn-meta">
              <span>${publisher}</span>
              <span class="sn-dot" aria-hidden="true"></span>
              <time datetime="${pubDate}">${timeAgo(pubDate)}</time>
            </div>
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
      if (e.key === "ArrowLeft")  strip.scrollBy({ left: -strip.clientWidth * 0.9, behavior: "smooth" });
    });

    // Arrow buttons
    const section = strip.closest(".sn-section");
    if (!section) return;
    section.querySelectorAll(".sn-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const dir = btn.dataset.dir === "right" ? 1 : -1;
        strip.scrollBy({ left: dir * strip.clientWidth * 0.9, behavior: "smooth" });
      });
    });
  }

  // --- INIT ---
  (function initScrollStrip() {
    if (Array.isArray(dbArticlesRaw) && dbArticlesRaw.length > 0) {
      // Use DB articles if we got any
      const mapped = dbArticlesRaw.map(row => ({
        title: row.title,
        publisher: row.publisher,
        link: row.url,
        image: row.image_url,
        pubDate: row.pub_date,
        pubDateForLink: row.pub_date,
        fromDb: true          // 👈 flag that this came from the DB
      }));
      renderNewsStrip("newsStrip", mapped);
    } else {
      // Fallback to RSS if DB unavailable or empty
      const rssUrl = "https://rss.app/feeds/tahaOzLGHPxMD9OC.xml";
      fetchRSSArticlesForScrollStrip(rssUrl);
    }
  })();
</script>
