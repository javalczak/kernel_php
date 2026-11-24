<?php
declare(strict_types=1);

/** Front controller. Every URL lands here: .htaccess in production, router.php locally. */

require_once __DIR__ . '/autoload.php';

use Engine\Environment;
use Engine\ErrorHandler;
use Engine\Kernel;

// First, so nothing below can reach the visitor unfiltered.
$environment = Environment::create($_SERVER);

(new ErrorHandler($environment))->register();

if (session_status() === PHP_SESSION_NONE) {
    // 96 minutes: the PHP default of 24 can expire while a form is being filled,
    // and an expired session means an invalid CSRF token.
    $lifetime = 1440 * 4;
    ini_set('session.gc_maxlifetime', (string) $lifetime);

    // Only accept session ids this server issued, and only from the cookie.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        // Environment also reads the proxy headers, so this holds on hosting
        // that terminates HTTPS in front of the web server.
        'secure'   => $environment->isSecure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Lets the inline <style> and <script> in the templates run under a policy
// that blocks every other inline block.
define('CSP_NONCE', base64_encode(random_bytes(16)));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// Browser features this app never uses.
header('Permissions-Policy: accelerometer=(), camera=(), display-capture=(), geolocation=(), '
    . 'gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');

header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; "
    . "frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; "
    . "style-src 'self' 'nonce-" . CSP_NONCE . "'; script-src 'self' 'nonce-" . CSP_NONCE . "'");

// Opt-in through config/app.php: a browser remembers HSTS for a year.
if ($environment->sendsHsts()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$kernel = new Kernel($environment);
$kernel->boot();

$kernel->handle($kernel->createRequest())->send();
