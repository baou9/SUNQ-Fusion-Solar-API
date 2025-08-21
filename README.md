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

## Proxy

All outbound FusionSolar traffic is routed through the proxy defined in `MA_PROXY`.

## CORS allowlist

The backend only responds to origins listed in the comma-separated `CORS_ORIGINS` variable. Requests from origins not on this list are rejected with `403 origin_not_allowed`. If `CORS_ORIGINS` is unset, the single value in `FRONTEND_ORIGIN` is used instead.

## Authentication and retries

The backend logs in to FusionSolar using credentials stored in env vars and retries once if the session expires. Network and 5xx errors are retried once with jitter.

## Green metrics

The `/api/stations/:code/overview` endpoint returns:

```json
{
  "currentPower": 0,
  "todayEnergy": 0,
  "totalEnergy": 0,
  "performanceRatio": 0,
  "co2AvoidedKg": 0,
  "treesEquivalent": 0,
  "homesPowered": 0
}
```

Carbon avoided calculations use the configurable `CO2_FACTOR_KG_PER_KWH` (default 0.6 kg/kWh). Trees equivalent and homes powered use `TREE_CO2_KG_PER_YEAR` (21 kg) and `HOME_KWH_PER_DAY` (30 kWh) respectively:

- `co2AvoidedKg = totalEnergy * CO2_FACTOR_KG_PER_KWH`
- `treesEquivalent = co2AvoidedKg / TREE_CO2_KG_PER_YEAR`
- `homesPowered = totalEnergy / HOME_KWH_PER_DAY`

## Credential rotation

1. Update `FS_USER` and `FS_CODE` in the deployment environment.
2. Restart backend service to pick up new credentials.

## Testing

Integration tests mock the FusionSolar endpoints.
