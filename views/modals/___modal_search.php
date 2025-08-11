<div class="modal fade" id="searchNewsModal" tabindex="-1" role="dialog" aria-labelledby="searchNewsLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title" id="searchNewsLabel">Search News Articles</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span>&times;</span>
                </button>
              </div>

              <div class="modal-body">
                <div class="form-inline mb-3">
                  <input type="text" id="newsSearchInput" class="form-control mr-2" placeholder="Search for news articles..." style="flex: 1;">
                  <button id="searchNewsBtn" class="btn btn-green">Search</button>
                </div>

                <div class="table-responsive">
                  <table id="searchResultsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                      <tr>
                        <th>Headline</th>
                        <th>Publisher</th>
                        <th>Published</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Dynamically populated -->
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>
        </div>