<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Iniciar Sesión - SIAMTEL') ?></title>
    <link rel="stylesheet" href="/css/login.css">
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>SIAMTEL</h1>
            <p>Gobernación del Estado Lara</p>
        </div>

        <?php if (!empty($_SESSION['error_login'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error_login']) ?>
            </div>
            <?php unset($_SESSION['error_login']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success_login'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_login']) ?>
            </div>
            <?php unset($_SESSION['success_login']); ?>
        <?php endif; ?>

        <form method="POST" action="/login">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">Ingresar al Sistema</button>
        </form>

        <div class="login-footer">
            <p>Sistema Integral de Atención Médica y Telemática</p>
            <p class="demo-credentials">Demo: <strong>admin</strong> / <strong>admin123</strong></p>
        </div>
    </div>
</body>
</html>
