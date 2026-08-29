(() => {
  const root = document.documentElement;
  const themeButtons = document.querySelectorAll('[data-theme-toggle]');
  const brandNodes = document.querySelectorAll('[data-theme-brand]');
  const faviconNode = document.querySelector('#eno-favicon');
  const brandPattern = /^eno\s*(?:的\s*)?小(?:黑|白)屋$/u;
  const cleanTitlePart = (part) => String(part || '').replace(/^\s+|\s+$/gu, '');
  const titleParts = (value) => {
    const raw = cleanTitlePart(value);
    const hasLocalMarker = /（本地开发）\s*$/u.test(raw);
    const withoutLocalMarker = cleanTitlePart(raw.replace(/\s*（本地开发）\s*$/u, ''));
    const parts = withoutLocalMarker.split(/\s*(?:–|—|\|)\s*/u).map(cleanTitlePart).filter(Boolean);
    return { parts, hasLocalMarker };
  };
  const titleBase = (value) => {
    const parsed = titleParts(value);
    const siteName = cleanTitlePart(root.dataset.siteName);
    const baseParts = parsed.parts.filter((part) => part !== siteName && !brandPattern.test(part));
    return {
      base: baseParts.join(' – '),
      hasLocalMarker: parsed.hasLocalMarker,
    };
  };
  const updateDocumentTitle = (sourceTitle = document.title) => {
    const parsed = titleBase(sourceTitle);
    const brand = root.dataset.theme === 'light' ? 'eno 的小白屋' : 'eno 的小黑屋';
    const suffix = parsed.hasLocalMarker ? '（本地开发）' : '';
    document.title = `${parsed.base ? `${parsed.base} – ` : ''}${brand}${suffix}`;
  };
  const setFavicon = (visible) => {
    if (!faviconNode) return;
    const next = visible ? faviconNode.dataset.faviconZ : faviconNode.dataset.faviconX;
    if (next && faviconNode.getAttribute('href') !== next) faviconNode.setAttribute('href', next);
  };
  const syncFavicon = () => setFavicon(document.visibilityState !== 'hidden');
  document.addEventListener('visibilitychange', syncFavicon);
  window.addEventListener('pageshow', () => setFavicon(true));
  window.addEventListener('pagehide', () => setFavicon(false));
  const applyTheme = (theme) => {
    root.dataset.theme = theme;
    const light = theme === 'light';
    brandNodes.forEach((node) => {
      const label = light ? node.dataset.lightLabel : node.dataset.darkLabel;
      if (label) node.textContent = label;
    });
    themeButtons.forEach((button) => {
      button.setAttribute('aria-pressed', light ? 'true' : 'false');
      const label = light ? '切换到深色模式' : '切换到浅色模式';
      button.setAttribute('aria-label', label);
      button.setAttribute('title', label);
      const icon = button.querySelector('.ti');
      if (icon) icon.className = `ti ${light ? 'ti-moon' : 'ti-sun'}`;
      const text = button.querySelector('.screen-reader-text');
      if (text) text.textContent = label;
    });
    updateDocumentTitle();
  };
  applyTheme(root.dataset.theme || 'dark');
  syncFavicon();
  themeButtons.forEach((button) => button.addEventListener('click', (event) => {
    const next = root.dataset.theme === 'light' ? 'dark' : 'light';
    const commit = () => { applyTheme(next); try { localStorage.setItem('eno-theme', next); } catch (error) {} };
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!document.startViewTransition || reduceMotion) { commit(); return; }
    const x = event.clientX || window.innerWidth / 2;
    const y = event.clientY || window.innerHeight / 2;
    root.style.setProperty('--theme-x', `${x}px`);
    root.style.setProperty('--theme-y', `${y}px`);
    root.classList.add('eno-theme-transition');
    try {
      const transition = document.startViewTransition(commit);
      transition.ready.catch(() => {});
      transition.finished.then(() => root.classList.remove('eno-theme-transition'), () => root.classList.remove('eno-theme-transition'));
    } catch (error) {
      root.classList.remove('eno-theme-transition');
      commit();
    }
  }));

  // Chrome can abort cross-document transitions before either page is ready.
  // For article links, fetch the real WordPress route and swap only the route
  // content inside a same-document transition. Native navigation remains the
  // fallback whenever the enhancement cannot run.
  const postTitleSelector = '[data-post-transition-title][data-post-url][data-post-id]';
  const normalizeUrl = (value) => {
    if (!value) return '';
    try {
      const url = new URL(value, window.location.href);
      if (url.origin !== window.location.origin) return '';
      url.hash = '';
      url.pathname = url.pathname.length > 1 ? url.pathname.replace(/\/+$/, '') : '/';
      return url.href;
    } catch (error) {
      return '';
    }
  };
  const titleForUrl = (url, scope = document) => {
    if (!url) return null;
    return Array.from(scope.querySelectorAll(postTitleSelector)).find((node) => normalizeUrl(node.dataset.postUrl) === url) || null;
  };
  const articleTitle = (scope = document) => scope.querySelector('.entry-shell ' + postTitleSelector);
  let preparedTitles = [];
  const clearPreparedTitles = () => {
    preparedTitles.forEach((node) => {
      node.style.removeProperty('view-transition-name');
    });
    preparedTitles = [];
  };
  const preparePostTitle = (url, nextTitle) => {
    clearPreparedTitles();
    const destinationTitle = titleForUrl(normalizeUrl(url));
    const currentTitle = articleTitle();
    if (currentTitle && currentTitle !== destinationTitle) {
      currentTitle.style.setProperty('view-transition-name', 'none');
      preparedTitles.push(currentTitle);
    }
    if (!destinationTitle || !nextTitle) return false;
    destinationTitle.style.setProperty('view-transition-name', 'eno-post-title');
    nextTitle.style.setProperty('view-transition-name', 'eno-post-title');
    preparedTitles.push(destinationTitle, nextTitle);
    return true;
  };
  window.addEventListener('pageshow', clearPreparedTitles);

  const routeCache = new Map();
  const createRouteKey = () => `eno-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 9)}`;
  const setRouteScroll = (top) => {
    // The page keeps smooth scrolling for normal anchor navigation, but a
    // route transition must not animate the browser window underneath the
    // captured snapshots. Temporarily opt out for this one positioning step.
    const previousScrollBehavior = root.style.getPropertyValue('scroll-behavior');
    const previousScrollBehaviorPriority = root.style.getPropertyPriority('scroll-behavior');
    root.style.setProperty('scroll-behavior', 'auto', 'important');
    // Force the new computed value before calling scrollTo; otherwise Chrome
    // may queue a smooth scroll using the page-level `scroll-behavior` rule.
    void root.offsetHeight;
    window.scrollTo({ top, left: 0, behavior: 'auto' });
    root.style.setProperty('scroll-behavior', previousScrollBehavior, previousScrollBehaviorPriority);
  };
  const disableRouteScrollAnchoring = () => {
    const previousOverflowAnchor = root.style.getPropertyValue('overflow-anchor');
    const previousOverflowAnchorPriority = root.style.getPropertyPriority('overflow-anchor');
    root.style.setProperty('overflow-anchor', 'none', 'important');
    return () => root.style.setProperty('overflow-anchor', previousOverflowAnchor, previousOverflowAnchorPriority);
  };
  const cleanRouteClone = (route) => {
    const clone = route.cloneNode(true);
    clone.querySelectorAll(postTitleSelector).forEach((node) => {
      node.style.removeProperty('view-transition-name');
      if (!node.getAttribute('style')) node.removeAttribute('style');
    });
    return clone;
  };
  let currentRouteKey = history.state && history.state.enoRouteKey ? history.state.enoRouteKey : createRouteKey();
  let currentRouteUrl = normalizeUrl(window.location.href);
  const rememberCurrentRoute = () => {
    const route = document.querySelector('#eno-route-content');
    if (!route || !currentRouteKey) return;
    routeCache.set(currentRouteKey, {
      route: cleanRouteClone(route),
      bodyClass: document.body.className,
      documentTitle: document.title,
      scrollY: window.scrollY,
    });
  };
  try {
    const initialState = history.state && typeof history.state === 'object' ? history.state : {};
    history.replaceState({ ...initialState, enoRouteKey: currentRouteKey }, '', window.location.href);
  } catch (error) {}
  rememberCurrentRoute();

  let postNavigationController = null;
  const nativePostNavigation = (url) => { window.location.assign(url); };
  const navigateToPost = async (url) => {
    const normalizedUrl = normalizeUrl(url);
    if (!normalizedUrl || normalizeUrl(window.location.href) === normalizedUrl) return;
    if (postNavigationController) postNavigationController.abort();
    const controller = new AbortController();
    postNavigationController = controller;

    let response;
    let nextDocument;
    try {
      response = await fetch(url, {
        credentials: 'same-origin',
        headers: { 'X-Eno-View-Transition': 'article' },
        signal: controller.signal,
      });
      if (!response.ok) throw new Error(`Navigation failed with ${response.status}`);
      nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
    } catch (error) {
      if (error && error.name === 'AbortError') return;
      nativePostNavigation(url);
      return;
    }
    if (postNavigationController !== controller) return;

    const currentRoute = document.querySelector('#eno-route-content');
    const nextRoute = nextDocument.querySelector('#eno-route-content');
    const nextTitle = articleTitle(nextDocument);
    if (!currentRoute || !nextRoute || !nextTitle || !preparePostTitle(url, nextTitle)) {
      clearPreparedTitles();
      nativePostNavigation(url);
      return;
    }

    const nextBodyClass = nextDocument.body.className;
    const nextDocumentTitle = nextDocument.title;
    const nextRouteKey = createRouteKey();
    rememberCurrentRoute();
    const restoreScrollAnchoring = disableRouteScrollAnchoring();
    let transition;
    try {
      transition = document.startViewTransition(() => {
        currentRoute.replaceWith(nextRoute);
        document.body.className = nextBodyClass;
        updateDocumentTitle(nextDocumentTitle);
        window.history.pushState({ enoPostRoute: true, enoRouteKey: nextRouteKey }, '', url);
        currentRouteKey = nextRouteKey;
        currentRouteUrl = normalizedUrl;
        setRouteScroll(0);
        setupReadingProgress();
        rememberCurrentRoute();
      });
    } catch (error) {
      restoreScrollAnchoring();
      clearPreparedTitles();
      nativePostNavigation(url);
      return;
    }
    transition.ready.catch(() => {});
    transition.finished.then(() => { restoreScrollAnchoring(); clearPreparedTitles(); }, () => { restoreScrollAnchoring(); clearPreparedTitles(); });
    postNavigationController = null;
  };

  document.addEventListener('click', (event) => {
    if ((typeof event.button === 'number' && event.button !== 0) || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
    const destinationUrl = normalizeUrl(link.href);
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!destinationUrl || !document.startViewTransition || reduceMotion || !titleForUrl(destinationUrl)) return;
    if (normalizeUrl(window.location.href) === destinationUrl) return;
    event.preventDefault();
    navigateToPost(link.href);
  });

  window.addEventListener('popstate', (event) => {
    const targetRouteKey = event.state && event.state.enoRouteKey;
    const cachedRoute = targetRouteKey ? routeCache.get(targetRouteKey) : null;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!cachedRoute || !document.startViewTransition || reduceMotion) {
      window.location.reload();
      return;
    }
    if (postNavigationController) postNavigationController.abort();

    const currentRoute = document.querySelector('#eno-route-content');
    const nextRoute = cachedRoute.route.cloneNode(true);
    if (!currentRoute || !nextRoute) {
      window.location.reload();
      return;
    }

    const targetRouteUrl = normalizeUrl(window.location.href);
    const currentArticleTitle = articleTitle();
    const nextArticleTitle = articleTitle(nextRoute);
    const sourceTitle = currentArticleTitle || titleForUrl(targetRouteUrl);
    const destinationTitle = nextArticleTitle || titleForUrl(currentRouteUrl, nextRoute);
    clearPreparedTitles();
    if (sourceTitle && destinationTitle) {
      sourceTitle.style.setProperty('view-transition-name', 'eno-post-title');
      destinationTitle.style.setProperty('view-transition-name', 'eno-post-title');
      preparedTitles.push(sourceTitle, destinationTitle);
    }

    const restoreScrollAnchoring = disableRouteScrollAnchoring();
    let transition;
    try {
      transition = document.startViewTransition(() => {
        currentRoute.replaceWith(nextRoute);
        document.body.className = cachedRoute.bodyClass;
        updateDocumentTitle(cachedRoute.documentTitle);
        currentRouteKey = targetRouteKey;
        currentRouteUrl = targetRouteUrl;
        setRouteScroll(cachedRoute.scrollY);
        setupReadingProgress();
      });
    } catch (error) {
      restoreScrollAnchoring();
      clearPreparedTitles();
      window.location.reload();
      return;
    }
    transition.ready.catch(() => {});
    transition.finished.then(() => { restoreScrollAnchoring(); clearPreparedTitles(); }, () => { restoreScrollAnchoring(); clearPreparedTitles(); });
  });

  // Keep the title link's native keyboard/modifier behavior, while allowing
  // the rest of a card to act as a convenient pointer target.
  document.addEventListener('click', (event) => {
    if ((typeof event.button === 'number' && event.button !== 0) || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const target = event.target;
    const card = target instanceof Element ? target.closest('.post-card') : null;
    if (!card || (target instanceof Element && target.closest('a,button,input,select,textarea,summary,[role="button"]'))) return;
    const selection = window.getSelection && window.getSelection();
    if (selection && String(selection).trim()) return;
    const titleLink = card.querySelector('[data-post-transition-title] a[href]') || card.querySelector('h3 a[href]');
    if (!titleLink) return;
    event.preventDefault();
    titleLink.click();
  });

  const rail = document.querySelector('.left-rail');
  const menuOpeners = document.querySelectorAll('[data-menu-open]');
  const menuClosers = document.querySelectorAll('[data-menu-close]');
  const firstCloseButton = rail ? rail.querySelector('[data-menu-close]') : null;
  let lastMenuFocus = null;
  const setMenu = (open) => {
    if (!rail) return;
    if (open) lastMenuFocus = document.activeElement;
    rail.classList.toggle('is-open', open);
    document.body.classList.toggle('menu-open', open);
    menuOpeners.forEach((button) => button.setAttribute('aria-expanded', open ? 'true' : 'false'));
    if (open && firstCloseButton) window.requestAnimationFrame(() => firstCloseButton.focus());
    if (!open && lastMenuFocus && typeof lastMenuFocus.focus === 'function') window.requestAnimationFrame(() => lastMenuFocus.focus());
  };
  menuOpeners.forEach((button) => button.addEventListener('click', () => setMenu(true)));
  menuClosers.forEach((button) => button.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setMenu(false);
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      const input = document.querySelector('input[type="search"]');
      if (input) { event.preventDefault(); input.focus(); }
    }
  });

  let cleanupReadingProgress = () => {};
  const setupReadingProgress = () => {
    cleanupReadingProgress();
    const progress = document.querySelector('.reading-progress span');
    const article = document.querySelector('.entry-shell');
    if (!progress || !article) {
      cleanupReadingProgress = () => {};
      return;
    }
    const updateProgress = () => {
      const start = article.getBoundingClientRect().top + window.scrollY;
      const distance = Math.max(1, article.offsetHeight - window.innerHeight);
      const amount = Math.min(1, Math.max(0, (window.scrollY - start) / distance));
      progress.style.transform = `scaleX(${amount})`;
    };
    window.addEventListener('scroll', updateProgress, { passive: true });
    window.addEventListener('resize', updateProgress);
    cleanupReadingProgress = () => {
      window.removeEventListener('scroll', updateProgress);
      window.removeEventListener('resize', updateProgress);
    };
    updateProgress();
  };
  setupReadingProgress();
})();
