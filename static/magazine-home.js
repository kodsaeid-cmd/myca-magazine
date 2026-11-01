/* MYCA Magazine: tiny progressive enhancement for the prerendered homepage.
   The editorial page remains readable and links work even with JavaScript disabled. */
(() => {
  function init() {
    const desk = document.querySelector('.news-desk');
    if (desk) {
      const buttons = Array.from(desk.querySelectorAll('.news-item'));
      const image = desk.querySelector('.news-stage-photo');
      const imageMobile = desk.querySelector('.news-stage source');
      const title = desk.querySelector('.news-stage-copy h1');
      const excerpt = desk.querySelector('.news-stage-copy p');
      const category = desk.querySelector('.news-category');
      const readingTime = desk.querySelector('.news-readtime');
      const link = desk.querySelector('.news-read');
      const count = desk.querySelector('.news-stage-index');
      const play = desk.querySelector('.news-play-toggle');
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      let selected = 0;
      let hovered = false;
      let manuallyPaused = false;
      let stopped = false;

      const isPaused = () => hovered || manuallyPaused || stopped || prefersReducedMotion;
      function updateControl() {
        const paused = isPaused();
        play.classList.toggle('is-paused', paused);
        play.setAttribute('aria-label', paused ? 'پخش خودکار مطالب' : 'توقف پخش خودکار مطالب');
        play.setAttribute('title', paused ? 'پخش خودکار' : 'توقف پخش خودکار');
        const progress = desk.querySelector('.news-item-progress');
        if (progress) progress.classList.toggle('progress-paused', paused);
      }
      function change(index, manual = false) {
        if (!buttons[index]) return;
        if (manual) manuallyPaused = true;
        if (index === selected) { updateControl(); return; }
        selected = index;
        const item = buttons[index].dataset;
        imageMobile.srcset = item.imageMobile;
        image.src = item.imageFull;
        image.alt = item.imageAlt || '';
        category.textContent = item.category;
        title.textContent = item.title;
        excerpt.textContent = item.excerpt;
        link.href = item.url;
        const timeNode = readingTime.lastChild;
        if (timeNode && timeNode.nodeType === Node.TEXT_NODE) timeNode.textContent = ' ' + item.minutes + ' دقیقه مطالعه';
        count.textContent = String(index + 1).padStart(2, '0') + ' / ' + String(buttons.length).padStart(2, '0');
        buttons.forEach((button, i) => {
          button.classList.toggle('news-item-active', i === index);
          button.setAttribute('aria-pressed', String(i === index));
          button.querySelector('.news-item-progress')?.remove();
        });
        const progress = document.createElement('span');
        progress.className = 'news-item-progress';
        progress.setAttribute('aria-hidden', 'true');
        buttons[index].appendChild(progress);
        if (!prefersReducedMotion) {
          desk.classList.remove('news-switching');
          void desk.offsetWidth;
          desk.classList.add('news-switching');
        }
        updateControl();
      }
      buttons.forEach((button, index) => {
        button.addEventListener('mouseenter', () => change(index));
        button.addEventListener('focus', () => change(index));
        button.addEventListener('click', () => change(index, true));
      });
      desk.addEventListener('mouseenter', () => { hovered = true; updateControl(); });
      desk.addEventListener('mouseleave', () => { hovered = false; updateControl(); });
      desk.addEventListener('focusin', () => { hovered = true; updateControl(); });
      desk.addEventListener('focusout', (event) => {
        if (!desk.contains(event.relatedTarget)) { hovered = false; updateControl(); }
      });
      play.addEventListener('click', () => {
        manuallyPaused = !isPaused();
        stopped = false;
        updateControl();
      });
      if (!prefersReducedMotion && buttons.length > 1) {
        const timer = window.setInterval(() => {
          if (document.hidden || isPaused()) return;
          change((selected + 1) % buttons.length);
        }, 6500);
        window.addEventListener('pagehide', () => window.clearInterval(timer), { once: true });
      }
      updateControl();
    }
    const menuButton = document.querySelector('.mobile-menu-toggle');
    const menu = document.querySelector('.mobile-menu');
    menuButton?.addEventListener('click', () => {
      if (!menu) return;
      const open = menu.hasAttribute('hidden');
      if (open) menu.removeAttribute('hidden'); else menu.setAttribute('hidden', '');
      menuButton.setAttribute('aria-expanded', String(open));
      menuButton.setAttribute('aria-label', open ? 'بستن منو' : 'بازکردن منو');
    });
    menu?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
      menu.setAttribute('hidden', '');
      menuButton?.setAttribute('aria-expanded', 'false');
    }));
    const searchToggle = document.querySelector('.mobile-search-toggle');
    const searchForm = document.querySelector('.header-search');
    searchToggle?.addEventListener('click', () => {
      const expanded = searchForm?.classList.toggle('expanded') ?? false;
      searchToggle.setAttribute('aria-expanded', String(expanded));
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
