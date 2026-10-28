<?php
declare(strict_types=1);

/**
 * Catalog search. Returns [rows, total].
 * Filters: q, field (all|title|author|subject|isbn), lang, avail (1), format, year_from, year_to, sort, page
 */
function catalog_search(array $f, int $perPage = 12): array
{
    $where = ['1=1'];
    $p = [];
    $qstr = trim((string)($f['q'] ?? ''));
    $field = $f['field'] ?? 'all';
    if ($qstr !== '') {
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $qstr) . '%';
        switch ($field) {
            case 'title': $where[] = '(b.title LIKE ? OR b.subtitle LIKE ?)'; array_push($p, $like, $like); break;
            case 'author': $where[] = 'b.author LIKE ?'; $p[] = $like; break;
            case 'subject': $where[] = 'b.subjects LIKE ?'; $p[] = $like; break;
            case 'isbn': $where[] = 'REPLACE(b.isbn, "-", "") LIKE ?'; $p[] = '%' . str_replace('-', '', $qstr) . '%'; break;
            default:
                $where[] = '(b.title LIKE ? OR b.subtitle LIKE ? OR b.author LIKE ? OR b.subjects LIKE ? OR b.isbn LIKE ? OR b.call_number LIKE ? OR EXISTS (SELECT 1 FROM items ib WHERE ib.biblio_id=b.id AND ib.barcode = ?))';
                array_push($p, $like, $like, $like, $like, $like, $like, $qstr);
        }
    }
    if (!empty($f['lang']) && is_array($f['lang'])) {
        $langs = array_values(array_intersect($f['lang'], ['ur', 'en', 'ar', 'other']));
        if ($langs) { $where[] = 'b.language IN (' . implode(',', array_fill(0, count($langs), '?')) . ')'; $p = array_merge($p, $langs); }
    }
    if (!empty($f['format']) && is_array($f['format'])) {
        $fm = array_values(array_intersect($f['format'], ['book', 'magazine', 'newspaper', 'manuscript', 'other']));
        if ($fm) { $where[] = 'b.format IN (' . implode(',', array_fill(0, count($fm), '?')) . ')'; $p = array_merge($p, $fm); }
    }
    if (!empty($f['subject_tag'])) { $where[] = 'b.subjects LIKE ?'; $p[] = '%' . $f['subject_tag'] . '%'; }
    if (!empty($f['author_tag'])) { $where[] = 'b.author = ?'; $p[] = $f['author_tag']; }
    if (!empty($f['year_from']) && ctype_digit((string)$f['year_from'])) { $where[] = 'CAST(b.pub_year AS UNSIGNED) >= ?'; $p[] = (int)$f['year_from']; }
    if (!empty($f['year_to']) && ctype_digit((string)$f['year_to'])) { $where[] = 'CAST(b.pub_year AS UNSIGNED) <= ?'; $p[] = (int)$f['year_to']; }
    if (!empty($f['avail'])) $where[] = "EXISTS (SELECT 1 FROM items ia WHERE ia.biblio_id=b.id AND ia.status='available')";
    if (!empty($f['new'])) $where[] = 'b.date_added >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)';

    $order = match ($f['sort'] ?? 'relevance') {
        'newest' => 'b.date_added DESC, b.id DESC',
        'title' => 'b.title ASC',
        'popular' => 'b.popularity DESC',
        default => $qstr !== '' ? '(b.title LIKE ' . db()->quote($qstr . '%') . ') DESC, b.popularity DESC, b.title ASC' : 'b.date_added DESC, b.id DESC',
    };
    $w = implode(' AND ', $where);
    $total = (int)val("SELECT COUNT(*) FROM biblios b WHERE $w", $p);
    $page = max(1, (int)($f['page'] ?? 1));
    $off = ($page - 1) * $perPage;
    $list = rows("SELECT b.* FROM biblios b WHERE $w ORDER BY $order LIMIT $perPage OFFSET $off", $p);
    foreach ($list as &$b) {
        $b['av'] = biblio_availability((int)$b['id']);
        $b['shelf'] = val('SELECT shelf_location FROM items WHERE biblio_id = ? AND shelf_location IS NOT NULL ORDER BY status="available" DESC LIMIT 1', [$b['id']]);
    }
    return [$list, $total];
}

function book_list(string $kind, int $limit = 10): array
{
    $order = $kind === 'popular' ? 'popularity DESC, id DESC' : 'date_added DESC, id DESC';
    $list = rows("SELECT * FROM biblios ORDER BY $order LIMIT " . (int)$limit);
    foreach ($list as &$b) $b['av'] = biblio_availability((int)$b['id']);
    return $list;
}

function member_loans(int $memberId): array
{
    return rows('SELECT l.*, b.id AS biblio_id, b.title, b.author, b.cover_url, b.call_number, i.barcode,
                 (SELECT a.status FROM actions a WHERE a.type="renew" AND a.ref_id=l.id AND a.status IN ("pending","processing") LIMIT 1) AS renew_pending
                 FROM loans l JOIN items i ON i.id = l.item_id JOIN biblios b ON b.id = i.biblio_id
                 WHERE l.member_id = ? ORDER BY l.due_date ASC', [$memberId]);
}
function member_holds(int $memberId): array
{
    return rows('SELECT h.*, b.title, b.author, b.cover_url, b.call_number FROM holds h JOIN biblios b ON b.id = h.biblio_id
                 WHERE h.member_id = ? AND h.status IN ("requested","waiting","ready") ORDER BY h.status="ready" DESC, h.created_at DESC', [$memberId]);
}
function member_history(int $memberId, int $limit = 100): array
{
    return rows('SELECT r.*, b.title, b.author, b.cover_url, b.call_number FROM reading_history r JOIN biblios b ON b.id = r.biblio_id
                 WHERE r.member_id = ? ORDER BY r.returned_at DESC, r.issued_at DESC LIMIT ' . (int)$limit, [$memberId]);
}
function member_notifications(int $memberId, int $limit = 50): array
{
    return rows('SELECT n.*, (n.read_at IS NOT NULL OR nr.member_id IS NOT NULL) AS is_read FROM notifications n
                 LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.member_id = ?
                 WHERE n.member_id = ? OR n.member_id IS NULL ORDER BY n.created_at DESC LIMIT ' . (int)$limit, [$memberId, $memberId]);
}
function unread_count(int $memberId): int
{
    return (int)val('SELECT COUNT(*) FROM notifications n LEFT JOIN notification_reads nr ON nr.notification_id=n.id AND nr.member_id=?
                     WHERE (n.member_id = ? OR (n.member_id IS NULL AND n.created_at >= (SELECT COALESCE(activated_at, NOW()) FROM members WHERE id=?)))
                     AND n.read_at IS NULL AND nr.member_id IS NULL', [$memberId, $memberId, $memberId]);
}
function mark_all_read(int $memberId): void
{
    q('UPDATE notifications SET read_at = NOW() WHERE member_id = ? AND read_at IS NULL', [$memberId]);
    q('INSERT IGNORE INTO notification_reads (notification_id, member_id) SELECT id, ? FROM notifications WHERE member_id IS NULL', [$memberId]);
}

/** Queue a renewal. Returns [ok, message_key]. */
function request_renew(array $m, int $loanId): array
{
    $l = row('SELECT * FROM loans WHERE id = ? AND member_id = ?', [$loanId, $m['id']]);
    if (!$l) return [false, 'err_not_found'];
    if ($m['status'] !== 'active') return [false, 'err_member_not_active'];
    if (!$l['renewable']) return [false, $l['not_renewable_reason'] === 'on_reserve' ? 'err_cannot_renew_reserved' : 'err_cannot_renew'];
    if (val('SELECT id FROM actions WHERE type="renew" AND ref_id=? AND status IN ("pending","processing")', [$loanId])) return [true, 'renew_already_requested'];
    insert('actions', ['type' => 'renew', 'member_id' => $m['id'], 'ref_id' => $loanId, 'payload' => json_encode(['koha_issue_id' => $l['koha_issue_id']])]);
    if (!agent_online()) return [true, 'renew_queued_offline'];
    return [true, 'renew_requested'];
}

/** Queue a reservation (hold). */
function request_reserve(array $m, int $biblioId): array
{
    $b = row('SELECT * FROM biblios WHERE id = ?', [$biblioId]);
    if (!$b) return [false, 'err_not_found'];
    if ($m['status'] !== 'active') return [false, 'err_member_not_active'];
    if (val('SELECT id FROM holds WHERE member_id=? AND biblio_id=? AND status IN ("requested","waiting","ready")', [$m['id'], $biblioId])) return [false, 'err_already_reserved'];
    if (val('SELECT l.id FROM loans l JOIN items i ON i.id=l.item_id WHERE l.member_id=? AND i.biblio_id=?', [$m['id'], $biblioId])) return [false, 'err_already_have'];
    $max = (int)setting('max_holds', '3');
    if ((int)val('SELECT COUNT(*) FROM holds WHERE member_id=? AND status IN ("requested","waiting","ready")', [$m['id']]) >= $max) return [false, 'err_max_holds'];
    $hid = insert('holds', ['member_id' => $m['id'], 'biblio_id' => $biblioId, 'status' => 'requested']);
    insert('actions', ['type' => 'reserve', 'member_id' => $m['id'], 'ref_id' => $hid, 'payload' => json_encode(['koha_biblionumber' => $b['koha_biblionumber'], 'koha_borrowernumber' => $m['koha_borrowernumber']])]);
    return [true, agent_online() ? 'reserve_requested' : 'reserve_queued_offline'];
}
function request_cancel_hold(array $m, int $holdId): array
{
    $h = row('SELECT * FROM holds WHERE id=? AND member_id=? AND status IN ("requested","waiting","ready")', [$holdId, $m['id']]);
    if (!$h) return [false, 'err_not_found'];
    if (!$h['koha_hold_id']) {
        // Not in Koha yet: cancel locally and drop the queued action.
        q('UPDATE holds SET status="cancelled" WHERE id=?', [$holdId]);
        q('UPDATE actions SET status="done", result="cancelled before sync" WHERE type="reserve" AND ref_id=? AND status="pending"', [$holdId]);
        return [true, 'hold_cancelled'];
    }
    insert('actions', ['type' => 'cancel_hold', 'member_id' => $m['id'], 'ref_id' => $holdId, 'payload' => json_encode(['koha_hold_id' => $h['koha_hold_id']])]);
    return [true, 'hold_cancel_requested'];
}

function agent_online(): bool
{
    $last = setting('agent_last_seen');
    return $last && strtotime($last) > time() - 600;
}

function library_stats(): array
{
    return [
        'members' => (int)setting('stat_members_override') ?: (int)val('SELECT COUNT(*) FROM members'),
        'books' => (int)setting('stat_books_override') ?: (int)val('SELECT COUNT(*) FROM items'),
        'titles' => (int)val('SELECT COUNT(*) FROM biblios'),
        'events' => (int)setting('stat_events_override') ?: (int)val('SELECT COUNT(*) FROM posts WHERE type="event" AND status="published"'),
    ];
}

function upcoming_events(int $limit = 3): array
{
    return rows('SELECT * FROM posts WHERE type="event" AND status="published" AND (event_end >= NOW() OR event_start >= NOW()) ORDER BY event_start ASC LIMIT ' . (int)$limit);
}
function latest_posts(int $limit = 3): array
{
    return rows('SELECT * FROM posts WHERE status="published" ORDER BY COALESCE(IF(type="event" AND event_start>=NOW(), event_start, NULL), created_at) DESC LIMIT ' . (int)$limit);
}
function event_seats_left(array $e): ?int
{
    if (!$e['seats']) return null;
    $taken = (int)val('SELECT COALESCE(SUM(seats),0) FROM event_registrations WHERE post_id=?', [$e['id']]);
    return max(0, (int)$e['seats'] - $taken);
}
function post_image(array $p): string
{
    return $p['image'] ? upload_url($p['image']) : asset('img/event-placeholder.svg');
}
