# MMBPL API v1 (for the mobile and desktop apps)

Base URL: `https://<website>/api/v1/`. JSON in and out. Send `Accept-Language: ur` to get Urdu messages and Urdu content.
Signed-in calls need `Authorization: Bearer <token>`. Tokens last 90 days.

## Auth
| Method | Path | Body | Returns |
|---|---|---|---|
| POST | `auth/login` | `{login, password, device}` (login = card no. or mobile) | `{token, member}`; 403 `not_activated` |
| POST | `auth/activate` | `{cardnumber, phone, dob}` | `{ticket, otp_required, phone_hint}` |
| POST | `auth/activate/verify` | `{ticket, code?, password, device}` | `{token, member}` |
| POST | `auth/logout` | | `{ok}` |

## Public
| GET | `home` | new arrivals, popular, events, news, stats, open_now |
|---|---|---|
| GET | `library` | name, address, phone, hours, fees, about/rules HTML, gallery |
| GET | `catalog?q=&field=all\|title\|author\|subject\|isbn&lang=ur,en&avail=1&sort=&page=` | `{total, results[]}` |
| GET | `books/{id}` | full record + copies + `me` (reserved / in list) |
| GET | `events?type=event\|news\|past`, `events/{slug}` | |
| POST | `events/{slug}/register` | `{name, phone, seats}` → `{reg_no}` |
| POST | `suggestions` | `{title, author, isbn, language, reason}` |
| POST | `feedback` | `{type, name, phone, email, message}` → `{ref_no}` |

## Member
| GET | `me` | member + counts (loans, due_soon, holds, ready, unread) |
|---|---|---|
| POST | `me` | `{lang, prefs:{due,holds,events,push,sms,whatsapp}, current_password, new_password}` |
| POST | `me/push-token` | `{token}` (Firebase) |
| GET | `loans` · POST `loans/{id}/renew` · POST `loans/renew-all` | renew is queued, then confirmed by the Sync Agent |
| GET | `holds` · POST `books/{id}/reserve` · POST `holds/{id}/cancel` | |
| GET | `history`, `list` · POST `books/{id}/list` (toggle) | |
| GET | `notifications` · POST `notifications/read` | |

Errors: `{error: code, message}` with HTTP 400/401/403/404/429.

## Sync API (agent only)
`/api/sync/*` with header `X-Agent-Key`. Endpoints: `hello`, `biblios`, `items`, `patrons`, `circulation`, `prune`, `finish`, `actions` (GET), `actions/{id}` (POST result).
