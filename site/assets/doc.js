/* Shared chrome for document pages: top navigation, table of contents, footer */
(function () {
  const root = document.body.dataset.root || '../';
  const page = document.body.dataset.page || '';
  const C = window.DL_CONFIG || {};
  const mark = '<path d="M12 5.5c-1.4-1-2.6-1.7-4.3-1.7C5 3.8 3.5 5.9 3.5 8.6c0 2 .8 3.4 1.3 5 .6 2 .7 6.6 2.4 6.6 1.6 0 1.6-3.2 2.4-5 .4-.9.9-1.4 2.4-1.4s2 .5 2.4 1.4c.8 1.8.8 5 2.4 5 1.7 0 1.8-4.6 2.4-6.6.5-1.6 1.3-3 1.3-5 0-2.7-1.5-4.8-4.2-4.8-1.7 0-2.9.7-4.3 1.7Z"/><path d="M12 7.6v4.6"/><path d="M9.2 8.6h5.6"/>';
  const links = [
    ['home', 'index.html', 'الرئيسية'],
    ['deck', 'presentation/index.html', 'العرض التقديمي'],
    ['proto', 'prototype/index.html', 'النموذج التفاعلي'],
    ['prd', 'docs/prd.html', 'وثيقة المتطلبات'],
    ['flows', 'docs/user-flows.html', 'مسارات المستخدم'],
    ['arch', 'docs/architecture.html', 'التصور التقني'],
    ['p1', 'docs/phase1.html', 'المرحلة الأولى'],
    ['p2', 'docs/phase2.html', 'عرض المرحلة الثانية'],
  ];
  const top = document.createElement('header');
  top.className = 'doc-top';
  top.innerHTML = `<div class="in"><a class="brand" href="${root}index.html"><span class="mk"><svg viewBox="0 0 24 24">${mark}</svg></span>DentLegal</a>
    <nav>${links.map(([k, h, l]) => `<a class="${k === page ? 'on' : ''}" href="${root}${h}">${l}</a>`).join('')}</nav></div>`;
  document.body.prepend(top);

  const main = document.querySelector('.doc-main');
  const toc = document.querySelector('.toc');
  if (main && toc) {
    const hs = [...main.querySelectorAll('h2[id]')];
    toc.innerHTML = '<b>المحتويات</b>' + hs.map((h) => { const c = h.cloneNode(true); c.querySelector('.n')?.remove(); return `<a href="#${h.id}">${c.textContent.trim()}</a>`; }).join('');
    const as = [...toc.querySelectorAll('a')];
    const io = new IntersectionObserver((es) => es.forEach((e) => {
      if (e.isIntersecting) as.forEach((a) => a.classList.toggle('on', a.getAttribute('href') === '#' + e.target.id));
    }), { rootMargin: '-20% 0px -70% 0px' });
    hs.forEach((h) => io.observe(h));
  }
  document.querySelectorAll('[data-cfg]').forEach((el) => { const v = C[el.dataset.cfg]; if (v) el.textContent = v; });
  const foot = document.createElement('footer');
  foot.className = 'doc-foot';
  foot.innerHTML = `DentLegal · حزمة المرحلة الأولى · الإصدار ${C.docVersion || '1.0'} · ${C.docDateLabel || ''}<br>إعداد: ${C.provider || ''} — جميع البيانات الواردة في النماذج تجريبية`;
  document.body.appendChild(foot);
})();
