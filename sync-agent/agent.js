#!/usr/bin/env node
/**
 * MMBPL Sync Agent
 * Runs on a computer inside the library that can reach Koha.
 *  - Reads catalog, items, patrons, checkouts and holds from Koha (saved SQL reports)
 *  - Sends them to the MMBPL website (cPanel) through /api/sync
 *  - Executes members' online requests (renew, reserve, cancel, new member) in Koha via the REST API
 * Local settings page: http://127.0.0.1:8765
 */
'use strict';
const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');
const os = require('os');
const { URL } = require('url');
const { exec } = require('child_process');

const VERSION = '1.0.0';
const DATA_DIR = process.env.MMBPL_AGENT_DIR || path.join(process.env.APPDATA || path.join(os.homedir(), '.config'), 'MMBPL-Sync-Agent');
const CONFIG_FILE = path.join(DATA_DIR, 'config.json');
const STATE_FILE = path.join(DATA_DIR, 'state.json');
const LOG_FILE = path.join(DATA_DIR, 'agent.log');
const UI_PORT = Number(process.env.MMBPL_AGENT_PORT || 8765);
fs.mkdirSync(DATA_DIR, { recursive: true });

const DEFAULTS = {
  websiteUrl: '', agentKey: '',
  kohaUrl: 'http://localhost:8080', kohaUser: '', kohaPass: '', clientId: '', clientSecret: '', insecureTls: false,
  branch: '', cardPrefix: 'MMB-', cardDigits: 6,
  reports: { biblios: '', items: '', patrons: '', checkouts: '', holds: '' },
  syncMinutes: 5, actionsSeconds: 30, fullSyncHour: 2, autoStart: true,
};
const load = (f, d) => { try { return Object.assign({}, d, JSON.parse(fs.readFileSync(f, 'utf8'))); } catch { return Object.assign({}, d); } };
let cfg = load(CONFIG_FILE, DEFAULTS);
cfg.reports = Object.assign({}, DEFAULTS.reports, cfg.reports || {});
let state = load(STATE_FILE, { lastIncremental: null, lastFull: null, lastFullDay: null, counts: {} });
const saveCfg = () => fs.writeFileSync(CONFIG_FILE, JSON.stringify(cfg, null, 2));
const saveState = () => fs.writeFileSync(STATE_FILE, JSON.stringify(state, null, 2));

/* ---------------- Logging ---------------- */
const recent = [];
function log(level, msg) {
  const line = `${new Date().toISOString().replace('T', ' ').slice(0, 19)} [${level}] ${msg}`;
  recent.push(line); if (recent.length > 300) recent.shift();
  console.log(line);
  try {
    fs.appendFileSync(LOG_FILE, line + os.EOL);
    if (fs.statSync(LOG_FILE).size > 5 * 1024 * 1024) fs.renameSync(LOG_FILE, LOG_FILE + '.old');
  } catch { /* ignore */ }
}

/* ---------------- HTTP client ---------------- */
function request(method, urlStr, { headers = {}, body = null, insecure = false, timeout = 120000 } = {}) {
  return new Promise((resolve, reject) => {
    const u = new URL(urlStr);
    const lib = u.protocol === 'https:' ? https : http;
    const data = body == null ? null : (typeof body === 'string' || Buffer.isBuffer(body) ? body : JSON.stringify(body));
    const h = Object.assign({ 'User-Agent': 'MMBPL-Sync-Agent/' + VERSION, Accept: 'application/json' }, headers);
    if (data != null && !h['Content-Type']) h['Content-Type'] = 'application/json';
    if (data != null) h['Content-Length'] = Buffer.byteLength(data);
    const req = lib.request(u, { method, headers: h, timeout, rejectUnauthorized: !insecure }, res => {
      const chunks = [];
      res.on('data', c => chunks.push(c));
      res.on('end', () => {
        const text = Buffer.concat(chunks).toString('utf8');
        let json = null; try { json = JSON.parse(text); } catch { /* not json */ }
        resolve({ status: res.statusCode, headers: res.headers, text, json });
      });
    });
    req.on('timeout', () => req.destroy(new Error('timeout')));
    req.on('error', reject);
    if (data != null) req.write(data);
    req.end();
  });
}

/* ---------------- Website (cPanel) ---------------- */
const site = {
  async call(method, p, body) {
    if (!cfg.websiteUrl || !cfg.agentKey) throw new Error('Website address or agent key is not set');
    const url = cfg.websiteUrl.replace(/\/+$/, '') + '/api/sync/' + p;
    const r = await request(method, url, { headers: { 'X-Agent-Key': cfg.agentKey }, body });
    if (r.status === 401) throw new Error('Website rejected the agent key (create a new key in Admin → Koha sync)');
    if (r.status >= 400 || !r.json) throw new Error(`Website ${p}: HTTP ${r.status} ${r.text.slice(0, 200)}`);
    return r.json;
  },
  hello: (b) => site.call('POST', 'hello', b),
  push: async (kind, records) => {
    let saved = 0;
    for (let i = 0; i < records.length; i += 500) saved += (await site.call('POST', kind, { records: records.slice(i, i + 500) })).saved || 0;
    return saved;
  },
};

/* ---------------- Koha ---------------- */
const koha = {
  cookie: null, token: null, tokenExp: 0,
  base() { return cfg.kohaUrl.replace(/\/+$/, ''); },
  async login() {
    const body = new URLSearchParams({ userid: cfg.kohaUser, password: cfg.kohaPass }).toString();
    const r = await request('POST', this.base() + '/cgi-bin/koha/svc/authentication', { body, headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'text/xml' }, insecure: cfg.insecureTls });
    const sc = [].concat(r.headers['set-cookie'] || []).map(c => c.split(';')[0]).find(c => c.startsWith('CGISESSID='));
    if (r.status !== 200 || !/<status>ok<\/status>/i.test(r.text) || !sc) throw new Error('Koha staff login failed (check user name / password). HTTP ' + r.status);
    this.cookie = sc;
  },
  async report(id, params = []) {
    if (!id) throw new Error('Report number is not set');
    if (!this.cookie) await this.login();
    const qs = new URLSearchParams({ id: String(id), annotated: '1' });
    params.forEach(p => qs.append('sql_params', String(p)));
    let r = await request('GET', this.base() + '/cgi-bin/koha/svc/report?' + qs, { headers: { Cookie: this.cookie }, insecure: cfg.insecureTls, timeout: 300000 });
    if (r.status === 401 || r.status === 403 || (!r.json && /login/i.test(r.text))) { await this.login(); r = await request('GET', this.base() + '/cgi-bin/koha/svc/report?' + qs, { headers: { Cookie: this.cookie }, insecure: cfg.insecureTls, timeout: 300000 }); }
    if (!Array.isArray(r.json)) throw new Error(`Koha report ${id}: unexpected answer (HTTP ${r.status}) ${r.text.slice(0, 160)}`);
    return r.json;
  },
  async paged(id, since, keyField) {
    const all = []; let after = 0;
    for (let guard = 0; guard < 10000; guard++) {
      const params = id === cfg.reports.biblios ? [since, since, after] : [since, after];
      const rows = await this.report(id, params);
      all.push(...rows);
      if (!rows.length) break;
      const last = Number(rows[rows.length - 1][keyField]);
      if (!last || last <= after) break;
      after = last;
      if (rows.length < 100) break;
    }
    return all;
  },
  async authHeader() {
    if (cfg.clientId && cfg.clientSecret) {
      if (!this.token || Date.now() > this.tokenExp) {
        const body = new URLSearchParams({ grant_type: 'client_credentials', client_id: cfg.clientId, client_secret: cfg.clientSecret }).toString();
        const r = await request('POST', this.base() + '/api/v1/oauth/token', { body, headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, insecure: cfg.insecureTls });
        if (!r.json || !r.json.access_token) throw new Error('Koha OAuth token failed: HTTP ' + r.status + ' ' + r.text.slice(0, 150));
        this.token = r.json.access_token; this.tokenExp = Date.now() + (Number(r.json.expires_in || 3600) - 60) * 1000;
      }
      return 'Bearer ' + this.token;
    }
    return 'Basic ' + Buffer.from(cfg.kohaUser + ':' + cfg.kohaPass).toString('base64');
  },
  async api(method, p, body) {
    const r = await request(method, this.base() + '/api/v1/' + p, { headers: { Authorization: await this.authHeader() }, body, insecure: cfg.insecureTls });
    return r;
  },
  async version() {
    try {
      const r = await request('GET', this.base() + '/api/v1/', { insecure: cfg.insecureTls, timeout: 20000 });
      const v = r.json && r.json.info && r.json.info.version; return v || (r.status === 200 ? 'REST ok' : 'unknown');
    } catch { return 'unknown'; }
  },
};

/* ---------------- Mapping Koha rows → website records ---------------- */
const d10 = v => (v && String(v) !== '0000-00-00' ? String(v).slice(0, 10) : null);
function langFrom(r) {
  const c = String(r.lang041 || '').trim().slice(0, 3).toLowerCase() || String(r.lang008 || '').trim().toLowerCase();
  if (c) return c;
  return /[؀-ۿ]/.test(r.title || '') ? 'urd' : 'eng';
}
function formatFrom(t) {
  t = String(t || '').toUpperCase();
  if (/MAG|SER|JOUR|PER/.test(t)) return 'magazine';
  if (/NEWS/.test(t)) return 'newspaper';
  if (/MAN|MSS/.test(t)) return 'manuscript';
  return 'book';
}
const mapBiblio = r => ({
  biblionumber: Number(r.biblionumber), title: r.title || '(untitled)', subtitle: r.subtitle || null, author: r.author || null,
  isbn: r.isbn ? String(r.isbn).split(/[|\s]/)[0] : null, publisher: r.publisher || null, pub_year: r.pub_year ? String(r.pub_year).replace(/[^\d]/g, '').slice(0, 4) || String(r.pub_year) : null,
  edition: r.edition || null, pages: r.pages || null, language: langFrom(r), format: formatFrom(r.itemtype),
  subjects: r.subjects ? String(r.subjects).split(/\s{2,}|\s*;\s*/).map(s => s.replace(/[.,]$/, '').trim()).filter(Boolean).join('; ') : null,
  summary: r.summary || null, call_number: r.call_number || null, date_added: d10(r.date_added),
});
function itemStatus(r) {
  if (Number(r.withdrawn)) return 'withdrawn';
  if (Number(r.itemlost)) return 'lost';
  if (Number(r.damaged)) return 'damaged';
  if (r.onloan) return 'checked_out';
  if (r.hold_found === 'W') return 'on_hold';
  if (r.hold_found === 'T') return 'in_transit';
  if (Number(r.notforloan)) return 'reference';
  return 'available';
}
const mapItem = r => ({ itemnumber: Number(r.itemnumber), biblionumber: Number(r.biblionumber), barcode: r.barcode || null, shelf_location: r.location || null, call_number: r.itemcallnumber || null, status: itemStatus(r), due_date: d10(r.onloan) });
const mapPatron = r => ({
  borrowernumber: Number(r.borrowernumber), cardnumber: r.cardnumber, firstname: r.firstname || null, surname: r.surname || null, category: r.categorycode || null,
  email: r.email || null, phone: r.phone || null, dob: d10(r.dateofbirth), expiry_date: d10(r.dateexpiry),
  blocked: Number(r.blocked) || Number(r.lost) ? 1 : 0, fines_due: Number(r.fines || 0),
});
const mapCheckout = r => ({
  issue_id: Number(r.issue_id), borrowernumber: Number(r.borrowernumber), itemnumber: Number(r.itemnumber), issued_at: d10(r.issuedate), due_date: d10(r.date_due),
  renewals: Number(r.renewals_count ?? r.renewals ?? 0), renewable: Number(r.on_reserve) ? 0 : 1, not_renewable_reason: Number(r.on_reserve) ? 'on_reserve' : null,
});
const mapHold = r => ({
  hold_id: Number(r.reserve_id), borrowernumber: Number(r.borrowernumber), biblionumber: Number(r.biblionumber),
  status: r.found === 'W' ? 'ready' : 'waiting', queue_position: r.found ? null : Number(r.priority || 0) || null, pickup_by: d10(r.expirationdate),
});

/* ---------------- Sync jobs ---------------- */
let busy = false;
const status = { running: false, lastRun: null, lastError: null, lastActions: null, website: null, koha: null };

async function syncAll(full) {
  if (busy) return log('INFO', 'Sync already running, skipped');
  busy = true; status.running = true;
  const started = new Date();
  const since = full || !state.lastIncremental ? '1900-01-01' : sqlDate(new Date(new Date(state.lastIncremental).getTime() - 10 * 60000));
  try {
    const hello = await site.hello({ agent_version: VERSION, koha_version: status.koha || await koha.version() });
    status.website = 'ok';
    const firstRun = !hello.counts || !hello.counts.biblios;
    if (firstRun && !full) { log('INFO', 'Website has no catalog yet: running a full sync'); busy = false; return syncAll(true); }
    log('INFO', `${full ? 'Full' : 'Incremental'} sync since ${since}`);

    const biblios = (await koha.paged(cfg.reports.biblios, since, 'biblionumber')).map(mapBiblio);
    const rawItems = await koha.paged(cfg.reports.items, since, 'itemnumber');
    const items = rawItems.map(mapItem);
    const pop = {}; // popularity = total issues of all copies
    for (const r of rawItems) pop[r.biblionumber] = (pop[r.biblionumber] || 0) + Number(r.issues || 0);
    const patrons = (await koha.paged(cfg.reports.patrons, since, 'borrowernumber')).map(mapPatron).filter(p => p.cardnumber);
    status.koha = 'ok';
    const nb = await site.push('biblios', biblios);
    const ni = await site.push('items', items);
    const np = await site.push('patrons', patrons);
    log('INFO', `Sent ${nb} titles, ${ni} items, ${np} patrons`);

    const checkouts = (await koha.report(cfg.reports.checkouts)).map(mapCheckout);
    const holds = (await koha.report(cfg.reports.holds)).map(mapHold);
    const circ = await site.call('POST', 'circulation', { full: true, checkouts, holds });
    log('INFO', `Circulation: ${circ.loans} loans, ${circ.holds} holds, ${circ.reminders || 0} reminders`);

    if (full) {
      if (biblios.length && items.length && patrons.length) {
        const pr = await site.call('POST', 'prune', { biblios: biblios.map(b => b.biblionumber), items: items.map(i => i.itemnumber), patrons: patrons.map(p => p.borrowernumber) });
        log('INFO', 'Removed records deleted in Koha: ' + JSON.stringify(pr.deleted || {}));
      }
      await site.call('POST', 'finish', { kind: 'full', records: biblios.length + items.length + patrons.length, popularity: pop, message: `${biblios.length} titles, ${items.length} items, ${patrons.length} patrons` });
      state.lastFull = started.toISOString(); state.lastFullDay = started.toISOString().slice(0, 10);
    }
    state.lastIncremental = started.toISOString();
    state.counts = { biblios: biblios.length, items: items.length, patrons: patrons.length, loans: circ.loans, holds: circ.holds };
    saveState();
    status.lastError = null;
  } catch (e) {
    status.lastError = e.message;
    if (/Koha/i.test(e.message)) status.koha = 'error'; else status.website = 'error';
    log('ERROR', 'Sync failed: ' + e.message);
  } finally {
    busy = false; status.running = false; status.lastRun = new Date().toISOString();
  }
}
function sqlDate(d) { const p = n => String(n).padStart(2, '0'); return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:00`; }

/* ---------------- Executing website requests in Koha ---------------- */
let actionsBusy = false;
async function processActions() {
  if (actionsBusy || !cfg.websiteUrl || !cfg.agentKey || !cfg.kohaUser) return;
  actionsBusy = true;
  try {
    const { actions } = await site.call('GET', 'actions');
    status.website = 'ok';
    for (const a of actions || []) {
      let result;
      try { result = await runAction(a); } catch (e) { result = { status: 'failed', result: e.message }; }
      await site.call('POST', 'actions/' + a.id, result);
      log(result.status === 'done' ? 'INFO' : 'WARN', `Request #${a.id} ${a.type}: ${result.status} ${result.result || ''}`);
    }
    if (actions && actions.length) {
      // Refresh circulation right away so members see the result.
      const checkouts = (await koha.report(cfg.reports.checkouts)).map(mapCheckout);
      const holds = (await koha.report(cfg.reports.holds)).map(mapHold);
      await site.call('POST', 'circulation', { full: true, checkouts, holds });
    }
    status.lastActions = new Date().toISOString();
  } catch (e) {
    log('ERROR', 'Requests: ' + e.message);
  } finally { actionsBusy = false; }
}
function kohaErr(r) {
  const j = r.json || {};
  return (j.error_code || j.error || (j.errors && JSON.stringify(j.errors)) || r.text || ('HTTP ' + r.status)).toString().slice(0, 300);
}
async function runAction(a) {
  const p = a.payload || {};
  switch (a.type) {
    case 'renew': {
      if (!p.koha_issue_id) return { status: 'failed', result: 'missing checkout id' };
      const r = await koha.api('POST', `checkouts/${p.koha_issue_id}/renewal`);
      if (r.status === 201 || r.status === 200) return { status: 'done', result: 'renewed', data: { due_date: r.json && (r.json.due_date || r.json.date_due) } };
      return { status: 'failed', result: kohaErr(r) };
    }
    case 'reserve': {
      if (!a.borrowernumber || !p.koha_biblionumber) return { status: 'failed', result: 'member or title not linked to Koha yet' };
      const branch = p.branch || cfg.branch;
      let r = await koha.api('POST', 'holds', { patron_id: a.borrowernumber, biblio_id: p.koha_biblionumber, pickup_library_id: branch });
      if (r.status === 400 && /patron_id|Properties not allowed/i.test(r.text)) r = await koha.api('POST', 'holds', { borrowernumber: a.borrowernumber, biblionumber: p.koha_biblionumber, branchcode: branch });
      if (r.status === 201 || r.status === 200) return { status: 'done', result: 'hold placed', data: { hold_id: r.json && (r.json.hold_id || r.json.reserve_id), priority: r.json && r.json.priority } };
      return { status: 'failed', result: kohaErr(r) };
    }
    case 'cancel_hold': {
      if (!p.koha_hold_id) return { status: 'done', result: 'nothing to cancel' };
      const r = await koha.api('DELETE', `holds/${p.koha_hold_id}`);
      if ([200, 204, 404].includes(r.status)) return { status: 'done', result: 'cancelled' };
      return { status: 'failed', result: kohaErr(r) };
    }
    case 'create_member': {
      const body = {
        surname: p.surname || p.firstname || 'Member', firstname: p.firstname || null, category_id: p.category_code, library_id: p.branch || cfg.branch,
        phone: p.phone || null, mobile: p.phone || null, email: p.email || null, date_of_birth: p.dob || null, address: p.address || null, city: p.city || null,
        other_name: p.guardian || null,
      };
      Object.keys(body).forEach(k => body[k] == null && delete body[k]);
      let r = await koha.api('POST', 'patrons', body);
      if (r.status === 400 && /mobile|other_name/i.test(r.text)) { delete body.mobile; delete body.other_name; r = await koha.api('POST', 'patrons', body); }
      if (r.status !== 201 && r.status !== 200) return { status: 'failed', result: kohaErr(r) };
      const patron = r.json; const id = patron.patron_id || patron.borrowernumber;
      let card = patron.cardnumber;
      if (!card) {
        card = cfg.cardPrefix + String(id).padStart(Number(cfg.cardDigits) || 6, '0');
        const g = await koha.api('GET', `patrons/${id}`);
        if (g.json) {
          const upd = Object.assign({}, g.json, { cardnumber: card });
          ['patron_id', 'updated_on', 'last_seen', 'anonymized', 'restricted', 'expired'].forEach(k => delete upd[k]);
          const u = await koha.api('PUT', `patrons/${id}`, upd);
          if (u.status >= 300) log('WARN', 'Could not set card number: ' + kohaErr(u));
        }
      }
      return { status: 'done', result: 'patron created', data: { borrowernumber: id, cardnumber: card, expiry_date: patron.expiry_date || patron.dateexpiry } };
    }
    case 'update_member': {
      if (!a.borrowernumber) return { status: 'failed', result: 'member not linked to Koha' };
      const g = await koha.api('GET', `patrons/${a.borrowernumber}`);
      if (!g.json) return { status: 'failed', result: kohaErr(g) };
      const ch = p.changes || {};
      const upd = Object.assign({}, g.json);
      ['patron_id', 'updated_on', 'last_seen', 'anonymized', 'restricted', 'expired'].forEach(k => delete upd[k]);
      if (ch.phone) { upd.phone = ch.phone; if ('mobile' in upd) upd.mobile = ch.phone; }
      if (ch.email) upd.email = ch.email;
      if (ch.address) upd.address = ch.address;
      const u = await koha.api('PUT', `patrons/${a.borrowernumber}`, upd);
      if (u.status < 300) return { status: 'done', result: 'updated' };
      return { status: 'failed', result: kohaErr(u) };
    }
    default: return { status: 'failed', result: 'unknown request type' };
  }
}

/* ---------------- Connection test ---------------- */
async function testAll() {
  const out = [];
  try { const h = await site.hello({ agent_version: VERSION }); out.push(['Website', true, `Connected (platform ${h.app_version}, ${h.counts.biblios} titles, ${h.pending_actions} waiting requests)`]); status.website = 'ok'; }
  catch (e) { out.push(['Website', false, e.message]); status.website = 'error'; }
  const v = await koha.version(); status.koha = v;
  try { await koha.login(); out.push(['Koha staff login', true, 'Signed in as ' + cfg.kohaUser + ' (Koha ' + v + ')']); }
  catch (e) { out.push(['Koha staff login', false, e.message]); }
  for (const [k, label] of [['biblios', 'Report 1 (titles)'], ['items', 'Report 2 (items)'], ['patrons', 'Report 3 (patrons)'], ['checkouts', 'Report 4 (checkouts)'], ['holds', 'Report 5 (holds)']]) {
    try {
      const params = k === 'biblios' ? ['2999-01-01', '2999-01-01', 0] : (['items', 'patrons'].includes(k) ? ['2999-01-01', 0] : []);
      await koha.report(cfg.reports[k], params); out.push([label, true, 'OK']);
    } catch (e) { out.push([label, false, e.message]); }
  }
  try {
    const r = await koha.api('GET', 'patrons?_per_page=1');
    out.push(['Koha REST API (for renew / reserve)', r.status === 200, r.status === 200 ? 'OK' : 'HTTP ' + r.status + ' — enable RESTBasicAuth or enter client ID / secret, and give the user borrowers + circulate + reserveforothers permissions']);
  } catch (e) { out.push(['Koha REST API', false, e.message]); }
  return out;
}

/* ---------------- Local settings page ---------------- */
const esc = s => String(s == null ? '' : s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
function page(body) {
  return `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MMBPL Sync Agent</title>
<style>body{font-family:Segoe UI,Inter,Arial,sans-serif;background:#F4F7FB;color:#0F1B2D;margin:0}header{background:#0B4DA2;color:#fff;padding:16px 28px;display:flex;justify-content:space-between;align-items:center}
main{max-width:1000px;margin:24px auto;padding:0 16px}.card{background:#fff;border:1px solid #D9E1EC;border-radius:12px;padding:20px;margin-bottom:18px}h2{margin:0 0 12px;font-size:19px}
label{display:block;font-weight:600;font-size:14px;margin:10px 0 4px}input{width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #D9E1EC;border-radius:8px;font-size:15px}
.g{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}.g5{display:grid;grid-template-columns:repeat(5,1fr);gap:0 12px}small{color:#5B6B7F}
button,.btn{background:#0B4DA2;color:#fff;border:0;border-radius:10px;padding:10px 18px;font-size:15px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.o{background:#fff;color:#0B4DA2;border:1.5px solid #0B4DA2}.gr{background:#1E8A2E}table{width:100%;border-collapse:collapse}td,th{padding:8px;border-bottom:1px solid #D9E1EC;text-align:left;font-size:14px}
.ok{color:#1E8A2E;font-weight:700}.bad{color:#C62828;font-weight:700}pre{background:#0F1B2D;color:#cfe3ff;padding:12px;border-radius:10px;max-height:340px;overflow:auto;font-size:12px;white-space:pre-wrap}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}</style></head><body><header><strong>MMBPL Sync Agent v${VERSION}</strong><span>Koha ↔ Website</span></header><main>${body}</main></body></html>`;
}
function dashboard(msg, tests) {
  const st = (v) => v === 'ok' ? '<span class="ok">● Connected</span>' : (v === 'error' ? '<span class="bad">● Error</span>' : (v ? esc(v) : '—'));
  return page(`${msg ? `<div class="card" style="border-color:#1E8A2E">${esc(msg)}</div>` : ''}
${tests ? `<div class="card"><h2>Connection test</h2><table>${tests.map(t => `<tr><td>${esc(t[0])}</td><td class="${t[1] ? 'ok' : 'bad'}">${t[1] ? '✓' : '✗'}</td><td>${esc(t[2])}</td></tr>`).join('')}</table></div>` : ''}
<div class="card"><h2>Status</h2><table>
<tr><td>Website</td><td>${st(status.website)}</td><td>${esc(cfg.websiteUrl)}</td></tr>
<tr><td>Koha</td><td>${st(status.koha)}</td><td>${esc(cfg.kohaUrl)}</td></tr>
<tr><td>Last sync</td><td colspan="2">${esc(status.lastRun || state.lastIncremental || 'never')} ${status.running ? '(running…)' : ''} ${status.lastError ? `<br><span class="bad">${esc(status.lastError)}</span>` : ''}</td></tr>
<tr><td>Last full sync</td><td colspan="2">${esc(state.lastFull || 'never')}</td></tr>
<tr><td>Last count</td><td colspan="2">${esc(JSON.stringify(state.counts || {}))}</td></tr></table>
<div class="row" style="margin-top:14px"><form method="post" action="/sync"><button>Sync now</button></form><form method="post" action="/full"><button class="o">Full sync</button></form><form method="post" action="/test"><button class="gr">Test connection</button></form><form method="post" action="/actions"><button class="o">Process requests now</button></form></div></div>
<form class="card" method="post" action="/save"><h2>Settings</h2>
<h3>1. Website (from Admin → Koha sync)</h3><div class="g"><div><label>Website address</label><input name="websiteUrl" value="${esc(cfg.websiteUrl)}" placeholder="https://library.example.com"></div><div><label>Agent key</label><input name="agentKey" value="${esc(cfg.agentKey)}" type="password" placeholder="mmb_..."></div></div>
<h3>2. Koha</h3><div class="g"><div><label>Koha staff address</label><input name="kohaUrl" value="${esc(cfg.kohaUrl)}" placeholder="http://localhost:8080"><small>The address staff open in the browser to use Koha.</small></div>
<div><label>Library (branch) code</label><input name="branch" value="${esc(cfg.branch)}" placeholder="MMBPL"></div>
<div><label>Koha staff user name</label><input name="kohaUser" value="${esc(cfg.kohaUser)}" placeholder="mmbpl_api"></div><div><label>Koha staff password</label><input name="kohaPass" type="password" value="${esc(cfg.kohaPass)}"></div>
<div><label>API client ID (optional)</label><input name="clientId" value="${esc(cfg.clientId)}"></div><div><label>API client secret (optional)</label><input name="clientSecret" type="password" value="${esc(cfg.clientSecret)}"></div></div>
<label><input type="checkbox" name="insecureTls" value="1" ${cfg.insecureTls ? 'checked' : ''} style="width:auto"> Koha uses a self-signed HTTPS certificate</label>
<h3>3. Koha report numbers (see koha-reports.sql)</h3><div class="g5">${['biblios', 'items', 'patrons', 'checkouts', 'holds'].map((k, i) => `<div><label>${i + 1}. ${k}</label><input name="r_${k}" value="${esc(cfg.reports[k])}"></div>`).join('')}</div>
<h3>4. Schedule & new members</h3><div class="g5"><div><label>Sync every (min)</label><input name="syncMinutes" value="${esc(cfg.syncMinutes)}"></div><div><label>Requests every (sec)</label><input name="actionsSeconds" value="${esc(cfg.actionsSeconds)}"></div><div><label>Full sync at hour</label><input name="fullSyncHour" value="${esc(cfg.fullSyncHour)}"></div><div><label>Card prefix</label><input name="cardPrefix" value="${esc(cfg.cardPrefix)}"></div><div><label>Card digits</label><input name="cardDigits" value="${esc(cfg.cardDigits)}"></div></div>
<p><button>Save settings</button></p></form>
<div class="card"><h2>Log</h2><pre>${esc(recent.slice(-120).reverse().join('\n'))}</pre><small>Full log: ${esc(LOG_FILE)}</small></div>`);
}
function readBody(req) { return new Promise(r => { let d = ''; req.on('data', c => { d += c; if (d.length > 1e6) req.destroy(); }); req.on('end', () => r(new URLSearchParams(d))); }); }
const ui = http.createServer(async (req, res) => {
  const send = (html, code = 200) => { res.writeHead(code, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' }); res.end(html); };
  const back = (msg) => { res.writeHead(303, { Location: '/?m=' + encodeURIComponent(msg || '') }); res.end(); };
  // Only accept requests from this computer and from our own page (basic CSRF guard).
  if (req.method === 'POST' && req.headers.origin && !/^http:\/\/(127\.0\.0\.1|localhost):/.test(req.headers.origin)) return send('Forbidden', 403);
  try {
    if (req.method === 'GET' && req.url.startsWith('/status.json')) { res.writeHead(200, { 'Content-Type': 'application/json' }); return res.end(JSON.stringify({ status, state, version: VERSION })); }
    if (req.method === 'GET') { const m = new URL(req.url, 'http://x').searchParams.get('m'); return send(dashboard(m)); }
    if (req.url === '/save') {
      const b = await readBody(req);
      for (const k of ['websiteUrl', 'agentKey', 'kohaUrl', 'kohaUser', 'kohaPass', 'clientId', 'clientSecret', 'branch', 'cardPrefix']) cfg[k] = (b.get(k) || '').trim();
      for (const k of ['syncMinutes', 'actionsSeconds', 'fullSyncHour', 'cardDigits']) cfg[k] = Math.max(0, Number(b.get(k)) || DEFAULTS[k]);
      cfg.syncMinutes = Math.max(1, cfg.syncMinutes); cfg.actionsSeconds = Math.max(10, cfg.actionsSeconds);
      cfg.insecureTls = b.get('insecureTls') === '1';
      for (const k of Object.keys(DEFAULTS.reports)) cfg.reports[k] = (b.get('r_' + k) || '').trim();
      saveCfg(); koha.cookie = null; koha.token = null; schedule();
      log('INFO', 'Settings saved'); return back('Settings saved.');
    }
    if (req.url === '/test') return send(dashboard('', await testAll()));
    if (req.url === '/sync') { syncAll(false); return back('Sync started. Refresh in a minute.'); }
    if (req.url === '/full') { syncAll(true); return back('Full sync started. This can take a few minutes.'); }
    if (req.url === '/actions') { await processActions(); return back('Requests processed.'); }
    send('Not found', 404);
  } catch (e) { send(page('<div class="card bad">' + esc(e.message) + '</div>'), 500); }
});

/* ---------------- Scheduler ---------------- */
let tSync = null, tAct = null;
function schedule() {
  clearInterval(tSync); clearInterval(tAct);
  if (!cfg.websiteUrl || !cfg.agentKey || !cfg.kohaUser) { log('INFO', 'Waiting for settings: open http://127.0.0.1:' + UI_PORT); return; }
  tSync = setInterval(() => {
    const now = new Date();
    const fullDue = state.lastFullDay !== now.toISOString().slice(0, 10) && now.getHours() >= Number(cfg.fullSyncHour);
    syncAll(fullDue);
  }, cfg.syncMinutes * 60000);
  tAct = setInterval(processActions, cfg.actionsSeconds * 1000);
  setTimeout(() => syncAll(!state.lastFull), 3000);
  setTimeout(processActions, 8000);
}

if (require.main === module) {
  const once = process.argv.includes('--once');
  if (once) { syncAll(process.argv.includes('--full')).then(processActions).then(() => process.exit(status.lastError ? 1 : 0)); }
  else {
    ui.on('error', e => { if (e.code === 'EADDRINUSE') { log('ERROR', `Port ${UI_PORT} in use: the agent is probably already running.`); process.exit(1); } throw e; });
    ui.listen(UI_PORT, '127.0.0.1', () => {
      log('INFO', `MMBPL Sync Agent ${VERSION} started. Settings: http://127.0.0.1:${UI_PORT}`);
      if (!process.argv.includes('--no-browser') && (!cfg.websiteUrl || process.argv.includes('--open'))) {
        const url = `http://127.0.0.1:${UI_PORT}`;
        exec(process.platform === 'win32' ? `start "" "${url}"` : (process.platform === 'darwin' ? `open ${url}` : `xdg-open ${url}`), () => {});
      }
      schedule();
    });
  }
}
module.exports = { mapBiblio, mapItem, mapPatron, mapCheckout, mapHold, itemStatus };
