<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Iniciar Sesión - SIAMTEL') ?></title>
    <!-- Estilos modulares organizados por módulo /css/auth/login.css -->
    <link rel="stylesheet" href="/css/auth/login.css">
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>SIAMTEL</h1>
            <p>Gobernación del Estado Lara</p>
        </div>

        <?php if (!empty($_SESSION['error_login'])): ?>
            <div class="alert alert-danger" style="background-color: #ffeef0; color: #d73a49; border: 1px solid #f97583; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                <?= htmlspecialchars($_SESSION['error_login']) ?>
            </div>
            <?php unset($_SESSION['error_login']); ?>
        <?php endif; ?>

        <form method="POST" action="/login">
            <input type="hidden" name="peticion" value="sesion">

            <div class="form-group">
                <label for="CI">Documento de Identidad (Cédula)</label>
                <div style="display: flex; gap: 8px;">
                    <select name="particle" id="particle" style="width: 75px; padding: 12px; border: 1px solid #ddd; border-radius: 6px; background: #fff; font-size: 14px;">
                        <option value="V-">V-</option>
                        <option value="E-">E-</option>
                        <option value="J-">J-</option>
                        <option value="G-">G-</option>
                    </select>
                    <input type="text" id="CI" name="CI" placeholder="Ej: 12345678" style="flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Ingresar al Sistema</button>
        </form>

        <div class="login-footer">
            <p>Sistema Integral de Atención y Gestión</p>
        </div>
    </div>
</body>
</html>

