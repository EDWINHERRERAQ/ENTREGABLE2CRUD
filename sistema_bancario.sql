-- Base de datos: sistema_bancario
CREATE DATABASE IF NOT EXISTS sistema_bancario;

USE sistema_bancario;

-- Tabla de usuarios
CREATE TABLE `usuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido_paterno` VARCHAR(100) NOT NULL,
  `apellido_materno` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `telefono` VARCHAR(15) NOT NULL,
  `dni` VARCHAR(8) NOT NULL UNIQUE,
  `nombre_usuario` VARCHAR(50) NOT NULL UNIQUE,
  `clave` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
);

-- Tabla de cuentas bancarias
CREATE TABLE `cuentas_bancarias` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `saldo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tipo` VARCHAR(20) NOT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
);

-- Tabla de transacciones
CREATE TABLE `transacciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cuenta_id` INT(11) NOT NULL,
  `tipo_transaccion` VARCHAR(20) NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas_bancarias`(`id`) ON DELETE CASCADE
);
