/* ORWO Distribution theme interactions. All content is visible without JavaScript; motion respects reduced-motion. */
(() => {
  const d = document;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const wide = window.matchMedia('(min-width: 768px)');
  const header = d.querySelector('[data-header]');
  const bar = d.querySelector('[data-progress]');
  const adminBar = d.getElementById('wpadminbar');

  // Header: solid once scrolled, sits under the WordPress admin bar while it is visible; scroll progress; parallax.
  let ticking = false;
  const parallax = [...d.querySelectorAll('[data-speed]')];
  const frame = () => {
    ticking = false;
    const y = window.scrollY;
    const vh = window.innerHeight;
    if (header) {
      header.classList.toggle('scrolled', y > 24);
      header.style.top = adminBar ? Math.max(0, adminBar.getBoundingClientRect().bottom) + 'px' : '';
    }
    if (bar) {
      const max = d.documentElement.scrollHeight - vh;
      bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
    }
    if (reduce || !wide.matches) return;
    parallax.forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.bottom < -200 || r.top > vh + 200) return;
      const offset = (r.top + r.height / 2 - vh / 2) * parseFloat(el.dataset.speed || 0);
      el.style.translate = '0 ' + offset.toFixed(1) + 'px';
    });
  };
  const onScroll = () => { if (!ticking) { ticking = true; window.requestAnimationFrame(frame); } };
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll, { passive: true });
  frame();

  // Current page in the menu.
  const here = location.pathname.replace(/\/+$/, '') || '/';
  d.querySelectorAll('.menu > li > a').forEach((a) => {
    const path = new URL(a.href, location.href).pathname.replace(/\/+$/, '') || '/';
    if (path === here && !a.classList.contains('button')) a.classList.add('current');
  });

  // Mobile menu panel (opens directly under the header) and dropdowns (hover on desktop via CSS, tap/keyboard everywhere).
  const toggle = d.querySelector('.menu-toggle');
  const nav = d.getElementById('navigation');
  const closeSubs = (except) => d.querySelectorAll('.has-sub.open').forEach((li) => {
    if (li !== except) { li.classList.remove('open'); li.querySelector('.sub-toggle').setAttribute('aria-expanded', 'false'); }
  });
  const closeMenu = () => { d.body.classList.remove('menu-open'); if (toggle) toggle.setAttribute('aria-expanded', 'false'); };
  if (nav) [...nav.querySelectorAll('.menu > li')].forEach((li, i) => li.style.setProperty('--i', String(i)));
  if (toggle) toggle.addEventListener('click', () => {
    if (nav && header) nav.style.top = Math.max(0, header.getBoundingClientRect().bottom) + 'px';
    const open = d.body.classList.toggle('menu-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  d.querySelectorAll('.sub-toggle').forEach((btn) => btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const li = btn.parentElement;
    const open = !li.classList.contains('open');
    closeSubs(li);
    li.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', String(open));
  }));
  d.addEventListener('click', (e) => { if (!e.target.closest('.has-sub')) closeSubs(); });
  if (nav) nav.addEventListener('click', (e) => { if (e.target.closest('a')) { closeMenu(); closeSubs(); } });
  d.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeSubs(); closeMenu(); } });

  // Reveal on scroll (staggered siblings).
  const items = [...d.querySelectorAll('[data-reveal]')];
  const show = (el) => el.classList.add('in');
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach(show);
  } else {
    const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (entry.isIntersecting) { show(entry.target); io.unobserve(entry.target); }
    }), { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });
    items.forEach((el) => {
      if (!el.style.getPropertyValue('--d')) {
        const siblings = [...el.parentElement.children].filter((c) => c.hasAttribute('data-reveal'));
        const index = siblings.indexOf(el);
        if (index > 0) el.style.setProperty('--d', String(index % 6));
      }
      io.observe(el);
    });
    // Safety net for fast jumps (menu anchors, End key): anything already scrolled past is shown.
    let pending = items.slice();
    let sweeping = false;
    const sweep = () => {
      sweeping = false;
      const limit = window.innerHeight * 0.94;
      pending = pending.filter((el) => {
        if (el.getBoundingClientRect().top < limit) { show(el); io.unobserve(el); return false; }
        return true;
      });
      if (!pending.length) window.removeEventListener('scroll', onSweep);
    };
    const onSweep = () => { if (!sweeping) { sweeping = true; window.setTimeout(sweep, 160); } };
    window.addEventListener('scroll', onSweep, { passive: true });
    window.addEventListener('load', sweep);
  }
  window.addEventListener('beforeprint', () => items.forEach(show));

  // Lightbox for galleries and posters (film galleries in the editor link to the full image).
  d.querySelectorAll('.wp-block-gallery a[href]').forEach((a) => {
    if (/\.(jpe?g|png|webp|gif|avif)(\?|$)/i.test(a.getAttribute('href'))) a.setAttribute('data-lightbox', '');
  });
  const links = [...d.querySelectorAll('[data-lightbox]')];
  if (links.length && typeof HTMLDialogElement === 'function') {
    const box = d.createElement('dialog');
    box.className = 'lightbox';
    box.innerHTML = '<button class="lb-close" type="button" aria-label="Close">×</button><button class="lb-prev" type="button" aria-label="Previous image">‹</button><img alt=""><button class="lb-next" type="button" aria-label="Next image">›</button>';
    d.body.appendChild(box);
    const img = box.querySelector('img');
    let current = 0;
    const view = (i) => {
      current = (i + links.length) % links.length;
      img.src = links[current].href;
      const inner = links[current].querySelector('img');
      img.alt = inner ? inner.alt : '';
    };
    links.forEach((a, i) => a.addEventListener('click', (e) => { e.preventDefault(); view(i); box.showModal(); }));
    box.querySelector('.lb-close').addEventListener('click', () => box.close());
    box.querySelector('.lb-prev').addEventListener('click', () => view(current - 1));
    box.querySelector('.lb-next').addEventListener('click', () => view(current + 1));
    box.addEventListener('click', (e) => { if (e.target === box) box.close(); });
    box.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') view(current - 1); if (e.key === 'ArrowRight') view(current + 1); });
    if (links.length < 2) box.querySelectorAll('.lb-prev, .lb-next').forEach((b) => { b.hidden = true; });
  }

  // Phones: filmmaker quotes as a swipe carousel with dots and a gentle auto-advance (stops once touched).
  d.querySelectorAll('[data-carousel]').forEach((track) => {
    const dots = [...(track.parentElement.querySelector('[data-dots]') || d.createElement('div')).children];
    const cards = [...track.children];
    if (!dots.length || cards.length < 2) return;
    const active = () => Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
    const paint = () => dots.forEach((dot, i) => dot.classList.toggle('active', i === active()));
    const go = (i) => track.scrollTo({ left: i * track.clientWidth, behavior: reduce ? 'auto' : 'smooth' });
    dots.forEach((dot, i) => dot.addEventListener('click', () => go(i)));
    track.addEventListener('scroll', () => window.requestAnimationFrame(paint), { passive: true });
    paint();
    let timer = null;
    const stop = () => { if (timer) { window.clearInterval(timer); timer = null; } };
    const phone = window.matchMedia('(max-width: 640px)');
    if (!reduce && phone.matches) timer = window.setInterval(() => go((active() + 1) % cards.length), 6000);
    ['touchstart', 'pointerdown', 'wheel'].forEach((ev) => track.addEventListener(ev, stop, { passive: true }));
  });

  // Contact form: field validation, then an in-page submit (falls back to a normal post).
  d.querySelectorAll('[data-orwod-form]').forEach((form) => {
    let loaded = Date.now();
    const elapsed = form.querySelector('[data-elapsed]');
    const status = form.querySelector('.form-status');
    const setError = (field, message) => {
      const wrap = field.closest('.field');
      field.setAttribute('aria-invalid', message ? 'true' : 'false');
      if (wrap) wrap.querySelector('.field-error').textContent = message || '';
    };
    const say = (cls, text) => { status.innerHTML = ''; const p = d.createElement('p'); p.className = cls; p.textContent = text; status.appendChild(p); };
    form.addEventListener('submit', async (e) => {
      elapsed.value = String(Date.now() - loaded);
      let first = null;
      form.querySelectorAll('input:not([type=hidden]):not([name=orwod_website]), textarea').forEach((field) => {
        const label = field.closest('.field').querySelector('label').firstChild.textContent.trim();
        let message = '';
        if (field.required && !field.value.trim()) message = label + ' is required.';
        else if (field.type === 'email' && field.value && !field.checkValidity()) message = 'Please enter a valid email address.';
        setError(field, message);
        if (message && !first) first = field;
      });
      e.preventDefault();
      if (first) { say('form-error', 'Please check the highlighted fields.'); first.focus(); return; }
      form.classList.add('sending');
      try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (result.ok) {
          form.reset();
          loaded = Date.now();
          say('form-success', result.message);
        } else {
          say('form-error', result.message);
          Object.entries(result.errors || {}).forEach(([key, message]) => { const field = form.querySelector('[name="orwod_' + key + '"]'); if (field) setError(field, message); });
        }
      } catch (error) {
        HTMLFormElement.prototype.submit.call(form);
        return;
      } finally {
        form.classList.remove('sending');
      }
      status.scrollIntoView({ block: 'nearest', behavior: reduce ? 'auto' : 'smooth' });
    });
  });
})();
