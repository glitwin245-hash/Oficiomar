-- Pegá esto en phpMyAdmin > SQL y ejecutá
-- Si ya tenés la base de datos creada, ejecutá solo las partes nuevas

CREATE DATABASE IF NOT EXISTS oficioMar;
USE oficioMar;

-- TABLA USUARIOS
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  rol ENUM('turista', 'profesional', 'admin') NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  fecha_registro DATETIME DEFAULT NOW()
);

-- TABLA PROFESIONALES (info extra del profesional)
CREATE TABLE IF NOT EXISTS profesionales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  rubro VARCHAR(100),
  zona VARCHAR(150),
  precio VARCHAR(100),
  descripcion TEXT,
  anos_experiencia INT DEFAULT 0,
  tiempo_respuesta VARCHAR(100),
  telefono VARCHAR(100),
  sitio_web VARCHAR(255),
  disponible TINYINT(1) DEFAULT 1,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Si la tabla ya existe y faltan los nuevos campos, ejecutá esto en MySQL:
-- ALTER TABLE profesionales
--   ADD COLUMN anos_experiencia INT DEFAULT 0,
--   ADD COLUMN tiempo_respuesta VARCHAR(100),
--   ADD COLUMN telefono VARCHAR(100),
--   ADD COLUMN sitio_web VARCHAR(255);

-- TABLA RESEÑAS
CREATE TABLE IF NOT EXISTS resenas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  profesional_id INT NOT NULL,
  turista_id INT NOT NULL,
  calificacion INT NOT NULL,
  comentario TEXT,
  fecha DATETIME DEFAULT NOW(),
  FOREIGN KEY (profesional_id) REFERENCES usuarios(id),
  FOREIGN KEY (turista_id) REFERENCES usuarios(id)
);

-- TABLA SOLICITUDES DE SERVICIO (Turista contacta o pide presupuesto a Profesional)
CREATE TABLE IF NOT EXISTS solicitudes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  profesional_id INT NOT NULL,
  turista_id INT NOT NULL,
  nombre_contacto VARCHAR(100) NOT NULL,
  telefono_contacto VARCHAR(50) NOT NULL,
  zona VARCHAR(150),
  mensaje TEXT NOT NULL,
  fecha_deseada VARCHAR(100),
  estado ENUM('pendiente', 'aceptado', 'rechazado', 'completado') DEFAULT 'pendiente',
  fecha_creacion DATETIME DEFAULT NOW(),
  FOREIGN KEY (profesional_id) REFERENCES usuarios(id),
  FOREIGN KEY (turista_id) REFERENCES usuarios(id)
);
