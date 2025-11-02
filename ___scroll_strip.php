<!-- ========== Article Strip (HTML + CSS + JS) ========== -->
<section class="sn-section">
  <div class="container-fluid">
    <div class="text-center">
        <h2 class="section-heading text-uppercase">Top Stories</h2>
        <!--<h3 class="section-subheading text-muted">Lorem ipsum dolor sit amet consectetur.</h3>-->
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
  .sn-media img { width:100%; height:100%; object-fit:cover; display:block; }
  .sn-badge {
    position:absolute; left:10px; bottom:10px;
    background:rgba(0,0,0,.7); color:#fff; font-size:.75rem;
    padding:4px 8px; border-radius:999px; backdrop-filter:saturate(140%) blur(2px);
  }
  .sn-body { padding:12px 14px 14px; display:grid; gap:8px; }
  .sn-title { font-size:.98rem; line-height:1.25; font-weight:600; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
  .sn-meta { font-size:.8rem; color:#666; display:flex; align-items:center; gap:8px; }
  .sn-dot { width:4px; height:4px; background:#bbb; border-radius:50%; display:inline-block; }
  @media (max-width:480px) {
    .sn-section { --card-w: 240px; }
    .sn-header h2 { font-size:1rem; }
  }
</style>

<script>
  // ---- Minimal example data (replace with your real feed) ----
  demoArticles = [
    {
      title: "Central banks signal cautious path as inflation cools",
      publisher: "Reuters",
      link: "https://example.com/a1",
      image: "https://picsum.photos/seed/reuters/800/450",
      pubDate: new Date(Date.now() - 60*60*1000).toISOString() // 1h ago
    },
    {
      title: "Wildfire season intensifies in the West amid heat wave",
      publisher: "AP News",
      link: "https://example.com/a2",
      image: "https://picsum.photos/seed/ap/800/450",
      pubDate: new Date(Date.now() - 3*60*60*1000).toISOString()
    },
    {
      title: "Breakthrough battery promises faster charging, longer life",
      publisher: "The Verge",
      link: "https://example.com/a3",
      image: "https://picsum.photos/seed/verge/800/450",
      pubDate: new Date(Date.now() - 7*60*60*1000).toISOString()
    },
    {
      title: "EU unveils framework on AI accountability and audits",
      publisher: "FT",
      link: "https://example.com/a4",
      image: "https://picsum.photos/seed/ft/800/450",
      pubDate: new Date(Date.now() - 26*60*60*1000).toISOString()
    }
  ];


  function fetchRSSArticlesForScrollStrip(feedUrl, category) {
    $.ajax({
      url: "rss_proxy.php", // PHP file that fetches RSS content
      method: "POST",
      cache: false,
      data: { feed: feedUrl },
      dataType: "json", // This is the key addition
      success: function(response) {
        const articles = response.items || [];

        renderNewsStrip("newsStrip", articles);

      }
    });
  }

  // ---- Render function ----
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
    strip.innerHTML = articles.map(a => {

      // Encode article URL for query string
      const encodedUrl = encodeURIComponent(a.link);

      // Encode source for query string
      const encodedPub = encodeURIComponent(a.publisher);

      // Build Scroll News newsroom link
      const newsroomLink = `newsroom.php?url=${encodedUrl}&category=Politics&publisher=${encodedPub}&pub_date=${a.pubDateForLink}`;

      return `
        <a class="sn-card" role="listitem" href="${newsroomLink}" rel="noopener noreferrer">
          <div class="sn-media">
            <img src="${a.image}" alt="${a.title}" onerror="this.src = 'assets/img/news-placeholder.jpg';">
            <span class="sn-badge">${a.publisher}</span>
          </div>
          <div class="sn-body">
            <div class="sn-title">${a.title}</div>
            <div class="sn-meta">
              <span>${a.publisher}</span>
              <span class="sn-dot" aria-hidden="true"></span>
              <time datetime="${a.pubDate}">${timeAgo(a.pubDate)}</time>
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
    section.querySelectorAll(".sn-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const dir = btn.dataset.dir === "right" ? 1 : -1;
        strip.scrollBy({ left: dir * strip.clientWidth * 0.9, behavior: "smooth" });
      });
    });
  }

  // --- INIT (swap demoArticles with your real data) ---

  // Trigger the article fetch
  let rssUrl = "https://rss.app/feeds/tahaOzLGHPxMD9OC.xml";
  fetchRSSArticlesForScrollStrip(rssUrl);

</script>
