<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    redirect('login');
}

$user = $_SESSION['user'];

view('dashboard', [
    'titulo'  => 'Dashboard Principal - SIAMTEL',
    'seccion' => 'Dashboard',
    'user'    => $user,
]);
