-- ==============================================================================
-- SEEDS INICIALES PARA EL ESQUEMA DE SEGURIDAD (SECURITY) - SIAMTEL
-- Inserta usuario administrador inicial (admin / admin123)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
USE `security`;

-- ------------------------------------------------------------------------------
-- USUARIO ADMINISTRADOR INICIAL (Usuario: admin | Clave: admin123)
-- ------------------------------------------------------------------------------
INSERT INTO `usuario` (`id`, `empleado_id_referencia`, `nombre_usuario`, `password_hash`, `activo`)
VALUES (1, NULL, 'admin', '$2y$10$x5Wrg81Pl14hNA8zPDWoeOwi7i4CX14d8DdlRWMWXaNQnLs6ZRoYO', 1)
ON DUPLICATE KEY UPDATE 
  `nombre_usuario` = VALUES(`nombre_usuario`), 
  `password_hash` = VALUES(`password_hash`),
  `activo` = 1;

SET FOREIGN_KEY_CHECKS = 1;
