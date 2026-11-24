<?php
declare(strict_types=1);

/** Autoloader without Composer: Engine\ maps to engine/, App\ to src/. */

define('BASE_PATH', __DIR__);

require_once __DIR__ . '/engine/Helpers.php';

spl_autoload_register(function (string $class): void {
    $namespaces = [
        'Engine\\' => BASE_PATH . '/engine/',
        'App\\'    => BASE_PATH . '/src/',
    ];

    foreach ($namespaces as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        // Engine\Translation\Translator -> engine/Translation/Translator.php
        $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

        if (is_file($file)) {
            require_once $file;

            return;
        }
    }
});
