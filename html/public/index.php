<?php

require_once __DIR__ . '/../vendor/autoload.php';

define('DS', DIRECTORY_SEPARATOR);

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

$basePath = rtrim(dirname($scriptName), '/\\');

define('BASE_URL', rtrim($protocol . $host . $basePath, '/\\') . '/');
define('BASE_PATH', realpath(__DIR__ . '/..'));

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("America/Caracas");

require_once BASE_PATH . '/app/bootstrap.php';
require_once BASE_PATH . '/app/core/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Controllers\FrontController;

try {

    $frontController = new FrontController();

} catch (Throwable $e) {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

    if ($isAjax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'resultado' => 500,
            'mensaje' => 'Error crítico del sistema',
            'debug' => [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]
        ]);
        exit;
    }

    echo "<h1>Error en la aplicación</h1>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
