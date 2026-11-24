<?php
declare(strict_types=1);

namespace App\Entity;

use Engine\Entity;

/**
 * Example entity: one row of `examples`.
 * Properties are readonly, a change means a new object (withTitle, withId).
 */
final class Example implements Entity
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $title,
        public readonly bool $isActive = true,
        public readonly ?string $createdAt = null,
    ) {}

    /** New entity, no id yet: the database assigns it on save. */
    public static function create(string $title, bool $isActive = true): self
    {
        return new self(null, $title, $isActive);
    }

    public static function fromRow(array $row): static
    {
        return new self(
            (int) $row['id'],
            (string) $row['title'],
            // MySQL returns TINYINT(1) as "0"/"1", so the cast is needed
            (bool) $row['is_active'],
            $row['created_at'] ?? null,
        );
    }

    public function toRow(): array
    {
        // No id (the database sets it) and no created_at (DEFAULT CURRENT_TIMESTAMP)
        return [
            'title'     => $this->title,
            'is_active' => (int) $this->isActive,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function withId(int $id): static
    {
        return new self($id, $this->title, $this->isActive, $this->createdAt);
    }

    public function withTitle(string $title): self
    {
        return new self($this->id, $title, $this->isActive, $this->createdAt);
    }

    public function deactivated(): self
    {
        return new self($this->id, $this->title, false, $this->createdAt);
    }
}
