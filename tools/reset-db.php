<?php
declare(strict_types=1);

/** Database reset for developers: drop + create + fixtures. Never uploaded to production. */

require_once __DIR__ . '/../autoload.php';

use App\Fixtures\AppFixtures;
use App\Schema\SchemaInstaller;
use Engine\Database;
use Engine\Environment;

// Same resolution as the kernel, so a config outside the web root works here too.
$config = require Environment::create($_SERVER)->configFile('database.php');

// -------------------------------------------------------------------------
// Console: php tools/reset-db.php --force
// -------------------------------------------------------------------------

// No token here: whoever can read the files has everything anyway.
if (PHP_SAPI === 'cli') {
    if (!in_array('--force', $argv ?? [], true)) {
        die("Reset drops every table. Run: php tools/reset-db.php --force\n");
    }

    resetDatabase($config);
    echo "Database reset.\n";
    exit;
}

// -------------------------------------------------------------------------
// Browser: localhost only
// -------------------------------------------------------------------------

// Even with the right token the script will not run from another machine.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    die('The reset only runs locally.');
}

$token = $config['reset_token'] ?? null;

// The token from the repository is public knowledge until you change it.
if (!$token || $token === 'change-me-to-a-long-random-string') {
    http_response_code(403);
    die('Set your own reset_token in config/database.php.');
}

$provided = $_GET['token'] ?? $_POST['token'] ?? null;

// hash_equals, not ===, so the comparison takes the same time either way.
if (!hash_equals($token, (string) $provided)) {
    http_response_code(403);
    die('Unauthorized.');
}

// -------------------------------------------------------------------------
// GET shows the form, POST runs the reset
// -------------------------------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database reset</title>
        <style>
            body { font-family: sans-serif; max-width: 500px; margin: 80px auto; }
            .danger { color: #c00; }
            .confirm { padding: 8px; font-size: 16px; }
            button { background: #c00; color: #fff; padding: 10px 24px; border: none; cursor: pointer; font-size: 16px; }
            button:hover { opacity: 0.85; }
        </style>
    </head>
    <body>
    <h2>Database reset</h2>
    <p class="danger">This drops every table and creates them again. The data is gone.</p>

    <form method="POST" action="/tools/reset-db.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <p>Type <strong>RESET</strong> to confirm:</p>
        <input type="text" name="confirm" placeholder="RESET" class="confirm">
        <br><br>
        <button type="submit">Reset the database</button>
    </form>
    </body>
    </html>
    <?php
    exit;
}

// -------------------------------------------------------------------------
// POST: run the reset
// -------------------------------------------------------------------------

if (trim((string) ($_POST['confirm'] ?? '')) !== 'RESET') {
    http_response_code(422);
    die('Wrong confirmation. Type RESET.');
}

try {
    resetDatabase($config);

    echo '<pre>';
    echo "Database reset.\n";
    echo "Tables dropped, created again and filled with the starting data.\n\n";
    echo '<a href="/">Go to the home page</a>';
    echo '</pre>';
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<pre>Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}

/** Drop, create, fixtures. Shared by the console and the browser path. */
function resetDatabase(array $config): void
{
    // Connect without a database name first, otherwise it cannot be created.
    $configWithoutDatabase = $config;
    $configWithoutDatabase['dbname'] = '';

    $db = new Database($configWithoutDatabase);

    // The name comes from the config, not a request, but it still goes
    // through identifier(): one rule for every name in SQL.
    $db->query('CREATE DATABASE IF NOT EXISTS ' . $db->identifier($config['dbname'])
        . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $db = new Database($config);
    $installer = new SchemaInstaller($db);

    $installer->uninstall();
    $installer->install();

    (new AppFixtures($db))->load();
}
