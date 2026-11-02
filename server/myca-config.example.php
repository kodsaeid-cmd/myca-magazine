<?php
declare(strict_types=1);
// Copy manually to: /home/mycabl/myca-magazine-config.php (OUTSIDE public_html).
// NEVER commit real database credentials or setup tokens.
return [
    'db_host' => 'localhost',
    'db_name' => 'mycabl_REPLACE_WITH_DATABASE',
    'db_user' => 'mycabl_REPLACE_WITH_USER',
    'db_password' => 'REPLACE_WITH_STRONG_DATABASE_PASSWORD',
    'setup_token' => 'REPLACE_WITH_LONG_RANDOM_ONETIME_TOKEN',
    // Set this to the exact HTTPS host you are deploying to; use staging first.
    'site_url' => 'https://staging.mycablog.dandoonii.ir',
    'session_secret' => 'REPLACE_WITH_ANOTHER_RANDOM_64_CHAR_VALUE',
];
