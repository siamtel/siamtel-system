<?php

declare(strict_types=1);

namespace App\Controllers;

class FrontController
{
    private string $dir;
    private string $controller;
    private string $url;

    public function __construct()
    {
        if (!isset($_REQUEST["page"]) || empty($_REQUEST["page"])) {
            $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $path = trim($requestUri, '/');
            $_REQUEST["page"] = (!empty($path) && $path !== 'index.php') ? $path : "login";
        }

        if (isset($_REQUEST["page"])) {
            $page = preg_replace('/\.php$/', '', $_REQUEST["page"]);
            $_REQUEST["page"] = $page;
            $_GET["page"] = $page;

            if (in_array($page, ['login', 'crear-cuenta', 'auth'], true)) {
                $this->url = 'Auth';
            } elseif ($page === 'logout') {
                $this->url = 'Auth';
                $_REQUEST["type"] = 'logout';
            } elseif (in_array($page, ['recuperar-password', 'verificar-codigo', 'restablecer-password'], true)) {
                $this->url = 'RecuperacionClave';
            } else {
                $this->url = $page;
            }

            $this->dir = BASE_PATH . '/app/controllers/';
            $this->controller = 'Controller.php';

            $this->getURL();
        } else {
            $this->show404();
        }
    }

    private function getURL(): void
    {
        $controllerName = ucfirst($this->url) . $this->controller;

        if (file_exists($this->dir . $controllerName)) {
            require_once $this->dir . $controllerName;
        } else {
            $this->show404();
        }
    }

    private function show404(): void
    {
        http_response_code(404);
        require_once BASE_PATH . '/app/views/404.php';
        exit;
    }
}
