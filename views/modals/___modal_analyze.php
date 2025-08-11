<div class="modal fade" id="analyzeModal" tabindex="-1" role="dialog" aria-labelledby="analyzeModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title" id="analyzeModalLabel">Analyze Any News Article</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>

              <form id="analyzeForm">
                <div class="modal-body">
                  <div class="form-group">
                    <label for="articleUrl">Enter article URL:</label>
                    <input type="url" class="form-control" id="articleUrl" name="articleUrl" placeholder="https://example.com/article" required>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="submit" class="btn btn-green text-black">Analyze</button>
                </div>
              </form>

            </div>
          </div>
        </div>