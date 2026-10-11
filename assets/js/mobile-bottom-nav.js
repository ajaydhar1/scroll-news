(() => {
  const root = document.documentElement;
  const standaloneMode = window.matchMedia('(display-mode: standalone)');
  const mobileViewport = window.matchMedia('(max-width: 767.98px)');
  const mobileNav = document.querySelector('.sn-mobile-bottom-nav');
  const viewportMeta = document.querySelector('meta[name="viewport"]');
  const initialViewportContent = viewportMeta?.content || '';
  const visualViewport = window.visualViewport;

  function isStandalonePwa() {
    return standaloneMode.matches || window.navigator.standalone === true;
  }

  function isAppShell() {
    return isStandalonePwa();
  }

  function isEditableElement(element) {
    if (!(element instanceof HTMLElement)) return false;
    if (element.isContentEditable || element.matches('textarea')) return true;
    if (!element.matches('input')) return false;

    return !element.disabled && !element.readOnly
      && !['button', 'checkbox', 'color', 'file', 'hidden', 'image', 'radio', 'range', 'reset', 'submit'].includes(element.type);
  }

  function keyboardIsOpen() {
    if (isEditableElement(document.activeElement)) return true;
    return Boolean(visualViewport && window.screen.height - visualViewport.height > 150);
  }

  function updateViewportFit(enabled) {
    if (!viewportMeta || !initialViewportContent) return;
    if (!enabled) {
      viewportMeta.content = initialViewportContent;
      return;
    }

    viewportMeta.content = /viewport-fit\s*=/i.test(initialViewportContent)
      ? initialViewportContent.replace(/viewport-fit\s*=\s*[^,]+/i, 'viewport-fit=cover')
      : `${initialViewportContent}, viewport-fit=cover`;
  }

  function update() {
    const enabled = isAppShell() && mobileViewport.matches;
    const keyboardOpen = enabled && keyboardIsOpen();
    updateViewportFit(enabled);
    if (mobileNav) mobileNav.hidden = !enabled || keyboardOpen;
    root.classList.toggle('sn-mobile-nav-enabled', enabled);
    root.classList.toggle('sn-mobile-keyboard-open', keyboardOpen);
  }

  document.addEventListener('focusin', update);
  document.addEventListener('focusout', () => requestAnimationFrame(update));
  window.addEventListener('resize', update);
  window.addEventListener('orientationchange', update);
  window.addEventListener('pageshow', update);
  window.visualViewport?.addEventListener('resize', update);
  window.visualViewport?.addEventListener('scroll', update);

  [standaloneMode, mobileViewport].forEach((mediaQuery) => {
    if (mediaQuery.addEventListener) mediaQuery.addEventListener('change', update);
    else mediaQuery.addListener(update);
  });

  update();
})();