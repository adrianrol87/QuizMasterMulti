<?php

final class Database
{
    private mysqli $connection;

    public function __construct(array $config)
    {
        $this->connection = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name']
        );

        if ($this->connection->connect_errno) {
            throw new RuntimeException('Database connection failed: ' . $this->connection->connect_error);
        }

        $this->connection->set_charset('utf8mb4');
    }

    public function connection(): mysqli
    {
        return $this->connection;
    }

    public function fetchAll(string $sql): array
    {
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException('Query failed: ' . $this->connection->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchOne(string $sql): ?array
    {
        $rows = $this->fetchAll($sql);
        return $rows[0] ?? null;
    }

    public function execute(string $sql): bool
    {
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException('Query failed: ' . $this->connection->error);
        }
        return true;
    }

    public function escape(string $value): string
    {
        return $this->connection->real_escape_string($value);
    }

    public function lastInsertId(): int
    {
        return (int) $this->connection->insert_id;
    }
}
