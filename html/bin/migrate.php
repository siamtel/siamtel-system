<?php

declare(strict_types=1);


$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$bootstrap = dirname(__DIR__) . '/app/bootstrap.php';
if (file_exists($bootstrap)) {
    require_once $bootstrap;
}

use App\Core\Database;

$rawArgs = array_slice($argv ?? [], 1);

// Normalizar comando y módulo objetivo
$validCommands = ['run', 'fresh', 'status', 'make', 'seed'];
$validModules = ['system', 'security', 'all'];

$command = 'run';
$module = 'all';
$extraParam = null;

if (!empty($rawArgs)) {
    $first = strtolower(trim($rawArgs[0]));
    if (in_array($first, ['system', 'security'], true)) {
        $command = 'run';
        $module = $first;
    } elseif (in_array($first, $validCommands, true)) {
        $command = $first;
        if (isset($rawArgs[1])) {
            $second = strtolower(trim($rawArgs[1]));
            if (in_array($second, $validModules, true)) {
                $module = $second;
            } else {
                $extraParam = $rawArgs[1];
            }
        }
        if (isset($rawArgs[2])) {
            $third = strtolower(trim($rawArgs[2]));
            if (in_array($third, $validModules, true)) {
                $module = $third;
            }
        }
    } else {
        $extraParam = $rawArgs[0];
    }
}

$baseMigrationsDir = dirname(__DIR__) . '/database/migrations';

// Asegurar que las carpetas modulares existan
foreach (['system', 'security'] as $m) {
    $dir = "{$baseMigrationsDir}/{$m}";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

$systemDb = Database::getSystemDb();
$securityDb = Database::getSecurityDb();

// Generador de nuevas migraciones
if ($command === 'make') {
    $name = $extraParam;
    if (!$name) {
        fwrite(STDERR, "✖ Error: Debes indicar un nombre para la migración.\n");
        fwrite(STDERR, "   Uso: php bin/migrate.php make <nombre> [system|security]\n");
        fwrite(STDERR, "   Ejemplo: php bin/migrate.php make agregar_telefono_a_empleado system\n");
        exit(1);
    }

    $targetModule = in_array($module, ['system', 'security'], true) ? $module : 'system';
    $targetDir = "{$baseMigrationsDir}/{$targetModule}";
    $dbName = ($targetModule === 'security') ? $securityDb : $systemDb;

    $files = scandir($targetDir) ?: [];
    $migrationFiles = array_filter($files, fn($f) => str_ends_with($f, '.sql'));
    $nextNumber = count($migrationFiles) + 1;
    $prefix = str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);

    $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower(trim($name)));
    $fileName = "{$prefix}_{$sanitized}.sql";
    $filePath = "{$targetDir}/{$fileName}";

    $template = "-- Migración ({$targetModule}) en BD `{$dbName}`: {$sanitized}\n-- Creada: " . date('Y-m-d H:i:s') . "\n\nUSE `{$dbName}`;\nSET FOREIGN_KEY_CHECKS = 0;\n\n-- Escribe tus sentencias SQL aquí:\n\n\nSET FOREIGN_KEY_CHECKS = 1;\n";
    file_put_contents($filePath, $template);

    echo "✔ Migración creada con éxito para [{$targetModule}] en base de datos `{$dbName}`:\n";
    echo "   html/database/migrations/{$targetModule}/{$fileName}\n";
    exit(0);
}

$isDocker = file_exists('/.dockerenv') || getenv('DB_HOST') === 'siamtel_db';
if (!$isDocker) {
    if (!extension_loaded('pdo_mysql')) {
        $argsStr = implode(' ', array_map('escapeshellarg', $rawArgs));
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

$pdo = getMigrationAdminConnection($systemDb);
$pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$systemDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
} catch (\Throwable $t) {}

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$securityDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
} catch (\Throwable $t) {}

$appUser = getenv('DB_USER') ?: 'siamtel_user';
try {
    $pdo->exec("GRANT ALL PRIVILEGES ON `{$systemDb}`.* TO '{$appUser}'@'%';");
    $pdo->exec("GRANT ALL PRIVILEGES ON `{$securityDb}`.* TO '{$appUser}'@'%';");
    $pdo->exec("FLUSH PRIVILEGES;");
} catch (\Throwable $t) {}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `{$systemDb}`.`migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL,
            `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (\Throwable $t) {}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `{$securityDb}`.`migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL,
            `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (\Throwable $t) {}

switch ($command) {
    case 'status':
        showStatus($pdo, $baseMigrationsDir, $module, $systemDb, $securityDb);
        break;

    case 'fresh':
        executeFresh($pdo, $baseMigrationsDir, $module, $systemDb, $securityDb);
        break;

    case 'seed':
        executeSeed();
        break;

    case 'run':
    default:
        executeRun($pdo, $baseMigrationsDir, $module, $systemDb, $securityDb);
        break;
}


function getMigrationAdminConnection(string $defaultDb): PDO
{
    $host = getenv('DB_HOST') ?: 'siamtel_db';
    $port = getenv('DB_PORT') ?: '3306';
    $rootPass = getenv('DB_ROOT_PASSWORD') ?: 'root_siamtel';
    $appUser = getenv('DB_USER') ?: 'siamtel_user';
    $appPass = getenv('DB_PASSWORD') ?: 'user_siamtel_password';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $dsnRoot = "mysql:host={$host};port={$port};charset=utf8mb4";
        return new PDO($dsnRoot, 'root', $rootPass, $options);
    } catch (\Throwable $e) {
        $dsnUser = "mysql:host={$host};port={$port};dbname={$defaultDb};charset=utf8mb4";
        return new PDO($dsnUser, $appUser, $appPass, $options);
    }
}

function getModuleDb(string $mod, string $systemDb, string $securityDb): string
{
    return ($mod === 'security') ? $securityDb : $systemDb;
}

function showStatus(PDO $pdo, string $baseMigrationsDir, string $module, string $systemDb, string $securityDb): void
{
    $targets = ($module === 'all') ? ['system', 'security'] : [$module];

    echo "\n================================================================================\n";
    echo " ESTADO DE MIGRACIONES - SIAMTEL (Bases de Datos Separadas)\n";
    echo "================================================================================\n";

    foreach ($targets as $m) {
        $db = getModuleDb($m, $systemDb, $securityDb);
        $applied = [];
        try {
            $stmt = $pdo->query("SELECT migration, batch, applied_at FROM `{$db}`.`migrations` ORDER BY id ASC");
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $applied[$row['migration']] = $row;
                }
            }
        } catch (\Throwable $t) {}

        $dir = "{$baseMigrationsDir}/{$m}";
        $scanned = is_dir($dir) ? (scandir($dir) ?: []) : [];
        $files = array_values(array_filter($scanned, fn($f) => str_ends_with($f, '.sql')));
        sort($files);

        echo "\n[Base de Datos: `{$db}` | Módulo: " . strtoupper($m) . "]\n";
        printf("%-7s | %-44s | %-6s | %-19s\n", "ESTADO", "ARCHIVO", "LOTE", "FECHA APLICACIÓN");
        echo str_repeat('-', 84) . "\n";

        if (empty($files)) {
            echo " (No hay archivos de migración registrados)\n";
        }

        foreach ($files as $file) {
            $isRan = isset($applied[$file]);
            $status = $isRan ? "\033[32m✔ OK\033[0m   " : "\033[33m⏳ PEND\033[0m ";
            $batch = $isRan ? (string)$applied[$file]['batch'] : "-";
            $date = $isRan ? $applied[$file]['applied_at'] : "Pendiente";
            printf("%-7s | %-44s | %-6s | %-19s\n", $status, $file, $batch, $date);
        }
    }
    echo "\n" . str_repeat('=', 84) . "\n\n";
}

function executeRun(PDO $pdo, string $baseMigrationsDir, string $module, string $systemDb, string $securityDb): void
{
    $targets = ($module === 'all') ? ['system', 'security'] : [$module];

    foreach ($targets as $m) {
        $db = getModuleDb($m, $systemDb, $securityDb);
        $dir = "{$baseMigrationsDir}/{$m}";
        if (!is_dir($dir)) {
            continue;
        }

        $applied = [];
        try {
            $stmt = $pdo->query("SELECT migration FROM `{$db}`.`migrations`");
            $applied = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (\Throwable $t) {}

        $scanned = scandir($dir) ?: [];
        $files = array_values(array_filter($scanned, fn($f) => str_ends_with($f, '.sql')));
        sort($files);

        $pending = array_values(array_diff($files, $applied));

        if (empty($pending)) {
            echo "✔ No hay migraciones pendientes para `{$db}` [" . strtoupper($m) . "].\n";
            continue;
        }

        $nextBatch = 1;
        try {
            $stmtBatch = $pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM `{$db}`.`migrations`");
            $nextBatch = (int) $stmtBatch->fetchColumn();
        } catch (\Throwable $t) {}

        echo "\nIniciando migraciones en base de datos `{$db}` (Lote: {$nextBatch})...\n";

        $pdo->exec("USE `{$db}`;");

        foreach ($pending as $file) {
            $fullPath = "{$dir}/{$file}";
            echo "-> Migrando [{$db}]: {$file}... ";

            $startTime = microtime(true);
            $sql = file_get_contents($fullPath);
            if ($sql === false) {
                throw new RuntimeException("No se pudo leer el archivo: {$fullPath}");
            }

            $pdo->exec($sql);

            $stmtInsert = $pdo->prepare("INSERT INTO `{$db}`.`migrations` (migration, batch) VALUES (?, ?) ON DUPLICATE KEY UPDATE batch = VALUES(batch)");
            $stmtInsert->execute([$file, $nextBatch]);

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            echo "\033[32m✔ OK ({$elapsed}ms)\033[0m\n";
        }

        echo "✔ ¡Migraciones aplicadas con éxito en `{$db}`!\n";
    }
}

function executeFresh(PDO $pdo, string $baseMigrationsDir, string $module, string $systemDb, string $securityDb): void
{
    if ($module === 'security') {
        echo "⚠ Vaciando base de datos de seguridad `{$securityDb}`...\n";
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $stmt = $pdo->query("SHOW FULL TABLES FROM `{$securityDb}` WHERE Table_type = 'BASE TABLE'");
        if ($stmt) {
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $t) {
                $pdo->exec("DROP TABLE IF EXISTS `{$securityDb}`.`{$t}` CASCADE;");
            }
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "✔ Base de datos `{$securityDb}` vaciada.\n\n";

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$securityDb}`.`migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_migration` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        executeRun($pdo, $baseMigrationsDir, 'security', $systemDb, $securityDb);
    } else {
        echo "⚠ Eliminando todas las tablas en `{$securityDb}` y `{$systemDb}`...\n";
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        foreach ([$securityDb, $systemDb] as $db) {
            $stmt = $pdo->query("SHOW FULL TABLES FROM `{$db}` WHERE Table_type = 'BASE TABLE'");
            if ($stmt) {
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $t) {
                    $pdo->exec("DROP TABLE IF EXISTS `{$db}`.`{$t}` CASCADE;");
                }
            }
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "✔ Bases de datos `{$systemDb}` y `{$securityDb}` vaciadas.\n\n";

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$systemDb}`.`migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_migration` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$securityDb}`.`migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_migration` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        executeRun($pdo, $baseMigrationsDir, $module, $systemDb, $securityDb);
        executeSeed();
    }
}

function executeSeed(): void
{
    $seedScript = __DIR__ . '/seed.php';
    if (file_exists($seedScript)) {
        echo "\n-> Ejecutando carga de datos iniciales (seeders)...\n";
        require $seedScript;
    }
}
