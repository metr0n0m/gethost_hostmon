# GetHost / HostMon

GetHost helps quickly answer two practical questions:

1. What host belongs to this IP?
2. What IP belongs to this host?

On top of that, it provides live site monitoring and request analytics in one dashboard.

## What You Get

- Fast IP/Host lookup
- Monitoring list with periodic rechecks
- Request history with counters
- Multi-language interface (English, Russian, Hebrew)
- Lightweight API for integrations

## Why This Project

This project is built for teams who need a simple, clear, and extendable tool for network checks and uptime visibility without heavy infrastructure.

## Product Pages

- Main: `index.php`
- Monitoring dashboard: `dashboard.php`
- Query history: `history.php`
- About: `about.php`
- Contacts: `contacts.php`

## API

Base format:

- `/api/<module>?search=<value>`

Examples:

- `/api/resolve?search=8.8.8.8`
- `/api/health?search=ping`
- `/api/modules?search=list`
- `/api/settings?search=list`

Full API reference:

- `docs/API.md`

## Database

- Current schema: [DB_SCHEMA_CURRENT.sql](docs/DB_SCHEMA_CURRENT.sql)

## Tech

- PHP
- MySQL / MariaDB
- Bootstrap + jQuery

## Contributing

- Contribution rules: `CONTRIBUTING.md`
- Bug/feature templates: `.github/ISSUE_TEMPLATE`
