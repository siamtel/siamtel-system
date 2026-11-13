<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Clase Abstracta Database
 *
 * Implementa la conexión centralizada a MariaDB/MySQL mediante el patrón Singleton.
 * Garantiza que solo se mantenga una única conexión activa (PDO) durante el ciclo
 * de vida completo de la petición, evitando sobrecarga en el servidor.
 */
abstract class Database
{
    private static ?PDO $connection = null;

    /**
     * Obtiene la instancia única de conexión PDO.
     */
    public static function getConnection(): ?PDO
    {
        if (self::$connection === null) {
            $host = getenv('DB_HOST') ?: 'siamtel_db';
            $port = getenv('DB_PORT') ?: '3306';
            $database = getenv('DB_NAME') ?: 'siamtel_db';
            $username = getenv('DB_USER') ?: 'siamtel_user';
            $password = getenv('DB_PASSWORD') ?: 'user_siamtel_password';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$connection = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                error_log('[Database Error] ' . $e->getMessage());
                return null;
            }
        }

        return self::$connection;
    }

    /**
     * Cierra explícitamente la conexión activa.
     */
    public static function closeConnection(): void
    {
        self::$connection = null;
    }

    /**
     * Ejecuta una consulta preparada con parámetros de forma segura contra inyecciones SQL.
     */
    protected static function executeQuery(string $sql, array $params = []): ?\PDOStatement
    {
        $db = self::getConnection();
        if ($db === null) {
            return null;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

