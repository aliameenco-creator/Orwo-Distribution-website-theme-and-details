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
    if (path === here && !a.hash && !a.classList.contains('button')) a.classList.add('current');
  });

  // Mobile menu panel (opens directly under the header).
  const toggle = d.querySelector('.menu-toggle');
  const nav = d.getElementById('navigation');
  const closeMenu = () => { d.body.classList.remove('menu-open'); if (toggle) toggle.setAttribute('aria-expanded', 'false'); };
  if (nav) [...nav.querySelectorAll('.menu > li')].forEach((li, i) => li.style.setProperty('--i', String(i)));
  if (toggle) toggle.addEventListener('click', () => {
    if (nav && header) nav.style.top = Math.max(0, header.getBoundingClientRect().bottom) + 'px';
    const open = d.body.classList.toggle('menu-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  if (nav) nav.addEventListener('click', (e) => { if (e.target.closest('a')) closeMenu(); });
  d.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });

  // Homepage hero: film stills crossfade (slow zoom) with the matching "now showing" entry; pauses on hover.
  const hero = d.querySelector('[data-hero]');
  const slides = hero ? [...hero.querySelectorAll('.hero-slide')] : [];
  const nowItems = hero ? [...hero.querySelectorAll('.now-item')] : [];
  if (slides.length > 1) {
    const duration = 7000;
    hero.style.setProperty('--slide', duration + 'ms');
    let current = 0;
    let timer = null;
    const go = (i) => {
      current = (i + slides.length) % slides.length;
      slides.forEach((s, n) => s.classList.toggle('active', n === current));
      nowItems.forEach((a, n) => {
        a.classList.remove('active');
        if (n === current) { void a.offsetWidth; a.classList.add('active'); }
      });
      const img = slides[(current + 1) % slides.length].querySelector('img');
      if (img && img.loading === 'lazy') img.loading = 'eager';
    };
    const play = () => { if (!reduce && !timer) timer = window.setInterval(() => go(current + 1), duration); };
    const pause = () => { if (timer) { window.clearInterval(timer); timer = null; } };
    nowItems.forEach((a) => {
      a.addEventListener('mouseenter', () => { if (window.matchMedia('(hover: hover)').matches) { pause(); go(parseInt(a.dataset.slide, 10)); } });
      a.addEventListener('mouseleave', play);
    });
    d.addEventListener('visibilitychange', () => (d.hidden ? pause() : play()));
    play();
  }

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

  // Trailers: a poster, tile or "now showing" title with a trailer plays it in a pop-up (the link itself still goes to
  // the film page, so it works without JavaScript and with ctrl/cmd-click). Closing stops the video.
  if (d.querySelector('[data-trailer]') && typeof HTMLDialogElement === 'function') {
    const box = d.createElement('dialog');
    box.className = 'trailer-box';
    box.setAttribute('aria-label', 'Trailer');
    box.innerHTML = '<div class="trailer-inner"><div class="trailer-head"><span class="trailer-title"></span><a class="text-link trailer-page" href="#">VIEW TITLE ↗</a><button type="button" class="trailer-close" aria-label="Close trailer">×</button></div><div class="video-frame"></div></div>';
    d.body.appendChild(box);
    const frame = box.querySelector('.video-frame');
    const page = box.querySelector('.trailer-page');
    const stop = () => { frame.innerHTML = ''; frame.classList.remove('loading'); };
    // Warm up the video player's connections early (after the page has loaded, and again on hover/focus/touch) so a
    // click starts the trailer quickly.
    let warmed = false;
    const warm = () => {
      if (warmed) return;
      warmed = true;
      const hosts = new Set(['https://www.youtube.com', 'https://i.ytimg.com', 'https://www.google.com', 'https://static.doubleclick.net', 'https://jnn-pa.googleapis.com']);
      d.querySelectorAll('[data-trailer]').forEach((a) => { try { hosts.add(new URL(a.dataset.trailer).origin); } catch (err) { /* ignore */ } });
      hosts.forEach((href) => { const l = d.createElement('link'); l.rel = 'preconnect'; l.href = href; l.crossOrigin = ''; d.head.appendChild(l); });
    };
    if (d.readyState === 'complete') window.setTimeout(warm, 1500); else window.addEventListener('load', () => window.setTimeout(warm, 1500));
    ['pointerover', 'focusin', 'touchstart'].forEach((ev) => d.addEventListener(ev, (e) => { if (e.target.closest && e.target.closest('[data-trailer]')) warm(); }, { passive: true }));
    box.addEventListener('close', stop);
    box.querySelector('.trailer-close').addEventListener('click', () => box.close());
    box.addEventListener('click', (e) => { if (e.target === box) box.close(); });
    d.addEventListener('click', (e) => {
      const link = e.target.closest('[data-trailer]');
      if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      e.preventDefault();
      const iframe = d.createElement('iframe');
      iframe.src = link.dataset.trailer + (link.dataset.trailer.includes('?') ? '&' : '?') + 'autoplay=1';
      iframe.title = (link.dataset.trailerTitle || 'Film') + ' – trailer';
      iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
      iframe.allowFullscreen = true;
      iframe.referrerPolicy = 'strict-origin-when-cross-origin';
      stop();
      warm();
      // The film still and a spinner show at once; the player fades in when it has loaded.
      frame.style.backgroundImage = link.dataset.trailerStill ? 'url("' + link.dataset.trailerStill.replace(/"/g, '%22') + '")' : '';
      frame.classList.add('loading');
      iframe.addEventListener('load', () => window.setTimeout(() => frame.classList.remove('loading'), 250));
      frame.appendChild(iframe);
      box.querySelector('.trailer-title').textContent = link.dataset.trailerTitle || '';
      const onFilmPage = link.closest('.film-hero');
      page.hidden = !!onFilmPage;
      page.href = onFilmPage ? '#' : link.href;
      box.showModal();
      box.querySelector('.trailer-close').focus();
    });
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
