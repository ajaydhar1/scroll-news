// ============================
// Analyze Article module
// ============================

$('#analyzeForm').on('submit', function(e) {
	e.preventDefault();
	const url = $('#articleUrl').val().trim();
	if (url) {
  		const encoded = encodeURIComponent(url);
  		window.location.href = `newsroom.php?url=${encoded}`;
	}
});


// ============================
// Browse News module
// ============================

function fetchRSSArticles(feedUrl, category) {
  	$.ajax({
    	url: "/api/rss_proxy.php", // PHP file that fetches RSS content
    	method: "POST",
    	data: { feed: feedUrl },
    	dataType: "json", // This is the key addition
    	success: function(response) {
      		const articles = response.items || [];
      		const container = $("#rssArticles");
      		container.empty();

      		if (articles.length === 0) {
        		container.append('<p>No articles found.</p>');
        		return;
      		}

      		articles.forEach(article => {

            const filterOutPublisher = pubsToFilterOut.some(substring => article.link.includes(substring));

            if (!filterOutPublisher) {

              // Encode source for query string
              const encodedPub = encodeURIComponent(article.publisher);

              // Google favicon endpoint using the full URL (your working pattern)
              const faviconUrl = 'https://t0.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=http://' + encodedPub + '&size=64';

              const card = `
                <div class="col-md-4 mb-4">
                  <div class="card h-100">
                      <img src="${article.image || 'assets/img/news-placeholder.jpg'}" class="card-img-top news-modal" alt="" onerror="this.src = 'assets/img/news-placeholder.jpg';">
                      <div class="card-body d-flex flex-column">
                        <h4 class="card-title mb-2">${article.title}</h4>
                        <p class="card-text text-muted mb-1">
                        <img src="${faviconUrl}" alt="${encodedPub} logo" class="sn-favicon"><small><a target="_blank" href="https://${article.publisher}">${article.publisher}</a>${article.pubDate ? ' • ' + timeElapsedString(article.pubDate) : ''}</small></p>
                        <p class="card-text">${article.description}</p>
                        <a href="newsroom.php?url=${encodeURIComponent(article.link)}&category=${category}&pub_date=${article.pubDateForLink}" class="btn btn-green mt-auto">Analyze</a>
                      </div>
                  </div>
              </div>
            `;
            container.append(card);

            }

      		});
   		}
  	});
}

$(document).ready(function() {
	const defaultFeed = $("#categorySelect").val();
    fetchRSSArticles(defaultFeed, "Politics");

    $("#categorySelect").change(function() {
    	fetchRSSArticles($(this).val(), $(this).find(":selected").text());
    });
});

$(document).on("click", ".category-link", function(e) {
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