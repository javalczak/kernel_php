<?php
declare(strict_types=1);

/**
 * Application settings. Example values - adjust them after deployment.
 */
return [
    // 'dev' keeps error details on screen, 'prod' hides them and logs instead.
    // null lets the kernel decide: the built-in server and localhost are 'dev'.
    'env' => null,

    // Absolute path to a directory no visitor can reach. The kernel reads
    // database.php from there when the file exists and writes its log there.
    // null lets the kernel pick: ../kernel-private when it exists, otherwise var/.
    'private_dir' => null,

    // Trust X-Forwarded-Proto and friends when deciding whether the visitor is
    // on HTTPS. Needed on hosting that terminates HTTPS on a proxy or CDN.
    'trusted_proxy' => true,

    // Send Strict-Transport-Security. Turn this on only once the site is
    // served over HTTPS for good, because browsers remember it for a year.
    'hsts' => false,
];
