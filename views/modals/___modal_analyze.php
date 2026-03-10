<div class="modal fade" id="analyzeModal" tabindex="-1" role="dialog" aria-labelledby="analyzeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="analyzeModalLabel">Analyze Any News Article</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="analyzeForm" method="get" action="newsroom.php">
        <div class="modal-body">

          <!-- Input mode -->
          <div class="form-group">
            <label class="d-block mb-2">Choose input type:</label>

            <div class="custom-control custom-radio custom-control-inline">
              <input
                type="radio"
                id="analyzeModeUrl"
                name="analyzeMode"
                class="custom-control-input"
                value="url"
                checked
              >
              <label class="custom-control-label" for="analyzeModeUrl">URL</label>
            </div>

            <div class="custom-control custom-radio custom-control-inline">
              <input
                type="radio"
                id="analyzeModeText"
                name="analyzeMode"
                class="custom-control-input"
                value="text"
              >
              <label class="custom-control-label" for="analyzeModeText">Pasted text</label>
            </div>
          </div>

          <!-- URL input -->
          <div class="form-group" id="analyzeUrlGroup">
            <label for="articleUrl">Enter article URL:</label>
            <input
              type="url"
              class="form-control"
              id="articleUrl"
              name="url"
              placeholder="https://example.com/article"
              required
            >
          </div>

          <!-- Text input -->
          <div class="form-group d-none" id="analyzeTextGroup">
            <label for="articleText">Paste article text:</label>
            <textarea
              class="form-control"
              id="articleText"
              name="text"
              rows="8"
              placeholder="Paste the article text here..."
            ></textarea>
            <small class="form-text text-muted">
              Paste the article body or excerpt you want to analyze.
            </small>
          </div>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-green text-black">Analyze</button>
        </div>
      </form>

    </div>
  </div>
</div>

<script>
(function () {
  const form = document.getElementById('analyzeForm');
  if (!form) return;

  const modeUrl = document.getElementById('analyzeModeUrl');
  const modeText = document.getElementById('analyzeModeText');

  const urlGroup = document.getElementById('analyzeUrlGroup');
  const textGroup = document.getElementById('analyzeTextGroup');

  const urlInput = document.getElementById('articleUrl');
  const textInput = document.getElementById('articleText');

  function updateAnalyzeMode() {
    const isTextMode = modeText.checked;

    if (isTextMode) {
      urlGroup.classList.add('d-none');
      textGroup.classList.remove('d-none');

      form.setAttribute('action', 'textroom.php');
      form.setAttribute('method', 'post');

      urlInput.required = false;
      urlInput.disabled = true;
      urlInput.value = '';

      textInput.disabled = false;
      textInput.required = true;
    } else {
      urlGroup.classList.remove('d-none');
      textGroup.classList.add('d-none');

      form.setAttribute('action', 'newsroom.php');
      form.setAttribute('method', 'get');

      textInput.required = false;
      textInput.disabled = true;

      urlInput.disabled = false;
      urlInput.required = true;
    }
  }

  modeUrl.addEventListener('change', updateAnalyzeMode);
  modeText.addEventListener('change', updateAnalyzeMode);

  updateAnalyzeMode();
})();
</script>