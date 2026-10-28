(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Menu mobilne
  var nav = $('#nav'), burger = $('#burger');
  if (burger) {
    burger.addEventListener('click', function () {
      burger.setAttribute('aria-expanded', String(nav.classList.toggle('open')));
    });
    $$('#nav a').forEach(function (a) {
      a.addEventListener('click', function () { nav.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); });
    });
  }

  // Lightbox
  var items = $$('[data-lb]'), lb, idx = 0, opener;
  function show(i) {
    idx = (i + items.length) % items.length;
    var it = items[idx], pic = $('picture', lb);
    pic.textContent = '';
    var src = document.createElement('source');
    src.type = 'image/webp'; src.srcset = '/uploads/full/' + it.dataset.base + '.webp';
    var img = document.createElement('img');
    img.src = it.href; img.alt = it.dataset.alt || '';
    pic.append(src, img);
    $('.lb-c', lb).textContent = (idx + 1) + ' / ' + items.length;
  }
  function close() { lb.hidden = true; document.body.style.overflow = ''; if (opener) opener.focus(); }
  function build() {
    lb = document.createElement('div');
    lb.className = 'lb'; lb.hidden = true;
    lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true'); lb.setAttribute('aria-label', 'Podgląd zdjęcia');
    lb.innerHTML = '<button class="lb-x" aria-label="Zamknij">×</button><button class="lb-p" aria-label="Poprzednie">‹</button><picture></picture><button class="lb-n" aria-label="Następne">›</button><p class="lb-c" aria-live="polite"></p>';
    document.body.appendChild(lb);
    $('.lb-x', lb).onclick = close;
    $('.lb-p', lb).onclick = function () { show(idx - 1); };
    $('.lb-n', lb).onclick = function () { show(idx + 1); };
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') show(idx - 1);
      else if (e.key === 'ArrowRight') show(idx + 1);
    });
  }
  items.forEach(function (a, i) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      if (!lb) build();
      opener = a; show(i); lb.hidden = false; document.body.style.overflow = 'hidden';
      $('.lb-x', lb).focus();
    });
  });

  // Formularz kontaktowy
  var form = $('#cform'), msg = $('#fmsg');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('button[type=submit]', form);
      btn.disabled = true; msg.textContent = 'Wysyłanie…';
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { msg.textContent = d.msg; if (d.ok) form.reset(); })
        .catch(function () { msg.textContent = 'Nie udało się wysłać. Zadzwoń lub spróbuj później.'; })
        .finally(function () { btn.disabled = false; });
    });
  }
})();
