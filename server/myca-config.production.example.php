<?php
declare(strict_types=1);
// PRODUCTION ONLY. Copy OUTSIDE document root to:
// /home/<cpanel-account>/myca-magazine-config-production.php
// Public document root: /home/<cpanel-account>/public_html/blog.mycaapp.ir\n// or /home/<cpanel-account>/blog.mycaapp.ir if hosting allows it.\n// In BOTH cases, this config file remains outside the public document root.
// Do not commit credentials, setup secrets, or database dumps.
return [
    'db_host' => 'localhost',
    'db_name' => 'REPLACE_WITH_PRODUCTION_DB_NAME',
    'db_user' => 'REPLACE_WITH_PRODUCTION_DB_USER',
    'db_password' => 'REPLACE_WITH_PRODUCTION_DB_PASSWORD',
    'site_url' => 'https://blog.mycaapp.ir',
    'session_secret' => 'REPLACE_WITH_RANDOM_SECRET_64_CHARS',
    'setup_token' => 'REPLACE_WITH_RANDOM_ONE_TIME_SETUP_TOKEN_64_CHARS',
];
