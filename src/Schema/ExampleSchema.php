<?php
declare(strict_types=1);

namespace App\Schema;

/** Table definition as PHP, so it ships over FTP with the rest. */
final class ExampleSchema
{
    public const string TABLE = 'examples';

    public static function createTableSql(): string
    {
        return '
            CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title      VARCHAR(255) NOT NULL,
                is_active  TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                -- covers findActive() and the list ordering
                INDEX idx_active_created (is_active, created_at)
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci
        ';
    }

    public static function dropTableSql(): string
    {
        return 'DROP TABLE IF EXISTS ' . self::TABLE;
    }
}
