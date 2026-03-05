/*!
 * Scroll News Mini HUD Player — YouTube Edition (Playlist Selector v2)
 * sn-mini-player-yt.js
 *
 * NEW ✨
 * - Playlist <select> to switch between multiple YouTube playlist IDs — no PHP arrays needed
 * - Per‑playlist resume: remembers last index & timestamp for each playlist
 * - Global prefs persist: mute, shuffle, repeat, collapsed, last active playlist, playing
 * - LocalStorage schema kept simple & robust; fail‑soft on parsing errors
 *
 * Usage:
 *   1) Edit PLAYLISTS below: [{ name: 'Chill', id: 'PLxxxxxxxx...' }, ...]
 *   2) <script src="/path/sn-mini-player-yt.js" defer></script>
 *   3) Optional: set START_COLLAPSED
 *
 * Notes:
 * - Titles are taken from getVideoData() (no Data API key required)
 * - Autoplay only resumes if HUD is open and you were playing last time
 */
(function(){
  'use strict';

  // ---- Desktop-only guard (no player on <= 1024px) ----
  const IS_DESKTOP = window.matchMedia('(min-width: 1025px)').matches;

  if (!IS_DESKTOP) {
    // Optional: expose a no-op API so other code can safely call SNPlayer.*
    window.SNPlayer = window.SNPlayer || {};
    ['hide','show','toggle','setPlaylist'].forEach(fn => {
      if (typeof window.SNPlayer[fn] !== 'function') {
        window.SNPlayer[fn] = () => {};
      }
    });

    // Do NOT load CSS, DOM, or YouTube on mobile/tablet
    return;
  }

  // ----------------- Config -----------------
  const PLAYLISTS = [
    { name: 'MS NOW (MSNBC)', id: 'PLDIVi-vBsOEy7nK-gNvoU8TS_BDOqBOxE' },
    { name: 'Fox News (Trump Administration)', id: 'PLGmceqLQ0UeYSzvsA6agwpkdoCMySiLA6' },
    { name: 'Bloomberg',  id: 'PLGaYlBJIOoa9DV4I6sC8R8bX4L0Jq16XZ' },
    { name: 'ABC News',  id: 'PLQOa26lW-uI8H1WxYPbSEqQJYoTRq2h5n' },
  ];
  const START_COLLAPSED = false;

  // Storage: bump version if schema changes
  const STORAGE_KEY = 'sn_hud_player_yt_v1';

  const NEWS_RESET_HOURS = 1.5;
  const NEWS_RESET_MS = NEWS_RESET_HOURS * 60 * 60 * 1000;

  // ----------------- Utilities -----------------
  const $  = (sel, ctx=document)=>ctx.querySelector(sel);
  const $$ = (sel, ctx=document)=>Array.from(ctx.querySelectorAll(sel));
  function safeGet(key){ try { return JSON.parse(localStorage.getItem(key)||'null'); } catch { return null; } }
  function safeSet(key,val){ try { localStorage.setItem(key, JSON.stringify(val)); } catch {} }
  function formatTime(s){ s = Math.max(0, Math.round(s||0)); const m = Math.floor(s/60); const r = s%60; return `${m}:${r.toString().padStart(2,'0')}`; }
  function isMobileish(){ return window.matchMedia('(max-width: 640px), (pointer:coarse)').matches; }

  // ----------------- Mount helpers (Newsroom / IntroJS-safe) -----------------
  function getHudMountEl(){
    return (
      document.getElementById('sn-mini-player-mount') ||
      document.querySelector('.page') ||
      document.body
    );
  }

  function mount(node){
    if (!node) return;
    const target = getHudMountEl();
    if (node.parentElement !== target) target.appendChild(node);
  }


  // SVG icons (currentColor-aware)
  const ICONS = {
    prev:  `<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M6 5v14M19 6l-9 6 9 6V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>`,
    play:  `<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7L8 5z"/></svg></span>`,
    pause: `<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg></span>`,
    next:  `<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M18 19V5M5 18l9-6-9-6v12z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>`,
    shuffle:`<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M16 3h5v5M3 8h6l6 8h7M21 16v5h-5M3 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>`,
    repeat: `<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M17 2l3 3-3 3M4 12a7 7 0 0 1 12-5h4M7 22l-3-3 3-3M20 12a7 7 0 0 1-12 5H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>`,
    repeatOne:`<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M17 2l3 3-3 3M4 12a7 7 0 0 1 12-5h4M7 22l-3-3 3-3M20 12a7 7 0 0 1-12 5H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="badge">1</span>`,
    repeatAll: `<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M17 2l3 3-3 3M4 12a7 7 0 0 1 12-5h4M7 22l-3-3 3-3M20 12a7 7 0 0 1-12 5H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="badge">∞</span>`,
    volumeOn:`<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 9v6h4l5 4V5L8 9H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16 9a4 4 0 0 1 0 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>`,
    volumeOff:`<span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 9v6h4l5 4V5L8 9H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M20 9l-6 6M14 9l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>`,
    widgetOpen:  `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9h16M4 15h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`, // “menu” (HUD expanded; press to collapse)
    widgetClosed:`<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`  // “plus” (HUD collapsed; press to expand)
  };

  // --- UI helpers ---
  function setBtnIcon(act, iconHTML){
    const el = root.querySelector(`.hud-btn[data-act="${act}"]`);
    if (el) el.innerHTML = iconHTML;
  }

  function isPlayingNow(){
    try { return player && player.getPlayerState() === YT.PlayerState.PLAYING; }
    catch { return !!getState().playing; } // fallback to persisted flag
  }

  let _muteTouchAt = 0;
  function afterMuteApplied(){
    // Let the iframe apply; then sync truth
    setTimeout(()=>{ syncMutedFromPlayer(); }, 0);  // microtask/next tick
  }

  function recentlyTouchedMute(){ return Date.now() - _muteTouchAt < 250; }

  function syncPlayIcon(){
    setBtnIcon('play', isPlayingNow() ? ICONS.pause : ICONS.play);
  }

  function isPlayerMuted(){
    try {
      if (player && typeof player.isMuted === 'function') return player.isMuted();
    } catch {}
    // Fallback ONLY if API lacks isMuted (rare)
    const s = getState();
    return !!s.prefs?.muted;
  }

  function syncMuteIcon(){
    setBtnIcon('mute', isPlayerMuted() ? ICONS.volumeOff : ICONS.volumeOn);
  }

  // Keep this: it reconciles iframe-click reality -> prefs + icon
  function syncMutedFromPlayer(){
    const muted = isPlayerMuted();
    const s = getState();
    if (!!s.prefs?.muted !== muted) savePrefs({ muted }); // persist truth
    syncMuteIcon();
  }

  let mutePoll = null;
  function startMutePoll(){
    if (mutePoll) return;
    mutePoll = setInterval(()=>{
      if (!player) return;
      if (recentlyTouchedMute()) return;  // don’t fight the fresh toggle
      if (!isPlayingNow()) return;        // optional: poll only while playing
      syncMutedFromPlayer();
    }, 600);
  }
  function stopMutePoll(){
    if (mutePoll){ clearInterval(mutePoll); mutePoll = null; }
  }

  let widget = null;
  function ensureWidget(){            // desktop-only creator
    if (widget) return;
    widget = document.createElement('button');
    widget.className = 'hud-widget';
    widget.type = 'button';
    widget.setAttribute('aria-label','Toggle media player');
    widget.setAttribute('aria-pressed','false');
    widget.innerHTML = ICONS.widgetClosed;
    appendWhenReady(widget);

    widget.addEventListener('click', ()=>{
      shell().classList.remove('hud-hidden');
      shell().classList.toggle('hud-collapsed');
      //persist();
      syncWidget();
    });
  }

  function loadYTAPI(){
    return new Promise((resolve,reject)=>{
      if (window.YT && window.YT.Player) return resolve();
      const tag = document.createElement('script');
      tag.src = 'https://www.youtube.com/iframe_api';
      tag.onerror = reject;
      document.head.appendChild(tag);
      const prev = window.onYouTubeIframeAPIReady;
      window.onYouTubeIframeAPIReady = function(){
        if (typeof prev === 'function') try { prev(); } catch {}
        resolve();
      };
    });
  }

  // Poll until YouTube actually loads the playlist
  function waitForPlaylist(player, tries=30){
    return new Promise((resolve,reject)=>{
      (function tick(n){
        try{
          const pl = player.getPlaylist && player.getPlaylist();
          if (pl && pl.length){ resolve(pl); return; }
        }catch{}
        if (n<=0) return reject(new Error('Playlist not ready'));
        setTimeout(()=>tick(n-1), 150);
      })(tries);
    });
  }

  function maybeResetForFreshNews(){
    const now = Date.now();
    const s = getState();

    const last = s?.ui?.lastNewsResetTs || 0;
    const due  = !last || (now - last) > NEWS_RESET_MS;
    if (!due) return;

    const firstPlaylist = PLAYLISTS[0]?.id || s.active;
    if (!firstPlaylist) return;

    // Reset to first playlist + first video + start time
    s.active = firstPlaylist;
    if (!s.playlists) s.playlists = {};
    s.playlists[firstPlaylist] = { index: 0, seconds: 0, ts: now };

    // Mark the reset time (store in ui so it’s stable with your schema)
    if (!s.ui) s.ui = {};
    s.ui.lastNewsResetTs = now;

    s.active = firstPlaylist;          // ✅ must update active

    // Optional: make sure it actually plays after reset
    s.playing = true;

    putState(s);
  }

  function syncPlaylistSelectFromState(){
    try {
      const s = getState();
      const el = selPL();
      if (!el) return;
      if (el.value !== s.active) el.value = s.active;
    } catch {}
  }

  // --------------- Persistent state (schema) ---------------
  //
  // {
  //   active: 'PLAYLIST_ID',
  //   ui: { collapsed: bool },
  //   prefs: { muted: bool, shuffle: bool, repeat: 'none'|'one'|'all' },
  //   playing: bool,
  //   playlists: {
  //      [playlistId]: { index: number, seconds: number, ts: number }
  //   }
  // }
  //
  function defaultState(){
    const first = PLAYLISTS[0]?.id || '';
    return { active:first, ui:{collapsed:false}, prefs:{muted:true, shuffle:false, repeat:'none'}, playing:false, playlists:{} };
  }
  function getState(){ return Object.assign(defaultState(), safeGet(STORAGE_KEY)||{}); }
  function putState(next){ safeSet(STORAGE_KEY, next); }

  // Helpers
  function saveUICollapsed(collapsed){ const s = getState(); s.ui.collapsed = !!collapsed; putState(s); }
  function savePrefs(partial){ const s = getState(); s.prefs = Object.assign({}, s.prefs, partial||{}); putState(s); }
  function saveActivePlaylist(id){ const s = getState(); s.active = id; putState(s); }
  function savePlaying(flag){ const s = getState(); s.playing = !!flag; putState(s); }
  function savePlaylistProgress(id, index, seconds){
    const s = getState();
    if (!s.playlists) s.playlists = {};
    s.playlists[id] = { index: Math.max(0, index|0), seconds: Math.max(0, Math.round(seconds||0)), ts: Date.now() };
    putState(s);
  }
  function getPlaylistProgress(id){
    const s = getState();
    return s.playlists?.[id] || { index:0, seconds:0 };
  }

  // ----------------- Inject CSS/HTML -----------------
  const css = `
.hud-mini-player{
  position:fixed;
  right:16px;
  bottom:69px;
  z-index:600;
  width:320px;
  color:#f9fbff;
  font:500 14px/1.4 system-ui, -apple-system, 'Segoe UI', Roboto, Arial;
}
.hud-shell{
  background:radial-gradient(circle at top left, rgba(23,213,255,.14), rgba(5,7,20,.96));
  border:1px solid rgba(23,213,255,.45);
  border-radius:14px;
  box-shadow:0 14px 40px rgba(3,6,25,.75);
  overflow:hidden;
}
.hud-header{
  display:flex;
  align-items:center;
  gap:.5rem;
  padding:.5rem .75rem;
  background:linear-gradient(to right, rgba(5,7,20,.95), rgba(17,20,45,.92));
  border-bottom:1px solid rgba(255,255,255,.06);
}
.hud-close{
  appearance:none;
  border:0;
  background:transparent;
  color:rgba(249,251,255,.85);
  cursor:pointer;
  font-size:18px;
  line-height:1;
  width:28px;
  height:28px;
  border-radius:8px;
  display:flex;
  align-items:center;
  justify-content:center;
  margin-left:auto;
}
.hud-close:hover{
  background:rgba(255,255,255,.08);
  color:#fff;
}
.hud-close:active{
  transform:scale(.96);
}
.hud-dot{
  width:8px;
  height:8px;
  border-radius:999px;
  background:#17d5ff;
  box-shadow:0 0 0 2px rgba(23,213,255,.35);
}
  .hud-title{ font-weight:600; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display: none; }
  .hud-collapse{ appearance:none; border:0; background:transparent; color:#aaa; cursor:pointer; font-size:16px; line-height:1; padding:4px; }
.hud-subbar{
  display:flex;
  align-items:center;
  gap:.5rem;
  padding:.4rem .75rem;
  background:linear-gradient(to right, rgba(5,7,20,.96), rgba(10,16,40,.96));
  border-bottom:1px solid rgba(255,255,255,.05);
}
  .hud-link { display:inline-flex; align-items:center; gap:.3rem; font-weight:600; font-size:12px; line-height:1; padding:.35rem .55rem; border-radius:999px; border:1px solid rgba(0,0,0,.15); text-decoration:none; transition:transform .08s ease, filter .12s ease, opacity .12s; }
  .hud-link-youtube{ background:#e41a39; color:#fff; }
  .hud-link:hover{ /* filter:brightness(.95); */ color: #fff; }
  .hud-link:active{ transform:translateY(1px); }
  .hud-link[aria-disabled="true"]{ opacity:.5; pointer-events:none; }
  @media (max-width:480px){ .hud-link{ font-size:11px; padding:.3rem .5rem; } }
  .hud-body{ display:grid; grid-template-rows:auto auto auto; gap:.5rem; padding:.5rem; }
  .hud-iframe-wrap{ position:relative; width:100%; padding-top:56.25%; background:#000; border-radius:10px; overflow:hidden; }
  .hud-iframe-wrap iframe, .hud-iframe-wrap #hudYT{ position:absolute; inset:0; width:100%; height:100%; border:0; }
  .hud-controls{ display:flex; align-items:center; gap:.4rem; }
.hud-btn{
  appearance:none;
  border:0;
  border-radius:10px;
  background:rgba(18,24,56,.95);
  color:#f4f7ff;
  padding:.45rem .55rem;
  cursor:pointer;
  transition:transform .1s ease, background .15s ease, box-shadow .15s ease;
}
.hud-btn:hover{
  background:rgba(27,36,88,1);
  box-shadow:0 0 0 1px rgba(23,213,255,.45);
}
  .hud-btn:active{ transform:scale(.98); }
.hud-btn.tog-on{
  background:linear-gradient(135deg, #17d5ff, #7c5cff);
  color:#050816;
  box-shadow:0 0 0 1px rgba(255,255,255,.3);
}  
  .hud-seek{ flex:1; display:flex; align-items:center; gap:.4rem; }
.hud-range{
  width:100%;
  accent-color:#17d5ff;
}
  .hud-time{ font-variant-numeric: tabular-nums; color:#bbb; font-size:12px; min-width:84px; text-align:right; }
  .hud-select{ appearance:none; border:1px solid rgba(255,255,255,.12); background:#141421; color:#eee; border-radius:8px; padding:.35rem .6rem; font-size:12px; }
  .hud-select:focus{ outline:2px solid rgba(56,132,255,.4); }
  .hud-select-wrap{ display:flex; align-items:center; gap:.5rem; }
  .hud-collapsed .hud-body{ display:none; }
  .hud-collapsed .hud-title{ opacity:.9; }
  @media (max-width:480px){ .hud-mini-player{ width:92vw; right:4vw; bottom:12px; } .hud-title{ display:none; } }
  .hud-hidden{ display:none !important; }
  @media (max-width:480px){ .hud-mini-player{ width:92vw; right:4vw; bottom:12px; } }
  /* Hide the old header caret */
  .hud-collapse{ display:none !important; }
  .hud-btn{ cursor:pointer !important; display:flex !important; }
  .hud-btn svg{ width:18px; height:18px; display:block; }
  .hud-btn .icon{ display:inline-flex; align-items:center; justify-content:center; margin:auto !important;}
  .hud-btn .badge{ position:absolute; right:4px; bottom:4px; font-size:10px; line-height:1; padding:1px 4px; border-radius:999px; background:rgba(255,255,255,.9); color:#000; font-weight:700; }
  .hud-btn.icon-btn{ position:relative; padding:.45rem .55rem; height:50px; width:100%; }
  .hud-btn.tog-on{ background:#2b2b4a; color:#fff; }
.hud-widget{
  position:fixed;
  right:16px;
  bottom:16px;
  z-index:610;
  width:46px;
  height:46px;
  border-radius:999px;
  display:flex;
  align-items:center;
  justify-content:center;
  border:1px solid rgba(23,213,255,.5);
  background: radial-gradient(circle at top, rgba(23,213,255,.16), rgba(5,7,20,.96));
  backdrop-filter: blur(14px) saturate(1.2);
  box-shadow: 0 14px 40px rgba(3,6,25,.9);
  color:#f9fbff;
  cursor:pointer;
  user-select:none;
  transition: transform .08s ease, filter .15s ease, opacity .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.hud-widget:hover{
  filter:brightness(1.07);
  box-shadow:0 16px 48px rgba(10,90,140,.9);
}
.hud-widget:active{
  transform: scale(.97);
}
.hud-widget svg{ width:20px; height:20px; display:block; }
.hud-widget[aria-pressed="true"]{
  border-color: rgba(124,92,255,.8);
  box-shadow:0 16px 50px rgba(124,92,255,.35);
}


  .hud-widget:hover{ filter:brightness(1.05); }
  .hud-widget:active{ transform: scale(.98); }
  .hud-widget svg{ width:20px; height:20px; display:block; }
  .hud-widget[aria-pressed="true"]{ /* pressed = HUD expanded */ border-color: rgba(23,213,255,.6); box-shadow: 0 12px 36px rgba(23,213,255,.20); }
  @media (min-width: 481px){ .hud-widget{ bottom:16px; } /* stays below HUD */ }
  @media (max-width: 480px){ .hud-widget{ right:4vw; bottom:12px; } }
  @media (max-width: 640px){ .hud-widget{ display:none; } }


  @media (max-width:1024px) {
    .hud-mini-player {
      display: none;
    }
    .hud-widget {
      display: none !important;
    }
  }
  `;
  const style = document.createElement('style'); style.textContent = css; document.head.appendChild(style);

  const root = document.createElement('div');
  root.className = 'hud-mini-player';
  root.innerHTML = `
    <div class="hud-shell hud-hidden ${START_COLLAPSED ? 'hud-collapsed' : ''}" data-step="4" data-intro="News media player">
      <div class="hud-header">
        <div class="hud-dot" title="connected"></div>
        <div class="hud-select-wrap">
          <label style="font-size:12px;color:#aaa">Playlists</label>
          <select class="hud-select" id="hudPlaylist"></select>
        </div>
        <div class="hud-title">Loading YouTube…</div>

        <!-- ✅ Close button -->
        <button class="hud-close" type="button" aria-label="Close media player" title="Close">×</button>

        <button class="hud-collapse" title="Collapse/Expand">▾</button>
      </div>
      <div class="hud-subbar">
        <a class="hud-link hud-link-youtube" href="https://youtube.com/" target="_blank" rel="noopener" aria-label="Open in YouTube">YouTube ↗</a>
      </div>
      <div class="hud-body">
        <div class="hud-iframe-wrap">
          <div id="hudYT"></div>
        </div>
        <div class="hud-controls">
          <button class="hud-btn" data-act="prev" title="Previous">⏮</button>
          <button class="hud-btn" data-act="play" title="Play/Pause">▶</button>
          <button class="hud-btn" data-act="next" title="Next">⏭</button>
          <button class="hud-btn" data-act="shuffle" title="Shuffle">🔀</button>
          <button class="hud-btn" data-act="repeat" title="Repeat: none">🔁</button>
          <button class="hud-btn" data-act="mute" title="Mute/Unmute">🔇</button>
        </div>
        <div class="hud-controls">
          <div class="hud-seek"><input class="hud-range" type="range" min="0" max="1000" value="0" step="1"></div>
          <div class="hud-time">0:00 / 0:00</div>
        </div>
      </div>
    </div>
  `;

  function applyIconSet(){
    ['prev','play','next','shuffle','repeat','mute'].forEach(a=>{
      const b = root.querySelector(`[data-act="${a}"]`);
      if (b) b.classList.add('icon-btn');
    });
    setBtnIcon('prev',   ICONS.prev);
    setBtnIcon('play',   ICONS.play);        // will sync when state changes
    setBtnIcon('next',   ICONS.next);
    setBtnIcon('shuffle',ICONS.shuffle);
    setBtnIcon('repeat', ICONS.repeat);      // will sync by setRepeatLabel()
    // initial mute icon: prefer saved prefs (default muted:true)
    const s = getState();
    setBtnIcon('mute',   (s.prefs?.muted ? ICONS.volumeOff : ICONS.volumeOn));
  }
  document.addEventListener('DOMContentLoaded', applyIconSet);

    // Floating widget button
  widget = document.createElement('button');
  widget.className = 'hud-widget';
  widget.type = 'button';
  widget.setAttribute('aria-label','Toggle media player');
  widget.setAttribute('aria-pressed','false'); // true when HUD expanded
  widget.innerHTML = ICONS.widgetClosed;

  document.addEventListener('DOMContentLoaded', () => {
    mount(widget);
    widget.classList.remove('hud-hidden');
    syncWidget();
  });

  widget.addEventListener('click', () => {
    const c = shell();

    // If hidden, open expanded (don’t collapse on first click)
    if (c.classList.contains('hud-hidden')) {
      c.classList.remove('hud-hidden');
      c.classList.remove('hud-collapsed');
    } else {
      // Otherwise, toggle collapsed/expanded
      c.classList.toggle('hud-collapsed');
    }

    saveUICollapsed(c.classList.contains('hud-collapsed'));
    syncWidget();
  });

  function isCollapsed(){ return shell().classList.contains('hud-collapsed'); }

  function syncWidget(){
    // If the widget doesn't exist (mobile), do nothing
    if (!widget) return;

    const collapsed = shell().classList.contains('hud-collapsed');
    const hidden    = shell().classList.contains('hud-hidden');

    //widget.classList.toggle('hud-hidden', hidden);
    widget.setAttribute('aria-pressed', String(!collapsed && !hidden));
    widget.innerHTML = (collapsed || hidden) ? ICONS.widgetClosed : ICONS.widgetOpen;
  }


  // Also keep widget in sync when code collapses/expands elsewhere
  document.addEventListener('click', (e)=>{
    // If you still have any other toggles, they’ll trigger this; otherwise harmless
    if (root.contains(e.target)) setTimeout(syncWidget, 0);
  });

  // ----------------- State -----------------
  let player = null;
  let durationSec = 0;
  let polling = null;
  let isPlayingFlag = false;
  let _switchNonce = 0; // protects against one-behind race

  const shell = ()=>$('.hud-shell', root);
  const titleEl = ()=>$('.hud-title', root);
  const selPL  = ()=>$('#hudPlaylist', root);
  const btnPlay = ()=>$('.hud-btn[data-act="play"]', root);
  const btnShuffle = ()=>$('.hud-btn[data-act="shuffle"]', root);
  const btnRepeat = ()=>$('.hud-btn[data-act="repeat"]', root);
  const range  = ()=>$('.hud-range', root);
  const timeEl = ()=>$('.hud-time', root);
  const container = ()=>$('#hudYT', root);

  function initPlaylistSelect(){
    const s = getState();
    selPL().innerHTML = PLAYLISTS.map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
    const has = PLAYLISTS.find(p=>p.id===s.active);
    selPL().value = has ? s.active : (PLAYLISTS[0]?.id || '');
    if (!has) saveActivePlaylist(selPL().value);
  }

  function setRepeatLabel(rep){
    const map = { none:'Repeat: none', one:'Repeat: one', all:'Repeat: all' };
    btnRepeat().title = map[rep] || 'Repeat: none';
    if (rep === 'one')      setBtnIcon('repeat', ICONS.repeatOne);
    else if (rep === 'all') setBtnIcon('repeat', ICONS.repeatAll);
    else                    setBtnIcon('repeat', ICONS.repeat);
  }

  function applyShuffleLoop() {
    try {
      const { shuffle, repeat } = getState().prefs || {};
      // Set desired state (safe even before list exists)
      if (player?.setShuffle) player.setShuffle(!!shuffle);
      // Don't call shufflePlaylist() here anymore; wait until after cueActivePlaylist()
      if (player?.setLoop) player.setLoop(repeat === 'all');

      btnShuffle().classList.toggle('tog-on', !!shuffle);
      setRepeatLabel(repeat || 'none');
    } catch {}
  }

  async function ensureShuffleAppliedWithoutAutoplay() {
    const { shuffle } = getState().prefs || {};
    if (!shuffle || !player) return;

    try {
      // Apply shuffle on an attached list
      if (player.setShuffle) player.setShuffle(true);
      if (player.shufflePlaylist) player.shufflePlaylist();

      // “Poke” the internal queue so Next honors shuffle,
      // but don't start playback if autoplay is off.
      const wasPlaying = player.getPlayerState?.() === YT.PlayerState.PLAYING;
      const curIdx = player.getPlaylistIndex?.() ?? 0;

      // hop to a random index once to force a shuffled position
      const list = player.getPlaylist?.() || [];
      if (list.length > 1) {
        const rand = Math.floor(Math.random() * list.length);
        if (rand !== curIdx && player.playVideoAt) player.playVideoAt(rand);
      }

      // keep it paused if we were paused
      if (!wasPlaying) {
        try { player.pauseVideo?.(); } catch {}
      }
    } catch (e) {
      console.warn('[YT shuffle ensure] noop:', e);
    }

    // Reflect UI state
    btnShuffle().classList.toggle('tog-on', true);
  }

  function updateTitle(){
    try {
      const d = player.getVideoData();
      titleEl().textContent = d && d.title ? d.title : 'Playing…';
      titleEl().title = d && d.title ? d.title : 'Playing…';
    } catch { 
      titleEl().textContent = 'Playing…';
      titleEl().title = 'Playing…';
    }
  }

  function startPolling(){
    stopPolling();
    polling = setInterval(()=>{
      if (!player) return;
      try {
        const t = player.getCurrentTime ? player.getCurrentTime() : 0;
        durationSec = player.getDuration ? Math.round(player.getDuration() || 0) : 0;
        if (durationSec > 0){
          const pos = Math.round((t / durationSec) * 1000);
          range().value = pos;
          timeEl().textContent = `${formatTime(t)} / ${formatTime(durationSec)}`;
        }
        // persist progress every ~1s
        const now = Date.now();
        if (!startPolling._last || now - startPolling._last > 1000){
          startPolling._last = now;
          const cur = getState();
          const idx = (player && player.getPlaylistIndex) ? player.getPlaylistIndex() : (getPlaylistProgress(cur.active).index||0);
          savePlaylistProgress(cur.active, idx, t);
        }
      } catch {}
    }, 500);
  }
  function stopPolling(){ if (polling){ clearInterval(polling); polling = null; } }

  async function cueActivePlaylist({autoPlay=false, nonce}={}){
    const s = getState();
    const { index, seconds } = getPlaylistProgress(s.active);
    try{
      // Use loadPlaylist to force switching immediately; avoid stale cue
      if (player.loadPlaylist){
        player.loadPlaylist({ list:s.active, listType:'playlist', index:index||0, startSeconds:Math.max(0, seconds||0) });
      } else if (player.cuePlaylist){
        player.cuePlaylist({ listType:'playlist', list:s.active, index:index||0, startSeconds:Math.max(0, seconds||0) });
      }
      await waitForPlaylist(player).catch(()=>{});
      // If another switch started, abort finishing this one
      if (typeof nonce==='number' && nonce !== _switchNonce) return;

      if (autoPlay || s.playing){
        const {muted} = s.prefs; if (muted) player.mute(); else player.unMute();
        player.playVideo(); isPlayingFlag = true; btnPlay().textContent = '⏸';
      } else {
        player.pauseVideo(); isPlayingFlag = false; btnPlay().textContent = '▶';
      }
      updateTitle();
    }catch(e){ console.warn('cueActivePlaylist', e); }
  }

  function handleStateChange(e){
    const YTP = window.YT && window.YT.PlayerState; if (!YTP) return;
    const st = getState();

    if (e.data === YTP.PLAYING || e.data === YTP.CUED){
      try{
        const idx = player.getPlaylistIndex ? player.getPlaylistIndex() : 0;
        const t   = player.getCurrentTime ? player.getCurrentTime() : 0;
        savePlaylistProgress(st.active, idx, t);
      }catch{}
    }

    if (e.data === YTP.PLAYING){
      savePlaying(true);
      isPlayingFlag = true;
      syncPlayIcon();
      syncMutedFromPlayer();    // <— critical: reflect iframe click reality
      updateTitle();
      startPolling();
      startMutePoll();          // optional robustness
    } else if (e.data === YTP.PAUSED){
      savePlaying(false);
      isPlayingFlag = false;
      syncPlayIcon();
      syncMutedFromPlayer();    // harmless, keeps UI true
      try{
        const t = player.getCurrentTime ? player.getCurrentTime() : 0;
        const idx = player.getPlaylistIndex ? player.getPlaylistIndex() : 0;
        savePlaylistProgress(st.active, idx, t);
      }catch{}
    } else if (e.data === YTP.ENDED){
      if ((st.prefs.repeat||'none') === 'one'){ try { player.playVideo(); } catch{} }
      try{
        const t = player.getCurrentTime ? player.getCurrentTime() : 0;
        const idx = player.getPlaylistIndex ? player.getPlaylistIndex() : 0;
        savePlaylistProgress(st.active, idx, t);
      }catch{}
      updateTitle();
    }
  }

  function setSelectDisabled(disabled){ try{ selPL().disabled = !!disabled; selPL().style.opacity = disabled ? .6 : 1; }catch{} }
  async function switchToPlaylist(id){
    if (!PLAYLISTS.find(p=>p.id===id)) return;
    setSelectDisabled(true);
    _switchNonce++;
    const myNonce = _switchNonce;
    saveActivePlaylist(id);
    // stop current immediately to reduce "account in use" transient
    try{ player && player.stopVideo && player.stopVideo(); }catch{}
    await cueActivePlaylist({ autoPlay:true, nonce: myNonce });
    // Re-enable only if still current
    if (myNonce === _switchNonce) setSelectDisabled(false);
  }

  // ----------------- Boot -----------------
  document.addEventListener('DOMContentLoaded', async ()=>{
    mount(root);
    titleEl().textContent = 'Loading YouTube…';
    maybeResetForFreshNews();
    initPlaylistSelect();
    syncPlaylistSelectFromState();

    const s0 = getState();
    if (typeof s0.ui.collapsed === 'boolean'){
      shell().classList.toggle('hud-collapsed', !!s0.ui.collapsed);
    } else if (START_COLLAPSED || isMobileish()) {
      shell().classList.add('hud-collapsed');
      saveUICollapsed(true);
    }
    syncWidget();

    try{
      await loadYTAPI();
      player = new YT.Player(container(), {
        width: '100%', height: '100%',
        playerVars: {
          autoplay: 1,          // ✅ request autoplay
          controls: 0,
          playsinline: 1,
          modestbranding: 1,
          rel: 0
        },
        events: {
          onReady: async ()=> {
            const s = getState();

            // ✅ FORCE mute on first paint so autoplay is allowed
            try {
              player.mute();
              savePrefs({ muted: true }); // keep prefs in sync
            } catch {}

            applyShuffleLoop();
            shell().classList.remove('hud-hidden');
            syncWidget();

            // ✅ cue + autoplay on load
            syncPlaylistSelectFromState();
            await cueActivePlaylist({ autoPlay: true, nonce: _switchNonce });
            syncPlaylistSelectFromState();

            // optional: keep shuffle tweak but DON'T force pause
            await ensureShuffleAppliedWithoutAutoplay();

            startPolling();
            startMutePoll();
            syncMutedFromPlayer(); // align icon with real mute state
            syncPlayIcon();
          },
          onStateChange: handleStateChange
        }
      });
    }catch(e){
      titleEl().textContent = 'YouTube API failed';
      console.error(e);
    }
  });

  document.addEventListener('change', async (e)=>{
    if (!root.contains(e.target)) return;
    if (e.target === selPL()){
      const id = selPL().value;
      await switchToPlaylist(id);
    }
  });

  document.addEventListener('click', (e)=>{
    if (!root.contains(e.target)) return;

    const closeBtn = e.target.closest('.hud-close');
    if (closeBtn) {
      // Do EXACTLY what the widget does (collapse/expand)
      shell().classList.remove('hud-hidden');
      shell().classList.toggle('hud-collapsed');
      saveUICollapsed(shell().classList.contains('hud-collapsed'));
      syncWidget();
      return;
    }

    const btn = e.target.closest('.hud-btn');
    const isCollapse = e.target.closest('.hud-collapse');
    if (isCollapse){
      const c = shell(); c.classList.toggle('hud-collapsed');
      e.target.textContent = c.classList.contains('hud-collapsed') ? '▴' : '▾';
      saveUICollapsed(c.classList.contains('hud-collapsed'));
      syncWidget();
      return;
    }
    if (!btn || !player) return;
    const act = btn.dataset.act;

    const state = getState();

    if (act === 'play'){
      try{
        const stp = player.getPlayerState();
        const YTP = YT.PlayerState;
        const s   = getState();
        if (s.prefs.muted) player.mute(); else player.unMute();

        if (stp === YTP.PAUSED || stp === YTP.CUED || stp === YTP.BUFFERING){
          player.playVideo(); savePlaying(true);
        } else if (stp === YTP.PLAYING){
          player.pauseVideo(); savePlaying(false);
        } else {
          player.playVideo(); savePlaying(true);
        }
      }catch{}
      syncPlayIcon();
      setTimeout(syncMutedFromPlayer, 0); // reflect true mute state

      try{
        const t = player.getCurrentTime?player.getCurrentTime():0;
        const idx=player.getPlaylistIndex?player.getPlaylistIndex():0;
        const s = getState();
        savePlaylistProgress(s.active, idx, t);
      }catch{}
    }

    if (act === 'next'){
      try {
        const s = getState();
        if (s.prefs.muted) player.mute(); else player.unMute();
        player.nextVideo(); savePlaying(true); isPlayingFlag = true;
        syncPlayIcon();
        setTimeout(syncMutedFromPlayer, 0); // reflect true mute state
      } catch {}
    }

    if (act === 'prev'){
      try {
        const t = player.getCurrentTime ? player.getCurrentTime() : 0;
        const s = getState();
        if (s.prefs.muted) player.mute(); else player.unMute();
        if (t > 3) { player.seekTo(0, true); } else { player.previousVideo(); }
        savePlaying(true); isPlayingFlag = true;
        syncPlayIcon();
        setTimeout(syncMutedFromPlayer, 0); // reflect true mute state
      } catch {}
    }

    if (act === 'shuffle'){
      const cur = getState();
      const on = !cur.prefs.shuffle; // toggle
      savePrefs({ shuffle:on });
      applyShuffleLoop();
    }

    if (act === 'repeat'){
      const cur  = getState();
      const next = (cur.prefs.repeat==='none') ? 'one'
                 : (cur.prefs.repeat==='one')  ? 'all'
                 : 'none';
      savePrefs({ repeat: next });
      setRepeatLabel(next);
      applyShuffleLoop(); // keep loop behavior aligned with icon
    }

    if (act === 'mute'){
      // Prevent rapid double toggles fighting each other
      const now = Date.now(); if (now - _muteTouchAt < 120) return;
      _muteTouchAt = now;

      try {
        const target = !isPlayerMuted();                 // flip actual player state
        if (target) player.mute(); else player.unMute(); // ask YT to apply
        savePrefs({ muted: target });                    // persist our intent
      } catch {}
      afterMuteApplied();                                // sync icon from real state
    }

  });

  document.addEventListener('input', (e)=>{
    if (!root.contains(e.target)) return;
    if (!e.target.classList.contains('hud-range')) return;
    if (!player || !player.seekTo) return;
    const pct = Number(e.target.value)/1000;
    const dur = player.getDuration ? player.getDuration() : 0;
    player.seekTo(dur*pct, true);
  });

  window.addEventListener('beforeunload', ()=>{
    try{
      const s = getState();
      const t = player && player.getCurrentTime ? player.getCurrentTime() : 0;
      const idx = player && player.getPlaylistIndex ? player.getPlaylistIndex() : 0;
      const st = player && player.getPlayerState ? player.getPlayerState() : null;
      savePlaying(st === (YT && YT.PlayerState && YT.PlayerState.PLAYING));
      savePlaylistProgress(s.active, idx, t);
    }catch{}
  });

  // --------------- Simple programmatic API ---------------
  window.SNPlayer = window.SNPlayer || {};
  (function exposeAPI(){
    let wasPlayingBeforeHide = false;
    async function isPlaying(){ if (!player) return false; try{ return player.getPlayerState()===YT.PlayerState.PLAYING; }catch{return false;} }
    async function hide(){ wasPlayingBeforeHide = await isPlaying(); try{ player.pauseVideo(); savePlaying(false); }catch{} shell().classList.add('hud-hidden'); syncWidget();}
    async function show(){ shell().classList.remove('hud-hidden'); syncWidget(); const s=getState(); if (wasPlayingBeforeHide || s.playing){ try{ if (s.prefs.muted) player.mute(); else player.unMute(); player.playVideo(); savePlaying(true);}catch{} } }
    window.SNPlayer.hide = hide;
    window.SNPlayer.show = show;
    window.SNPlayer.toggle = (force)=> typeof force==='boolean' ? (force?show():hide()) : (shell().classList.contains('hud-hidden')?show():hide());
    window.SNPlayer.setPlaylist = async (playlistId)=>{ if (!PLAYLISTS.find(p=>p.id===playlistId)) return; saveActivePlaylist(playlistId); await cueActivePlaylist({autoPlay:true}); };
  })();

})();
