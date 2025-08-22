SUNQ FusionSolar Dashboard
===========================

Setup
-----
1. Upload the entire `app` folder to your web host via FTP.
2. Edit `app/config/.env.php` and fill in:
   - `FS_USER` and `FS_CODE` with FusionSolar credentials.
   - `FRONTEND_ORIGIN` with your domain.
3. Set permissions:
   - `app/storage` directory writable by the web server.
4. Access the app at `https://yourdomain.tld/app/public/index.html`.

Configuration
-------------
- Logos: replace files in `app/public/assets/` (`client-logo.svg`, `sunq-logo.svg`).
- Brand color: edit `--brand-color` in `app/public/assets/styles.css`.
- Green metrics factors are defined in `app/public/index.html` near the top JS block.

Quick Tests
-----------
Run from your browser or terminal:
- `curl https://yourdomain.tld/app/api/healthz`
- `curl https://yourdomain.tld/app/api/stations`
