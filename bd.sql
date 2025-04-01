-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS lista_compras;
USE lista_compras;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de familias
CREATE TABLE IF NOT EXISTS familias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de miembros de familia
CREATE TABLE IF NOT EXISTS miembros_familia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    familia_id INT NOT NULL,
    es_admin TINYINT(1) DEFAULT 0,
    fecha_union DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, familia_id)
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    familia_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    cantidad VARCHAR(50),
    comprado TINYINT(1) DEFAULT 0,
    usuario_id INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla de invitaciones
CREATE TABLE IF NOT EXISTS invitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    familia_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    fecha_invitacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE
);