# Changelog

## Unreleased
- handle CORS preflight, deny /storage access and support XSRF token from cookie
- replace PHP utilities with env-based configuration and structured logging
- add caching layer and robust FusionSolar client with proxy, retries and cookie management
- expose clean REST endpoints via `/api` front controller
- improve health check and CORS handling
- refresh frontend to use new API paths and show empty states
- add `.env.example` and update documentation
- expose request id header, add optional file cache and per-IP rate limiting
- align frontend with `{ok,data}` API shape and support alarm severity filtering
