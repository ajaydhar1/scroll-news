(() => {
  const frame = document.getElementById('ytFrame');
  const sel   = document.getElementById('ytTab');
  if (!frame || !sel) return;

  const toPlaylistId = (val) => {
    try {
      const u = new URL(val);
      return u.searchParams.get('list') || val;
    } catch {
      return val;
    }
  };

  const embed = (plId) =>
    `https://www.youtube.com/embed/videoseries?list=${encodeURIComponent(plId)}&rel=0&modestbranding=1`;

  const load = () => (frame.src = embed(toPlaylistId(sel.value)));

  sel.addEventListener('change', load);
  load();
})();