<?php

declare(strict_types=1);

/**
 * Controlador de Autenticación y Registro (Script / Procedural)
 *
 * En esta arquitectura los controladores son archivos de script modulares (no clases)
 * que ejecutan la lógica correspondiente apoyados en funciones de responsabilidad única.
 */

use App\Models\Security\Usuario;
use App\Models\Security\LoginSettings;
use App\Models\System\Cliente;

// 1. Manejo de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Si ya hay usuario autenticado, redirigir según su rol
if (isset($_SESSION['user'])) {
    $rol = strtoupper(trim($_SESSION['user']['rol'] ?? ''));
    $target = ($rol === 'CLIENTE') ? 'home' : 'dashboard';
    redirect($target);
}

// 3. Obtener tipo de acción solicitada
$type = $_REQUEST['type'] ?? 'index';

if ($type === 'index') {
    $siteKey = '';
    if (class_exists(LoginSettings::class)) {
        $loginSettings = new LoginSettings();
        $siteKey = $loginSettings->get_recaptcha_sitekey();
    }

    $currentPage = $_GET['page'] ?? 'login';
    $openRegisterSlide = ($currentPage === 'crear-cuenta');

    // Procesar peticiones POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $peticion = $_POST['peticion'] ?? 'sesion';

        switch ($peticion) {
            case 'verificar_cedula':
                authVerifyCedula();
                break;

            case 'registrar':
                authProcessRegister($siteKey);
                break;

            case 'sesion':
            default:
                authProcessLogin($siteKey);
                break;
        }
    }

    // Renderizar la vista de login
    view('auth/login', [
        'titulo'            => 'Iniciar Sesión - SIAMTEL',
        'siteKey'           => $siteKey,
        'openRegisterSlide' => $openRegisterSlide,
    ]);

} elseif ($type === 'logout') {
    authLogout();
}

// ============================================================================
// Funciones de Responsabilidad Única para Autenticación (Clean Code)
// ============================================================================

/**
 * Procesa el formulario de inicio de sesión.
 */
function authProcessLogin(string $siteKey): void
{
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';
    if (!empty($siteKey) && empty($recaptcha)) {
        $_SESSION['error_login'] = 'Por favor, complete el reCAPTCHA.';
        redirect('login');
    }

    $particle = $_POST['particle'] ?? 'V-';
    $ci = trim($_POST['CI'] ?? '');
    $password = $_POST['password'] ?? '';
    $cedula = $particle . $ci;

    if (empty($ci) || empty($password)) {
        $_SESSION['error_login'] = 'Por favor, complete todos los campos requeridos.';
        redirect('login');
    }

    // Si los modelos aún no están implementados (fase de maquetación UI/UX)
    if (!class_exists(Usuario::class)) {
        $_SESSION['error_login'] = 'Módulo de autenticación en desarrollo (sin conexión activa).';
        redirect('login');
    }

    try {
        $usuarioModel = new Usuario();
        $usuarioModel->setCedula($cedula);
        $usuarioModel->setClave($password, false);
        $validacion = $usuarioModel->Transaccion(['peticion' => 'sesion']);

        if (!empty($validacion['response']['verificacion'])) {
            if (isset($validacion['response']['estatus']) && (int)$validacion['response']['estatus'] === 0) {
                $_SESSION['error_login'] = 'Su cuenta de usuario se encuentra inhabilitada.';
                redirect('login');
            }

            unset($_SESSION['login_attempts'][$cedula]);
            $datos = $usuarioModel->Transaccion(['peticion' => 'perfil']);

            if ($datos && isset($datos['response']['datos'])) {
                $_SESSION['user'] = $datos['response']['datos'];
                unset($_SESSION['error_login']);

                $rol = strtoupper(trim($_SESSION['user']['rol'] ?? ''));
                $target = ($rol === 'CLIENTE') ? 'home' : 'dashboard';
                redirect($target);
            }
        }

        $_SESSION['error_login'] = 'Cédula o contraseña incorrectos.';
    } catch (\Exception $e) {
        error_log('[Auth Error] ' . $e->getMessage());
        $_SESSION['error_login'] = 'Error interno al procesar el inicio de sesión.';
    }

    redirect('login');
}

/**
 * Procesa el registro de un nuevo usuario.
 */
function authProcessRegister(string $siteKey): void
{
    $clave = $_POST['clave'] ?? '';
    $rclave = $_POST['rclave'] ?? '';

    if ($clave !== $rclave) {
        $_SESSION['error_register'] = 'Las contraseñas no coinciden.';
        redirect('crear-cuenta');
    }

    if (!class_exists(Cliente::class) || !class_exists(Usuario::class)) {
        $_SESSION['error_register'] = 'Módulo de registro en desarrollo.';
        redirect('crear-cuenta');
    }

    // Transacción de registro cuando los modelos estén listos
    redirect('login');
}

/**
 * Verificación asíncrona (AJAX) de cédula existente.
 */
function authVerifyCedula(): void
{
    $particle = $_POST['nacionalidad'] ?? 'V-';
    $ci = trim($_POST['cedula'] ?? '');
    $cedula = $particle . $ci;

    if (!class_exists(Cliente::class)) {
        json(['resultado' => 200, 'existe' => false, 'mensaje' => 'Modelo no cargado']);
    }

    try {
        $clienteModel = new Cliente();
        $clienteModel->setCedula($cedula);
        $resultado = $clienteModel->Transaccion(['peticion' => 'verificar_cedula']);
        json($resultado['response'] ?? ['resultado' => 500, 'existe' => false]);
    } catch (\Exception $e) {
        json(['resultado' => 500, 'existe' => false, 'mensaje' => $e->getMessage()], 500);
    }
}

/**
 * Cierra la sesión activa del usuario.
 */
function authLogout(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }

    redirect('login');
}
