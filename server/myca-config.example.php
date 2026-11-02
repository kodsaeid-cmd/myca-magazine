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
    // Temporary development host. At final launch change to https://blog.mycaapp.ir.
    // Keep consistent with the host used for CMS login (Origin/CSRF) and canonical URLs.
    'site_url' => 'https://mycablog.dandoonii.ir',
    'session_secret' => 'REPLACE_WITH_ANOTHER_RANDOM_64_CHAR_VALUE',
];
