-- ==============================================================================
-- INICIALIZACIÓN DE ESQUEMAS SEPARADOS EN MARIADB - SIAMTEL
-- Crea las dos bases de datos requeridas:
--   - `system`: Lógica de negocio y servicios médicos
--   - `security`: Usuarios, claves y control de acceso
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `security` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `system`.* TO 'siamtel_user'@'%';
GRANT ALL PRIVILEGES ON `security`.* TO 'siamtel_user'@'%';
GRANT ALL PRIVILEGES ON `system`.* TO 'siamtel_user'@'localhost';
GRANT ALL PRIVILEGES ON `security`.* TO 'siamtel_user'@'localhost';

FLUSH PRIVILEGES;
