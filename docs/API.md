# API

## Base URL formats

- Stable (current production-safe format): `/api/?module=<module>&search=<value>`
- Short format (optional): `/api/<module>?search=<value>` only if URL rewrite is configured on the server

If `module` is omitted, default module is `resolve`:

- `/api/?search=8.8.8.8`

## Common rules

- All modules accept request input via `search`
- Default response format: `text/plain; charset=utf-8`
- JSON response: add `format=json` or send `Accept: application/json`

## Common JSON contract

```json
{
  "status": "ok|error",
  "code": "MACHINE_CODE",
  "message": "Human readable message",
  "data": {}
}
```

## Quick start (working examples)

1. Health check:

`GET /api/?module=health&search=ping`

2. Resolve host/IP:

`GET /api/?module=resolve&search=8.8.8.8`

3. Resolve without explicit module (default `resolve`):

`GET /api/?search=example.com`

4. Get JSON:

`GET /api/?module=resolve&search=example.com&format=json`

## Modules

### 1) resolve

Purpose:

- Detect query type and resolve:
  - `IP -> Host`
  - `Host -> IP`

Request examples:

- `/api/?module=resolve&search=8.8.8.8`
- `/api/?module=resolve&search=example.com`

Success JSON example:

```json
{
  "status": "ok",
  "code": "RESOLVE_OK",
  "message": "IP resolved",
  "data": {
    "query": "example.com",
    "type": "host_to_ip",
    "protocol": "http + https",
    "host": "example.com",
    "ips": ["93.184.216.34"],
    "summary": "IP: 93.184.216.34"
  }
}
```

Codes:

- `RESOLVE_OK`
- `VALIDATION_ERROR`
- `RESOLVE_FAILED`

### 2) health

Purpose:

- Runtime liveness check

Request:

- `/api/?module=health&search=ping`

Code:

- `HEALTH_OK`

### 3) modules

Purpose:

- List/enable/disable API modules

Commands:

- `search=list`
- `search=enable:<module>`
- `search=disable:<module>`

Examples:

- `/api/?module=modules&search=list`
- `/api/?module=modules&search=enable:resolve`
- `/api/?module=modules&search=disable:settings`

Codes:

- `MODULES_LIST`
- `MODULE_ENABLED`
- `MODULE_DISABLED`
- `MODULE_ENABLE_FAILED`
- `MODULE_DISABLE_FAILED`
- `MODULES_INVALID_COMMAND`

### 4) settings

Purpose:

- Runtime settings management (`app_settings` table)

Commands:

- `search=list`
- `search=get:<key>`
- `search=set:<key>=<value>`

Examples:

- `/api/?module=settings&search=list`
- `/api/?module=settings&search=get:about_text`
- `/api/?module=settings&search=set:contact_email=support@example.com`

Codes:

- `SETTINGS_LIST`
- `SETTINGS_GET`
- `SETTINGS_SET`
- `SETTINGS_KEY_REQUIRED`
- `SETTINGS_INVALID_KEY`
- `SETTINGS_INVALID_SET_FORMAT`
- `SETTINGS_INVALID_COMMAND`
- `SETTINGS_TABLE_NOT_CONFIGURED`
- `SETTINGS_KEY_NOT_FOUND`

## Add a new module

1. Create class in `src/Modules/<Group>/<ModuleName>.php`
2. Implement `GetHost\Core\Contracts\ApiModuleInterface`
3. Register module in `config/modules.php`
4. Call module as `/api/?module=<module-key>&search=...`

Minimal template:

```php
<?php
declare(strict_types=1);

namespace GetHost\Modules\Custom;

use GetHost\Core\Contracts\ApiModuleInterface;
use GetHost\Core\Http\ApiRequest;
use GetHost\Core\Http\ApiResponse;

final class BrowserStatsModule implements ApiModuleInterface
{
    public function name(): string
    {
        return 'browser-stats';
    }

    public function handle(ApiRequest $request): ApiResponse
    {
        $search = trim($request->search());
        if ($search === '') {
            return new ApiResponse(422, 'error', 'BROWSER_SEARCH_REQUIRED', "Use search=<value>");
        }

        return new ApiResponse(200, 'ok', 'BROWSER_STATS_OK', 'Done', ['search' => $search]);
    }
}
```
