<?php
declare(strict_types=1);

namespace Engine;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;%scharset=%s',
            $config['host'],
            !empty($config['dbname']) ? 'dbname=' . $config['dbname'] . ';' : '',
            $config['charset'] ?? 'utf8mb4'
        );

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Real prepared statements: a value never becomes part of the SQL.
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // The PDO message holds the login and database name, so keep it in
            // the previous exception: log only, never the response.
            throw new \RuntimeException('Database connection failed.', 0, $e);
        }
    }

    // -------------------------------------------------------------------------
    // Basic operations
    // -------------------------------------------------------------------------

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();

        return $result ?: null; // PDO returns false, we return null
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // Insert / Update / Delete
    // -------------------------------------------------------------------------

    public function insert(string $table, array $data): int
    {
        $columns      = implode(', ', array_map($this->identifier(...), array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->query(
            'INSERT INTO ' . $this->identifier($table) . " ($columns) VALUES ($placeholders)",
            array_values($data)
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        if ($where === []) {
            throw new \InvalidArgumentException('UPDATE without WHERE would change the whole table.');
        }

        $set = implode(', ', array_map(fn (string $col): string => $this->identifier($col) . ' = ?', array_keys($data)));

        return $this->query(
            'UPDATE ' . $this->identifier($table) . " SET $set WHERE " . $this->whereClause($where),
            [...array_values($data), ...array_values($where)]
        )->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        if ($where === []) {
            throw new \InvalidArgumentException('DELETE without WHERE would empty the whole table.');
        }

        return $this->query(
            'DELETE FROM ' . $this->identifier($table) . ' WHERE ' . $this->whereClause($where),
            array_values($where)
        )->rowCount();
    }

    // -------------------------------------------------------------------------
    // Transactions
    // -------------------------------------------------------------------------

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /** All or nothing: an exception inside rolls back and keeps going up. */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollback();

            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function exists(string $table, array $where): bool
    {
        return $this->count($table, $where) > 0;
    }

    public function count(string $table, array $where = []): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->identifier($table);

        if ($where !== []) {
            $sql .= ' WHERE ' . $this->whereClause($where);
        }

        return (int) $this->fetchColumn($sql, array_values($where));
    }

    /** Table and column names cannot be bound, so: whitelist plus backticks. */
    public function identifier(string $name): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new \InvalidArgumentException("Illegal name in SQL: {$name}");
        }

        return '`' . $name . '`';
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /** Conditions joined with AND, values always as ? */
    private function whereClause(array $where): string
    {
        return implode(' AND ', array_map(
            fn (string $col): string => $this->identifier($col) . ' = ?',
            array_keys($where)
        ));
    }
}
