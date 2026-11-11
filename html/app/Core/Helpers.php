<?php

declare(strict_types=1);

/**
 * Funciones Auxiliares Globales para Vistas y Respuestas
 */

if (!function_exists('view')) {
    /**
     * Renderiza una vista y le pasa datos asociativos como variables locales.
     *
     * @param string $viewPath Ruta de la vista relativa a app/Views (ej: 'auth/login')
     * @param array $data Variables que se extraerán en la vista
     */
    function view(string $viewPath, array $data = []): void
    {
        extract($data);

        $cleanPath = ltrim($viewPath, '/');
        $fullPath = BASE_PATH . '/app/Views/' . $cleanPath . '.php';

        if (!file_exists($fullPath)) {
            $altPath = BASE_PATH . '/app/Views/' . basename($cleanPath) . '.php';
            if (file_exists($altPath)) {
                $fullPath = $altPath;
            } else {
                http_response_code(404);
                echo "<h1>Error 404: Vista no encontrada</h1>";
                echo "<p>No se pudo localizar el archivo: <code>app/Views/{$cleanPath}.php</code></p>";
                exit();
            }
        }

        require_once $fullPath;
        exit();
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirige el navegador a una ruta interna o externa.
     */
    function redirect(string $path): void
    {
        $url = (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
            ? $path
            : rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');

        header('Location: ' . $url);
        exit();
    }
}

if (!function_exists('json')) {
    /**
     * Emite una respuesta en formato JSON.
     */
    function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }
}

