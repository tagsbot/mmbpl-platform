/* MMB Library — mobile app (Capacitor). Plain JS, no framework. Talks to /api/v1 on the library website. */
(function () {
  'use strict';
  const VERSION = '1.0.0';
  const CFG = window.APP_CONFIG || {};
  const store = {
    get: (k, d = null) => { try { const v = localStorage.getItem('mmb_' + k); return v === null ? d : JSON.parse(v); } catch { return d; } },
    set: (k, v) => { try { localStorage.setItem('mmb_' + k, JSON.stringify(v)); } catch { /* full */ } },
    del: (k) => localStorage.removeItem('mmb_' + k),
  };
  const S = { lang: store.get('lang', 'en'), token: store.get('token'), member: store.get('member'), server: store.get('server', CFG.server || ''), unread: 0, cache: {} };
  const $app = document.getElementById('app');
  const $tab = document.getElementById('tabbar');

  /* ---------- helpers ---------- */
  const t = (k, v = {}) => { let s = (I18N[S.lang] && I18N[S.lang][k]) || I18N.en[k] || k; for (const x in v) s = s.split('{' + x + '}').join(v[x]); return s; };
  const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const isUr = (s) => /[؀-ۿ]/.test(s || '');
  const fmtDate = (d, time) => { if (!d) return ''; const x = new Date(String(d).replace(' ', 'T')); if (isNaN(x)) return d; const o = { day: '2-digit', month: 'short', year: 'numeric' }; if (time) Object.assign(o, { hour: 'numeric', minute: '2-digit' }); return x.toLocaleDateString(S.lang === 'ur' ? 'en-GB' : 'en-GB', o); };
  const daysLeft = (d) => { if (!d) return null; const a = new Date(); a.setHours(0, 0, 0, 0); const b = new Date(String(d).slice(0, 10) + 'T00:00:00'); return Math.round((b - a) / 86400000); };
  const applyLang = () => { document.documentElement.lang = S.lang; document.documentElement.dir = S.lang === 'ur' ? 'rtl' : 'ltr'; };
  applyLang();

  const ICONS = {
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>', home: '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
    books: '<path d="M2 4h6v16H2zM8 4h6v16H8z"/><path d="m15 5 4-1 3 15-4 1z"/>', card: '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>', bell: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
    back: '<path d="m15 18-6-6 6-6"/>', chev: '<path d="m9 18 6-6-6-6"/>', refresh: '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>', bookmark: '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>',
    heart: '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7z"/>',
    calendar: '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>', info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
    bulb: '<path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/>', clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', pin: '<path d="M12 22s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="10" r="2.5"/>',
    phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
    mail: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>', chat: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>', filter: '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
    check: '<path d="M20 6 9 17l-5-5"/>', x: '<path d="M18 6 6 18M6 6l12 12"/>', alert: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
    lock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>', globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>', share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>', server: '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 8h.01M7 17h.01"/>',
    qr: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/>', history: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/>',
  };
  const ic = (n, s = 22, cls = '') => `<svg class="${cls}" width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[n] || ICONS.info}</svg>`;

  function toast(msg, kind = '') {
    const el = document.getElementById('toast');
    el.className = 'toast ' + kind; el.textContent = msg; el.hidden = false;
    clearTimeout(el._t); el._t = setTimeout(() => { el.hidden = true; }, 3600);
  }
  function sheet(html, onMount) {
    const root = document.getElementById('sheet-root');
    root.innerHTML = `<div class="sheet-bg" data-close></div><div class="sheet" role="dialog"><div class="grab"></div>${html}</div>`;
    root.querySelectorAll('[data-close]').forEach(e => e.addEventListener('click', closeSheet));
    if (onMount) onMount(root.querySelector('.sheet'));
  }
  function closeSheet() { document.getElementById('sheet-root').innerHTML = ''; }

  /* ---------- API ---------- */
  async function api(path, { method = 'GET', body = null, auth = true } = {}) {
    if (!S.server) throw Object.assign(new Error(t('offline')), { code: 'no_server' });
    const h = { Accept: 'application/json', 'Accept-Language': S.lang };
    if (body) h['Content-Type'] = 'application/json';
    if (auth && S.token) h.Authorization = 'Bearer ' + S.token;
    let res;
    try { res = await fetch(S.server.replace(/\/+$/, '') + '/api/v1/' + path, { method, headers: h, body: body ? JSON.stringify(body) : null }); }
    catch (e) { throw Object.assign(new Error(t('offline')), { code: 'offline' }); }
    let data = null; try { data = await res.json(); } catch { /* not json */ }
    if (res.status === 401 && auth && S.token) { signOut(true); throw Object.assign(new Error(t('need_signin')), { code: 'unauthorized' }); }
    if (!res.ok) throw Object.assign(new Error((data && data.message) || t('err_generic')), { code: (data && data.error) || 'http_' + res.status, data });
    return data;
  }
  function signOut(silent) {
    S.token = null; S.member = null; store.del('token'); store.del('member'); S.cache = {};
    if (!silent) location.hash = '#/signin';
  }

  /* ---------- small renderers ---------- */
  const PAL = ['#0B4DA2', '#1E8A2E', '#7A3E12', '#5B2C83', '#0F5E6B', '#8A1E2E', '#2D3B55'];
  function hash(s) { let h = 0; for (const c of String(s)) h = (h * 31 + c.charCodeAt(0)) >>> 0; return h; }
  function cover(b, size = '') {
    const c = PAL[hash(b.title) % PAL.length];
    return `<div class="cover ${size}" style="--c:${c}">${b.cover_url ? `<img src="${esc(b.cover_url)}" alt="" loading="lazy" onerror="this.remove()">` : ''}<span class="t ${isUr(b.title) ? 'ur' : ''}" dir="auto">${esc(b.title)}</span>${b.call_number ? `<span class="cn">${esc(b.call_number)}</span>` : ''}</div>`;
  }
  function badge(b) {
    switch (b.availability) {
      case 'available': return `<span class="badge b-green"><span class="d"></span>${t('st_available')}</span>`;
      case 'checked_out': return `<span class="badge b-amber">${t('st_checked_out', { d: fmtDate(b.next_due) })}</span>`;
      case 'on_hold': return `<span class="badge b-blue">${t('st_on_hold')}</span>`;
      case 'reference': return `<span class="badge b-slate">${t('st_reference')}</span>`;
      default: return `<span class="badge b-slate">${t('st_unknown')}</span>`;
    }
  }
  const langLabel = (l) => t('l_' + (['ur', 'en', 'ar'].includes(l) ? l : 'other'));
  function countdown(due) {
    const d = daysLeft(due); if (d === null) return '';
    const cls = d < 0 ? 'over' : d <= 3 ? 'soon' : 'ok';
    return `<span class="cd ${cls}">${d < 0 ? t('overdue', { n: -d }) : d === 0 ? t('due_today') : t('due_in', { n: d })}</span>`;
  }
  const tile = (b) => `<a class="tile" href="#/book/${b.id}">${cover(b)}<div class="tt" dir="auto">${esc(b.title)}</div><div class="ta" dir="auto">${esc(b.author || '')}</div></a>`;
  const resultRow = (b) => `<a class="list-row" href="#/book/${b.id}" style="color:inherit">${cover(b, 'sm')}<div class="info"><b dir="auto">${esc(b.title)}</b><span dir="auto">${esc(b.author || '')}${b.year ? ' · ' + esc(b.year) : ''}</span><div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">${badge(b)}<span class="badge b-slate">${langLabel(b.language)}</span></div>${b.shelf ? `<span class="tiny" style="margin-top:4px">${ic('pin', 12)} ${esc(b.shelf)}</span>` : ''}</div></a>`;
  const evCard = (e) => { const d = new Date(String(e.start || e.created_at).replace(' ', 'T')); return `<a class="ev-card" href="#/event/${esc(e.slug)}"><div class="im" style="background-image:url('${esc(e.image || 'img/event-placeholder.svg')}')"><span class="date-b"><b>${String(d.getDate()).padStart(2, '0')}</b><span>${d.toLocaleString('en', { month: 'short' })}</span></span></div><div class="bd"><b dir="auto">${esc(e.title)}</b><span class="tiny muted">${e.type === 'event' ? fmtDate(e.start, true) + (e.venue ? ' · ' + esc(e.venue) : '') : fmtDate(e.created_at)}</span></div></a>`; };
  const appbar = (title, { back = true, right = '' } = {}) => `<header class="appbar">${back ? `<button class="icon-btn" data-back aria-label="Back">${ic('back', 22, 'flip')}</button>` : ''}<h1>${esc(title)}</h1>${right}</header>`;
  const bell = () => `<a class="icon-btn" href="#/notifications" aria-label="${t('notifications')}">${ic('bell')}${S.unread ? `<span class="dot">${S.unread > 9 ? '9+' : S.unread}</span>` : ''}</a>`;
  const loading = () => `<div class="spinner"></div>`;
  const errorBox = (e, retry = true) => `<div class="empty">${ic('alert', 40)}<p style="margin-top:10px">${esc(e.message)}</p>${retry ? `<button class="btn sm outline" data-reload>${t('retry')}</button>` : ''}</div>`;
  const needSignin = () => `<div class="empty" style="padding-top:60px">${ic('lock', 44)}<h2 style="margin-top:12px">${t('need_signin')}</h2><p>${t('need_signin_sub')}</p><a class="btn" href="#/signin">${t('sign_in')}</a><p style="margin-top:12px"><a href="#/activate">${t('activate')}</a></p></div>`;

  /* ---------- Code 128 barcode ---------- */
  const C128 = '212222 222122 222221 121223 121322 131222 122213 122312 132212 221213 221312 231212 112232 122132 122231 113222 123122 123221 223211 221132 221231 213212 223112 312131 311222 321122 321221 312212 322112 322211 212123 212321 232121 111323 131123 131321 112313 132113 132311 211313 231113 231311 112133 112331 132131 113123 113321 133121 313121 211331 231131 213113 213311 213131 311123 311321 331121 312113 312311 332111 314111 221411 431111 111224 111422 121124 121421 141122 141221 112214 112412 122114 122411 142112 142211 241211 221114 413111 241112 134111 111242 121142 121241 114212 124112 124211 411212 421112 421211 212141 214121 412121 111143 111341 131141 114113 114311 411113 411311 113141 114131 311141 411131 211412 211214 211232 2331112'.split(' ');
  function barcode(text) {
    const s = String(text).replace(/[^\x20-\x7E]/g, ''); const codes = [104]; let sum = 104;
    [...s].forEach((ch, i) => { const v = ch.charCodeAt(0) - 32; codes.push(v); sum += v * (i + 1); });
    codes.push(sum % 103, 106);
    let x = 10, bars = '';
    for (const c of codes) { const p = C128[c]; for (let i = 0; i < p.length; i++) { const w = +p[i]; if (i % 2 === 0) bars += `<rect x="${x}" y="0" width="${w}" height="60"/>`; x += w; } }
    return `<svg viewBox="0 0 ${x + 10} 60" preserveAspectRatio="none"><rect width="100%" height="100%" fill="#fff"/><g fill="#000">${bars}</g></svg>`;
  }
  function qrSvg(text, size = 180) { try { const q = qrcode(0, 'M'); q.addData(String(text)); q.make(); return q.createSvgTag({ cellSize: Math.max(2, Math.floor(size / q.getModuleCount())), margin: 0 }); } catch { return ''; } }

  /* ---------- screens ---------- */
  const R = {};

  R.splash = () => {
    $app.innerHTML = `<div class="splash"><img src="img/logo-192.png" alt=""><h1>${t('library_full')}</h1><p style="opacity:.85">${t('place')}</p><div class="waves"></div></div>`;
    setTimeout(() => { location.hash = !store.get('onboarded') ? '#/lang' : '#/home'; }, 1100);
  };

  R.lang = () => {
    $app.innerHTML = `<div class="onb"><div style="text-align:center;margin-top:20px"><img src="img/logo-96.png" alt="" style="width:72px"><h1 style="margin-top:14px">${t('choose_lang')}</h1><p class="muted">اپنی زبان منتخب کریں</p></div>
      <div style="margin-top:22px">
        <button class="lang-card ${S.lang === 'en' ? 'on' : ''}" data-lang="en"><span style="font-size:26px">Aa</span><span><b>English</b><span class="muted small">${I18N.en.eng_sub}</span></span></button>
        <button class="lang-card ${S.lang === 'ur' ? 'on' : ''}" data-lang="ur"><span style="font-size:26px" class="ur">اب</span><span><b class="ur">اردو</b><span class="muted small ur">${I18N.ur.urdu_sub}</span></span></button>
      </div>
      <div class="field" style="margin-top:10px"><label>${ic('server', 16)} ${t('server')}</label><input id="srv" type="url" inputmode="url" dir="ltr" value="${esc(S.server)}" placeholder="https://library.example.com"><div class="hint">${t('server_hint')}</div></div>
      <div style="flex:1"></div><button class="btn" id="go">${t('continue')}</button><p class="muted small" style="text-align:center;margin-top:10px">${t('choose_lang_sub')}</p></div>`;
    $app.querySelectorAll('[data-lang]').forEach(b => b.onclick = () => { S.lang = b.dataset.lang; store.set('lang', S.lang); applyLang(); R.lang(); });
    document.getElementById('go').onclick = () => {
      let v = document.getElementById('srv').value.trim().replace(/\/+$/, '');
      if (v && !/^https?:\/\//.test(v)) v = 'https://' + v;
      if (!v) { toast(t('server_hint'), 'err'); return; }
      S.server = v; store.set('server', v); location.hash = '#/onboarding/0';
    };
  };

  R.onboarding = (i = '0') => {
    const n = +i; const slides = [['search', 'onb1_t', 'onb1_d'], ['refresh', 'onb2_t', 'onb2_d'], ['qr', 'onb3_t', 'onb3_d']];
    const s = slides[n] || slides[0];
    $app.innerHTML = `<div class="onb"><div style="display:flex;justify-content:flex-end"><button class="link" id="skip">${t('skip')}</button></div>
      <div class="art"><div class="circle">${ic(s[0], 96)}</div></div>
      <div style="text-align:center"><h1>${t(s[1])}</h1><p class="muted">${t(s[2])}</p></div>
      <div class="dots">${slides.map((_, k) => `<i class="${k === n ? 'on' : ''}"></i>`).join('')}</div>
      <button class="btn" id="nx">${n < 2 ? t('next') : t('get_started')}</button></div>`;
    const done = () => { store.set('onboarded', true); location.hash = S.token ? '#/home' : '#/signin'; };
    document.getElementById('skip').onclick = done;
    document.getElementById('nx').onclick = () => n < 2 ? (location.hash = '#/onboarding/' + (n + 1)) : done();
  };

  R.signin = () => {
    $app.innerHTML = `<div class="onb"><div style="text-align:center;margin-top:10px"><img src="img/logo-96.png" alt="" style="width:76px"><h1 style="margin-top:12px">${t('welcome_back')}</h1><p class="muted">${t('signin_sub')}</p></div>
      <form id="f" style="margin-top:12px"><div class="field"><label>${t('card_or_mobile')}</label><input name="login" required autocomplete="username" dir="ltr" placeholder="MMB-004521 / 03XX-XXXXXXX"></div>
      <div class="field"><label>${t('password')}</label><input name="password" type="password" required autocomplete="current-password"></div>
      <button class="btn">${t('sign_in')}</button></form>
      <div class="card" style="margin-top:18px;background:var(--surface)"><b>${t('first_time')}</b><a class="btn outline sm" style="margin-top:10px;width:100%" href="#/activate">${t('activate')}</a></div>
      <a class="btn ghost" href="${esc(S.server)}/membership" target="_blank" rel="noopener">${t('not_member')}</a>
      <a class="btn ghost" href="#/home" style="color:var(--muted)">${t('guest')}</a></div>`;
    document.getElementById('f').onsubmit = async (e) => {
      e.preventDefault(); const fd = new FormData(e.target); const btn = e.target.querySelector('button'); btn.disabled = true;
      try {
        const r = await api('auth/login', { method: 'POST', auth: false, body: { login: fd.get('login'), password: fd.get('password'), device: 'android-app' } });
        setSession(r); location.hash = '#/home';
      } catch (err) { toast(err.message, 'err'); if (err.code === 'not_activated') location.hash = '#/activate'; }
      finally { btn.disabled = false; }
    };
  };
  function setSession(r) { S.token = r.token; S.member = r.member; store.set('token', r.token); store.set('member', r.member); if (r.member && r.member.lang && r.member.lang !== S.lang) { S.lang = r.member.lang; store.set('lang', S.lang); applyLang(); } }

  R.activate = () => {
    $app.innerHTML = appbar(t('activate')) + `<div class="pad"><p class="muted">${t('activate_sub')}</p><form id="f">
      <div class="field"><label>${t('card_number')}</label><input name="cardnumber" required dir="ltr" placeholder="MMB-004521"></div>
      <div class="field"><label>${t('mobile')}</label><input name="phone" type="tel" required dir="ltr" placeholder="03XX-XXXXXXX"></div>
      <div class="field"><label>${t('dob')}</label><input name="dob" type="date"></div><button class="btn">${t('continue')}</button></form></div>`;
    document.getElementById('f').onsubmit = async (e) => {
      e.preventDefault(); const fd = Object.fromEntries(new FormData(e.target)); const btn = e.target.querySelector('button'); btn.disabled = true;
      try { const r = await api('auth/activate', { method: 'POST', auth: false, body: fd }); S.act = r; activateStep2(); }
      catch (err) { toast(err.message, 'err'); } finally { btn.disabled = false; }
    };
  };
  function activateStep2() {
    const a = S.act;
    $app.innerHTML = appbar(a.otp_required ? t('verify_title') : t('set_password')) + `<div class="pad"><form id="f">
      ${a.otp_required ? `<p class="muted" style="text-align:center">${t('verify_sub', { p: esc(a.phone_hint) })}</p>${a.demo_code ? `<div class="alert warn">${t('demo_code')} <b class="mono">${esc(a.demo_code)}</b></div>` : ''}
        <div class="otp">${[0, 1, 2, 3, 4, 5].map(() => '<input inputmode="numeric" maxlength="1" pattern="[0-9]">').join('')}</div><div style="height:18px"></div>` : ''}
      <p class="muted small">${t('set_password_sub')}</p>
      <div class="field"><label>${t('new_password')}</label><input name="password" type="password" minlength="8" required autocomplete="new-password"></div>
      <div class="field"><label>${t('confirm_password')}</label><input name="password2" type="password" minlength="8" required autocomplete="new-password"></div>
      <button class="btn">${t('verify')}</button></form></div>`;
    const boxes = [...$app.querySelectorAll('.otp input')];
    boxes.forEach((b, i) => { b.oninput = () => { b.value = b.value.replace(/\D/g, '').slice(-1); if (b.value && boxes[i + 1]) boxes[i + 1].focus(); }; b.onkeydown = (e) => { if (e.key === 'Backspace' && !b.value && boxes[i - 1]) boxes[i - 1].focus(); }; });
    if (boxes[0]) boxes[0].focus();
    document.getElementById('f').onsubmit = async (e) => {
      e.preventDefault(); const fd = new FormData(e.target);
      if (fd.get('password') !== fd.get('password2')) return toast(t('passwords_differ'), 'err');
      const btn = e.target.querySelector('button'); btn.disabled = true;
      try { const r = await api('auth/activate/verify', { method: 'POST', auth: false, body: { ticket: a.ticket, code: boxes.map(b => b.value).join(''), password: fd.get('password'), device: 'android-app' } }); setSession(r); toast('✓', 'ok'); location.hash = '#/home'; }
      catch (err) { toast(err.message, 'err'); } finally { btn.disabled = false; }
    };
  }

  R.home = async () => {
    const m = S.member;
    const head = `<header class="appbar"><div class="brand"><img src="img/logo-96.png" alt=""><div><b>${t('library_full')}</b><small>${t('place')}</small></div></div>${S.token ? bell() : `<a class="btn sm" href="#/signin">${t('sign_in')}</a>`}</header>`;
    $app.innerHTML = head + `<div class="hello pad"><h1 style="font-size:22px;margin-top:6px">${m ? t('salam') + (S.lang === 'ur' ? '، ' : ', ') + esc(m.firstname || m.name) : t('guest_hi')}</h1>
      <form class="search" id="hs" style="margin-top:10px">${ic('search', 20)}<input name="q" placeholder="${t('search_ph')}" enterkeyhint="search" autocomplete="off"></form></div><div id="home-body">${loading()}</div>`;
    document.getElementById('hs').onsubmit = (e) => { e.preventDefault(); const q = new FormData(e.target).get('q'); location.hash = '#/search?q=' + encodeURIComponent(q); };
    try {
      const [home, me, loans] = await Promise.all([api('home'), S.token ? api('me').catch(() => null) : null, S.token ? api('loans').catch(() => null) : null]);
      if (me) { S.member = me.member; store.set('member', me.member); S.unread = me.counts.unread; const b = $app.querySelector('.appbar'); if (b) b.outerHTML = head.replace(/<a class="icon-btn" href="#\/notifications"[\s\S]*?<\/a>/, bell()); }
      const due = loans ? loans.loans.filter(l => l.days_left !== null && l.days_left <= 7).slice(0, 2) : [];
      const mm = S.member;
      document.getElementById('home-body').innerHTML = `
        ${mm ? `<div class="pad-x"><a class="card-tile" href="#/card"><div style="flex:1"><div class="lbl">${t('card_title')}</div><div style="font-size:18px;font-weight:700;margin-top:2px">${esc(mm.name)}</div><div class="mono">${esc(mm.cardnumber)}</div><div class="tiny" style="opacity:.8">${t('valid_until')} ${fmtDate(mm.expiry_date)}</div></div><div class="qr">${qrSvg(mm.cardnumber, 64)}</div><div class="waves" style="height:80px"></div></a></div>` : ''}
        ${due.length ? `<div class="section"><div class="section-head"><h2>${t('due_soon')}</h2><a href="#/mybooks">${t('see_all')}</a></div><div class="pad-x">${due.map(l => `<div class="card due-card">${cover(l.book, 'sm')}<div style="flex:1;min-width:0"><b dir="auto" style="display:block">${esc(l.book.title)}</b><div style="margin:4px 0">${countdown(l.due_date)}</div></div>${l.renewable && !l.renew_pending ? `<button class="btn sm outline" data-renew="${l.id}">${ic('refresh', 16)} ${t('renew')}</button>` : (l.renew_pending ? `<span class="badge b-blue">${t('renew_pending')}</span>` : '')}</div>`).join('')}</div></div>` : ''}
        <div class="section quick">
          <button data-go="#/search">${'<span class="ic">' + ic('search') + '</span>'}${t('q_search')}</button>
          <button data-go="#/mybooks">${'<span class="ic">' + ic('books') + '</span>'}${t('q_mybooks')}</button>
          <button data-go="#/mybooks/holds">${'<span class="ic">' + ic('bookmark') + '</span>'}${t('q_reserve')}</button>
          <button data-go="#/suggest">${'<span class="ic">' + ic('bulb') + '</span>'}${t('q_suggest')}</button>
          <button data-go="#/events">${'<span class="ic">' + ic('calendar') + '</span>'}${t('q_events')}</button>
          <button data-go="#/library">${'<span class="ic">' + ic('info') + '</span>'}${t('q_info')}</button>
        </div>
        ${home.new_arrivals.length ? `<div class="section"><div class="section-head"><h2>${t('new_arrivals')}</h2><a href="#/search?sort=newest">${t('see_all')}</a></div><div class="shelf">${home.new_arrivals.map(tile).join('')}</div></div>` : ''}
        ${home.popular.length ? `<div class="section"><div class="section-head"><h2>${t('popular')}</h2><a href="#/search?sort=popular">${t('see_all')}</a></div><div class="shelf">${home.popular.map(tile).join('')}</div></div>` : ''}
        ${home.events.length ? `<div class="section"><div class="section-head"><h2>${t('upcoming')}</h2><a href="#/events">${t('see_all')}</a></div><div class="shelf">${home.events.map(evCard).join('')}</div></div>` : ''}
        <div style="height:16px"></div>`;
    } catch (e) { document.getElementById('home-body').innerHTML = errorBox(e); }
  };

  R.search = async (_, qs) => {
    const p = new URLSearchParams(qs || '');
    const st = { q: p.get('q') || '', lang: p.get('lang') || '', avail: p.get('avail') || '', sort: p.get('sort') || '', subject: p.get('subject') || '' };
    const recent = store.get('recent', []);
    const hasQuery = st.q || st.lang || st.avail || st.sort || st.subject;
    const push = (o) => { const n = Object.assign({}, st, o); const q = new URLSearchParams(Object.entries(n).filter(([, v]) => v)); location.hash = '#/search?' + q; };
    $app.innerHTML = `<header class="appbar"><form class="search" id="sf" style="flex:1;box-shadow:none">${ic('search', 20)}<input name="q" value="${esc(st.q)}" placeholder="${t('search_ph')}" enterkeyhint="search" autocomplete="off" ${hasQuery ? '' : 'autofocus'}></form><button class="icon-btn" id="flt" aria-label="${t('filters')}">${ic('filter')}</button></header>
      <div class="chips" style="padding-top:12px"><button class="chip ${st.avail ? 'on' : ''}" data-f="avail">${t('available_now')}</button><button class="chip ${st.lang === 'ur' ? 'on' : ''}" data-f="ur">${t('l_ur')}</button><button class="chip ${st.lang === 'en' ? 'on' : ''}" data-f="en">${t('l_en')}</button>${st.subject ? `<button class="chip on" data-f="subj">${esc(st.subject)} ✕</button>` : ''}</div>
      <div id="sbody">${hasQuery ? loading() : ''}</div>`;
    document.getElementById('sf').onsubmit = (e) => { e.preventDefault(); const q = new FormData(e.target).get('q').trim(); if (q) store.set('recent', [q, ...recent.filter(x => x !== q)].slice(0, 8)); push({ q }); };
    $app.querySelectorAll('[data-f]').forEach(c => c.onclick = () => { const f = c.dataset.f; if (f === 'avail') push({ avail: st.avail ? '' : '1' }); else if (f === 'subj') push({ subject: '' }); else push({ lang: st.lang === f ? '' : f }); });
    document.getElementById('flt').onclick = () => sheet(`<h2>${t('filters')}</h2>
      <div class="field"><label>${t('language')}</label><select id="fl"><option value="">${t('all')}</option>${['ur', 'en', 'ar', 'other'].map(l => `<option value="${l}" ${st.lang === l ? 'selected' : ''}>${langLabel(l)}</option>`).join('')}</select></div>
      <div class="field"><label>${t('sort')}</label><select id="fs">${[['', 's_relevance'], ['newest', 's_newest'], ['title', 's_title'], ['popular', 's_popular']].map(([v, k]) => `<option value="${v}" ${st.sort === v ? 'selected' : ''}>${t(k)}</option>`).join('')}</select></div>
      <label class="list-row" style="justify-content:space-between"><span>${t('available_now')}</span><span class="switch"><input type="checkbox" id="fa" ${st.avail ? 'checked' : ''}><span></span></span></label>
      <div class="btn-row" style="margin-top:14px"><button class="btn outline" id="frs">${t('reset')}</button><button class="btn" id="fap">${t('apply')}</button></div>`, (sh) => {
      sh.querySelector('#fap').onclick = () => { closeSheet(); push({ lang: sh.querySelector('#fl').value, sort: sh.querySelector('#fs').value, avail: sh.querySelector('#fa').checked ? '1' : '' }); };
      sh.querySelector('#frs').onclick = () => { closeSheet(); location.hash = '#/search?q=' + encodeURIComponent(st.q); };
    });
    const body = document.getElementById('sbody');
    if (!hasQuery) {
      const cats = [['cat_urdu', 'lang', 'ur'], ['cat_islamic', 'subject', 'Islamic'], ['cat_science', 'subject', 'Science'], ['cat_exams', 'subject', 'Competitive'], ['cat_children', 'subject', 'Children'], ['cat_history', 'subject', 'History']];
      body.innerHTML = `${recent.length ? `<div class="section"><div class="section-head"><h2>${t('recent')}</h2></div><div class="pad-x card" style="margin:0 16px;padding:0 16px">${recent.map(r => `<a class="list-row" href="#/search?q=${encodeURIComponent(r)}" style="color:inherit">${ic('history', 18)}<div class="info"><b dir="auto">${esc(r)}</b></div>${ic('chev', 18, 'flip')}</a>`).join('')}</div></div>` : ''}
        <div class="section"><div class="section-head"><h2>${t('categories')}</h2></div><div class="quick">${cats.map(([k, f, v]) => `<button data-go="#/search?${f}=${encodeURIComponent(v)}"><span class="ic">${ic('books')}</span>${t(k)}</button>`).join('')}</div></div>`;
      return;
    }
    try {
      const qp = new URLSearchParams({ q: st.q, lang: st.lang, avail: st.avail, sort: st.sort, subject: st.subject });
      let page = 1; const r = await api('catalog?' + qp);
      if (!r.results.length) { body.innerHTML = `<div class="empty"><img src="img/empty-books.svg" alt=""><h2>${t('no_results')}</h2><p>${t('no_results_sub')}</p><a class="btn sm" href="#/suggest?title=${encodeURIComponent(st.q)}">${ic('bulb', 16)} ${t('suggest_this')}</a></div>`; return; }
      body.innerHTML = `<p class="pad-x muted small" style="margin-top:12px">${t('results', { n: r.total })}</p><div class="card" style="margin:0 16px;padding:0 14px" id="rl">${r.results.map(resultRow).join('')}</div><div id="more" class="pad"></div>`;
      const more = document.getElementById('more');
      const addMore = () => { if (page * r.per_page >= r.total) { more.innerHTML = ''; return; } more.innerHTML = `<button class="btn outline">${t('see_all')} (${r.total - page * r.per_page})</button>`; more.firstChild.onclick = async () => { page++; qp.set('page', page); const n = await api('catalog?' + qp); document.getElementById('rl').insertAdjacentHTML('beforeend', n.results.map(resultRow).join('')); addMore(); }; };
      addMore();
    } catch (e) { body.innerHTML = errorBox(e); }
  };

  R.book = async (id) => {
    $app.innerHTML = appbar('') + loading();
    try {
      const b = await api('books/' + id);
      const me = b.me || {};
      const action = !S.token ? `<a class="btn" href="#/signin">${ic('lock', 18)} ${t('sign_in_reserve')}</a>`
        : me.reserved ? `<button class="btn" disabled>${ic('check', 18)} ${me.reserved === 'ready' ? t('ready_pickup') : t('reserved')}</button>`
          : `<button class="btn" id="rsv">${ic('bookmark', 18)} ${t('reserve')}</button>`;
      $app.innerHTML = appbar(b.title, { right: `<button class="icon-btn" id="shr" aria-label="${t('share')}">${ic('share')}</button>` }) + `
        <div class="pad" style="background:linear-gradient(180deg,#fff,var(--surface));text-align:center">${cover(b, 'lg')}
          <h1 style="margin-top:16px" dir="auto">${esc(b.title)}</h1>${b.subtitle ? `<p class="muted" dir="auto">${esc(b.subtitle)}</p>` : ''}
          <p dir="auto"><a href="#/search?q=${encodeURIComponent(b.author || '')}"><b>${esc(b.author || '')}</b></a></p>
          <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">${badge(b)}<span class="badge b-slate">${langLabel(b.language)}</span>${b.call_number ? `<span class="call">${esc(b.call_number)}</span>` : ''}</div>
          <p class="muted small" style="margin-top:8px">${t('copies', { a: b.available, t: b.copies })}</p></div>
        <div class="pad"><div class="alert info">${ic('info', 18)}<span>${t('physical')}</span></div>
          ${b.summary ? `<div class="card"><h3>${t('summary')}</h3><p class="muted" dir="auto">${esc(b.summary)}</p></div>` : ''}
          <div class="card"><h3>${t('details')}</h3>${[['publisher', b.publisher], ['year', b.year], ['edition', b.edition], ['pages', b.pages], ['ISBN', b.isbn], ['call_number', b.call_number]].filter(x => x[1]).map(([k, v]) => `<div class="list-row"><div class="info"><span>${k === 'ISBN' ? 'ISBN' : t(k)}</span></div><b dir="auto">${esc(v)}</b></div>`).join('')}
            ${b.subjects.length ? `<div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap">${b.subjects.map(s => `<a class="chip" href="#/search?subject=${encodeURIComponent(s)}">${esc(s)}</a>`).join('')}</div>` : ''}</div>
          <div class="card"><h3>${t('copies_title')}</h3>${b.items.map(i => `<div class="list-row"><div class="info"><b>${esc(i.shelf || '—')}</b><span class="mono">${esc(i.barcode || '')} · ${esc(i.call_number || '')}</span></div>${i.status === 'available' ? `<span class="badge b-green">${t('st_available')}</span>` : i.status === 'checked_out' ? `<span class="badge b-amber">${fmtDate(i.due_date)}</span>` : i.status === 'reference' ? `<span class="badge b-slate">${t('st_reference')}</span>` : `<span class="badge b-blue">${t('st_on_hold')}</span>`}</div>`).join('') || '<p class="muted">—</p>'}</div>
        </div>
        ${b.more_by_author.length ? `<div class="section" style="margin-top:0"><div class="section-head"><h2>${t('more_author')}</h2></div><div class="shelf">${b.more_by_author.map(tile).join('')}</div></div>` : ''}
        <div style="height:90px"></div>
        <div class="sticky-bar">${action}${S.token ? `<button class="btn outline" id="fav" style="width:56px;padding:0;flex:0 0 56px;${me.in_list ? 'background:var(--red-50);color:var(--red);border-color:var(--red)' : ''}" aria-label="List">${ic('heart')}</button>` : ''}</div>`;
      $tab.hidden = true;
      const rsv = document.getElementById('rsv');
      if (rsv) rsv.onclick = () => sheet(`<div style="display:flex;gap:14px;align-items:center">${cover(b, 'sm')}<div><h2 dir="auto">${t('reserve_confirm')}</h2><p class="muted small" dir="auto">${esc(b.title)}</p></div></div><p class="muted">${t('reserve_confirm_sub')}</p><div class="btn-row"><button class="btn outline" data-close>${t('cancel')}</button><button class="btn" id="cf">${t('confirm')}</button></div>`, (sh) => {
        sh.querySelector('#cf').onclick = async () => { try { const r = await api('books/' + id + '/reserve', { method: 'POST' }); closeSheet(); toast(r.message, 'ok'); R.book(id); } catch (e) { closeSheet(); toast(e.message, 'err'); } };
      });
      const fav = document.getElementById('fav');
      if (fav) fav.onclick = async () => { try { const r = await api('books/' + id + '/list', { method: 'POST' }); fav.style.cssText = 'width:56px;padding:0;flex:0 0 56px;' + (r.in_list ? 'background:var(--red-50);color:var(--red);border-color:var(--red)' : ''); } catch (e) { toast(e.message, 'err'); } };
      document.getElementById('shr').onclick = () => shareText(b.title + (b.author ? ' — ' + b.author : ''), b.url);
    } catch (e) { $app.innerHTML = appbar('') + errorBox(e); }
  };

  R.mybooks = async (tab = 'loans') => {
    if (!S.token) { $app.innerHTML = appbar(t('q_mybooks'), { back: false }) + needSignin(); return; }
    const tabs = [['loans', 'borrowed'], ['holds', 'reservations'], ['history', 'history']];
    $app.innerHTML = appbar(t('q_mybooks'), { back: false, right: bell() }) + `<div class="seg">${tabs.map(([k, l]) => `<button class="${tab === k ? 'on' : ''}" data-go="#/mybooks/${k}">${t(l)}</button>`).join('')}</div><div id="mb">${loading()}</div>`;
    const body = document.getElementById('mb');
    try {
      if (tab === 'loans') {
        const r = await api('loans');
        if (!r.loans.length) { body.innerHTML = `<div class="empty"><img src="img/empty-books.svg" alt=""><h2>${t('no_loans')}</h2><p>${t('no_loans_sub')}</p><a class="btn sm" href="#/search">${t('q_search')}</a></div>`; return; }
        body.innerHTML = `<div class="pad-x">${r.loans.map(l => `<div class="card"><div style="display:flex;gap:12px">${cover(l.book, 'sm')}<div style="flex:1;min-width:0"><b dir="auto">${esc(l.book.title)}</b><div class="small muted" dir="auto">${esc(l.book.author || '')}</div>
          <div class="small" style="margin-top:6px">${fmtDate(l.due_date)} ${countdown(l.due_date)}</div>${l.renewals ? `<div class="tiny muted">${t('renewed_x', { n: l.renewals })}</div>` : ''}</div></div>
          ${!l.renewable ? `<div class="alert err" style="margin:10px 0 0">${ic('alert', 16)}<span>${t('cannot_renew_res')}</span></div>` : ''}
          <div style="margin-top:10px">${l.renew_pending ? `<span class="badge b-blue">${t('renew_pending')}</span>` : `<button class="btn sm outline" data-renew="${l.id}" ${l.renewable ? '' : 'disabled'} style="width:100%">${ic('refresh', 16)} ${t('renew')}</button>`}</div></div>`).join('')}
          <button class="btn" id="ra" style="margin-top:14px">${ic('refresh', 18)} ${t('renew_all')}</button><p class="tiny muted" style="text-align:center;margin-top:8px">${t('pull_refresh')}</p></div>`;
        document.getElementById('ra').onclick = async () => { try { const x = await api('loans/renew-all', { method: 'POST' }); toast(x.message, x.ok ? 'ok' : 'err'); R.mybooks('loans'); } catch (e) { toast(e.message, 'err'); } };
      } else if (tab === 'holds') {
        const r = await api('holds');
        if (!r.holds.length) { body.innerHTML = `<div class="empty"><img src="img/empty-books.svg" alt=""><h2>${t('no_holds')}</h2><a class="btn sm" href="#/search">${t('q_search')}</a></div>`; return; }
        body.innerHTML = `<div class="pad-x">${r.holds.map(h => `<div class="card"><div style="display:flex;gap:12px;align-items:center">${cover(h.book, 'sm')}<div style="flex:1;min-width:0"><b dir="auto">${esc(h.book.title)}</b><div style="margin-top:6px">
          ${h.status === 'ready' ? `<span class="badge b-green">${t('ready_pickup')}</span><div class="small" style="margin-top:4px">${h.pickup_by ? t('collect_by', { d: fmtDate(h.pickup_by) }) : ''}</div>` : h.status === 'requested' ? `<span class="badge b-blue">${t('requested')}</span>` : `<span class="badge b-amber">${t('waiting')}</span> <span class="small muted">${h.queue_position ? t('queue', { n: h.queue_position }) : ''}</span>`}
          </div></div><button class="btn sm danger" data-cancel="${h.id}">${t('cancel')}</button></div></div>`).join('')}</div>`;
      } else {
        const r = await api('history');
        if (!r.history.length) { body.innerHTML = `<div class="empty"><h2>${t('no_history')}</h2></div>`; return; }
        const g = {}; r.history.forEach(h => { const k = new Date((h.returned_at || h.issued_at || '') + 'T00:00:00').toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }); (g[k] = g[k] || []).push(h); });
        body.innerHTML = Object.entries(g).map(([k, list]) => `<h3 class="pad-x muted" style="margin-top:14px">${esc(k)}</h3><div class="card" style="margin:0 16px;padding:0 14px">${list.map(h => `<a class="list-row" href="#/book/${h.book.id}" style="color:inherit">${cover(h.book, 'sm')}<div class="info"><b dir="auto">${esc(h.book.title)}</b><span>${fmtDate(h.issued_at)} → ${fmtDate(h.returned_at)}</span></div></a>`).join('')}</div>`).join('');
      }
    } catch (e) { body.innerHTML = errorBox(e); }
  };

  R.card = async () => {
    if (!S.token) { $app.innerHTML = appbar(t('tab_card'), { back: false }) + needSignin(); return; }
    let m = S.member;
    try { const me = await api('me'); m = me.member; S.member = m; store.set('member', m); } catch { /* offline: show cached card */ }
    const st = m.status || 'active';
    $app.innerHTML = appbar(t('card_title'), { back: false }) + `<div class="pad">
      ${st === 'expired' ? `<div class="alert warn">${ic('alert', 18)}<div><b>${t('card_expired')}</b><br>${t('card_expired_sub')}</div></div>` : ''}
      <div class="libcard" id="lc"><div class="head"><img src="img/logo-96.png" alt=""><div><b>${t('library_full')}</b><div class="lbl">${t('place')}</div></div></div>
        <div class="qrbox">${qrSvg(m.cardnumber, 190)}</div>
        <div style="text-align:center"><div class="lbl">${t('member')}</div><div style="font-size:21px;font-weight:700">${esc(m.name)}</div><div class="mono" style="font-size:17px">${esc(m.cardnumber)}</div>
          <div class="small" style="opacity:.85;margin-top:4px">${esc(m.category || '')} · ${t('valid_until')} ${fmtDate(m.expiry_date)}</div>
          <span class="badge ${st === 'active' ? 'b-green' : st === 'expired' ? 'b-amber' : 'b-red'}" style="margin:10px 0">${t(st)}</span></div>
        <div class="bar">${barcode(m.cardnumber)}</div><div class="waves"></div></div>
      <div class="btn-row" style="margin-top:16px"><button class="btn outline" id="br">${ic('sun', 18)} ${t('brightness')}</button><button class="btn outline" id="sh" style="flex:0 0 auto;width:auto">${ic('share', 18)}</button></div>
      <div class="alert info" style="margin-top:14px">${ic('info', 18)}<span>${t('how_card')}</span></div></div>`;
    document.getElementById('br').onclick = () => { const el = document.getElementById('lc'); (el.requestFullscreen ? el.requestFullscreen() : Promise.reject()).catch(() => {}); el.style.background = '#fff'; el.style.color = '#0F1B2D'; };
    document.getElementById('sh').onclick = () => shareText(t('library_full') + ' — ' + m.name + ' (' + m.cardnumber + ')');
  };

  R.notifications = async () => {
    if (!S.token) { $app.innerHTML = appbar(t('notifications')) + needSignin(); return; }
    const f = (location.hash.split('?')[1] || '').replace('f=', '');
    $app.innerHTML = appbar(t('notifications'), { right: `<button class="link" id="mr" style="padding:0 6px">${t('mark_read')}</button>` }) + `<div class="chips" style="padding-top:12px">${[['', 'all'], ['books', 'books'], ['event', 'events'], ['library', 'library']].map(([k, l]) => `<a class="chip ${f === k ? 'on' : ''}" href="#/notifications${k ? '?f=' + k : ''}">${t(l)}</a>`).join('')}</div><div id="nb">${loading()}</div>`;
    document.getElementById('mr').onclick = async () => { await api('notifications/read', { method: 'POST' }).catch(() => {}); S.unread = 0; R.notifications(); };
    try {
      const r = await api('notifications'); S.unread = r.unread;
      const icons = { due: 'clock', overdue: 'alert', hold: 'bookmark', renew: 'refresh', event: 'calendar', membership: 'card', library: 'info' };
      let list = r.notifications;
      if (f === 'books') list = list.filter(n => ['due', 'overdue', 'hold', 'renew'].includes(n.type)); else if (f) list = list.filter(n => n.type === f);
      if (!list.length) { document.getElementById('nb').innerHTML = `<div class="empty">${ic('bell', 46)}<h2 style="margin-top:10px">${t('caught_up')}</h2><p>${t('caught_up_sub')}</p></div>`; return; }
      const today = new Date().toISOString().slice(0, 10);
      const grp = (arr, title) => arr.length ? `<h3 class="pad-x muted" style="margin-top:14px">${title}</h3><div class="card" style="margin:0 16px;padding:0 14px">${arr.map(n => `<a class="list-row" style="color:inherit" href="${n.link ? linkToRoute(n.link) : '#/notifications'}"><span style="width:40px;height:40px;border-radius:50%;background:var(--blue-50);color:var(--blue);display:grid;place-items:center;flex:0 0 40px">${ic(icons[n.type] || 'bell', 18)}</span><div class="info"><b dir="auto">${esc(n.title)}</b>${n.body ? `<span dir="auto">${esc(n.body)}</span>` : ''}<span class="tiny">${fmtDate(n.created_at, true)}</span></div>${n.read ? '' : '<span style="width:9px;height:9px;border-radius:50%;background:var(--blue);flex:0 0 9px"></span>'}</a>`).join('')}</div>` : '';
      document.getElementById('nb').innerHTML = grp(list.filter(n => n.created_at.slice(0, 10) === today), t('today')) + grp(list.filter(n => n.created_at.slice(0, 10) !== today), t('earlier'));
    } catch (e) { document.getElementById('nb').innerHTML = errorBox(e); }
  };
  function linkToRoute(link) {
    if (/^account\/loans/.test(link)) return '#/mybooks/loans';
    if (/^account\/holds/.test(link)) return '#/mybooks/holds';
    if (/^book\/(\d+)/.test(link)) return '#/book/' + link.match(/^book\/(\d+)/)[1];
    if (/^events\/(.+)/.test(link)) return '#/event/' + link.match(/^events\/(.+)/)[1];
    if (/^account\/profile/.test(link)) return '#/profile';
    return '#/notifications';
  }

  R.events = async (type = 'event') => {
    $app.innerHTML = appbar(t('events')) + `<div class="seg"><button class="${type === 'event' ? 'on' : ''}" data-go="#/events/event">${t('upcoming')}</button><button class="${type === 'news' ? 'on' : ''}" data-go="#/events/news">${t('news')}</button></div><div id="eb">${loading()}</div>`;
    try {
      const r = await api('events?type=' + type);
      const b = document.getElementById('eb');
      if (!r.items.length) { b.innerHTML = `<div class="empty">${ic('calendar', 44)}<h2 style="margin-top:10px">${t('no_events')}</h2></div>`; return; }
      b.innerHTML = `<div class="pad-x" style="display:grid;gap:14px">${r.items.map(e => evCard(e).replace('class="ev-card"', 'class="ev-card" style="flex:none;width:100%"')).join('')}</div><div style="height:16px"></div>`;
    } catch (e) { document.getElementById('eb').innerHTML = errorBox(e); }
  };

  R.event = async (slug) => {
    $app.innerHTML = appbar('') + loading();
    try {
      const e = await api('events/' + encodeURIComponent(slug));
      const isEv = e.type === 'event'; const past = isEv && e.start && new Date(e.start.replace(' ', 'T')) < new Date();
      const m = S.member;
      $app.innerHTML = appbar(e.title, { right: `<button class="icon-btn" id="shr">${ic('share')}</button>` }) + `<div style="height:200px;background:var(--ink) url('${esc(e.image || 'img/event-placeholder.svg')}') center/cover"></div>
        <div class="pad"><h1 dir="auto">${esc(e.title)}</h1>
        ${isEv ? `<div class="card"><div class="list-row">${ic('calendar', 18)}<div class="info"><b>${fmtDate(e.start, true)}</b>${e.end ? `<span>– ${new Date(e.end.replace(' ', 'T')).toLocaleTimeString('en', { hour: 'numeric', minute: '2-digit' })}</span>` : ''}</div></div>${e.venue ? `<div class="list-row">${ic('pin', 18)}<div class="info"><b>${esc(e.venue)}</b></div></div>` : ''}${e.seats_left !== null ? `<div class="list-row">${ic('user', 18)}<div class="info"><b>${e.seats_left ? t('seats_left', { n: e.seats_left }) : t('full')}</b></div></div>` : ''}</div>` : `<p class="muted">${fmtDate(e.created_at)}</p>`}
        <div class="prose" dir="auto" style="margin-top:14px">${e.body_html || ''}</div>
        ${isEv && e.start ? `<a class="btn outline" style="margin-top:10px" target="_blank" href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(e.title)}&dates=${gcal(e.start)}/${gcal(e.end || e.start, !e.end)}&location=${encodeURIComponent(e.venue || '')}">${ic('calendar', 18)} ${t('add_calendar')}</a>` : ''}
        </div><div style="height:90px"></div>
        ${isEv && !past && e.registration_open ? `<div class="sticky-bar"><button class="btn" id="reg" ${e.seats_left === 0 ? 'disabled' : ''}>${e.seats_left === 0 ? t('full') : t('register')}</button></div>` : (past ? `<div class="pad"><div class="alert info">${t('event_over')}</div></div>` : '')}`;
      $tab.hidden = true;
      document.getElementById('shr').onclick = () => shareText(e.title, e.url);
      const reg = document.getElementById('reg');
      if (reg) reg.onclick = () => sheet(`<h2>${t('register')}</h2><form id="rf"><div class="field"><label>${t('your_name')}</label><input name="name" required value="${esc(m ? m.name : '')}"></div><div class="field"><label>${t('mobile')}</label><input name="phone" type="tel" dir="ltr" required value="${esc(m ? m.phone || '' : '')}"></div><div class="field"><label>${t('seats')}</label><select name="seats">${[1, 2, 3, 4, 5].map(n => `<option>${n}</option>`).join('')}</select></div><button class="btn">${t('register')}</button></form>`, (sh) => {
        sh.querySelector('#rf').onsubmit = async (ev) => {
          ev.preventDefault(); const fd = Object.fromEntries(new FormData(ev.target));
          try {
            const r = await api('events/' + encodeURIComponent(slug) + '/register', { method: 'POST', body: fd });
            sheet(`<div class="ticket"><div style="color:var(--green)">${ic('check', 44)}</div><h2>${t('registered')}</h2><p dir="auto"><b>${esc(e.title)}</b><br><span class="muted small">${fmtDate(e.start, true)} · ${esc(e.venue || '')}</span></p><div style="display:inline-block;background:#fff;padding:8px">${qrSvg(r.reg_no, 150)}</div><div class="muted small" style="margin-top:8px">${t('reg_no')}</div><div class="mono" style="font-size:22px;color:var(--blue)">${esc(r.reg_no)}</div><p class="small muted">${esc(r.name)} · ${r.seats} ${t('seats')}<br>${t('show_entrance')}</p></div><button class="btn" data-close style="margin-top:14px">${t('close')}</button>`);
          } catch (err) { toast(err.message, 'err'); }
        };
      });
    } catch (err) { $app.innerHTML = appbar('') + errorBox(err); }
  };
  const gcal = (d, plus2) => { const x = new Date(d.replace(' ', 'T')); if (plus2) x.setHours(x.getHours() + 2); return x.toISOString().replace(/[-:]/g, '').replace(/\.\d+/, ''); };

  R.library = async () => {
    $app.innerHTML = appbar(t('info')) + loading();
    try {
      const L = await api('library');
      const wa = (L.whatsapp || '').replace(/\D/g, '').replace(/^0/, '92');
      $app.innerHTML = appbar(t('info')) + `<div style="height:150px;background:linear-gradient(135deg,var(--blue),#062C60);display:grid;place-items:center;position:relative;overflow:hidden"><img src="img/logo-192.png" alt="" style="width:96px;background:#fff;border-radius:50%"><div class="waves"></div></div>
        <div class="pad"><h1 dir="auto">${esc(L.name)}</h1><span class="badge ${L.open_now ? 'b-green' : 'b-red'}">${L.open_now ? t('open_now') : t('closed_now')}</span>
        <div class="card" style="margin-top:14px"><div class="list-row">${ic('clock', 18)}<div class="info"><span>${t('hours')}</span><b dir="auto">${esc(L.hours)}</b></div></div><div class="list-row">${ic('pin', 18)}<div class="info"><span>${t('address')}</span><b dir="auto">${esc(L.address)}</b></div></div>
          <a class="btn outline sm" style="width:100%;margin-top:8px" target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(L.map_query || L.address)}">${ic('pin', 16)} ${t('directions')}</a></div>
        <div class="quick" style="padding:0;margin-top:14px">
          ${L.phone ? `<button data-href="tel:${esc(L.phone)}"><span class="ic">${ic('phone')}</span>${t('call')}</button>` : ''}
          ${wa ? `<button data-href="https://wa.me/${wa}"><span class="ic">${ic('chat')}</span>${t('whatsapp')}</button>` : ''}
          ${L.email ? `<button data-href="mailto:${esc(L.email)}"><span class="ic">${ic('mail')}</span>${t('email')}</button>` : ''}
          ${L.facebook ? `<button data-href="${esc(L.facebook)}"><span class="ic">${ic('globe')}</span>${t('facebook')}</button>` : ''}
          <button data-href="${esc(L.website)}"><span class="ic">${ic('globe')}</span>${t('open_website')}</button>
          <button data-href="${esc(L.membership_url)}"><span class="ic">${ic('user')}</span>${t('apply_membership')}</button>
        </div>
        <div class="card" style="margin-top:14px"><h3>${t('fees')}</h3>${['student', 'general', 'child'].map(k => `<div class="list-row"><div class="info"><b>${t(k)}</b></div><span class="small muted" dir="auto">${esc(L.fees[k] || '—')}</span></div>`).join('')}</div>
        ${L.rules_html ? `<details style="margin-top:14px"><summary>${t('rules')}</summary><div class="prose" dir="auto">${L.rules_html}</div></details>` : ''}
        ${L.about_html ? `<details><summary>${t('about')}</summary><div class="prose" dir="auto">${L.about_html}</div></details>` : ''}
        </div>
        ${L.gallery.length ? `<div class="section-head"><h2>${t('gallery')}</h2></div><div class="gal">${L.gallery.map(g => `<div style="background-image:url('${esc(g.image)}')"></div>`).join('')}</div>` : ''}
        <div class="pad"><button class="btn" id="fb">${ic('chat', 18)} ${t('feedback')}</button></div>`;
      $app.querySelectorAll('[data-href]').forEach(b => b.onclick = () => openExternal(b.dataset.href));
      document.getElementById('fb').onclick = feedbackSheet;
    } catch (e) { $app.innerHTML = appbar(t('info')) + errorBox(e); }
  };
  function feedbackSheet() {
    const m = S.member;
    sheet(`<h2>${t('feedback')}</h2><form id="ff"><div class="field"><label>${t('fb_type')}</label><select name="type">${['feedback', 'complaint', 'suggestion', 'question'].map(k => `<option value="${k}">${t('fb_' + k)}</option>`).join('')}</select></div>
      ${m ? '' : `<div class="field"><label>${t('your_name')}</label><input name="name" required></div><div class="field"><label>${t('mobile')}</label><input name="phone" type="tel" dir="ltr"></div>`}
      <div class="field"><label>${t('message')}</label><textarea name="message" required></textarea></div><button class="btn">${t('send')}</button></form>`, (sh) => {
      sh.querySelector('#ff').onsubmit = async (e) => { e.preventDefault(); try { const r = await api('feedback', { method: 'POST', body: Object.fromEntries(new FormData(e.target)) }); closeSheet(); toast(t('sent_ref', { r: r.ref_no }), 'ok'); } catch (err) { toast(err.message, 'err'); } };
    });
  }

  R.profile = async () => {
    if (!S.token) {
      $app.innerHTML = appbar(t('tab_profile'), { back: false }) + needSignin() + settingsBlock();
      bindSettings(); return;
    }
    $app.innerHTML = appbar(t('profile'), { back: false }) + loading();
    let m = S.member;
    try { m = (await api('me')).member; S.member = m; store.set('member', m); } catch { /* cached */ }
    const sw = (k, label) => `<label class="list-row" style="justify-content:space-between"><span>${label}</span><span class="switch"><input type="checkbox" data-pref="${k}" ${m.prefs && m.prefs[k] ? 'checked' : ''}><span></span></span></label>`;
    $app.innerHTML = appbar(t('profile'), { back: false }) + `<div class="pad">
      <div class="card" style="display:flex;gap:14px;align-items:center"><div style="width:60px;height:60px;border-radius:50%;background:var(--blue);color:#fff;display:grid;place-items:center;font-size:24px;font-weight:700">${esc((m.name || '?').slice(0, 1))}</div><div style="flex:1"><b style="font-size:18px">${esc(m.name)}</b><div class="mono small">${esc(m.cardnumber)}</div><div class="tiny muted">${esc(m.category || '')} · ${t('valid_until')} ${fmtDate(m.expiry_date)}</div></div></div>
      <div class="card"><h3>${t('contact')}</h3><div class="list-row"><div class="info"><span>${t('mobile')}</span><b dir="ltr">${esc(m.phone || '—')}</b></div></div><div class="list-row"><div class="info"><span>${t('email')}</span><b>${esc(m.email || '—')}</b></div></div>${m.fines_due ? `<div class="list-row"><div class="info"><span>${t('fines')}</span><b style="color:var(--red)">Rs ${Math.round(m.fines_due)}</b></div></div>` : ''}</div>
      <div class="card"><h3>${t('notif_prefs')}</h3>${sw('due', t('p_due'))}${sw('holds', t('p_holds'))}${sw('events', t('p_events'))}${sw('push', t('p_push'))}${sw('sms', t('p_sms'))}${sw('whatsapp', t('p_wa'))}</div>
      <div class="card"><a class="list-row" style="color:inherit" href="#/suggest">${ic('bulb', 18)}<div class="info"><b>${t('suggest')}</b></div>${ic('chev', 18, 'flip')}</a><a class="list-row" style="color:inherit" href="#/library">${ic('info', 18)}<div class="info"><b>${t('info')}</b></div>${ic('chev', 18, 'flip')}</a><button class="list-row link" style="width:100%;color:inherit;font-weight:400" id="pw">${ic('lock', 18)}<div class="info" style="text-align:start"><b>${t('change_password')}</b></div>${ic('chev', 18, 'flip')}</button><button class="list-row link" style="width:100%;color:inherit;font-weight:400" id="fbk">${ic('chat', 18)}<div class="info" style="text-align:start"><b>${t('help')}</b></div>${ic('chev', 18, 'flip')}</button></div>
      </div>${settingsBlock()}<div class="pad"><button class="btn danger" id="so">${ic('logout', 18)} ${t('sign_out')}</button></div>`;
    bindSettings();
    $app.querySelectorAll('[data-pref]').forEach(i => i.onchange = async () => { const prefs = {}; prefs[i.dataset.pref] = i.checked; try { await api('me', { method: 'POST', body: { prefs } }); toast(t('saved'), 'ok'); } catch (e) { toast(e.message, 'err'); } });
    document.getElementById('so').onclick = () => sheet(`<h2>${t('sign_out_q')}</h2><div class="btn-row" style="margin-top:14px"><button class="btn outline" data-close>${t('cancel')}</button><button class="btn" id="yes">${t('sign_out')}</button></div>`, (sh) => { sh.querySelector('#yes').onclick = async () => { await api('auth/logout', { method: 'POST' }).catch(() => {}); closeSheet(); signOut(); }; });
    document.getElementById('fbk').onclick = feedbackSheet;
    document.getElementById('pw').onclick = () => sheet(`<h2>${t('change_password')}</h2><form id="pf"><div class="field"><label>${t('current_password')}</label><input type="password" name="current_password" required></div><div class="field"><label>${t('new_password')}</label><input type="password" name="new_password" minlength="8" required></div><button class="btn">${t('save')}</button></form>`, (sh) => {
      sh.querySelector('#pf').onsubmit = async (e) => { e.preventDefault(); try { await api('me', { method: 'POST', body: Object.fromEntries(new FormData(e.target)) }); closeSheet(); toast(t('saved'), 'ok'); } catch (err) { toast(err.message, 'err'); } };
    });
  };
  const settingsBlock = () => `<div class="pad" style="padding-top:0"><div class="card"><h3>${t('language')}</h3><div class="seg" style="margin:6px 0 0"><button data-setlang="en" class="${S.lang === 'en' ? 'on' : ''}">English</button><button data-setlang="ur" class="${S.lang === 'ur' ? 'on' : ''} ur">اردو</button></div>
    <div class="field" style="margin-top:14px"><label>${ic('server', 16)} ${t('server')}</label><input id="srv2" type="url" dir="ltr" value="${esc(S.server)}"></div><button class="btn sm outline" id="srvsave">${t('save')}</button>
    <p class="tiny muted" style="margin-top:12px">${t('about_app')}: ${t('library_full')} · ${t('version')} ${VERSION}</p></div></div>`;
  function bindSettings() {
    $app.querySelectorAll('[data-setlang]').forEach(b => b.onclick = async () => { S.lang = b.dataset.setlang; store.set('lang', S.lang); applyLang(); if (S.token) api('me', { method: 'POST', body: { lang: S.lang } }).catch(() => {}); route(); });
    const sv = document.getElementById('srvsave');
    if (sv) sv.onclick = () => { let v = document.getElementById('srv2').value.trim().replace(/\/+$/, ''); if (v && !/^https?:\/\//.test(v)) v = 'https://' + v; S.server = v; store.set('server', v); toast(t('saved'), 'ok'); };
  }

  R.suggest = async (_, qs) => {
    const p = new URLSearchParams(qs || '');
    $app.innerHTML = appbar(t('suggest')) + `<div class="pad"><form id="sg" class="card"><div class="field"><label>${t('book_title')} *</label><input name="title" required value="${esc(p.get('title') || '')}" dir="auto"></div>
      <div class="field"><label>${t('author')}</label><input name="author" dir="auto"></div><div class="field"><label>${t('isbn_opt')}</label><input name="isbn" dir="ltr" inputmode="numeric"></div>
      <div class="field"><label>${t('language')}</label><select name="language">${['ur', 'en', 'ar', 'other'].map(l => `<option value="${l}">${langLabel(l)}</option>`).join('')}</select></div>
      <div class="field"><label>${t('why')}</label><textarea name="reason" maxlength="500"></textarea></div><button class="btn">${t('submit')}</button></form><div id="mine"></div></div>`;
    document.getElementById('sg').onsubmit = async (e) => { e.preventDefault(); try { await api('suggestions', { method: 'POST', body: Object.fromEntries(new FormData(e.target)) }); toast(t('thanks'), 'ok'); e.target.reset(); loadMine(); } catch (err) { toast(err.message, 'err'); } };
    const loadMine = async () => {
      if (!S.token) return;
      try { const r = await api('suggestions'); const cls = { review: 'b-amber', approved: 'b-blue', purchased: 'b-green', rejected: 'b-slate' };
        document.getElementById('mine').innerHTML = r.items.length ? `<h3 style="margin-top:18px">${t('my_requests')}</h3><div class="card" style="padding:0 14px">${r.items.map(s => `<div class="list-row"><div class="info"><b dir="auto">${esc(s.title)}</b><span dir="auto">${esc(s.author || '')}</span></div><span class="badge ${cls[s.status]}">${t('sg_' + s.status)}</span></div>`).join('')}</div>` : ''; } catch { /* ignore */ }
    };
    loadMine();
  };

  /* ---------- native helpers ---------- */
  function openExternal(url) {
    const Cap = window.Capacitor;
    if (Cap && Cap.Plugins && Cap.Plugins.Browser && /^https?:/.test(url)) { Cap.Plugins.Browser.open({ url }); return; }
    window.open(url, '_system') || (location.href = url);
  }
  function shareText(text, url) {
    const Cap = window.Capacitor;
    if (Cap && Cap.Plugins && Cap.Plugins.Share) { Cap.Plugins.Share.share({ title: text, text, url }).catch(() => {}); return; }
    if (navigator.share) navigator.share({ title: text, text, url }).catch(() => {});
    else { navigator.clipboard && navigator.clipboard.writeText((text + ' ' + (url || '')).trim()); toast('✓ copied'); }
  }

  /* ---------- global actions ---------- */
  document.addEventListener('click', async (e) => {
    const go = e.target.closest('[data-go]'); if (go) { location.hash = go.dataset.go; return; }
    if (e.target.closest('[data-back]')) { history.length > 1 ? history.back() : (location.hash = '#/home'); return; }
    if (e.target.closest('[data-reload]')) { route(); return; }
    const rn = e.target.closest('[data-renew]');
    if (rn) { rn.disabled = true; try { const r = await api('loans/' + rn.dataset.renew + '/renew', { method: 'POST' }); toast(r.message, 'ok'); route(); } catch (err) { toast(err.message, 'err'); rn.disabled = false; } return; }
    const cn = e.target.closest('[data-cancel]');
    if (cn) { sheet(`<h2>${t('cancel_hold_q')}</h2><div class="btn-row" style="margin-top:14px"><button class="btn outline" data-close>${t('close')}</button><button class="btn danger" id="yes">${t('cancel')}</button></div>`, (sh) => { sh.querySelector('#yes').onclick = async () => { try { const r = await api('holds/' + cn.dataset.cancel + '/cancel', { method: 'POST' }); closeSheet(); toast(r.message, 'ok'); route(); } catch (err) { closeSheet(); toast(err.message, 'err'); } }; }); }
  });

  /* ---------- pull to refresh ---------- */
  let y0 = null;
  document.addEventListener('touchstart', (e) => { y0 = window.scrollY === 0 ? e.touches[0].clientY : null; }, { passive: true });
  document.addEventListener('touchend', (e) => { if (y0 !== null && e.changedTouches[0].clientY - y0 > 110) route(); y0 = null; }, { passive: true });

  /* ---------- router ---------- */
  const TABS = [['home', 'home', 'tab_home'], ['search', 'search', 'tab_search'], ['mybooks', 'books', 'tab_books'], ['card', 'card', 'tab_card'], ['profile', 'user', 'tab_profile']];
  function route() {
    closeSheet();
    const h = location.hash || '#/splash';
    const [path, qs] = h.slice(2).split('?');
    const [name, arg] = path.split('/');
    const fn = R[name] || R.home;
    const noTabs = ['splash', 'lang', 'onboarding', 'signin', 'activate'].includes(name);
    $app.classList.toggle('no-tabs', noTabs);
    $tab.hidden = noTabs;
    if (!noTabs) $tab.innerHTML = TABS.map(([k, icn, l]) => `<a href="#/${k}" class="${name === k ? 'on' : ''}"><span class="pill">${ic(icn, 22)}</span>${t(l)}</a>`).join('');
    window.scrollTo(0, 0);
    fn(arg ? decodeURIComponent(arg) : undefined, qs);
  }
  window.addEventListener('hashchange', route);

  // Android back button & app resume (Capacitor)
  document.addEventListener('DOMContentLoaded', () => {
    const Cap = window.Capacitor;
    if (Cap && Cap.Plugins) {
      if (Cap.Plugins.App) {
        Cap.Plugins.App.addListener('backButton', () => {
          if (document.getElementById('sheet-root').innerHTML) return closeSheet();
          const n = (location.hash.slice(2).split(/[/?]/)[0]) || 'home';
          if (['home', 'splash', 'signin'].includes(n)) Cap.Plugins.App.exitApp(); else history.back();
        });
        Cap.Plugins.App.addListener('resume', () => { if (S.token) api('me').then(r => { S.unread = r.counts.unread; }).catch(() => {}); });
      }
      if (Cap.Plugins.StatusBar) { Cap.Plugins.StatusBar.setBackgroundColor({ color: '#FFFFFF' }).catch(() => {}); Cap.Plugins.StatusBar.setStyle({ style: 'LIGHT' }).catch(() => {}); }
      if (Cap.Plugins.SplashScreen) Cap.Plugins.SplashScreen.hide().catch(() => {});
    }
  });
  if (!location.hash) location.hash = '#/splash'; else route();
  window.MMB = { S, route };
})();
