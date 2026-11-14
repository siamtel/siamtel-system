<?php
$user = auth_user();
$userName = $user['nombre_usuario'] ?? 'Usuario';
$avatarLetter = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Dashboard - SIAMTEL') ?></title>
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/dashboard.css">
</head>
<body>
<div class="app-layout">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <h2>SIAMTEL</h2>
            <p>Gobernación del Estado Lara</p>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-section">Menú Principal</li>
            <li class="menu-item">
                <a href="/dashboard" class="menu-link active">
                    <span class="menu-icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-snippet">
                <div class="user-avatar"><?= $avatarLetter ?></div>
                <div class="user-meta">
                    <div class="user-meta-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="user-meta-role">En línea</div>
                </div>
            </div>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-navbar">
            <div class="nav-title">Panel de Control</div>
            <div class="nav-actions">
                <span class="badge badge-info">Sesión Activa</span>
                <a href="/logout" class="btn-logout">Cerrar Sesión</a>
            </div>
        </header>

        <div class="page-container">
            <div class="page-header">
                <div>
                    <h1>Bienvenido, <?= htmlspecialchars($userName) ?></h1>
                    <p>Sistema Integral de Atención y Gestión</p>
                </div>
            </div>

            <div class="card empty-state-card">
                <div class="empty-state-icon">📋</div>
                <h2>Dashboard Vacío</h2>
                <p class="empty-state-text">
                    No hay módulos clínicos ni registros de citas cargados en este momento.
                </p>
                
            </div>
        </div>
    </main>
</div>
</body>
</html>
