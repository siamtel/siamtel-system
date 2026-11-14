-- ==============================================================================
-- SCHEMA DE SISTEMA / LÓGICA DE NEGOCIO (SYSTEM) - SIAMTEL
-- Compatible con: MariaDB 10.3+ / MySQL 8.0+
-- Motor: InnoDB | Charset: utf8mb4_unicode_ci
-- Convención: Tablas con nombres de negocio puros, sin prefijos.
-- Relaciones: 'contacto' y 'empleado' hacia 'persona' de 1 a N.
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `system`;

-- ------------------------------------------------------------------------------
-- 1. TABLAS DE CATÁLOGOS BASE
-- ------------------------------------------------------------------------------

DROP TABLE IF EXISTS `tipo_documento`;
CREATE TABLE `tipo_documento` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `acronimo` VARCHAR(20) NOT NULL COMMENT 'Ej: V, E, J, G, P',
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Ej: Cédula Venezolana, Pasaporte'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tipo_relacion`;
CREATE TABLE `tipo_relacion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Ej: Padre, Madre, Hijo/a, Cónyuge'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tipo_personal`;
CREATE TABLE `tipo_personal` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Ej: EMPLEADO FIJO, CONTRATADO, OBRERO',
  `descripcion` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tipo_nomina`;
CREATE TABLE `tipo_nomina` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Ej: NOMINA ORDINARIA, NOMINA ESPECIAL',
  `descripcion` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tipo_contrato`;
CREATE TABLE `tipo_contrato` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Ej: TIEMPO INDETERMINADO, TIEMPO DETERMINADO',
  `descripcion` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `estado`;
CREATE TABLE `estado` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Ej: ACTIVO, VACACIONES, REPOSO, JUBILADO, EGRESADO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cargo`;
CREATE TABLE `cargo` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft Delete de Filament/Framework'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `unidad_organizativa`;
CREATE TABLE `unidad_organizativa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `id_padre` INT NULL,
  `tipo_unidad` VARCHAR(50) NULL,
  `descripcion` TEXT NULL,
  `activo` BOOLEAN NOT NULL DEFAULT TRUE,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft Delete de Filament/Framework',
  UNIQUE KEY `uq_unidad_codigo` (`codigo`),
  KEY `idx_unidad_padre` (`id_padre`),
  KEY `idx_unidad_tipo` (`tipo_unidad`),
  CONSTRAINT `fk_unidad_padre` 
    FOREIGN KEY (`id_padre`) 
    REFERENCES `unidad_organizativa` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. TABLAS DE PERSONAS Y CONTACTO (Relación 1 a N)
-- ------------------------------------------------------------------------------

DROP TABLE IF EXISTS `persona`;
CREATE TABLE `persona` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `numero_documento` VARCHAR(50) NOT NULL,
  `id_tipo_documento` INT NOT NULL,
  `fecha_nacimiento` DATE NULL,
  `genero` ENUM('M', 'F') NULL,
  UNIQUE KEY `uq_persona_documento` (`numero_documento`, `id_tipo_documento`),
  CONSTRAINT `fk_persona_tipo_documento` 
    FOREIGN KEY (`id_tipo_documento`) 
    REFERENCES `tipo_documento` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `contacto`;
CREATE TABLE `contacto` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_persona` INT NOT NULL,
  `direccion` VARCHAR(255) NULL,
  `telefono` VARCHAR(50) NULL,
  `correo` VARCHAR(150) NULL,
  KEY `idx_contacto_persona` (`id_persona`),
  KEY `idx_contacto_persona_telefono` (`id_persona`, `telefono`),
  KEY `idx_contacto_persona_correo` (`id_persona`, `correo`),
  CONSTRAINT `fk_contacto_persona` 
    FOREIGN KEY (`id_persona`) 
    REFERENCES `persona` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. TABLAS DE RRHH, EMPLEADOS (Relación 1 a N con Persona) Y ASIGNACIONES
-- ------------------------------------------------------------------------------

DROP TABLE IF EXISTS `empleado`;
CREATE TABLE `empleado` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_persona` INT NOT NULL,
  `id_cargo` INT NULL,
  `id_tipo_personal` INT NOT NULL,
  `id_tipo_nomina` INT NOT NULL,
  `id_tipo_contrato` INT NOT NULL,
  `id_estado_actual` INT NOT NULL,
  `fecha_ingreso` DATE NULL,
  `fecha_egreso` DATE NULL,
  `fecha_fin_contrato` DATE NULL,
  `observacion` TEXT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft Delete de Filament/Framework',
  KEY `idx_empleado_persona` (`id_persona`),
  KEY `idx_empleado_cargo` (`id_cargo`),
  CONSTRAINT `fk_empleado_persona` 
    FOREIGN KEY (`id_persona`) 
    REFERENCES `persona` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT,
  CONSTRAINT `fk_empleado_cargo` 
    FOREIGN KEY (`id_cargo`) 
    REFERENCES `cargo` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL,
  CONSTRAINT `fk_empleado_tipo_personal` 
    FOREIGN KEY (`id_tipo_personal`) 
    REFERENCES `tipo_personal` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT,
  CONSTRAINT `fk_empleado_tipo_nomina` 
    FOREIGN KEY (`id_tipo_nomina`) 
    REFERENCES `tipo_nomina` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT,
  CONSTRAINT `fk_empleado_tipo_contrato` 
    FOREIGN KEY (`id_tipo_contrato`) 
    REFERENCES `tipo_contrato` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT,
  CONSTRAINT `fk_empleado_estado_actual` 
    FOREIGN KEY (`id_estado_actual`) 
    REFERENCES `estado` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `familiar`;
CREATE TABLE `familiar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_empleado` INT NOT NULL,
  `id_persona` INT NOT NULL,
  `id_tipo_relacion` INT NOT NULL,
  UNIQUE KEY `uq_familiar` (`id_empleado`, `id_persona`, `id_tipo_relacion`),
  CONSTRAINT `fk_familiar_empleado` 
    FOREIGN KEY (`id_empleado`) 
    REFERENCES `empleado` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE CASCADE,
  CONSTRAINT `fk_familiar_persona` 
    FOREIGN KEY (`id_persona`) 
    REFERENCES `persona` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE CASCADE,
  CONSTRAINT `fk_familiar_tipo_relacion` 
    FOREIGN KEY (`id_tipo_relacion`) 
    REFERENCES `tipo_relacion` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `historial_laboral`;
CREATE TABLE `historial_laboral` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_empleado` INT NOT NULL,
  `id_cargo` INT NOT NULL,
  `id_unidad_organizativa` INT NOT NULL,
  `id_estado_laboral` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `observacion` TEXT NULL,
  KEY `idx_historial_empleado_fecha` (`id_empleado`, `fecha`),
  CONSTRAINT `fk_historial_empleado` 
    FOREIGN KEY (`id_empleado`) 
    REFERENCES `empleado` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE CASCADE,
  CONSTRAINT `fk_historial_cargo` 
    FOREIGN KEY (`id_cargo`) 
    REFERENCES `cargo` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT,
  CONSTRAINT `fk_historial_unidad` 
    FOREIGN KEY (`id_unidad_organizativa`) 
    REFERENCES `unidad_organizativa` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT,
  CONSTRAINT `fk_historial_estado` 
    FOREIGN KEY (`id_estado_laboral`) 
    REFERENCES `estado` (`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

