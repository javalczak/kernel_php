<?php
declare(strict_types=1);

/** Router for the built-in server (php -S localhost:8000 router.php). Not uploaded. */

// Without this list the server handed out every file in the project,
// config/database.php included.
const PUBLIC_DIRS = ['/assets/', '/tools/'];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$isPublic = false;

// ".." is out: /assets/../config/database.php would pass the prefix check.
if (!str_contains($path, '..')) {
    foreach (PUBLIC_DIRS as $dir) {
        if (str_starts_with($path, $dir)) {
            $isPublic = true;
            break;
        }
    }
}

// is_file, not file_exists: a directory is not a file to send back.
if ($isPublic && is_file(__DIR__ . $path)) {
    return false; // false = let the server serve it
}

require __DIR__ . '/index.php';
