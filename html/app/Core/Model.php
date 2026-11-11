<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Clase Abstracta Model
 *
 * Clase base para todos los Modelos del sistema (App\Models\...).
 * Hereda de Database para reutilizar la conexión y proporciona métodos CRUD comunes.
 */
abstract class Model extends Database
{
    /**
     * Retorna la conexión activa directamente desde la clase abstracta Database.
     */
    protected function db(): ?PDO
    {
        return self::getConnection();
    }

    /**
     * Ejecuta una consulta SELECT y retorna todos los resultados como arreglo asociativo.
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Ejecuta una consulta SELECT y retorna una única fila o null si no se encontró.
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::executeQuery($sql, $params);
        $result = $stmt ? $stmt->fetch() : false;
        return $result !== false ? $result : null;
    }

    /**
     * Ejecuta una sentencia INSERT, UPDATE o DELETE y retorna el número de filas afectadas.
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = self::executeQuery($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Retorna el último ID autonumérico insertado.
     */
    protected function lastInsertId(): ?string
    {
        $db = self::getConnection();
        return $db ? $db->lastInsertId() : null;
    }
}

