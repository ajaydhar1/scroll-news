(() => {
  const frame = document.getElementById('ytFrame');
  const sel   = document.getElementById('ytTab');
  const section = document.getElementById('playlists');
  const channelList = document.getElementById('watchDeskChannels');
  const channelCount = document.getElementById('watchDeskCount');
  if (!frame || !sel || !section || !channelList) return;

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

  const channels = Array.from(sel.options).map((option, index) => {
    const label = option.textContent.trim();
    const match = label.match(/^(.+?)\s*\((.+)\)$/);
    const button = document.createElement('button');
    const number = document.createElement('span');
    const details = document.createElement('span');
    const publisher = document.createElement('span');
    const program = document.createElement('span');

    button.type = 'button';
    button.className = 'watch-desk-channel';
    button.setAttribute('aria-label', `Select ${label} playlist`);
    button.setAttribute('aria-pressed', 'false');
    number.className = 'watch-desk-channel-number';
    number.textContent = String(index + 1).padStart(2, '0');
    details.className = 'watch-desk-channel-details';
    publisher.className = 'watch-desk-channel-publisher';
    publisher.textContent = match ? match[1] : label;
    details.appendChild(publisher);

    if (match) {
      program.className = 'watch-desk-channel-program';
      program.textContent = match[2];
      details.appendChild(program);
    }

    button.append(number, details);
    button.addEventListener('click', () => {
      sel.selectedIndex = index;
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    });
    channelList.appendChild(button);

    return { button, option, label };
  });

  if (channelCount) channelCount.textContent = `${channels.length} channels`;

  const updateActiveChannel = () => {
    channels.forEach(({ button, option, label }) => {
      const active = option === sel.selectedOptions[0];
      button.setAttribute('aria-pressed', String(active));
      if (active) frame.title = `${label} playlist player`;
    });
  };

  const load = () => {
    frame.src = embed(toPlaylistId(sel.value));
    updateActiveChannel();
  };

  sel.addEventListener('change', load);
  section.classList.add('is-enhanced');
  load();
})();