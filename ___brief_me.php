<style>
  #aiNewsLookupStrip .form-control.is-invalid {
    border-color: #dc3545;
  }
  #aiNewsLookupStrip .bg-white {
    /* keeps it light and thin */
    line-height: 1.2;
  }
</style>


<div class="small text-uppercase text-center text-muted mb-1" style="letter-spacing:.04em;">
  Quick Context
</div>

<!-- AI Mode News Lookup Strip (place between Persistent Card + First Look card) -->
<div class="container-fluid px-0 my-2" id="aiNewsLookupStrip">
  <div class="bg-light border rounded-3 px-3 py-2 d-flex flex-wrap align-items-center justify-content-center gap-2 text-center">
    <span class="text-muted mr-2 mb-1 mb-sm-0">
      What is happening in the news with respect to
    </span>

    <form class="d-flex flex-wrap align-items-center mb-0" id="aiNewsLookupForm">
      <input
        id="aiNewsLookupInput"
        type="text"
        class="form-control form-control-sm mr-1 mb-2 mb-sm-0"
        placeholder="Enter a news topic..."
        style="width: min(420px, 70vw);"
      />

      <button type="button" class="btn btn-sm btn-light" style="box-shadow: none !important;" id="aiNewsLookupBtn">
        Come up to speed
      </button>
    </form>
  </div>
</div>

<script>
(function() {
  const input = document.getElementById('aiNewsLookupInput');
  const btn = document.getElementById('aiNewsLookupBtn');

  function buildQuery(topic) {
    return `What is happening in the news with respect to ${topic}? Summarize the situation, key players, timeline, and current status.`;
  }

  function openLookup() {
    const topic = (input.value || '').trim();
    if (!topic) {
      input.focus();
      return;
    }

    const q = buildQuery(topic);

    // AI-leaning Google Search (unofficial)
    const url = `https://www.google.com/search?q=${encodeURIComponent(q)}&udm=50&hl=en`;

    window.open(url, '_blank', 'noopener,noreferrer');
  }

  btn.addEventListener('click', openLookup);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') openLookup();
    if (e.key === 'Escape') input.value = '';
  });
})();
</script>
