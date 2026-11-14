-- ==============================================================================
-- SCHEMA DE SEGURIDAD (SECURITY) - SIAMTEL
-- Compatible con: MariaDB 10.3+ / MySQL 8.0+
-- Motor: InnoDB | Charset: utf8mb4_unicode_ci
-- Usuarios, credenciales de acceso y auditoría de seguridad
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `security` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `security`;

-- Eliminar tablas obsoletas si existieran
DROP VIEW IF EXISTS `sys_bitacora`;
DROP VIEW IF EXISTS `sys_usuario`;
DROP TABLE IF EXISTS `sys_rol_permiso`;
DROP TABLE IF EXISTS `sys_usuario_rol`;
DROP TABLE IF EXISTS `sys_permiso`;
DROP TABLE IF EXISTS `sys_rol`;
DROP TABLE IF EXISTS `sys_bitacora`;
DROP TABLE IF EXISTS `sys_usuario`;

-- ------------------------------------------------------------------------------
-- 1. TABLA DE USUARIOS (Usuarios y claves)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `usuario`;
CREATE TABLE `usuario` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `empleado_id_referencia` INT NULL COMMENT 'Puntero foráneo opcional a empleado en el esquema system',
  `nombre_usuario` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identificador de acceso (username)',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hash nativo mediante password_hash() BCRYPT',
  `activo` BOOLEAN NOT NULL DEFAULT TRUE,
  `ultimo_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_usuario_empleado` (`empleado_id_referencia`),
  CONSTRAINT `fk_usuario_empleado` 
    FOREIGN KEY (`empleado_id_referencia`) 
    REFERENCES `system`.`empleado` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. TABLA DE BITÁCORA / AUDITORÍA DE SEGURIDAD
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `bitacora`;
CREATE TABLE `bitacora` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NULL COMMENT 'Usuario que originó la acción',
  `accion` VARCHAR(50) NOT NULL COMMENT 'LOGIN, LOGOUT, CREAR, ACTUALIZAR, ELIMINAR',
  `tabla_afectada` VARCHAR(50) NULL,
  `registro_id` INT NULL,
  `detalles` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_bitacora_usuario` (`id_usuario`),
  KEY `idx_bitacora_created_at` (`created_at`),
  CONSTRAINT `fk_bitacora_usuario` 
    FOREIGN KEY (`id_usuario`) 
    REFERENCES `usuario` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vistas de compatibilidad
CREATE OR REPLACE VIEW `sys_usuario` AS SELECT * FROM `usuario`;
CREATE OR REPLACE VIEW `sys_bitacora` AS SELECT * FROM `bitacora`;

SET FOREIGN_KEY_CHECKS = 1;
