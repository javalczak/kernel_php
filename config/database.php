<?php
declare(strict_types=1);

/** Example values. Replace them after cloning and never commit real ones. */
return [
    'host'     => 'localhost',
    'dbname'   => 'kernel_php',
    'user'     => 'root',
    'password' => 'root',
    'charset'  => 'utf8mb4',

    // Token for running tools/reset-db.php from a browser. Generate your own:
    // php -r "echo bin2hex(random_bytes(24));"
    'reset_token' => 'change-me-to-a-long-random-string',
];
