// ============================
// Analyze Article module
// ============================

$('#analyzeForm').on('submit', function (e) {

  const mode = $('input[name="analyzeMode"]:checked').val();

  // URL mode
  if (mode === 'url') {

    e.preventDefault();

    const url = $('#articleUrl').val().trim();

    if (url) {
      const encoded = encodeURIComponent(url);
      window.location.href = `newsroom.php?url=${encoded}`;
    }

  }

  // Text mode
  if (mode === 'text') {

    e.preventDefault();

    const text = $('#articleText').val().trim();

    if (!text) return;

    const form = $('<form>', {
      method: 'POST',
      action: 'textroom.php'
    });

    const field = $('<textarea>', {
      name: 'text'
    }).val(text);

    form.append(field);
    $('body').append(form);
    form.submit();

  }

});


// ============================
// Browse News module
// ============================

function fetchRSSArticles(feedUrl, category) {
  $.ajax({
    url: "/api/rss_local.php",
    method: "POST",
    data: { feed: feedUrl },
    dataType: "json",
    success: function (response) {
      const articles = response.items || [];
      const container = $("#rssArticles");
      container.empty();

      if (articles.length === 0) {
        container.append("<p>No articles found.</p>");
        return;
      }

      articles.forEach((article) => {
        const filterOutPublisher = pubsToFilterOut.some((substring) =>
          (article.link || "").includes(substring)
        );

        if (!filterOutPublisher) {
          const encodedPub = encodeURIComponent(article.publisher || "");
          const faviconUrl =
            "https://t0.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=http://" +
            encodedPub +
            "&size=64";

          const card = `
            <div class="col-md-4 mb-4">
              <div class="card h-100">
                <img src="${article.image || "assets/img/news-placeholder.jpg"}"
                     class="card-img-top news-modal" alt=""
                     onerror="this.src='assets/img/news-placeholder.jpg';">
                <div class="card-body d-flex flex-column">
                  <h4 class="card-title mb-2">${article.title || ""}</h4>
                  <p class="card-text text-muted mb-1">
                    <img src="${faviconUrl}" alt="${encodedPub} logo" class="sn-favicon">
                    <small>
                      <a target="_blank" href="https://${article.publisher || ""}">${article.publisher || ""}</a>
                      ${article.pubDate ? " • " + timeElapsedString(article.pubDate) : ""}
                    </small>
                  </p>
                  <p class="card-text">${article.description || ""}</p>
                  <div class="row g-2 mt-auto">
                    <div class="col-6 d-grid browse-card-btn-col">
                      <a href="${article.link}" class="btn btn-secondary mt-auto w-100" target="_blank">Read Story</a>
                    </div>
                    <div class="col-6 d-grid browse-card-btn-col">
                      <a href="newsroom.php?url=${encodeURIComponent(article.link)}&category=${category}&pub_date=${article.pubDateForLink || ""}&db=1"
                         class="btn btn-green mt-auto w-100">Analyze</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;

          container.append(card);
        }
      });
    },

    error: function (xhr, textStatus, errorThrown) {
      console.group("fetchRSSArticles ERROR");
      console.log("textStatus:", textStatus);
      console.log("errorThrown:", errorThrown);
      console.log("HTTP status:", xhr.status);
      console.log("Response Content-Type:", xhr.getResponseHeader("Content-Type"));
      console.log("Response text (first 500):", (xhr.responseText || "").slice(0, 500));
      console.groupEnd();

      $("#rssArticles").empty().append(
        `<p class="text-danger">Failed to load feed (${xhr.status} ${textStatus}). Check console.</p>`
      );
    },

    complete: function (xhr) {
      // This runs even if JSON parsing fails (super useful)
      console.log("fetchRSSArticles complete:", {
        status: xhr.status,
        contentType: xhr.getResponseHeader("Content-Type"),
      });
    },

    timeout: 12000,
  });
}

// Shuffle articles
const shuffleBtn = document.getElementById("shuffleArticlesBtn");

function shuffleArticleCards() {

  const container = document.getElementById("rssArticles");
  if (!container) return;

  const cards = Array.from(container.children);

  for (let i = cards.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [cards[i], cards[j]] = [cards[j], cards[i]];
  }

  cards.forEach(card => container.appendChild(card));
}

shuffleBtn?.addEventListener("click", shuffleArticleCards);

$(document).ready(function () {
  const defaultFeed = $("#categorySelect").val();
  fetchRSSArticles(defaultFeed, "Politics");

  $("#categorySelect").change(function () {
    fetchRSSArticles($(this).val(), $(this).find(":selected").text());
  });
});

$(document).on("click", ".category-link", function (e) {
  e.preventDefault();

  // Get category from data attribute
  const category = $(this).data("category");

  var categoryArray = ["Politics", "Business", "Technology", "Sports", "Health", "Science", "Entertainment"];

  if (categoryArray.includes(category)) {

    // Get the RSS URL from the data attribute
    const rssUrl = $(this).data("category-url");

    // Set the dropdown in the modal to match this URL
    $("#categorySelect").val(rssUrl);

    // Open the modal
    $("#browseNewsModal").modal("show");

    // Trigger the article fetch
    fetchRSSArticles(rssUrl);

  }
});



// ============================
// Search News module
// ============================

let searchTable;

$("#searchNewsBtn").click(function () {
  const query = $("#newsSearchInput").val().trim();
  if (!query) return;

  fetchWithRetry(`search_news_proxy.php?q=${encodeURIComponent(query)}`, {
    method: 'GET',
    cache: 'no-store'
  })
    //.then(res => res.json())
    .then(data => {
      if (!data.items) return;

      const rows = data.items.map(article => {

        const filterOutPublisher = pubsToFilterOut.some(substring => article.link.includes(substring));

        if (!filterOutPublisher) {
          return [
            article.title,
            article.publisher,
            timeElapsedString(new Date(article.pubDate)),
            `<button class="btn btn-sm btn-green" onclick="analyzeNews('${article.link}', '${article.pubDateForLink}')">Analyze</button>`
          ];
        }
      });

      if (!searchTable) {
        searchTable = $('#searchResultsTable').DataTable({
          data: rows,
          columns: [
            { title: "Title" },
            { title: "Publisher" },
            { title: "Published" },
            { title: "Actions" }
          ]
        });
      } else {
        searchTable.clear();
        searchTable.rows.add(rows).draw();
      }

    })
    .catch(err => {
      alert("Failed to fetch.");
      console.error("Fetch error:", err);
    });
});

function fetchWithRetry(url, options = {}, retries = 3, delay = 1000) {
  return fetch(url, { cache: 'no-store', ...options })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .catch(err => {
      if (retries > 0) {
        return new Promise(resolve => setTimeout(resolve, delay)).then(() =>
          fetchWithRetry(url, options, retries - 1, delay)
        );
      } else {
        alert("Failed to fetch after retries.");
        throw err;
      }
    });
}

function analyzeNews(rssLink, pubDateForLink) {
  fetch(`get_real_url.php?link=${encodeURIComponent(rssLink)}`)
    .then(res => res.json())
    .then(data => {
      if (data.resolved_url) {
        window.location.href = `newsroom.php?url=${encodeURIComponent(data.resolved_url)}&pub_date=${pubDateForLink}`;
      } else {
        alert("Could not resolve article URL.");
      }
    });
}

/*
document.getElementById("newsSearchInput").addEventListener("keydown", function(event) {
  if (event.key === "Enter") {
      event.preventDefault(); // Prevent form submission if inside a form
        document.getElementById("searchNewsBtn").click(); // Simulate button click
    }
});
*/