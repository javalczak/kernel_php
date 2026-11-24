<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Example;
use App\Repository\ExampleRepository;
use Engine\Database;

/**
 * Domain rules. The controller knows HTTP, the repository knows SQL, the
 * service knows the rules, so they hold the same from a controller or a CLI script.
 */
final class ExampleService
{
    private const MAX_TITLE_LENGTH = 255;

    public function __construct(
        private ExampleRepository $repository,
        private Database $db,
    ) {}

    /** @throws \InvalidArgumentException on an empty, too long or duplicate title */
    public function create(string $title): Example
    {
        $title = $this->cleanTitle($title);

        if ($this->repository->findOneBy(['title' => $title]) !== null) {
            throw new \InvalidArgumentException('That title already exists.');
        }

        return $this->repository->save(Example::create($title));
    }

    /**
     * All items or none: an exception inside the loop rolls back the rest.
     *
     * @param list<string> $titles
     * @return list<Example>
     */
    public function createMany(array $titles): array
    {
        return $this->db->transaction(function () use ($titles): array {
            $saved = [];

            foreach ($titles as $title) {
                $saved[] = $this->create($title);
            }

            return $saved;
        });
    }

    private function cleanTitle(string $title): string
    {
        $title = trim($title);

        if ($title === '') {
            throw new \InvalidArgumentException('Title is required.');
        }

        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new \InvalidArgumentException('Title is too long (max ' . self::MAX_TITLE_LENGTH . ' characters).');
        }

        return $title;
    }
}
