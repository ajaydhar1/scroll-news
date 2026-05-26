<div class="modal fade" id="browseNewsModal" tabindex="-1" role="dialog" aria-labelledby="browseNewsLabel" aria-hidden="true" style="">
  <div class="modal-dialog modal-dialog-scrollable modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Browse News by Category</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-lg-5">
            <div class="mb-3">
              <label for="categorySelect">Select Category:</label>
              <div class="d-flex align-items-center flex-wrap">
                <select id="categorySelect" class="form-control w-auto mr-2 mb-2">
                  <option value="/rss.php?category=Politics">🗳 Politics</option>
                  <option value="/rss.php?category=Business">💼 Business</option>
                  <option value="/rss.php?category=Technology">💻 Technology</option>
                  <option value="/rss.php?category=Sports">🏈 Sports</option>
                  <option value="/rss.php?category=Health">🩺 Health</option>
                  <option value="/rss.php?category=Science">🔬 Science</option>
                  <option value="/rss.php?category=Entertainment">🎬 Entertainment</option>
                </select>
                <button id="shuffleArticlesBtn" type="button" class="btn btn-light mb-2">
                  🔀 AI-powered Shuffle
                </button>
              </div>
            </div>
          </div>
        </div>
        <div id="rssArticles" class="row"></div>
      </div>
    </div>
  </div>
</div>


<script>
  window.isLoggedIn = <?php echo !empty($currentUser) ? 'true' : 'false'; ?>;
</script>