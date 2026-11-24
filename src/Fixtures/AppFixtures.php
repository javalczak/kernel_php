<?php
declare(strict_types=1);

namespace App\Fixtures;

use App\Entity\Example;
use App\Repository\ExampleRepository;
use Engine\Database;

/** Starting data for development, loaded by tools/reset-db.php. */
final class AppFixtures
{
    public function __construct(
        private Database $db
    ) {}

    public function load(): void
    {
        $repository = new ExampleRepository($this->db);

        $items = [
            Example::create('First item'),
            Example::create('Second item'),
            Example::create('Third item, inactive', isActive: false),
        ];

        foreach ($items as $item) {
            $repository->save($item);
        }
    }
}
