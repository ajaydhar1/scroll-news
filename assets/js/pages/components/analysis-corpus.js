// js/sn-analysis-corpus.js
// Analysis → corpus table client-side filters + chips + magnets

document.addEventListener('DOMContentLoaded', function () {
  const rows = Array.from(document.querySelectorAll('.corpus-row'));
  if (!rows.length) return; // nothing to do

  const state = {
    entity: '',
    source: '',
    frame: '',
    title: '',
    sentiment: '',
    category: ''
  };

  const el = {
    topEntityChips: document.getElementById('topEntityChips'),
    topSourceChips: document.getElementById('topSourceChips'),
    topFrameChips: document.getElementById('topFrameChips'),
    entityInput: document.getElementById('entityInput'),
    sourceInput: document.getElementById('sourceInput'),
    titleInput: document.getElementById('titleInput'),
    sentimentSelect: document.getElementById('sentimentSelect'),
    corpusCategorySelect: document.getElementById('corpusCategorySelect'),
    active: document.getElementById('corpusActiveFilters'),
    countLine: document.getElementById('corpusCountLine'),
    clearBtn: document.getElementById('corpusClearBtn'),
    entityList: document.getElementById('entityList'),
    sourceList: document.getElementById('sourceList')
  };

  const norm = (s) => (s || '').toString().trim().toLowerCase().replace(/\s+/g, ' ');

  function getPipeSet(str) {
    const s = norm(str);
    if (!s) return new Set();
    return new Set(s.split('|').map(x => x.trim()).filter(Boolean));
  }

  function buildStats() {
    const entityCounts = new Map();
    const sourceCounts = new Map();
    const frameCounts = new Map();
    const categoryCounts = new Map();

    for (const r of rows) {
      const entities = getPipeSet(r.dataset.entityList);
      const frames   = getPipeSet(r.dataset.frameList);
      const src      = norm(r.dataset.source);
      const cat      = norm(r.dataset.category);

      for (const e of entities) entityCounts.set(e, (entityCounts.get(e) || 0) + 1);
      for (const t of frames)   frameCounts.set(t, (frameCounts.get(t) || 0) + 1);
      if (src) sourceCounts.set(src, (sourceCounts.get(src) || 0) + 1);
      if (cat) categoryCounts.set(cat, (categoryCounts.get(cat) || 0) + 1);
    }

    if (el.entityList) fillDatalist(el.entityList, entityCounts);
    if (el.sourceList) fillDatalist(el.sourceList, sourceCounts);

    if (el.corpusCategorySelect) fillCorpusCategorySelect(categoryCounts);

    if (el.topEntityChips) {
      renderTopChips(el.topEntityChips, entityCounts, 10, (v) => {
        state.entity = v;
        if (el.entityInput) el.entityInput.value = v;
        applyFilters();
      });
    }

    if (el.topSourceChips) {
      renderTopChips(el.topSourceChips, sourceCounts, 10, (v) => {
        state.source = v;
        if (el.sourceInput) el.sourceInput.value = v;
        applyFilters();
      });
    }

    if (el.topFrameChips) {
      renderTopChips(el.topFrameChips, frameCounts, 4, (v) => {
        state.frame = v;
        applyFilters();
      });
    }
  }

  function fillDatalist(datalistEl, countsMap) {
    const items = Array.from(countsMap.entries())
      .sort((a,b) => b[1] - a[1])
      .slice(0, 200)
      .map(([k]) => k);

    datalistEl.innerHTML = items
      .map(v => `<option value="${escapeHtml(v)}"></option>`)
      .join('');
  }

  function fillCorpusCategorySelect(categoryCounts) {
    const cats = Array.from(categoryCounts.entries())
      .sort((a,b) => b[1] - a[1])
      .map(([k]) => k);

    const keepFirst =
      el.corpusCategorySelect.querySelector('option[value=""]')?.outerHTML
      || '<option value="">All</option>';

    el.corpusCategorySelect.innerHTML =
      keepFirst + cats.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(titleCase(c))}</option>`).join('');
  }

  function renderTopChips(container, countsMap, n, onClick) {
    const top = Array.from(countsMap.entries())
      .sort((a,b) => b[1] - a[1])
      .slice(0, n);

    container.innerHTML = top.map(([label, count]) => {
      return `
        <button type="button" class="corpus-chip" data-value="${escapeHtml(label)}">
          ${escapeHtml(label)} <span class="text-muted">(${count})</span>
        </button>
      `;
    }).join('');

    container.querySelectorAll('button.corpus-chip').forEach(btn => {
      btn.addEventListener('click', () => onClick(btn.dataset.value));
    });
  }

  function matchesRow(row) {
    if (state.entity) {
      const entities = getPipeSet(row.dataset.entityList);
      if (!entities.has(state.entity)) return false;
    }

    if (state.source) {
      if (norm(row.dataset.source) !== state.source) return false;
    }

    if (state.frame) {
      const frames = getPipeSet(row.dataset.frameList);
      if (!frames.has(state.frame)) return false;
    }

    if (state.title) {
      const t = norm(row.dataset.title);
      if (!t.includes(state.title)) return false;
    }

    if (state.sentiment) {
      if (norm(row.dataset.sentiment) !== state.sentiment) return false;
    }

    if (state.category) {
      if (norm(row.dataset.category) !== state.category) return false;
    }

    return true;
  }

  function applyFilters() {
    const total = rows.length;
    let shown = 0;

    for (const r of rows) {
      const ok = matchesRow(r);
      r.style.display = ok ? '' : 'none';
      if (ok) shown++;
    }

    renderActiveFilters();
    if (el.countLine) el.countLine.textContent = `Showing ${shown} / ${total}`;
  }

  function renderActiveFilters() {
    if (!el.active) return;

    const chips = [];

    if (state.entity) chips.push(makeActiveChip('Entity', state.entity, () => { state.entity=''; if (el.entityInput) el.entityInput.value=''; applyFilters(); }));
    if (state.source) chips.push(makeActiveChip('Source', state.source, () => { state.source=''; if (el.sourceInput) el.sourceInput.value=''; applyFilters(); }));
    if (state.frame)  chips.push(makeActiveChip('Frame', state.frame,  () => { state.frame=''; applyFilters(); }));
    if (state.title)  chips.push(makeActiveChip('Title', state.title,  () => { state.title=''; if (el.titleInput) el.titleInput.value=''; applyFilters(); }));
    if (state.sentiment) chips.push(makeActiveChip('Sentiment', state.sentiment, () => { state.sentiment=''; if (el.sentimentSelect) el.sentimentSelect.value=''; applyFilters(); }));
    if (state.category)  chips.push(makeActiveChip('Category', state.category, () => { state.category=''; if (el.corpusCategorySelect) el.corpusCategorySelect.value=''; applyFilters(); }));

    el.active.innerHTML = chips.length ? chips.join('') : `<span class="text-muted small">No active filters</span>`;
  }

  function makeActiveChip(label, value, onClear) {
    const id = 'chip_' + Math.random().toString(16).slice(2);

    setTimeout(() => {
      const btn = document.getElementById(id);
      if (btn) btn.addEventListener('click', onClear);
    }, 0);

    return `
      <span class="badge bg-light text-dark border">
        ${escapeHtml(label)}: ${escapeHtml(value)}
        <button type="button" class="btn btn-sm p-0 ms-1" id="${id}" style="line-height:1;">×</button>
      </span>
    `;
  }

  function clearAll() {
    state.entity = '';
    state.source = '';
    state.frame = '';
    state.title = '';
    state.sentiment = '';
    state.category = '';

    if (el.entityInput) el.entityInput.value = '';
    if (el.sourceInput) el.sourceInput.value = '';
    if (el.titleInput) el.titleInput.value = '';
    if (el.sentimentSelect) el.sentimentSelect.value = '';
    if (el.corpusCategorySelect) el.corpusCategorySelect.value = '';

    applyFilters();
  }

  function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, (m) => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  function titleCase(s) {
    return (s || '').split(' ').map(w => w ? w[0].toUpperCase() + w.slice(1) : w).join(' ');
  }

  // Wire inputs (guard each element)
  if (el.entityInput) el.entityInput.addEventListener('input', () => { state.entity = norm(el.entityInput.value); applyFilters(); });
  if (el.sourceInput) el.sourceInput.addEventListener('input', () => { state.source = norm(el.sourceInput.value); applyFilters(); });
  if (el.titleInput)  el.titleInput.addEventListener('input', () => { state.title  = norm(el.titleInput.value); applyFilters(); });

  if (el.sentimentSelect) el.sentimentSelect.addEventListener('change', () => { state.sentiment = norm(el.sentimentSelect.value); applyFilters(); });
  if (el.corpusCategorySelect) el.corpusCategorySelect.addEventListener('change', () => { state.category = norm(el.corpusCategorySelect.value); applyFilters(); });

  if (el.clearBtn) el.clearBtn.addEventListener('click', clearAll);

  // Init
  buildStats();
  applyFilters();

  // Magnet buttons
  document.querySelectorAll('.sn-corpus-magnet').forEach(btn => {
    btn.addEventListener('click', function () {
      const entity    = norm(this.dataset.entity);
      const source    = norm(this.dataset.source);
      const frame     = norm(this.dataset.frame);
      const sentiment = norm(this.dataset.sentiment);

      // Reset dims (single dimension focus)
      state.entity = '';
      state.source = '';
      state.frame = '';
      state.sentiment = '';

      if (entity) {
        state.entity = entity;
        if (el.entityInput) el.entityInput.value = entity;
        if (el.sourceInput) el.sourceInput.value = '';
      }

      if (source) {
        state.source = source;
        if (el.sourceInput) el.sourceInput.value = source;
        if (el.entityInput) el.entityInput.value = '';
      }

      if (frame) {
        state.frame = frame;
      }

      if (sentiment) {
        state.sentiment = sentiment;
        if (el.sentimentSelect) el.sentimentSelect.value = sentiment;
      }

      applyFilters();

      const corpusCard = document.querySelector('.card.corpus');
      if (corpusCard) {
        const offset = 80;
        const top = corpusCard.getBoundingClientRect().top + window.pageYOffset - offset;

        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });
});