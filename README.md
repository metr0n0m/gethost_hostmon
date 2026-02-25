# GetHost / HostMon

GetHost is a practical web tool for two daily tasks:

1. Resolve `IP -> Host` and `Host -> IP`
2. Monitor host availability over time

It combines lookup, monitoring, and request analytics in one interface and one API style.

## Why this project

- Fast host/IP diagnostics without external SaaS
- Monitoring with periodic recheck and live status updates
- Query history with counters (what users ask most often)
- Single input contract for UI and API: `search=<value>`
- Simple structure today, modular growth path for future features

## Functional scope

- Resolver:
  - Auto-detects input type (`IP`, `host`, invalid)
  - Returns clear status and protocol info (`http`, `https`, `http + https`, no response)
- Monitoring:
  - Add host to monitoring directly from lookup result
  - Store status, last check, response time, HTTP code
  - Support manual and interval-based checks
- Analytics:
  - Request history with frequency counters
  - Top queries block on main page
- Localization:
  - English, Russian, Hebrew
  - Language persists in session

## Product pages

- `/index.php` - resolver + top queries + monitoring summary
- `/dashboard.php` - monitoring management
- `/history.php` - request history
- `/about.php` - project info
- `/contacts.php` - contacts from app settings/config

## API

Working base format (current IIS setup):

- `/api/?module=<module>&search=<value>`

Default module is `resolve`, so this also works:

- `/api/?search=8.8.8.8`

Quick examples:

- `/api/?module=resolve&search=example.com`
- `/api/?module=health&search=ping`
- `/api/?module=modules&search=list`
- `/api/?module=settings&search=list`

Add `&format=json` (or header `Accept: application/json`) for JSON response.

Full reference: [docs/API.md](docs/API.md)

## Database

Current schema: [docs/DB_SCHEMA_CURRENT.sql](docs/DB_SCHEMA_CURRENT.sql)

## Tech stack

- PHP 8+
- MySQL / MariaDB
- IIS (local dev: `gethost.loc`)
- Vanilla JS + AJAX
- Font Awesome + Bootstrap (UI)

## Project structure

- `api/` - API entrypoint and module routing
- `src/` - core contracts + modules
- `app/` - bootstrap, shared services, config bridge
- `assets/` - frontend JS/CSS
- `docs/` - API and DB documentation

## Development principle

Keep it operationally simple:

- no overengineering
- predictable endpoints
- stable data model
- modular extension when truly needed
