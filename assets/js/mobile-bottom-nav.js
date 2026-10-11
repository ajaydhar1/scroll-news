(() => {
  const root = document.documentElement;
  const standaloneMode = window.matchMedia('(display-mode: standalone)');
  const mobileViewport = window.matchMedia('(max-width: 767.98px)');
  const mobileNav = document.querySelector('.sn-mobile-bottom-nav');
  const navigationTimeoutMs = 15000;
  let navigationPending = false;
  let navigationTimeout = 0;
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

  function clearPendingNavigation() {
    navigationPending = false;
    if (navigationTimeout) {
      window.clearTimeout(navigationTimeout);
      navigationTimeout = 0;
    }
    window.SNProductLoadingOverlay?.hide();
  }

  function shouldShowNavigationLoading(event, link) {
    if (event.defaultPrevented || event.button !== 0
      || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

    const target = (link.getAttribute('target') || '_self').toLowerCase();
    if (target !== '_self' || link.hasAttribute('download')) return false;

    const stumbleLink = link.classList.contains('sn-mobile-bottom-nav__link--stumble');
    if (stumbleLink) return true;

    const destination = new URL(link.href, window.location.href);
    if (destination.origin !== window.location.origin) return false;

    return destination.pathname !== window.location.pathname
      || destination.search !== window.location.search;
  }

  mobileNav?.addEventListener('click', (event) => {
    if (mobileNav.hidden || !root.classList.contains('sn-mobile-nav-enabled')) return;

    const target = event.target;
    if (!(target instanceof Element)) return;
    const link = target.closest('a[href]');
    if (!link || !mobileNav.contains(link)) return;

    if (navigationPending) {
      event.preventDefault();
      return;
    }

    if (!shouldShowNavigationLoading(event, link)) return;

    navigationPending = true;
    window.SNProductLoadingOverlay?.show();
    navigationTimeout = window.setTimeout(clearPendingNavigation, navigationTimeoutMs);
  });

  window.addEventListener('pageshow', clearPendingNavigation);

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