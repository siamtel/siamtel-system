<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model extends Database
{
    protected function db(): ?PDO
    {
        return self::getConnection();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::executeQuery($sql, $params);
        $result = $stmt ? $stmt->fetch() : false;
        return $result !== false ? $result : null;
    }

    protected function execute(string $sql, array $params = []): int
    {
        $stmt = self::executeQuery($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }

    protected function lastInsertId(): ?string
    {
        $db = self::getConnection();
        return $db ? $db->lastInsertId() : null;
    }
}

