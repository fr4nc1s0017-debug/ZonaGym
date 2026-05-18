-- ============================================================
--  ZonaGym — Base de datos completa
-- ============================================================
CREATE DATABASE IF NOT EXISTS zonagym CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE zonagym;

-- ── Usuarios (autenticación + roles) ──────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100)  NOT NULL,
    apellidos     VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    rol           ENUM('admin','usuario') DEFAULT 'usuario',
    activo        TINYINT(1)    DEFAULT 1,
    creado_en     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ── Clientes ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS clientes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombres        VARCHAR(100) NOT NULL,
    apellidos      VARCHAR(100) NOT NULL,
    dui            VARCHAR(15)  NOT NULL UNIQUE,
    direccion      TEXT,
    usuario_id     INT DEFAULT NULL,          -- cliente vinculado a un usuario
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- ── Entrenadores ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS entrenadores (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombres       VARCHAR(100) NOT NULL,
    apellidos     VARCHAR(100) NOT NULL,
    especialidad  VARCHAR(100),
    turno         ENUM('Mañana','Tarde','Noche') NOT NULL,
    activo        TINYINT(1) DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Tipos de membresía ────────────────────────────────────
CREATE TABLE IF NOT EXISTS membresias (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(50)    NOT NULL,
    precio         DECIMAL(10,2)  NOT NULL,
    duracion_dias  INT            NOT NULL,
    descripcion    TEXT
);

-- ── Membresías de clientes ────────────────────────────────
CREATE TABLE IF NOT EXISTS cliente_membresias (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id         INT  NOT NULL,
    membresia_id       INT  NOT NULL,
    fecha_inicio       DATE NOT NULL,
    fecha_vencimiento  DATE NOT NULL,
    estado             ENUM('Activo','Vencido') DEFAULT 'Activo',
    FOREIGN KEY (cliente_id)   REFERENCES clientes(id)  ON DELETE CASCADE,
    FOREIGN KEY (membresia_id) REFERENCES membresias(id)
);

-- ── Rutinas ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rutinas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    grupo_muscular  VARCHAR(100) NOT NULL,
    nombre          VARCHAR(150) NOT NULL,
    descripcion     TEXT,
    ejercicios      TEXT         -- JSON con lista de ejercicios
);

-- ── Datos iniciales ───────────────────────────────────────

-- Admin por defecto  (password: Admin123!)
INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol) VALUES
('Administrador', 'ZonaGym', 'admin@zonagym.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uYpHuzzmu', 'admin');

-- Usuario demo      (password: User123!)
INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol) VALUES
('Carlos', 'Mendoza', 'carlos@email.com',
 '$2y$10$TKh8H1.PfuA2Pi/1Orf4bueXBg/6klhMv1a2SVWfIJ8yBJqGEhFRu', 'usuario');

-- Membresías
INSERT INTO membresias (nombre, precio, duracion_dias, descripcion) VALUES
('Mensual Básica',  25.00,  30,  'Acceso área de pesas,acceso caminadoras,acceso a los baños,acceso a otras sucursales'),
('Trimestral',      65.00,  90,  'Acceso área de pesas,acceso caminadoras,acceso a los baños,acceso a otras sucursales'),
('Semestral',      120.00, 180,  'Acceso área de pesas,acceso caminadoras,acceso a los baños,acceso a otras sucursales'),
('Anual',          220.00, 365,  'Acceso área de pesas,acceso caminadoras,acceso a los baños,acceso a otras sucursales');

-- Rutinas con ejercicios
INSERT INTO rutinas (grupo_muscular, nombre, descripcion, ejercicios) VALUES
('Pecho - Push', 'Rutina de Pecho', 'Rutina para desarrollar el músculo pectoral mayor y menor.',
 '["Press de banca plano 4x10","Press de banca inclinado 3x12","Aperturas con mancuernas 3x15","Fondos en paralelas 3x12","Crossover en polea 3x15","Pullover con mancuerna 3x12"]'),

('Espalda - Pull', 'Rutina de Espalda', 'Rutina para fortalecer dorsal ancho, romboides y trapecio.',
 '["Jalón al pecho 4x12","Remo con barra 4x10","Remo con mancuerna 3x12","Pull-ups 3xMax","Remo en máquina 3x12","Encogimientos de hombros 3x15"]'),

('Hombro', 'Rutina de Hombros', 'Rutina para deltoides anterior, medio y posterior.',
 '["Press militar con barra 4x10","Elevaciones laterales 4x15","Elevaciones frontales 3x12","Pájaro con mancuernas 3x15","Press Arnold 3x12","Face pull en polea 3x15"]'),

('Bicep', 'Rutina de Bíceps', 'Rutina para desarrollar el bíceps braquial y braquiorradial.',
 '["Curl con barra 4x12","Curl alterno con mancuerna 3x12","Curl martillo 3x12","Curl concentrado 3x12","Curl en polea baja 3x15","Curl 21s con barra 3x1"]'),

('Tricep', 'Rutina de Tríceps', 'Rutina para fortalecer las tres cabezas del tríceps.',
 '["Press francés 4x12","Extensión en polea alta 4x15","Fondos en banco 3x15","Press cerrado 3x10","Patada de tríceps 3x15","Extensión sobre cabeza 3x12"]'),

('Abdomen', 'Rutina de Abdomen', 'Rutina para fortalecer el core y definir el abdomen.',
 '["Crunches 4x20","Plancha 4x45seg","Elevación de piernas 3x15","Rueda abdominal 3x12","Oblicuos con mancuerna 3x20","Bicicleta abdominal 3x20"]');
