<?php
declare(strict_types=1);

namespace Engine;

/**
 * Entity: one table row as an object.
 *
 * No ORM and no reflection - the mapping is written by hand in two methods.
 * That makes it obvious where every value comes from, and PHP and the IDE keep
 * types in check: $item->title instead of $item['title'], so a typo shows up
 * right away instead of in production.
 */
interface Entity
{
    /** A database row (the associative array from PDO) into an object. */
    public static function fromRow(array $row): static;

    /**
     * The object as columns to write. Without id and without columns the
     * database fills in itself, such as created_at.
     *
     * @return array<string, string|int|float|bool|null>
     */
    public function toRow(): array;

    /** null = the entity is not in the database yet. */
    public function getId(): ?int;

    /** A copy carrying the given id - the repository calls this after an insert. */
    public function withId(int $id): static;
}
