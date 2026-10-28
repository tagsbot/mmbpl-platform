# MMBPL Digital Platform

Website, mobile-app API, master admin panel and Koha sync for **Mian Muhammad Bakhsh Public Library, Mirpur (AJK)**.

Koha stays the master system. The website database (MySQL on cPanel) is a mirror that the **Sync Agent** keeps up to date, and every platform (website, mobile app, desktop) works from it.

```
 Koha (library PC) ──► Sync Agent (library PC) ──HTTPS──► Website + API + Admin (cPanel, PHP/MySQL)
        ▲                       │                                   ▲            ▲
        └── renew / reserve / new member requests ◄─────────────────┘      Mobile app / Desktop
```

## Folders
| Folder | What it is |
|---|---|
| `web/` | PHP 8.1+ app: public website (English + Urdu RTL), member account, master admin panel, REST API `/api/v1` for apps, sync API `/api/sync` for the agent, web installer |
| `sync-agent/` | Node.js agent (built into a Windows .exe) that talks to Koha via saved SQL reports + REST API |
| `docs/` | Install guides (cPanel, Koha + agent) and API reference |

## Quick start
- Website: see [`docs/01-cPanel-Website-Install.md`](docs/01-cPanel-Website-Install.md)
- Koha + agent: see [`docs/02-Koha-Sync-Agent-Setup.md`](docs/02-Koha-Sync-Agent-Setup.md)
- App API: see [`docs/03-API.md`](docs/03-API.md)

## Build
```bash
# Windows agent executable
cd sync-agent && npx @yao-pkg/pkg agent.js --targets node22-win-x64 --output dist/MMBPL-Sync-Agent.exe
# Website zip
./build.sh
```

## Roadmap
1. ✅ Website + admin panel + API + Koha Sync Agent
2. ✅ Mobile app (Android, Capacitor) in `mobile/`; APK built by GitHub Actions (`apk-builds` branch / `app-latest` release)
3. ⏳ Desk Manager desktop app (issue / return / fees / visitors / printing)
