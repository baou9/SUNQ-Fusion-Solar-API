# SUNQ Fusion Solar Dashboard

This monorepo contains a minimal full-stack example for the SUNQ FusionSolar dashboard.

## Quick start

```bash
npm install --prefix backend
npm test --prefix backend
```

## Project structure

- `backend/` – Express server that proxies Huawei FusionSolar NB API.
- `frontend/` – Next.js frontend that consumes the backend.
- `.env.example` – environment variables.
- `docker-compose.yml` – run backend and frontend together.

## Frontend API base

The frontend builds all API requests using the helper in `frontend/lib/api.js`, which
prefixes paths with `NEXT_PUBLIC_API_BASE`. Set this environment variable to the
backend's base path (e.g., `/api`).

## Proxy

All outbound FusionSolar traffic is routed through the proxy defined in `MA_PROXY`.

## Authentication and retries

The backend logs in to FusionSolar using credentials stored in env vars and retries once if the session expires.

## Green metrics

Carbon avoided calculations use the configurable `CO2_FACTOR_KG_PER_KWH` (default 0.6 kg/kWh).

## Credential rotation

1. Update `FS_USER` and `FS_CODE` in the deployment environment.
2. Restart backend service to pick up new credentials.

## Testing

Integration tests mock the FusionSolar endpoints.
