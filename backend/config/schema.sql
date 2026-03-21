-- VIZENGO - Sistema de Gestión de Pedidos
-- Script de creación de base de datos MySQL
-- Para instalar: Ejecutar este script en phpMyAdmin o desde la línea de comandos

CREATE DATABASE IF NOT EXISTS vizengo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vizengo_db;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('vendedor', 'disenador', 'administrador') NOT NULL,
    email VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_sesion TIMESTAMP NULL,
    INDEX idx_usuario (nombre_usuario),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de pedidos (contratos)
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_pedido VARCHAR(20) UNIQUE NOT NULL,
    cliente_nombre VARCHAR(100) NOT NULL,
    cliente_contacto VARCHAR(100),
    cliente_telefono VARCHAR(20),
    cliente_email VARCHAR(100),
    tipo_contrato VARCHAR(50) NOT NULL,
    lugar_entrega VARCHAR(100),
    direccion_envio VARCHAR(255),
    vendedor_nombre VARCHAR(100),
    -- Campos de Proforma del Pedido
    camiseta_tipo VARCHAR(100),
    camiseta_tela VARCHAR(100),
    camiseta_talla_principal VARCHAR(20),
    short_tipo VARCHAR(100),
    short_tela VARCHAR(100),
    short_talla VARCHAR(20),
    medias_tipo VARCHAR(100),
    medias_detalles VARCHAR(255),
    banderolas_merch_articulo VARCHAR(100),
    banderolas_merch_regalo BOOLEAN DEFAULT FALSE,
    banderolas_merch_especificaciones TEXT,
    -- Observaciones
    observacion_general TEXT,
    observaciones_diseno TEXT,
    -- Hora de entrega
    hora_entrega TIME,
    fecha_entrega DATE,
    -- Totales
    tipo_prenda VARCHAR(50),
    cantidad_total INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    anticipo DECIMAL(10,2) DEFAULT 0,
    saldo_pendiente DECIMAL(10,2) DEFAULT 0,
    estado ENUM('contrato', 'integrantes', 'diseno', 'planchado', 'costura', 'entrega', 'completado') DEFAULT 'contrato',
    vendedor_id INT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_codigo (codigo_pedido),
    INDEX idx_estado (estado),
    INDEX idx_cliente (cliente_nombre),
    INDEX idx_vendedor (vendedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de integrantes del pedido
CREATE TABLE IF NOT EXISTS integrantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    talla ENUM('XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL') NOT NULL,
    tipo_cuello VARCHAR(50) DEFAULT 'Cuello Redondo',
    numero_camisa INT,
    imagen_ruta VARCHAR(255),
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    INDEX idx_pedido (pedido_id),
    INDEX idx_talla (talla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de diseños
CREATE TABLE IF NOT EXISTS disenos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    disenador_id INT,
    archivo_ruta VARCHAR(255) NOT NULL,
    archivo_nombre VARCHAR(255),
    archivo_tipo VARCHAR(50),
    archivo_tamaño INT,
    observaciones TEXT,
    aprobado BOOLEAN DEFAULT FALSE,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (disenador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_pedido (pedido_id),
    INDEX idx_disenador (disenador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de planchado
CREATE TABLE IF NOT EXISTS planchados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    disenador_id INT,
    tipo_planchado VARCHAR(50),
    temperatura VARCHAR(20),
    tiempo_presion VARCHAR(20),
    observaciones TEXT,
    completado BOOLEAN DEFAULT FALSE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_completado TIMESTAMP NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (disenador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de costura
CREATE TABLE IF NOT EXISTS costuras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    disenador_id INT,
    tipo_costura VARCHAR(50),
    hilo_color VARCHAR(30),
    maquina_usada VARCHAR(50),
    observaciones TEXT,
    completado BOOLEAN DEFAULT FALSE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_completado TIMESTAMP NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (disenador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de entregas
CREATE TABLE IF NOT EXISTS entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    vendedor_id INT,
    fecha_entrega_programada DATE,
    fecha_entrega_real DATE,
    recibido_por VARCHAR(100),
    observaciones TEXT,
    entregado BOOLEAN DEFAULT FALSE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de seguimiento (historial de cambios de estado)
CREATE TABLE IF NOT EXISTS seguimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50) NOT NULL,
    usuario_id INT,
    observaciones TEXT,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_pedido (pedido_id),
    INDEX idx_fecha (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios por defecto (passwords hasheados con password_hash())
-- Contraseña por defecto para todos: 123 (excepto admin que es 'admin')
INSERT INTO usuarios (nombre_usuario, nombre_completo, password_hash, rol, email) VALUES
('luis', 'Luis Vendedor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor', 'luis@vizengo.com'),
('karina', 'Karina Vendedora', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor', 'karina@vizengo.com'),
('carolina', 'Carolina Diseñadora', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'disenador', 'carolina@vizengo.com'),
('erick', 'Erick Diseñador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'disenador', 'erick@vizengo.com'),
('admin', 'Administrador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 'admin@vizengo.com');

-- Insertar datos de ejemplo para pedidos
INSERT INTO pedidos (codigo_pedido, cliente_nombre, cliente_contacto, cliente_telefono, tipo_prenda, cantidad_total, precio_unitario, anticipo, saldo_pendiente, fecha_entrega, observaciones, estado, vendedor_id) VALUES
('PED-001', 'LUISA MARMOLEJO', 'Luisa Marmolejo', '0991234567', 'Camiseta', 30, 15.00, 150.00, 300.00, '2025-02-15', 'Equipo de fútbol juvenil', 'integrantes', 1),
('PED-002', 'CARLOS MENDEZ', 'Carlos Mendez', '0992345678', 'Camiseta', 25, 15.00, 187.50, 187.50, '2025-02-20', 'Equipo de baloncesto', 'contrato', 1),
('PED-003', 'ALBERTO YAPIAS', 'Alberto Yapias', '0993456789', 'Conjunto', 12, 25.00, 150.00, 150.00, '2025-02-18', 'Uniformes ejecutivos', 'diseno', 2),
('PED-004', 'TERESA SALAS', 'Teresa Salas', '0994567890', 'Conjunto', 12, 25.00, 150.00, 150.00, '2025-02-22', 'Equipo de voleibol', 'planchado', 1),
('PED-005', 'MARCO GARCIA', 'Marco Garcia', '0995678901', 'Camiseta', 20, 15.00, 150.00, 150.00, '2025-02-25', 'Maratón empresarial', 'costura', 2),
('PED-006', 'PATRICIA RAMOS', 'Patricia Ramos', '0996789012', 'Short', 15, 12.00, 90.00, 90.00, '2025-02-28', 'Gimnasio local', 'entrega', 1);

-- Insertar integrantes de ejemplo
INSERT INTO integrantes (pedido_id, nombre_completo, talla, tipo_cuello, numero_camisa, observaciones) VALUES
(1, 'Juan Pérez', 'M', 'Cuello Redondo', 10, ''),
(1, 'María López', 'S', 'Cuello V', 8, ''),
(1, 'Pedro Sánchez', 'L', 'Cuello Redondo', 5, ''),
(3, 'Ana Torres', 'M', 'Cuello Redondo', 7, ''),
(3, 'Luis Díaz', 'L', 'Cuello V', 9, '');

-- Insertar diseño de ejemplo
INSERT INTO disenos (pedido_id, disenador_id, archivo_ruta, archivo_nombre, archivo_tipo, observaciones) VALUES
(3, 3, 'assets/uploads/disenos/diseno_ped003.png', 'diseno_ped003.png', 'image/png', 'Diseño aprobado por el cliente');

-- Insertar planchado de ejemplo
INSERT INTO planchados (pedido_id, disenador_id, tipo_planchado, temperatura, tiempo_presion, completado) VALUES
(4, 3, 'Sublimación', '200°C', '60 segundos', TRUE);

-- Insertar costura de ejemplo
INSERT INTO costuras (pedido_id, disenador_id, tipo_costura, hilo_color, maquina_usada, completado) VALUES
(5, 4, 'Recta doble', 'Blanco', 'Juki DDL-8700', TRUE);

-- Insertar entrega de ejemplo
INSERT INTO entregas (pedido_id, vendedor_id, fecha_entrega_programada, recibido_por, entregado) VALUES
(6, 1, '2025-02-28', 'Patricia Ramos', FALSE);
