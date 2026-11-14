<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

abstract class Database
{
   
    protected static ?PDO $connection = null;

    
    public static function getSystemDb(): string
    {
        return getenv('DB_SYSTEM_NAME') ?: (getenv('DB_NAME') ?: 'system');
    }

    
    public static function getSecurityDb(): string
    {
        return getenv('DB_SECURITY_NAME') ?: 'security';
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = getenv('DB_HOST') ?: 'siamtel_db';
            $port = getenv('DB_PORT') ?: '3306';
            $database = self::getSystemDb();
            $username = getenv('DB_USER') ?: 'siamtel_user';
            $password = getenv('DB_PASSWORD') ?: 'user_siamtel_password';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$connection = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                error_log('[Database Error] Fallo de conexión: ' . $e->getMessage());
                throw new RuntimeException("Error al conectar con la base de datos MariaDB: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return self::$connection;
    }

    
    protected function db(): PDO
    {
        return self::getConnection();
    }

   
    public static function closeConnection(): void
    {
        self::$connection = null;
    }

    
  
    public static function beginTransaction(): bool
    {
        $pdo = self::getConnection();
        return !$pdo->inTransaction() ? $pdo->beginTransaction() : true;
    }

   
    public static function commit(): bool
    {
        $pdo = self::getConnection();
        return $pdo->inTransaction() ? $pdo->commit() : true;
    }

    
    public static function rollBack(): bool
    {
        $pdo = self::getConnection();
        return $pdo->inTransaction() ? $pdo->rollBack() : true;
    }

    public static function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::executeQuery($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::executeQuery($sql, $params)->fetchAll();
    }

    public static function execute(string $sql, array $params = []): int
    {
        return self::executeQuery($sql, $params)->rowCount();
    }

    public static function lastInsertId(): string|false
    {
        return self::getConnection()->lastInsertId();
    }
}
