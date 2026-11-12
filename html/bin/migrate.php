<?php

declare(strict_types=1);

/**
 * ==============================================================================
 * SIAMTEL - Motor de Migraciones CLI (Capa de Base de Datos)
 * ==============================================================================
 * Ubicación: html/bin/migrate.php
 * Reutiliza: App\Core\Database::getConnection()
 * ==============================================================================
 */

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use App\Core\Database;

$command = $argv[1] ?? 'run';
$param = $argv[2] ?? null;
$migrationsDir = dirname(__DIR__) . '/database/migrations';

if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

if ($command === 'make') {
    if (!$param) {
        fwrite(STDERR, "✖ Error: Debes indicar un nombre. Ej: php bin/migrate.php make agregar_telefono_a_empleado\n");
        exit(1);
    }

    $files = scandir($migrationsDir) ?: [];
    $migrationFiles = array_filter($files, fn($f) => str_ends_with($f, '.sql'));
    $nextNumber = count($migrationFiles) + 1;
    $prefix = str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);

    $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower(trim($param)));
    $fileName = "{$prefix}_{$sanitized}.sql";
    $filePath = "{$migrationsDir}/{$fileName}";

    $template = "-- Migración: {$sanitized}\n-- Creada: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS = 0;\n\n-- Escribe tus sentencias SQL (CREATE TABLE, ALTER TABLE, etc.) aquí:\n\n\nSET FOREIGN_KEY_CHECKS = 1;\n";
    file_put_contents($filePath, $template);

    echo "✔ Migración creada con éxito:\n   html/database/migrations/{$fileName}\n";
    exit(0);
}

$isDocker = file_exists('/.dockerenv') || getenv('DB_HOST') === 'siamtel_db';
if (!$isDocker) {
    if (!extension_loaded('pdo_mysql')) {
        $args = array_slice($argv ?? [], 1);
        $argsStr = implode(' ', array_map('escapeshellarg', $args));
        $cmd = "docker compose exec siamtel_web php bin/migrate.php {$argsStr}";
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

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL,
        `batch` INT NOT NULL,
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_migration` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

switch ($command) {
    case 'status':
        $stmt = $pdo->query("SELECT migration, batch, applied_at FROM migrations ORDER BY id ASC");
        $applied = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $applied[$row['migration']] = $row;
        }

        $files = scandir($migrationsDir) ?: [];
        $migrationFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.sql')));
        sort($migrationFiles);

        $dbName = getenv('DB_NAME') ?: 'siamtel_db';
        echo "\n================================================================================\n";
        echo " ESTADO DE MIGRACIONES - SIAMTEL ({$dbName})\n";
        echo "================================================================================\n";
        printf("%-7s | %-42s | %-6s | %-19s\n", "ESTADO", "ARCHIVO DE MIGRACIÓN", "LOTE", "FECHA APLICACIÓN");
        echo str_repeat('-', 80) . "\n";

        if (empty($migrationFiles)) {
            echo " (No hay archivos en database/migrations/)\n";
        }

        foreach ($migrationFiles as $file) {
            $isRan = isset($applied[$file]);
            $status = $isRan ? "\033[32m✔ OK\033[0m   " : "\033[33m⏳ PEND\033[0m ";
            $batch = $isRan ? (string)$applied[$file]['batch'] : "-";
            $date = $isRan ? $applied[$file]['applied_at'] : "Pendiente";
            printf("%-7s | %-42s | %-6s | %-19s\n", $status, $file, $batch, $date);
        }
        echo str_repeat('-', 80) . "\n\n";
        break;

    case 'fresh':
        $dbName = getenv('DB_NAME') ?: 'siamtel_db';
        echo "⚠ Eliminando todas las tablas de la base de datos '{$dbName}'...\n";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}` CASCADE;");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "✔ Base de datos vaciada.\n\n";

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_migration` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        runMigrations($pdo, $migrationsDir);

        $seedScript = __DIR__ . '/seed.php';
        if (file_exists($seedScript)) {
            echo "\n-> Ejecutando carga de datos iniciales (seeders)...\n";
            require $seedScript;
        }
        break;

    case 'seed':
        $seedScript = __DIR__ . '/seed.php';
        if (file_exists($seedScript)) {
            require $seedScript;
        } else {
            fwrite(STDERR, "✖ Error: No se encontró seed.php\n");
        }
        break;

    case 'run':
    default:
        runMigrations($pdo, $migrationsDir);
        break;
}


function runMigrations(PDO $pdo, string $migrationsDir): void
{
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $applied = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

    $files = scandir($migrationsDir) ?: [];
    $migrationFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.sql')));
    sort($migrationFiles);

    $pending = array_values(array_diff($migrationFiles, $applied));

    if (empty($pending)) {
        echo "✔ No hay migraciones pendientes. La base de datos está al día.\n";
        return;
    }

    $stmtBatch = $pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations");
    $nextBatch = (int) $stmtBatch->fetchColumn();

    echo "Iniciando ejecución de migraciones (Lote: {$nextBatch})...\n";

    foreach ($pending as $file) {
        $filePath = "{$migrationsDir}/{$file}";
        echo "-> Migrando: {$file}... ";

        $startTime = microtime(true);
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new RuntimeException("No se pudo leer el archivo: {$filePath}");
        }

        $pdo->exec($sql);

        $stmtInsert = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?) ON DUPLICATE KEY UPDATE batch = VALUES(batch)");
        $stmtInsert->execute([$file, $nextBatch]);

        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
        echo "\033[32m✔ OK ({$elapsed}ms)\033[0m\n";
    }

    echo "\n✔ ¡Todas las migraciones pendientes fueron aplicadas con éxito!\n";
}

