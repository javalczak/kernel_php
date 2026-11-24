<?php
declare(strict_types=1);

/**
 * Quick check of the entity and repository layer, without PHPUnit.
 *
 *   php tools/smoke-test.php
 *
 * Runs against the examples table in the configured database and cleans up.
 */

// CLI only. Through a browser this would mess with someone's data.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Run it from the console: php tools/smoke-test.php');
}

require_once __DIR__ . '/../autoload.php';

use App\Entity\Example;
use App\Repository\ExampleRepository;
use App\Service\ExampleService;
use Engine\Database;
use Engine\Environment;

$failed = 0;
$total  = 0;

function check(string $description, bool $condition): void
{
    global $failed, $total;

    $total++;

    if ($condition) {
        echo "  ok   {$description}\n";

        return;
    }

    $failed++;
    echo "  FAIL {$description}\n";
}

function checkThrows(string $description, callable $code): void
{
    try {
        $code();
        check($description, false);
    } catch (\Throwable) {
        check($description, true);
    }
}

$db         = new Database(require Environment::create($_SERVER)->configFile('database.php'));
$repository = new ExampleRepository($db);
$service    = new ExampleService($repository, $db);

$marker = 'SMOKE_' . bin2hex(random_bytes(4));

echo "Entity\n";

$entity = Example::create($marker . '_a');
check('a new entity has no id', $entity->getId() === null);
check('toRow leaves out id', !array_key_exists('id', $entity->toRow()));
check('toRow turns bool into int', $entity->toRow()['is_active'] === 1);

$hydrated = Example::fromRow(['id' => '7', 'title' => 'x', 'is_active' => '0', 'created_at' => '2026-01-01 10:00:00']);
check('fromRow casts id to int', $hydrated->getId() === 7);
check('fromRow casts is_active to bool', $hydrated->isActive === false);

echo "Repository\n";

$saved = $repository->save($entity);
check('save assigns an id', $saved->getId() !== null);
check('the input entity is unchanged', $entity->getId() === null);

$loaded = $repository->findById((int) $saved->getId());
check('findById returns an Example', $loaded instanceof Example);
check('findById returns the same title', $loaded?->title === $marker . '_a');
check('findById returns null for a missing id', $repository->findById(999_999_999) === null);

$updated = $repository->save($saved->withTitle($marker . '_b'));
check('save with an id updates instead of inserting', $updated->getId() === $saved->getId());
check('the update changed the title', $repository->findById((int) $saved->getId())?->title === $marker . '_b');

check('findOneBy finds by column', $repository->findOneBy(['title' => $marker . '_b'])?->getId() === $saved->getId());
check('findActive returns only active rows', array_filter($repository->findActive(), static fn (Example $e): bool => !$e->isActive) === []);
check('searchByTitle finds by fragment', count($repository->searchByTitle($marker)) === 1);
check('count with criteria is correct', $repository->count(['title' => $marker . '_b']) === 1);

echo "Validation (these have to throw)\n";

checkThrows('ORDER BY with injected SQL throws', static fn () => $repository->findAll('id; DROP TABLE examples'));
checkThrows('a column name with a backtick throws', static fn () => $repository->findBy(['ti`tle' => 'x']));
checkThrows('UPDATE without WHERE throws', static fn () => $db->update('examples', ['title' => 'x'], []));
checkThrows('DELETE without WHERE throws', static fn () => $db->delete('examples', []));
checkThrows('an empty title is rejected by the service', static fn () => $service->create('   '));
checkThrows('a duplicate title is rejected by the service', static fn () => $service->create($marker . '_b'));

echo "Transaction\n";

$before = $repository->count();

try {
    // The second title duplicates the first, so the whole batch rolls back
    $service->createMany([$marker . '_c', $marker . '_c']);
} catch (\Throwable) {
    // expected
}

check('a failed transaction leaves nothing behind', $repository->count() === $before);

// -------------------------------------------------------------------------
// Cleanup
// -------------------------------------------------------------------------

foreach ($repository->searchByTitle($marker) as $row) {
    $repository->delete($row);
}

check('no test data is left', $repository->searchByTitle($marker) === []);

echo "\n" . ($failed === 0 ? "All passed ({$total})." : "FAILED: {$failed} of {$total}.") . "\n";

exit($failed === 0 ? 0 : 1);
