<?php
declare(strict_types=1);

/** Global helpers. One so far: dd() for debugging. */

if (!function_exists('dd')) {
    /** Dump and die. Local work only, a dump can contain config values. */
    function dd(mixed ...$vars): never
    {
        http_response_code(500);

        echo <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>Dump</title>
            </head>
            <body>
            HTML;

        foreach ($vars as $var) {
            echo '<pre>';
            highlight_string("<?php\n" . var_export($var, true));
            echo '</pre>';
        }

        echo '</body></html>';

        exit;
    }
}
