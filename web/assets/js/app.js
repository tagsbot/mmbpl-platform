/* MMBPL website interactions (no dependencies) */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  // Toasts fade out
  $$('.toast').forEach(function (t, i) { setTimeout(function () { t.style.transition = 'opacity .4s'; t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 400); }, 5000 + i * 600); });

  // Tabs
  $$('[data-tabs]').forEach(function (bar) {
    $$('[data-tab]', bar).forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('[data-tab]', bar).forEach(function (b) { b.classList.toggle('on', b === btn); });
        var scope = bar.parentNode;
        $$('[data-panel]', scope).forEach(function (p) { p.classList.toggle('on', p.getAttribute('data-panel') === btn.getAttribute('data-tab')); });
      });
    });
  });

  // Book shelves scroll buttons
  $$('[data-shelf]').forEach(function (b) {
    b.addEventListener('click', function () {
      var el = document.getElementById('shelf-' + b.getAttribute('data-shelf'));
      if (!el) return;
      var dir = parseInt(b.getAttribute('data-dir'), 10) * (window.MMBPL && MMBPL.rtl ? -1 : 1);
      el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
    });
  });

  // Show / hide password
  $$('[data-pw]').forEach(function (b) {
    b.addEventListener('click', function () { var i = b.parentNode.querySelector('input'); i.type = i.type === 'password' ? 'text' : 'password'; });
  });

  // OTP boxes
  $$('[data-otp]').forEach(function (wrap) {
    var inputs = $$('input', wrap);
    inputs.forEach(function (inp, idx) {
      inp.addEventListener('input', function () { inp.value = inp.value.replace(/\D/g, '').slice(-1); if (inp.value && inputs[idx + 1]) inputs[idx + 1].focus(); });
      inp.addEventListener('keydown', function (e) { if (e.key === 'Backspace' && !inp.value && inputs[idx - 1]) inputs[idx - 1].focus(); });
      inp.addEventListener('paste', function (e) {
        var d = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        if (d.length > 1) { e.preventDefault(); inputs.forEach(function (x, j) { x.value = d[j] || ''; }); (inputs[Math.min(d.length, inputs.length) - 1] || inp).focus(); }
      });
    });
    if (inputs[0]) inputs[0].focus();
  });

  // Multi-step form
  $$('form[data-steps]').forEach(function (form) {
    var steps = $$('.form-step', form), dots = $$('.steps .s', form), cur = 0;
    var prev = $('[data-prev]', form), next = $('[data-next]', form), submit = $('[data-submit]', form);
    function show(i) {
      cur = i;
      steps.forEach(function (s, j) { s.classList.toggle('on', j === i); });
      dots.forEach(function (d, j) { d.classList.toggle('on', j === i); d.classList.toggle('done', j < i); });
      prev.style.visibility = i === 0 ? 'hidden' : 'visible';
      next.hidden = i === steps.length - 1; submit.hidden = i !== steps.length - 1;
      if (i === steps.length - 1) review();
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function valid(i) {
      var ok = true;
      $$('input,select,textarea', steps[i]).forEach(function (el) { if (!el.checkValidity()) { ok = false; el.reportValidity(); } });
      return ok;
    }
    function review() {
      var t = $('[data-review]', form); if (!t) return;
      var html = '';
      $$('.form-step', form).slice(0, -1).forEach(function (s) {
        $$('.field', s).forEach(function (f) {
          var l = $('label', f), el = $('input,select', f); if (!l || !el) return;
          var v = el.type === 'file' ? (el.files[0] ? el.files[0].name : '—') : (el.tagName === 'SELECT' ? (el.options[el.selectedIndex] || {}).text : el.value);
          html += '<tr><th style="width:40%">' + l.textContent.replace('*', '') + '</th><td>' + (v ? String(v).replace(/</g, '&lt;') : '—') + '</td></tr>';
        });
      });
      var r = $('input[name=membership_type]:checked', form);
      if (r) html += '<tr><th>Type</th><td>' + r.parentNode.querySelector('strong').textContent + '</td></tr>';
      t.innerHTML = html;
    }
    next.addEventListener('click', function () { if (valid(cur)) show(Math.min(cur + 1, steps.length - 1)); });
    prev.addEventListener('click', function () { show(Math.max(cur - 1, 0)); });
    show(0);
  });

  // Gallery lightbox
  $$('[data-lightbox] a').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); var lb = $('#lightbox'); $('img', lb).src = a.href; lb.classList.add('on'); });
  });

  // Archive item modal
  $$('[data-arch]').forEach(function (el) {
    function open() {
      var d = JSON.parse(el.getAttribute('data-arch')), box = $('#archbox');
      $$('[data-f]', box).forEach(function (f) { var k = f.getAttribute('data-f'); if (k === 'img') f.src = d.img; else f.textContent = d[k] || '—'; });
      box.classList.add('on');
    }
    el.addEventListener('click', open);
    el.addEventListener('keydown', function (e) { if (e.key === 'Enter') open(); });
  });
  var ab = $('#archbox'); if (ab) ab.addEventListener('click', function () { ab.classList.remove('on'); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') $$('.lightbox.on').forEach(function (l) { l.classList.remove('on'); }); });

  // PWA
  if ('serviceWorker' in navigator && location.protocol === 'https:') {
    navigator.serviceWorker.register((window.MMBPL ? MMBPL.base : '') + '/sw.js').catch(function () {});
  }
})();
