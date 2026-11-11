<?php

declare(strict_types=1);

// 1. Constantes globales de ruta y entorno
define('BASE_PATH', dirname(__DIR__));

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
define('BASE_URL', $protocol . '://' . $host);

// 2. Cargar Autoload PSR-4 de Composer
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// 3. Cargar funciones auxiliares globales (view, redirect, json)
require_once BASE_PATH . '/app/Core/Helpers.php';

// 4. Inicializar sesión de usuario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 5. Procesamiento de ruta limpia
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($requestUri, '/');

// Obtener parámetro ?page= o deducirlo de la URL
$route = $_GET['page'] ?? $path;
if (empty($route) || $route === 'index.php') {
    $route = 'login';
}

// Limpiar extensión .php si fue ingresada en la barra de direcciones
$route = preg_replace('/\.php$/', '', $route);
$segments = explode('/', $route);
$primary = strtolower($segments[0]);

// 6. Enrutamiento a Controladores (Archivos de Script) o Vistas
switch ($primary) {
    case 'login':
    case 'crear-cuenta':
    case 'auth':
        require_once BASE_PATH . '/app/Controllers/Auth/AuthController.php';
        break;

    case 'logout':
        $_REQUEST['type'] = 'logout';
        require_once BASE_PATH . '/app/Controllers/Auth/AuthController.php';
        break;

    default:
        // A. Controlador Modular: app/Controllers/{Modulo}/{Nombre}Controller.php
        if (count($segments) >= 2) {
            $module = ucfirst($segments[0]);
            $controllerName = ucfirst($segments[1]) . 'Controller.php';
            $modularFile = BASE_PATH . '/app/Controllers/' . $module . '/' . $controllerName;

            if (file_exists($modularFile)) {
                require_once $modularFile;
                exit();
            }
        }

        // B. Controlador Directo: app/Controllers/{Nombre}Controller.php
        $directFile = BASE_PATH . '/app/Controllers/' . ucfirst($primary) . 'Controller.php';
        if (file_exists($directFile)) {
            require_once $directFile;
            exit();
        }

        // C. Vista directa para maquetar UI/UX rápido (app/Views/{ruta}.php)
        $viewFile = BASE_PATH . '/app/Views/' . $route . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
            exit();
        }

        // D. Fallback por defecto: Login
        require_once BASE_PATH . '/app/Controllers/Auth/AuthController.php';
        break;
}
