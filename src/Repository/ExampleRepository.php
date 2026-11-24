<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Example;
use App\Schema\ExampleSchema;
use Engine\Repository;

/**
 * Repository for `examples`. CRUD comes from Engine\Repository, only domain
 * queries live here.
 *
 * @extends Repository<Example>
 */
final class ExampleRepository extends Repository
{
    protected function table(): string
    {
        return ExampleSchema::TABLE;
    }

    protected function entityClass(): string
    {
        return Example::class;
    }

    /** @return list<Example> */
    public function findActive(): array
    {
        return $this->findBy(['is_active' => 1], 'created_at DESC');
    }

    /**
     * Hand written query, for when findBy is not enough.
     * The value still goes through ?, never string concatenation.
     *
     * @return list<Example>
     */
    public function searchByTitle(string $fragment): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM ' . $this->db->identifier($this->table()) . ' WHERE title LIKE ? ORDER BY title',
            ['%' . $fragment . '%']
        );

        return $this->hydrateAll($rows);
    }
}
