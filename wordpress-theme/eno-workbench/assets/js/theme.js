(() => {
  const root = document.documentElement;
  const themeButtons = document.querySelectorAll('[data-theme-toggle]');
  const applyTheme = (theme) => {
    root.dataset.theme = theme;
    const light = theme === 'light';
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
    try { const transition = document.startViewTransition(commit); transition.ready.catch(() => {}); transition.finished.catch(() => {}); } catch (error) { commit(); }
  }));
  const rail = document.querySelector('.left-rail');
  if (!rail) return;
  document.querySelectorAll('[data-menu-open]').forEach((button) => button.addEventListener('click', () => rail.classList.add('is-open')));
  document.querySelectorAll('[data-menu-close]').forEach((button) => button.addEventListener('click', () => rail.classList.remove('is-open')));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') rail.classList.remove('is-open');
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      const input = document.querySelector('input[type="search"]');
      if (input) { event.preventDefault(); input.focus(); }
    }
  });
})();
