(() => {
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
