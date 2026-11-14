<?php

declare(strict_types=1);


if (!function_exists('view')) {
    function view(string $viewPath, array $data = []): void
    {
        extract($data);

        $cleanPath = ltrim($viewPath, '/');
        $fullPath = BASE_PATH . '/app/views/' . $cleanPath . '.php';

        if (!file_exists($fullPath)) {
            $altPath = BASE_PATH . '/app/views/' . basename($cleanPath) . '.php';
            if (file_exists($altPath)) {
                $fullPath = $altPath;
            } elseif (file_exists(BASE_PATH . '/app/Views/' . $cleanPath . '.php')) {
                $fullPath = BASE_PATH . '/app/Views/' . $cleanPath . '.php';
            } else {
                http_response_code(404);
                echo "<h1>Error 404: Vista no encontrada</h1>";
                echo "<p>No se pudo localizar el archivo: <code>app/views/{$cleanPath}.php</code></p>";
                exit();
            }
        }

        require_once $fullPath;
        exit();
    }
}

if (!function_exists('redirect')) {

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
   
    function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

