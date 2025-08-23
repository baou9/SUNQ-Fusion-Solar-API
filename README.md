# SUNQ Fusion Solar API

PHP backend proxy for Huawei FusionSolar NB API with session handling, caching and a Morocco proxy.

## Quick start

```bash
docker build -t fusion-solar-api .
docker run -p 8096:8096 \
  -e FS_BASE=https://intl.fusionsolar.huawei.com \
  -e FS_USER=your_user \
  -e FS_CODE=your_code \
  -e MA_PROXY=http://154.70.204.15:3128 \
  fusion-solar-api
```

For local development without Docker:

```bash
export FS_BASE=https://intl.fusionsolar.huawei.com
export FS_USER=your_user
export FS_CODE=your_code
export MA_PROXY=http://154.70.204.15:3128
composer install
php -S localhost:8096 -t app app/api/index.php
```

## Public endpoints

* `GET /api/stations?page=1`
* `GET /api/stations/:code/overview`
* `GET /api/stations/:code/devices`
* `GET /api/stations/:code/alarms?levels=1,2,3,4`
* `GET /api/healthz`

## Required environment variables

- `FS_BASE` – FusionSolar NB base URL
- `FS_USER` – login user (from FusionSolar)
- `FS_CODE` – login system code
- `MA_PROXY` – HTTP proxy (e.g. `http://154.70.204.15:3128`)
- `CACHE_TTL_SECONDS` – cache time for NB responses
- `CACHE_BACKEND` – `file` (default, stored in `app/storage/cache`, TTL from `CACHE_TTL_SECONDS`) or `memory`
- `FRONTEND_ORIGIN` – exact origin allowed by CORS
- `APP_VERSION` – version string exposed by `/healthz`
- `RATE_LIMIT_PER_MINUTE` – requests per minute per IP for `/api/*` (0 disables)

Credentials are loaded from environment at runtime and are not stored in this repo.

All outbound HTTP requests go through `MA_PROXY`. The backend logs in to FusionSolar once, captures the `xsrf-token` (or `XSRF-TOKEN` cookie) and sends `XSRF-TOKEN` on every upstream call.

## Example requests

```bash
curl http://localhost:8096/api/stations?page=1
curl http://localhost:8096/api/stations/STATION_CODE/overview
curl http://localhost:8096/api/stations/STATION_CODE/devices
curl http://localhost:8096/api/stations/STATION_CODE/alarms?levels=1,2
curl http://localhost:8096/api/healthz
```

## Tests

A simple smoke script is available in `tests/smoke.sh` to exercise `/api/stations` and `/api/healthz` when the server is running.
