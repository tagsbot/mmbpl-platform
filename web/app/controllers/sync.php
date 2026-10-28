<?php
/**
 * Sync API used by the MMBPL Sync Agent running next to Koha in the library.
 * Auth: header "X-Agent-Key: <key from Admin → Koha sync>".
 */
declare(strict_types=1);

function sync_dispatch(string $path): void
{
    header('Cache-Control: no-store');
    $key = $_SERVER['HTTP_X_AGENT_KEY'] ?? '';
    $hash = setting('agent_key_hash');
    if (!$hash || !$key || !hash_equals($hash, hash('sha256', $key))) json_out(['error' => 'unauthorized'], 401);
    set_setting('agent_last_seen', date('Y-m-d H:i:s'));
    $body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
    $method = $_SERVER['REQUEST_METHOD'];
    $parts = explode('/', trim($path, '/'));

    switch (true) {
        case $parts[0] === 'hello': sync_hello($body); break;
        case $parts[0] === 'biblios' && $method === 'POST': sync_biblios($body['records'] ?? []); break;
        case $parts[0] === 'items' && $method === 'POST': sync_items($body['records'] ?? []); break;
        case $parts[0] === 'patrons' && $method === 'POST': sync_patrons($body['records'] ?? []); break;
        case $parts[0] === 'circulation' && $method === 'POST': sync_circulation($body); break;
        case $parts[0] === 'prune' && $method === 'POST': sync_prune($body); break;
        case $parts[0] === 'finish' && $method === 'POST': sync_finish($body); break;
        case $parts[0] === 'actions' && count($parts) === 1: sync_actions(); break;
        case $parts[0] === 'actions' && isset($parts[1]) && $method === 'POST': sync_action_result((int)$parts[1], $body); break;
        default: json_out(['error' => 'not_found'], 404);
    }
}

function sync_log(string $kind, int $n, string $msg = ''): void
{
    insert('sync_log', ['kind' => $kind, 'records' => $n, 'message' => mb_substr($msg, 0, 500)]);
    q('DELETE FROM sync_log WHERE id < (SELECT id FROM (SELECT id FROM sync_log ORDER BY id DESC LIMIT 1 OFFSET 500) x)');
}

function sync_hello(array $b): void
{
    if (!empty($b['agent_version'])) set_setting('agent_version', mb_substr((string)$b['agent_version'], 0, 20));
    if (!empty($b['koha_version'])) set_setting('koha_version', mb_substr((string)$b['koha_version'], 0, 30));
    $counts = ['biblios' => (int)val('SELECT COUNT(*) FROM biblios WHERE koha_biblionumber IS NOT NULL'), 'patrons' => (int)val('SELECT COUNT(*) FROM members WHERE koha_borrowernumber IS NOT NULL')];
    json_out(['ok' => true, 'server_time' => date('c'), 'app_version' => APP_VERSION, 'branch' => setting('koha_branch'), 'counts' => $counts,
        'last_full_sync' => setting('last_full_sync'), 'pending_actions' => (int)val('SELECT COUNT(*) FROM actions WHERE status="pending"')]);
}

function lang_code(?string $l): string
{
    $l = strtolower(trim((string)$l));
    if (in_array($l, ['urd', 'ur', 'urdu'], true)) return 'ur';
    if (in_array($l, ['eng', 'en', 'english'], true)) return 'en';
    if (in_array($l, ['ara', 'ar', 'arabic'], true)) return 'ar';
    return $l === '' ? 'en' : 'other';
}

function sync_biblios(array $records): void
{
    $sql = 'INSERT INTO biblios (koha_biblionumber, title, subtitle, author, isbn, publisher, pub_year, edition, pages, language, format, subjects, summary, call_number, cover_url, date_added, popularity)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE title=VALUES(title), subtitle=VALUES(subtitle), author=VALUES(author), isbn=VALUES(isbn), publisher=VALUES(publisher), pub_year=VALUES(pub_year),
            edition=VALUES(edition), pages=VALUES(pages), language=VALUES(language), format=VALUES(format), subjects=VALUES(subjects), summary=VALUES(summary),
            call_number=VALUES(call_number), cover_url=COALESCE(VALUES(cover_url), cover_url), date_added=COALESCE(VALUES(date_added), date_added), popularity=GREATEST(popularity, VALUES(popularity))';
    $st = db()->prepare($sql);
    $n = 0;
    db()->beginTransaction();
    foreach ($records as $r) {
        if (empty($r['biblionumber']) || !isset($r['title'])) continue;
        $subjects = is_array($r['subjects'] ?? null) ? implode('; ', $r['subjects']) : ($r['subjects'] ?? null);
        $isbn = $r['isbn'] ?? null;
        $cover = $r['cover_url'] ?? (setting('covers_openlibrary', '1') === '1' && $isbn ? 'https://covers.openlibrary.org/b/isbn/' . rawurlencode(preg_replace('/[^0-9Xx]/', '', explode(' ', (string)$isbn)[0])) . '-M.jpg?default=false' : null);
        $st->execute([(int)$r['biblionumber'], mb_substr(trim((string)$r['title'], " /:;,."), 0, 500) ?: 'Untitled', s500($r['subtitle'] ?? null), s500($r['author'] ?? null, 300), s500($isbn, 60),
            s500($r['publisher'] ?? null, 300), s500($r['pub_year'] ?? null, 20), s500($r['edition'] ?? null, 100), s500($r['pages'] ?? null, 50), lang_code($r['language'] ?? null),
            in_array($r['format'] ?? 'book', ['book', 'magazine', 'newspaper', 'manuscript', 'other'], true) ? ($r['format'] ?? 'book') : 'other',
            $subjects ? mb_substr($subjects, 0, 1000) : null, $r['summary'] ?? null, s500($r['call_number'] ?? null, 100), $cover,
            !empty($r['date_added']) ? substr((string)$r['date_added'], 0, 10) : null, (int)($r['popularity'] ?? 0)]);
        $n++;
    }
    db()->commit();
    sync_log('biblios', $n);
    json_out(['ok' => true, 'saved' => $n]);
}
function s500($v, int $len = 500): ?string { if ($v === null || $v === '') return null; return mb_substr(trim((string)$v), 0, $len); }

function biblio_id_map(array $kohaIds): array
{
    $kohaIds = array_values(array_unique(array_map('intval', $kohaIds)));
    if (!$kohaIds) return [];
    $map = [];
    foreach (array_chunk($kohaIds, 1000) as $chunk) {
        foreach (rows('SELECT id, koha_biblionumber FROM biblios WHERE koha_biblionumber IN (' . implode(',', $chunk) . ')') as $r) $map[(int)$r['koha_biblionumber']] = (int)$r['id'];
    }
    return $map;
}
function id_map(string $table, string $col, array $kohaIds): array
{
    $kohaIds = array_values(array_unique(array_filter(array_map('intval', $kohaIds))));
    $map = [];
    foreach (array_chunk($kohaIds, 1000) as $chunk) {
        foreach (rows("SELECT id, $col k FROM $table WHERE $col IN (" . implode(',', $chunk) . ')') as $r) $map[(int)$r['k']] = (int)$r['id'];
    }
    return $map;
}

function sync_items(array $records): void
{
    $map = id_map('biblios', 'koha_biblionumber', array_column($records, 'biblionumber'));
    $st = db()->prepare('INSERT INTO items (koha_itemnumber, biblio_id, barcode, shelf_location, call_number, status, due_date) VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE biblio_id=VALUES(biblio_id), barcode=VALUES(barcode), shelf_location=VALUES(shelf_location), call_number=VALUES(call_number), status=VALUES(status), due_date=VALUES(due_date)');
    $ok = ['available', 'checked_out', 'on_hold', 'in_transit', 'lost', 'damaged', 'reference', 'withdrawn'];
    $n = 0; $skipped = 0;
    db()->beginTransaction();
    foreach ($records as $r) {
        $bid = $map[(int)($r['biblionumber'] ?? 0)] ?? null;
        if (!$bid || empty($r['itemnumber'])) { $skipped++; continue; }
        $status = in_array($r['status'] ?? '', $ok, true) ? $r['status'] : 'available';
        $st->execute([(int)$r['itemnumber'], $bid, s500($r['barcode'] ?? null, 60), s500($r['shelf_location'] ?? null, 150), s500($r['call_number'] ?? null, 100), $status, !empty($r['due_date']) ? substr((string)$r['due_date'], 0, 10) : null]);
        $n++;
    }
    db()->commit();
    sync_log('items', $n, $skipped ? "$skipped skipped (unknown biblio)" : '');
    json_out(['ok' => true, 'saved' => $n, 'skipped' => $skipped]);
}

function sync_patrons(array $records): void
{
    $st = db()->prepare('INSERT INTO members (koha_borrowernumber, cardnumber, firstname, surname, category, email, phone, dob, expiry_date, status, fines_due, max_loans)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE cardnumber=VALUES(cardnumber), firstname=VALUES(firstname), surname=VALUES(surname), category=VALUES(category), email=VALUES(email), phone=VALUES(phone),
        dob=VALUES(dob), expiry_date=VALUES(expiry_date), status=VALUES(status), fines_due=VALUES(fines_due), max_loans=VALUES(max_loans)');
    $n = 0; $err = 0;
    db()->beginTransaction();
    foreach ($records as $r) {
        if (empty($r['borrowernumber']) || empty($r['cardnumber'])) { $err++; continue; }
        $exp = !empty($r['expiry_date']) ? substr((string)$r['expiry_date'], 0, 10) : null;
        $status = !empty($r['blocked']) ? 'blocked' : (($exp && $exp < date('Y-m-d')) ? 'expired' : 'active');
        // If a website-only (demo/manual) row holds this card number, attach it to Koha instead of failing.
        q('UPDATE members SET koha_borrowernumber=? WHERE cardnumber=? AND koha_borrowernumber IS NULL', [(int)$r['borrowernumber'], (string)$r['cardnumber']]);
        try {
            $st->execute([(int)$r['borrowernumber'], mb_substr((string)$r['cardnumber'], 0, 60), s500($r['firstname'] ?? null, 150), s500($r['surname'] ?? null, 150), s500($r['category'] ?? null, 60),
                s500($r['email'] ?? null, 190), s500($r['phone'] ?? null, 40), !empty($r['dob']) ? substr((string)$r['dob'], 0, 10) : null, $exp, $status, (float)($r['fines_due'] ?? 0), (int)($r['max_loans'] ?? 5)]);
            $n++;
        } catch (PDOException $e) { $err++; }
    }
    db()->commit();
    sync_log('patrons', $n, $err ? "$err skipped" : '');
    json_out(['ok' => true, 'saved' => $n, 'skipped' => $err]);
}

/**
 * Circulation snapshot. With "full": true every loan/hold not in the payload is removed.
 * Otherwise only the listed borrowers are replaced (incremental).
 */
function sync_circulation(array $b): void
{
    $checkouts = $b['checkouts'] ?? [];
    $holds = $b['holds'] ?? [];
    $full = !empty($b['full']);
    $pmap = id_map('members', 'koha_borrowernumber', array_merge(array_column($checkouts, 'borrowernumber'), array_column($holds, 'borrowernumber'), $b['borrowernumbers'] ?? []));
    $imap = id_map('items', 'koha_itemnumber', array_column($checkouts, 'itemnumber'));
    $bmap = id_map('biblios', 'koha_biblionumber', array_column($holds, 'biblionumber'));

    db()->beginTransaction();
    // Loans that disappear were returned: keep them as reading history.
    $scopeMembers = $full ? null : array_values(array_unique(array_values($pmap)));
    $seenIssues = array_map('intval', array_column($checkouts, 'issue_id'));
    $oldLoans = $full ? rows('SELECT l.*, i.biblio_id FROM loans l JOIN items i ON i.id=l.item_id')
        : ($scopeMembers ? rows('SELECT l.*, i.biblio_id FROM loans l JOIN items i ON i.id=l.item_id WHERE l.member_id IN (' . implode(',', $scopeMembers) . ')') : []);
    foreach ($oldLoans as $ol) {
        if ($ol['koha_issue_id'] && !in_array((int)$ol['koha_issue_id'], $seenIssues, true)) {
            q('INSERT IGNORE INTO reading_history (member_id, biblio_id, issued_at, returned_at) VALUES (?,?,?,CURDATE())', [$ol['member_id'], $ol['biblio_id'], $ol['issued_at']]);
            q('DELETE FROM loans WHERE id=?', [$ol['id']]);
        }
    }
    $stL = db()->prepare('INSERT INTO loans (koha_issue_id, member_id, item_id, issued_at, due_date, renewals, renewable, not_renewable_reason) VALUES (?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE member_id=VALUES(member_id), item_id=VALUES(item_id), due_date=VALUES(due_date), renewals=VALUES(renewals), renewable=VALUES(renewable), not_renewable_reason=VALUES(not_renewable_reason)');
    $nL = 0;
    foreach ($checkouts as $c) {
        $mid = $pmap[(int)($c['borrowernumber'] ?? 0)] ?? null;
        $iid = $imap[(int)($c['itemnumber'] ?? 0)] ?? null;
        if (!$mid || !$iid || empty($c['issue_id'])) continue;
        $stL->execute([(int)$c['issue_id'], $mid, $iid, !empty($c['issued_at']) ? substr((string)$c['issued_at'], 0, 10) : null, !empty($c['due_date']) ? substr((string)$c['due_date'], 0, 10) : null,
            (int)($c['renewals'] ?? 0), isset($c['renewable']) ? (int)(bool)$c['renewable'] : 1, s500($c['not_renewable_reason'] ?? null, 120)]);
        q('UPDATE items SET status="checked_out", due_date=? WHERE id=?', [!empty($c['due_date']) ? substr((string)$c['due_date'], 0, 10) : null, $iid]);
        $nL++;
    }

    // Holds
    $seenHolds = array_map('intval', array_column($holds, 'hold_id'));
    $oldHolds = $full ? rows('SELECT * FROM holds WHERE koha_hold_id IS NOT NULL AND status IN ("waiting","ready")')
        : ($scopeMembers ? rows('SELECT * FROM holds WHERE koha_hold_id IS NOT NULL AND status IN ("waiting","ready") AND member_id IN (' . implode(',', $scopeMembers) . ')') : []);
    foreach ($oldHolds as $oh) if (!in_array((int)$oh['koha_hold_id'], $seenHolds, true)) q('UPDATE holds SET status="fulfilled" WHERE id=?', [$oh['id']]);
    $nH = 0;
    foreach ($holds as $h) {
        $mid = $pmap[(int)($h['borrowernumber'] ?? 0)] ?? null;
        $bid = $bmap[(int)($h['biblionumber'] ?? 0)] ?? null;
        if (!$mid || !$bid || empty($h['hold_id'])) continue;
        $status = ($h['status'] ?? '') === 'ready' ? 'ready' : 'waiting';
        $existing = row('SELECT * FROM holds WHERE koha_hold_id=?', [(int)$h['hold_id']])
            ?: row('SELECT * FROM holds WHERE member_id=? AND biblio_id=? AND koha_hold_id IS NULL AND status="requested" ORDER BY id DESC LIMIT 1', [$mid, $bid]);
        $data = ['koha_hold_id' => (int)$h['hold_id'], 'member_id' => $mid, 'biblio_id' => $bid, 'status' => $status, 'queue_position' => isset($h['queue_position']) ? (int)$h['queue_position'] : null, 'pickup_by' => !empty($h['pickup_by']) ? substr((string)$h['pickup_by'], 0, 10) : null];
        if ($existing) {
            update('holds', $data, 'id=?', [$existing['id']]);
            if ($status === 'ready' && $existing['status'] !== 'ready') hold_ready_notice($mid, $bid, $data['pickup_by']);
        } else {
            insert('holds', $data);
            if ($status === 'ready') hold_ready_notice($mid, $bid, $data['pickup_by']);
        }
        $nH++;
    }
    db()->commit();

    $reminders = due_reminders();
    sync_log('circulation', $nL + $nH, "$nL loans, $nH holds" . ($full ? ' (full)' : '') . ($reminders ? ", $reminders reminders" : ''));
    json_out(['ok' => true, 'loans' => $nL, 'holds' => $nH, 'reminders' => $reminders]);
}

function hold_ready_notice(int $memberId, int $biblioId, ?string $pickupBy): void
{
    $m = row('SELECT * FROM members WHERE id=?', [$memberId]);
    $b = row('SELECT title FROM biblios WHERE id=?', [$biblioId]);
    if (!$m || !$b) return;
    $title = 'Your reserved book is ready for pickup';
    $body = '"' . $b['title'] . '" is waiting at the circulation desk' . ($pickupBy ? '. Collect by ' . fmt_date($pickupBy) : '') . '.';
    if ($m['notify_holds']) notify_member($memberId, 'hold', $title, $body, 'account/holds');
    if ($m['notify_holds'] && $m['notify_sms'] && $m['phone']) send_sms($m['phone'], setting('short_name', 'MMB Library') . ': ' . $body);
}

/** One reminder per loan, the day before it is due and on the first overdue day. */
function due_reminders(): int
{
    $n = 0;
    $list = rows('SELECT l.id, l.member_id, l.due_date, b.title, m.phone, m.notify_due, m.notify_sms FROM loans l JOIN items i ON i.id=l.item_id JOIN biblios b ON b.id=i.biblio_id JOIN members m ON m.id=l.member_id
                  WHERE l.due_date IN (DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)) AND m.notify_due=1');
    foreach ($list as $l) {
        $over = $l['due_date'] < date('Y-m-d');
        $link = 'account/loans#loan-' . $l['id'] . '-' . $l['due_date'] . ($over ? '-o' : '');
        if (val('SELECT 1 FROM notifications WHERE member_id=? AND link=?', [$l['member_id'], $link])) continue;
        $title = $over ? '"' . $l['title'] . '" is overdue' : '"' . $l['title'] . '" is due tomorrow';
        $body = $over ? 'Please return or renew it as soon as possible.' : 'Renew it online or return it to the library.';
        notify_member((int)$l['member_id'], $over ? 'overdue' : 'due', $title, $body, $link);
        if ($l['notify_sms'] && $l['phone']) send_sms($l['phone'], setting('short_name', 'MMB Library') . ': ' . $title . '. ' . $body);
        $n++;
    }
    return $n;
}

function sync_prune(array $b): void
{
    $out = [];
    foreach (['biblios' => ['biblios', 'koha_biblionumber'], 'items' => ['items', 'koha_itemnumber'], 'patrons' => ['members', 'koha_borrowernumber']] as $k => [$table, $col]) {
        if (!isset($b[$k]) || !is_array($b[$k])) continue;
        $keep = array_flip(array_map('intval', $b[$k]));
        if (!$keep) continue; // never wipe everything because of an empty list
        $del = [];
        foreach (rows("SELECT id, $col k FROM $table WHERE $col IS NOT NULL") as $r) if (!isset($keep[(int)$r['k']])) $del[] = (int)$r['id'];
        foreach (array_chunk($del, 1000) as $chunk) q("DELETE FROM $table WHERE id IN (" . implode(',', $chunk) . ')');
        $out[$k] = count($del);
    }
    sync_log('prune', array_sum($out), json_encode($out));
    json_out(['ok' => true, 'deleted' => $out]);
}

function sync_finish(array $b): void
{
    if (($b['kind'] ?? '') === 'full') set_setting('last_full_sync', date('Y-m-d H:i:s'));
    // Popularity can be sent as a separate map {biblionumber: issues}
    if (!empty($b['popularity']) && is_array($b['popularity'])) {
        $st = db()->prepare('UPDATE biblios SET popularity=? WHERE koha_biblionumber=?');
        foreach ($b['popularity'] as $bn => $cnt) $st->execute([(int)$cnt, (int)$bn]);
    }
    sync_log('finish', (int)($b['records'] ?? 0), (string)($b['message'] ?? ($b['kind'] ?? '')));
    json_out(['ok' => true]);
}

function sync_actions(): void
{
    // Requeue requests stuck in "processing" for more than 15 minutes.
    q('UPDATE actions SET status="pending" WHERE status="processing" AND processed_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $list = rows('SELECT a.*, m.koha_borrowernumber, m.cardnumber FROM actions a LEFT JOIN members m ON m.id=a.member_id WHERE a.status="pending" ORDER BY a.id LIMIT 50');
    $out = [];
    foreach ($list as $a) {
        $p = json_decode((string)$a['payload'], true) ?: [];
        if ($a['type'] === 'renew') { $l = row('SELECT koha_issue_id FROM loans WHERE id=?', [$a['ref_id']]); $p['koha_issue_id'] = $l['koha_issue_id'] ?? ($p['koha_issue_id'] ?? null); }
        if ($a['type'] === 'reserve') { $h = row('SELECT h.*, b.koha_biblionumber FROM holds h JOIN biblios b ON b.id=h.biblio_id WHERE h.id=?', [$a['ref_id']]); $p['koha_biblionumber'] = $h['koha_biblionumber'] ?? null; if (!$h || $h['status'] === 'cancelled') { update('actions', ['status' => 'done', 'result' => 'cancelled by member', 'processed_at' => date('Y-m-d H:i:s')], 'id=?', [$a['id']]); continue; } }
        if ($a['type'] === 'create_member') $p['branch'] = setting('koha_branch');
        $p['branch'] = $p['branch'] ?? setting('koha_branch');
        $out[] = ['id' => (int)$a['id'], 'type' => $a['type'], 'borrowernumber' => $a['koha_borrowernumber'] ? (int)$a['koha_borrowernumber'] : null, 'cardnumber' => $a['cardnumber'], 'payload' => $p, 'created_at' => $a['created_at']];
        update('actions', ['status' => 'processing', 'processed_at' => date('Y-m-d H:i:s')], 'id=?', [$a['id']]);
    }
    json_out(['actions' => $out]);
}

function sync_action_result(int $id, array $b): void
{
    $a = row('SELECT * FROM actions WHERE id=?', [$id]);
    if (!$a) json_out(['error' => 'not_found'], 404);
    $ok = ($b['status'] ?? '') === 'done';
    $result = mb_substr((string)($b['result'] ?? ($ok ? 'ok' : 'failed')), 0, 500);
    $d = $b['data'] ?? [];
    update('actions', ['status' => $ok ? 'done' : 'failed', 'result' => $result, 'processed_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
    $mid = $a['member_id'] ? (int)$a['member_id'] : null;

    switch ($a['type']) {
        case 'renew':
            $l = row('SELECT l.*, b.title FROM loans l JOIN items i ON i.id=l.item_id JOIN biblios b ON b.id=i.biblio_id WHERE l.id=?', [$a['ref_id']]);
            if ($ok && $l) {
                if (!empty($d['due_date'])) q('UPDATE loans SET due_date=?, renewals=renewals+1 WHERE id=?', [substr((string)$d['due_date'], 0, 10), $l['id']]);
                if ($mid) notify_member($mid, 'renew', 'Renewed: ' . $l['title'], !empty($d['due_date']) ? 'New due date ' . fmt_date((string)$d['due_date']) : '', 'account/loans');
            } elseif ($l && $mid) {
                if (stripos($result, 'reserve') !== false || stripos($result, 'on_reserve') !== false) q('UPDATE loans SET renewable=0, not_renewable_reason="on_reserve" WHERE id=?', [$l['id']]);
                notify_member($mid, 'renew', 'Could not renew: ' . $l['title'], friendly_koha_error($result), 'account/loans');
            }
            break;
        case 'reserve':
            $h = row('SELECT h.*, b.title FROM holds h JOIN biblios b ON b.id=h.biblio_id WHERE h.id=?', [$a['ref_id']]);
            if ($h && $ok) {
                update('holds', ['status' => 'waiting', 'koha_hold_id' => !empty($d['hold_id']) ? (int)$d['hold_id'] : null, 'queue_position' => $d['priority'] ?? null], 'id=?', [$h['id']]);
                if ($mid) notify_member($mid, 'hold', 'Reservation placed: ' . $h['title'], 'We will tell you when it is ready to collect.', 'account/holds');
            } elseif ($h) {
                update('holds', ['status' => 'failed', 'note' => $result], 'id=?', [$h['id']]);
                if ($mid) notify_member($mid, 'hold', 'Reservation not placed: ' . $h['title'], friendly_koha_error($result), 'book/' . $h['biblio_id']);
            }
            break;
        case 'cancel_hold':
            if ($ok) q('UPDATE holds SET status="cancelled" WHERE id=?', [$a['ref_id']]);
            break;
        case 'create_member':
            if ($ok && !empty($d['borrowernumber']) && !empty($d['cardnumber'])) {
                $app = row('SELECT * FROM applications WHERE id=?', [$a['ref_id']]);
                $p = json_decode((string)$a['payload'], true) ?: [];
                $memberId = (int)(val('SELECT id FROM members WHERE koha_borrowernumber=?', [(int)$d['borrowernumber']]) ?: insert('members', [
                    'koha_borrowernumber' => (int)$d['borrowernumber'], 'cardnumber' => (string)$d['cardnumber'], 'firstname' => $p['firstname'] ?? null, 'surname' => $p['surname'] ?? null,
                    'phone' => $p['phone'] ?? null, 'email' => $p['email'] ?? null, 'dob' => $p['dob'] ?? null, 'category' => $p['category_code'] ?? null,
                    'expiry_date' => !empty($d['expiry_date']) ? substr((string)$d['expiry_date'], 0, 10) : null]));
                if ($app) {
                    update('applications', ['member_id' => $memberId, 'staff_note' => trim(($app['staff_note'] ?? '') . ' Card: ' . $d['cardnumber'])], 'id=?', [$app['id']]);
                    send_sms((string)$app['phone'], setting('short_name', 'MMB Library') . ': Welcome! Your card number is ' . $d['cardnumber'] . '. Activate your online account at ' . absolute_url('activate'));
                }
            }
            break;
        case 'update_member':
            if ($ok && $mid) { $p = json_decode((string)$a['payload'], true) ?: []; $ch = array_intersect_key($p['changes'] ?? [], ['phone' => 1, 'email' => 1]); if ($ch) update('members', $ch, 'id=?', [$mid]); notify_member($mid, 'membership', 'Your contact details were updated', '', 'account/profile'); }
            break;
    }
    json_out(['ok' => true]);
}

function friendly_koha_error(string $r): string
{
    $map = ['on_reserve' => 'Another member has reserved this book.', 'too_many' => 'You have reached the renewal limit.', 'too_soon' => 'It is too early to renew this book.',
        'restriction' => 'Your account has a restriction. Please contact the library.', 'debt' => 'Please clear your fines first.', 'expired' => 'Your membership has expired.',
        'already' => 'You already have this book or reservation.', 'maximum' => 'You have reached the maximum number of reservations.'];
    foreach ($map as $k => $v) if (stripos($r, $k) !== false) return $v;
    return 'Please contact the library desk.';
}
