# GetHost / HostMon

Production-oriented PHP web application for:
- Resolving `IP -> Host` and `Host -> IP`
- Tracking query history and counters
- Monitoring hosts over `http` / `https` with periodic health checks
- Multi-language UI (English, Russian, Hebrew) with session persistence

## Core Features

### 1. Resolver
- Automatically detects whether input is IP, host, or invalid value
- Resolves:
  - `IP -> Host`
  - `Host -> IP`
- For host input, protocol probe is shown in response:
  - `http`
  - `https`
  - `http + https`
  - `No response on http/https`

### 2. Monitoring
- Add host/URL to monitoring from resolver and history
- Actions per monitored site:
  - Activate / Deactivate
  - Delete
- Health checks store:
  - Status
  - HTTP code
  - Response time
  - Last check time
- If a site returns HTTP error code (4xx/5xx), it is still considered reachable and marked as active with HTTP error badge

### 3. Query History
- Stores query events and aggregates counters
- Includes technical request metadata:
  - Client IP
  - OS / Browser
  - Proxy / Tor flags
  - Source host
- Top queries block shows most frequent requests

### 4. Session-based Refresh Interval
- Refresh interval priority:
  1. Session value (`$_SESSION['recheck_seconds']`)
  2. Config default (`$DEFAULT_RECHECK_SECONDS`)
- Same interval behavior is applied for `index` and `dashboard`

### 5. Multi-language
- Language selection via UI flags
- Session lifetime: 3 days
- Supported languages:
  - `eng`
  - `rus`
  - `heb`

---

## Tech Stack
- PHP (procedural/service-based architecture)
- MySQL / MariaDB
- jQuery + Bootstrap 5
- Font Awesome

No Composer required for current runtime.

---

## Project Structure

```text
app/
  bootstrap.php
  services/
    LangService.php
    MonitoringService.php
    QueryHistoryService.php
    ResolverService.php
    SettingsService.php
assets/
  css/custom.css
  js/
    app_index.js
    app_dashboard.js
    app_history.js
lang/
  eng.php
  rus.php
  heb.php
ajax_*.php
index.php
dashboard.php
history.php
config.php
```

---

## Requirements
- PHP 8.0+ (recommended 8.1+)
- MySQL 5.7+ / MariaDB
- Web server (Apache/Nginx/IIS) with PHP enabled
- DNS/network access for host checks

---

## Configuration

Main config file: `config.php`

Important parameters:
- `$DEFAULT_LANG = 'eng';`
- `$DEFAULT_RECHECK_SECONDS = 30;`
- `$APP_ABOUT_TEXT`
- `$APP_CONTACTS` (`email`, `telegram`, `github`)

Environment variables are expected in `.env` (do not commit secrets).

---

## Database Setup

Use provided SQL templates:
- `db_changes.txt`

`db_changes.txt` now includes optional runtime tables:
- `modules` (enable/disable modules without code changes)
- `app_settings` (override about/contact values from DB)

Apply SQL using your DB tool (phpMyAdmin, CLI, etc.).

At minimum, monitoring and history tables must exist before runtime.

---

## Local Run

1. Place project in web root
2. Configure virtual host/domain (example: `gethost.loc`)
3. Create database and apply schema
4. Set DB credentials in `.env`
5. Open:
   - `/index.php`
   - `/dashboard.php`
   - `/history.php`

---

## Main Endpoints

- `ajax_resolve.php` - resolve IP/host
- `ajax_add_site.php` - add host/url to monitoring
- `ajax_get_sites.php` - fetch monitoring list
- `ajax_recheck_sites.php` - recheck monitored sites
- `ajax_site_action.php` - activate/deactivate/delete monitored site
- `ajax_update_refresh.php` - save refresh interval in session
- `ajax_get_refresh.php` - read current effective refresh interval
- `ajax_get_query_history.php` - fetch query history and top counters

## Modular API (Phase 1)

Base route:
- `/api/`

Rules:
- Unified request parameter across modules: `search`
- Plain text response (`text/plain`) by design
- Optional JSON contract (`format=json` or `Accept: application/json`)

Response contract:
- `status` (`ok|error`)
- `code` (stable machine code)
- `message` (human-readable)
- `data` (module payload)

Examples:
- `/api/resolve?search=8.8.8.8`
- `/api/resolve?search=example.com`
- `/api/health?search=ping`

Module registration:
- `config/modules.php`

Current API modules:
- `resolve`
- `health`
- `modules`
- `settings`

Admin-ready module commands:
- `/api/modules?search=list`
- `/api/modules?search=enable:resolve`
- `/api/modules?search=disable:resolve`

Admin-ready settings commands:
- `/api/settings?search=list`
- `/api/settings?search=get:about_text`
- `/api/settings?search=set:contact_email=support@example.com`

---

## Development Workflow

This repository uses a production-style workflow:
- `main` stays deployable
- Work in feature/fix branches
- Use full PR and issue templates from `.github/`
- Follow `CONTRIBUTING.md`
- CI workflow runs PHP lint + API smoke checks on push/PR

Useful checks:
```powershell
php -l index.php
php -l app\services\MonitoringService.php
```

---

## Security Notes
- Do not commit `.env`
- Validate and sanitize user input
- Keep output escaped in templates/JS renderers
- Review network timeout behavior for external checks

---

## Roadmap (Suggested)
- Composer adoption with PSR-4 autoload
- Automated tests (PHPUnit) for resolver/monitoring logic
- Static analysis (PHPStan)
- Structured logging and error tracing
