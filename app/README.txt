SUNQ FusionSolar Plant Dashboard
=================================

Quickstart
----------
1. Upload the `app` folder to your shared host (PHP 8.x) via FTP.
2. Ensure `storage/` is writable and set cookies.txt to permission 600:
   `chmod 700 storage && chmod 600 storage/cookies.txt`.
3. Copy `config/.env.php` and fill in `FS_USER` and `FS_CODE` (never commit real secrets).
4. Browse to `public/index.html` to view the dashboard.

Configuration
-------------
- `config/.env.php` holds FusionSolar credentials and app settings.
- `ALLOWED_ORIGIN` should match the domain serving `index.html`.
- Branding images live in `public/assets/` (`client-logo.svg`, `sunq-logo.svg`).
- Adjust brand colors in `public/assets/styles.css` (`--brand`).
- Green metric factors are in `public/index.html` constants.

Endpoints
---------
All API endpoints are under `api/` and return JSON.
Run a quick health check:
```
$ curl https://yourhost/app/api/healthz.php
```
List stations:
```
$ curl https://yourhost/app/api/stations.php?pageNo=1&pageSize=50
```

Manual Test Script
------------------
1. Call `healthz.php` and ensure `{ "ok": true }` and `proxyReachable` is true.
2. Call `stations.php` and verify station data returns.
3. Open `public/index.html` in browser; stations list renders.
4. Click a station; overview, devices, and alarms populate and update every 60s.
5. Toggle light/dark mode and ensure preference persists after refresh.

Changing Branding
-----------------
- Replace `public/assets/client-logo.svg` and `sunq-logo.svg` with real logos.
- Update `clientName` in `public/index.html`.
- Modify `--brand` in CSS for accent color.

Green Metrics
-------------
Factors used (adjust in JS constants in `index.html`):
- `CO2_FACTOR_KG_PER_KWH` default 0.60
- `TREES_PER_TON_CO2` default 45
- `KWH_PER_HOME_PER_DAY` default 30

Support
-------
Optional proxy check:
```
$ php tools/check_proxy.php
```
