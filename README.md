# SUNQ Fusion Solar API

PHP backend proxy for Huawei FusionSolar NB API with simple frontend.

## Quick start

```bash
cp .env.example .env
# edit .env with real credentials (do not commit)
php -S localhost:8080 -t app app/api/index.php
```

Visit `http://localhost:8080/public/` in your browser.

## Required environment variables

- `FS_BASE` – FusionSolar NB base URL
- `FS_USER` – login user (from FusionSolar)
- `FS_CODE` – login system code
- `MA_PROXY` – HTTP proxy (e.g. `http://154.70.204.15:3128`)
- `CACHE_TTL_SECONDS` – cache time for NB responses
- `ALLOWED_ORIGIN` – optional exact origin for CORS; leave blank for same-origin
- `APP_VERSION` – version string exposed by `/healthz`

Credentials are loaded from environment at runtime and are not stored in this repo. Rotate them by updating your environment and restarting the service.

## Example requests

```bash
curl -H 'X-Request-Id: demo' http://localhost:8080/api/stations
curl http://localhost:8080/api/stations/STATION_CODE/overview
curl http://localhost:8080/api/stations/STATION_CODE/devices
curl http://localhost:8080/api/stations/STATION_CODE/alarms?severity=major
curl http://localhost:8080/api/healthz
```

## Troubleshooting

- To confirm the proxy is used, run `php app/tools/check_proxy.php`.
- Upstream failures return JSON:
  ```json
  {"error":{"code":"UPSTREAM_ERROR","status":502,"message":"...","req_id":"..."}}
  ```
- `/healthz` never contacts FusionSolar and reports whether the proxy is reachable.
