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
                          <select id="categorySelect" class="form-control">
                            <option value="https://rss.app/feeds/tahaOzLGHPxMD9OC.xml">Politics</option>
                            <option value="https://rss.app/feeds/tDmGft5qv7QGmWHv.xml">Business</option>
                            <option value="https://rss.app/feeds/t8coleFVxgPf56NK.xml">Technology</option>
                            <option value="https://rss.app/feeds/tCQMLQm6AHeQ5hJk.xml">Sports</option>
                            <option value="https://rss.app/feeds/tZPiCoHdJqTYlcZc.xml">Health</option>
                            <option value="https://rss.app/feeds/tLSguoVp4t7wa1eJ.xml">Science</option>
                            <option value="https://rss.app/feeds/tBiQM8jJROm1RYn3.xml">Entertainment</option>
                          </select>
                        </div>
                    </div>
                </div>
                <div id="rssArticles" class="row"></div>
              </div>
            </div>
          </div>
        </div>