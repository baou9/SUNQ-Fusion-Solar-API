<?php
return [
    // FusionSolar base URL
    'FS_BASE' => 'https://intl.fusionsolar.huawei.com',
    // FusionSolar credentials - set these on the server, not in source control
    'FS_USER' => getenv('FS_USER') ?: 'YOUR_USER',
    'FS_CODE' => getenv('FS_CODE') ?: 'YOUR_CODE',
    // Proxy for all outbound requests
    'MA_PROXY' => 'http://154.70.204.15:3128',
    // Cache time-to-live in seconds
    'CACHE_TTL_SECONDS' => 90,
    // Environment (prod or dev)
    'APP_ENV' => 'prod',
    // CORS: allowed origin
    'ALLOWED_ORIGIN' => 'https://yourdomain.tld',
];
