(() => {
  const root = document.documentElement;
  const themeButtons = document.querySelectorAll('[data-theme-toggle]');
  const brandNodes = document.querySelectorAll('[data-theme-brand]');
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
  };
  applyTheme(root.dataset.theme || 'dark');
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

  // Cross-document View Transitions cannot know which of several list titles
  // should be shared. Give only the title involved in this navigation a name.
  const postTransitionName = 'eno-post-title';
  const postTitleSelector = '[data-post-transition-title][data-post-url]';
  const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
  const getActivation = (event) => (event && event.activation) || (window.navigation && window.navigation.activation) || null;
  const activationUrl = (activation, key) => normalizeUrl(activation && activation[key] && activation[key].url);
  const titleForUrl = (url) => {
    if (!url) return null;
    return Array.from(document.querySelectorAll(postTitleSelector)).find((node) => normalizeUrl(node.dataset.postUrl) === url) || null;
  };
  const articleTitle = () => document.querySelector('.entry-shell ' + postTitleSelector);
  const skipTransition = (transition) => {
    if (transition && typeof transition.skipTransition === 'function') {
      try { transition.skipTransition(); } catch (error) {}
    }
  };
  const markTitle = (node, marked) => {
    if (!node || marked.has(node)) return;
    marked.set(node, node.style.viewTransitionName);
    node.style.viewTransitionName = postTransitionName;
  };
  const watchTransition = (transition, marked) => {
    let cleaned = false;
    const cleanup = () => {
      if (cleaned) return;
      cleaned = true;
      marked.forEach((previousName, node) => {
        node.style.viewTransitionName = previousName;
      });
      window.removeEventListener('pagehide', cleanup);
    };
    if (!transition) return cleanup;
    // A page entering BFCache may not get to the normal finished callback
    // while it is frozen, so never leave an inline name behind on restore.
    window.addEventListener('pagehide', cleanup, { once: true });
    try {
      if (transition.finished && typeof transition.finished.then === 'function') {
        transition.finished.then(cleanup, cleanup);
      } else {
        window.setTimeout(cleanup, 700);
      }
    } catch (error) {
      cleanup();
    }
    return cleanup;
  };

  window.addEventListener('pageswap', (event) => {
    const transition = event.viewTransition;
    const activation = getActivation(event);
    if (reduceMotion() || !transition || !activation) { skipTransition(transition); return; }

    const destinationUrl = activationUrl(activation, 'entry');
    const marked = new Map();
    const destinationTitle = titleForUrl(destinationUrl);
    if (destinationTitle) {
      markTitle(destinationTitle, marked);
    } else {
      // The incoming page will decide whether it has a matching list title.
      // This also covers search/category pages reached through browser history.
      markTitle(articleTitle(), marked);
    }
    if (!marked.size) { skipTransition(transition); return; }
    watchTransition(transition, marked);
  });

  window.addEventListener('pagereveal', (event) => {
    const transition = event.viewTransition;
    const activation = getActivation(event);
    if (reduceMotion() || !transition || !activation) { skipTransition(transition); return; }

    const fromUrl = activationUrl(activation, 'from');
    const marked = new Map();
    const incomingArticleTitle = articleTitle();
    if (incomingArticleTitle) {
      markTitle(incomingArticleTitle, marked);
    } else {
      markTitle(titleForUrl(fromUrl), marked);
    }
    if (!marked.size) { skipTransition(transition); return; }
    watchTransition(transition, marked);
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

  const progress = document.querySelector('.reading-progress span');
  const article = document.querySelector('.entry-shell');
  if (progress && article) {
    const updateProgress = () => {
      const start = article.getBoundingClientRect().top + window.scrollY;
      const distance = Math.max(1, article.offsetHeight - window.innerHeight);
      const amount = Math.min(1, Math.max(0, (window.scrollY - start) / distance));
      progress.style.transform = `scaleX(${amount})`;
    };
    window.addEventListener('scroll', updateProgress, { passive: true });
    window.addEventListener('resize', updateProgress);
    updateProgress();
  }
})();
