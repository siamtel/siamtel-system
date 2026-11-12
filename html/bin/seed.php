<?php

declare(strict_types=1);

/**
 * ==============================================================================
 * SIAMTEL - Motor de Seeders CLI (Capa de Base de Datos)
 * ==============================================================================
 * Ubicación: html/bin/seed.php
 * Reutiliza: App\Core\Database::getConnection()
 * ==============================================================================
 */

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use App\Core\Database;

$seedsDir = dirname(__DIR__) . '/database/seeds';
if (!is_dir($seedsDir)) {
    mkdir($seedsDir, 0755, true);
}

$isDocker = file_exists('/.dockerenv') || getenv('DB_HOST') === 'siamtel_db';
if (!$isDocker) {
    if (!extension_loaded('pdo_mysql')) {
        $args = array_slice($argv ?? [], 1);
        $argsStr = implode(' ', array_map('escapeshellarg', $args));
        $cmd = "docker compose exec siamtel_web php bin/seed.php {$argsStr}";
        passthru($cmd, $code);
        exit($code);
    }

    $envFile = dirname(__DIR__, 2) . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (getenv($key) === false) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
    if (getenv('DB_HOST') === false) {
        putenv('DB_HOST=127.0.0.1');
    }
}

$pdo = Database::getConnection();

if (!$pdo) {
    fwrite(STDERR, "✖ Error: No se pudo conectar a MariaDB mediante App\Core\Database.\n");
    exit(1);
}

$pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);

// Tabla de control de seeds
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `seeds` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `seed` VARCHAR(255) NOT NULL,
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_seed` (`seed`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$force = in_array('--force', $argv ?? [], true);

$stmt = $pdo->query("SELECT seed FROM seeds");
$applied = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

$files = scandir($seedsDir) ?: [];
$seedFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.sql')));
sort($seedFiles);

$pending = $force ? $seedFiles : array_values(array_diff($seedFiles, $applied));

if (empty($pending)) {
    echo "✔ No hay nuevos seeders pendientes por ejecutar.\n";
    exit(0);
}

echo "Cargando datos iniciales (Seeders)...\n";
foreach ($pending as $file) {
    $filePath = "{$seedsDir}/{$file}";
    echo "-> Ejecutando seeder: {$file}... ";

    $startTime = microtime(true);
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException("No se pudo leer el archivo: {$filePath}");
    }

    $pdo->exec($sql);

    $stmtInsert = $pdo->prepare("INSERT INTO seeds (seed) VALUES (?) ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP");
    $stmtInsert->execute([$file]);

    $elapsed = round((microtime(true) - $startTime) * 1000, 2);
    echo "\033[32m✔ OK ({$elapsed}ms)\033[0m\n";
}

echo "✔ Todos los seeders se cargaron con éxito.\n";

