<?php
return [
    // FusionSolar base URL
    'FS_BASE' => 'https://intl.fusionsolar.huawei.com',
    // Credentials are loaded from environment variables or filled here on the server.
    'FS_USER' => getenv('FS_USER') ?: '<set-on-server>',
    'FS_CODE' => getenv('FS_CODE') ?: '<set-on-server>',
    // Morocco proxy
    'MA_PROXY' => 'http://154.70.204.15:3128',
    // Cache TTL in seconds
    'CACHE_TTL_SECONDS' => 90,
    // Application environment: prod or dev
    'APP_ENV' => 'prod',
    // Allowed origin for CORS
    'ALLOWED_ORIGIN' => 'https://yourdomain.tld',
];
