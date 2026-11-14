<?php

declare(strict_types=1);

use App\Models\Usuario;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$type = $_REQUEST['type'] ?? 'index';

if ($type === 'logout') {
    authLogout();
}

if (isset($_SESSION['user'])) {
    redirect('dashboard');
}

if ($type === 'index') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        authProcessLogin();
    }

    view('login', [
        'titulo' => 'Iniciar Sesión - SIAMTEL',
    ]);
}

function authProcessLogin(): void
{
    $usuarioInput = trim($_POST['usuario'] ?? $_POST['nombre_usuario'] ?? '');
    $passwordInput = $_POST['password'] ?? $_POST['clave'] ?? '';

    if (empty($usuarioInput) || empty($passwordInput)) {
        $_SESSION['error_login'] = 'Por favor, ingrese su usuario y contraseña.';
        redirect('login');
    }

    try {
        $usuario = new Usuario();
        $usuario->setNombreUsuario($usuarioInput);
        $usuario->setPassword($passwordInput);

        $userData = $usuario->autenticar();

        if ($userData !== null) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'             => $userData['id'],
                'nombre_usuario' => $userData['nombre_usuario'],
                'empleado_id'    => $userData['empleado_id_referencia'],
                'ultimo_login'   => $userData['ultimo_login'] ?? date('Y-m-d H:i:s'),
            ];

            unset($_SESSION['error_login']);
            redirect('dashboard');
        }

        $_SESSION['error_login'] = 'Usuario o contraseña incorrectos.';
    } catch (\Exception $e) {
        error_log('[Auth Error] ' . $e->getMessage());
        $_SESSION['error_login'] = $e->getMessage();
    }

    redirect('login');
}


function authLogout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
    redirect('login');
}
