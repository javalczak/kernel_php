<?php
declare(strict_types=1);

namespace Engine;

/**
 * Repository base: one table, one entity.
 *
 * A concrete repository gives the table name and the entity class and gets
 * findAll, findById, save and delete for free. Domain queries go in the child.
 *
 * @template T of Entity
 */
abstract class Repository
{
    public function __construct(
        protected Database $db
    ) {}

    abstract protected function table(): string;

    /** @return class-string<T> */
    abstract protected function entityClass(): string;

    /** @return list<T> */
    public function findAll(string $orderBy = 'id DESC'): array
    {
        return $this->hydrateAll(
            $this->db->fetchAll('SELECT * FROM ' . $this->db->identifier($this->table()) . $this->orderBy($orderBy))
        );
    }

    /** @return T|null */
    public function findById(int $id): ?Entity
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM ' . $this->db->identifier($this->table()) . ' WHERE id = ?',
            [$id]
        );

        return $row === null ? null : $this->hydrate($row);
    }

    /**
     * @param array<string, scalar|null> $criteria column => value, joined with AND
     * @return list<T>
     */
    public function findBy(array $criteria, string $orderBy = 'id DESC'): array
    {
        if ($criteria === []) {
            return $this->findAll($orderBy);
        }

        $conditions = [];

        foreach (array_keys($criteria) as $column) {
            $conditions[] = $this->db->identifier($column) . ' = ?';
        }

        return $this->hydrateAll($this->db->fetchAll(
            'SELECT * FROM ' . $this->db->identifier($this->table())
            . ' WHERE ' . implode(' AND ', $conditions) . $this->orderBy($orderBy),
            array_values($criteria)
        ));
    }

    /**
     * @param array<string, scalar|null> $criteria
     * @return T|null
     */
    public function findOneBy(array $criteria): ?Entity
    {
        return $this->findBy($criteria)[0] ?? null;
    }

    /** @param array<string, scalar|null> $criteria */
    public function count(array $criteria = []): int
    {
        return $criteria === []
            ? (int) $this->db->fetchColumn('SELECT COUNT(*) FROM ' . $this->db->identifier($this->table()))
            : $this->db->count($this->table(), $criteria);
    }

    /**
     * Insert or update, decided by the entity id. After an insert it returns a
     * copy carrying the new id.
     *
     * @param T $entity
     * @return T
     */
    public function save(Entity $entity): Entity
    {
        $id = $entity->getId();

        if ($id === null) {
            return $entity->withId($this->db->insert($this->table(), $entity->toRow()));
        }

        $this->db->update($this->table(), $entity->toRow(), ['id' => $id]);

        return $entity;
    }

    /** @param int|T $entity an id or the entity itself */
    public function delete(int|Entity $entity): void
    {
        $id = $entity instanceof Entity ? $entity->getId() : $entity;

        if ($id === null) {
            throw new \InvalidArgumentException('Cannot delete an entity without an id.');
        }

        $this->db->delete($this->table(), ['id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     * @return T
     */
    protected function hydrate(array $row): Entity
    {
        return ($this->entityClass())::fromRow($row);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<T>
     */
    protected function hydrateAll(array $rows): array
    {
        return array_map($this->hydrate(...), $rows);
    }

    /** ORDER BY cannot be bound, so only "column ASC|DESC" lists are accepted. */
    private function orderBy(string $orderBy): string
    {
        $orderBy = trim($orderBy);

        if ($orderBy === '') {
            return '';
        }

        $parts = [];

        foreach (explode(',', $orderBy) as $part) {
            $words     = preg_split('/\s+/', trim($part)) ?: [];
            $column    = $this->db->identifier($words[0] ?? '');
            $direction = strtoupper($words[1] ?? 'ASC');

            if (count($words) > 2 || !in_array($direction, ['ASC', 'DESC'], true)) {
                throw new \InvalidArgumentException("Invalid ORDER BY: {$orderBy}");
            }

            $parts[] = $column . ' ' . $direction;
        }

        return ' ORDER BY ' . implode(', ', $parts);
    }
}
