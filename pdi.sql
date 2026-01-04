-- ============================================================
-- BASE DE DATOS COMPLETA PDI - TECNOSUCRE
-- Versión: 2.0 FINAL CORREGIDA
-- Fecha: Enero 2026
-- ============================================================

-- Eliminar BD si existe (CUIDADO: elimina todos los datos)
-- DROP DATABASE IF EXISTS pdi;

-- Crear base de datos con UTF-8
CREATE DATABASE IF NOT EXISTS pdi 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE pdi;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Datos personales
    cedula VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    
    -- Credenciales
    usuario VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    
    -- Rol y estado
    rol ENUM('admin','instalador') NOT NULL DEFAULT 'instalador',
    estado TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    
    -- Auditoría
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    
    -- Índices únicos
    UNIQUE KEY uk_cedula (cedula),
    UNIQUE KEY uk_usuario (usuario),
    
    -- Índices de búsqueda
    INDEX idx_rol (rol),
    INDEX idx_estado (estado),
    INDEX idx_ultimo_acceso (ultimo_acceso)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador inicial
INSERT INTO usuarios (cedula, nombre, apellido, usuario, password, rol, estado) 
VALUES (
    '18424899',
    'Darwin',
    'Amaya',
    'admin',
    '$2y$10$ypvob8TryrmGin0hX4ERP.6wcOhtLc3duBCMLnl1su4rcMDmAxqJ2',
    'admin',
    1
);
-- Contraseña: admin123

-- ============================================================
-- TABLA: clientes
-- ============================================================
DROP TABLE IF EXISTS clientes;

CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Datos básicos (OBLIGATORIOS)
    nombre_apellido VARCHAR(150) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    ubicacion VARCHAR(100) NOT NULL,
    
    -- Información del negocio en formato JSON (OBLIGATORIOS)
    target JSON NOT NULL,
    productos JSON NOT NULL,
    marcas JSON NOT NULL,
    diagnostico JSON NOT NULL,
    forecast_pipeline JSON NOT NULL,
    
    -- Campos opcionales
    marca_otra VARCHAR(100) DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    
    -- Período de visita sugerido (OPCIONAL)
    fecha_visita_desde DATE DEFAULT NULL,
    fecha_visita_hasta DATE DEFAULT NULL,
    
    -- Auditoría
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    
    -- Relación con usuario
    id_usuario INT NOT NULL,
    
    -- Clave foránea
    CONSTRAINT fk_cliente_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    
    -- Índices únicos
    UNIQUE KEY uk_telefono (telefono),
    
    -- Índices de búsqueda
    INDEX idx_nombre (nombre_apellido),
    INDEX idx_ubicacion (ubicacion),
    INDEX idx_fecha_registro (fecha_registro),
    INDEX idx_usuario (id_usuario),
    INDEX idx_telefono (telefono)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: visitas
-- ============================================================
DROP TABLE IF EXISTS visitas;

CREATE TABLE visitas (
    id_visita INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relaciones
    id_cliente INT NOT NULL,
    id_instalador INT NOT NULL,
    
    -- Programación
    fecha_visita DATE NOT NULL,
    hora_visita TIME NOT NULL,
    
    -- Tipo y prioridad
    tipo_visita ENUM(
        'Evaluación',
        'Instalación',
        'Reparación',
        'Mantenimiento',
        'Seguimiento',
        'Ampliación'
    ) NOT NULL,
    
    prioridad ENUM('Normal','Alta','Urgente') DEFAULT 'Normal',
    
    -- Estado
    estado ENUM(
        'Pendiente',
        'Confirmada',
        'En Proceso',
        'Completada',
        'Cancelada',
        'Reprogramada'
    ) DEFAULT 'Pendiente',
    
    -- Información adicional
    notas TEXT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    resultado TEXT DEFAULT NULL,
    
    -- Auditoría
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    
    -- Claves foráneas
    CONSTRAINT fk_visita_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
        
    CONSTRAINT fk_visita_instalador
        FOREIGN KEY (id_instalador)
        REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    
    -- Índices
    INDEX idx_fecha_visita (fecha_visita),
    INDEX idx_estado (estado),
    INDEX idx_instalador (id_instalador),
    INDEX idx_cliente (id_cliente),
    INDEX idx_prioridad (prioridad),
    INDEX idx_fecha_hora (fecha_visita, hora_visita)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: logs_actividad (OPCIONAL - Auditoría)
-- ============================================================
DROP TABLE IF EXISTS logs_actividad;

CREATE TABLE logs_actividad (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Tipo de acción
    accion VARCHAR(50) NOT NULL,
    
    -- Detalles
    detalles TEXT,
    tabla_afectada VARCHAR(50),
    id_registro INT,
    
    -- Usuario que realizó la acción
    id_usuario INT NOT NULL,
    
    -- Fecha
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Datos adicionales (JSON)
    datos_anteriores JSON DEFAULT NULL,
    datos_nuevos JSON DEFAULT NULL,
    
    -- Índices
    INDEX idx_accion (accion),
    INDEX idx_usuario (id_usuario),
    INDEX idx_fecha (fecha_hora),
    INDEX idx_tabla (tabla_afectada, id_registro)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VISTAS ÚTILES PARA REPORTES
-- ============================================================

-- Vista: Clientes con información completa
DROP VIEW IF EXISTS v_clientes_completo;

CREATE VIEW v_clientes_completo AS
SELECT 
    c.*,
    u.nombre AS instalador_nombre,
    u.apellido AS instalador_apellido,
    u.cedula AS instalador_cedula,
    u.usuario AS instalador_usuario,
    DATEDIFF(CURDATE(), c.fecha_registro) AS dias_desde_registro,
    (SELECT COUNT(*) FROM visitas v WHERE v.id_cliente = c.id_cliente) AS total_visitas,
    (SELECT COUNT(*) FROM visitas v WHERE v.id_cliente = c.id_cliente AND v.estado = 'Completada') AS visitas_completadas
FROM clientes c
INNER JOIN usuarios u ON u.id_usuario = c.id_usuario;

-- Vista: Visitas pendientes
DROP VIEW IF EXISTS v_visitas_pendientes;

CREATE VIEW v_visitas_pendientes AS
SELECT 
    v.id_visita,
    v.fecha_visita,
    v.hora_visita,
    v.tipo_visita,
    v.prioridad,
    v.estado,
    c.nombre_apellido AS cliente,
    c.telefono AS cliente_telefono,
    c.ubicacion AS cliente_ubicacion,
    u.nombre AS instalador_nombre,
    u.apellido AS instalador_apellido,
    DATEDIFF(v.fecha_visita, CURDATE()) AS dias_hasta_visita
FROM visitas v
INNER JOIN clientes c ON c.id_cliente = v.id_cliente
INNER JOIN usuarios u ON u.id_usuario = v.id_instalador
WHERE v.estado IN ('Pendiente', 'Confirmada')
ORDER BY v.fecha_visita, v.hora_visita;

-- Vista: Productividad por instalador
DROP VIEW IF EXISTS v_productividad_instaladores;

CREATE VIEW v_productividad_instaladores AS
SELECT 
    u.id_usuario,
    u.nombre,
    u.apellido,
    u.cedula,
    COUNT(DISTINCT c.id_cliente) AS total_clientes,
    COUNT(DISTINCT CASE WHEN DATE(c.fecha_registro) = CURDATE() THEN c.id_cliente END) AS clientes_hoy,
    COUNT(DISTINCT CASE WHEN MONTH(c.fecha_registro) = MONTH(CURDATE()) AND YEAR(c.fecha_registro) = YEAR(CURDATE()) THEN c.id_cliente END) AS clientes_mes,
    COUNT(v.id_visita) AS total_visitas,
    COUNT(CASE WHEN v.estado = 'Completada' THEN 1 END) AS visitas_completadas,
    COUNT(CASE WHEN v.estado IN ('Pendiente','Confirmada') THEN 1 END) AS visitas_pendientes,
    ROUND(
        (COUNT(CASE WHEN v.estado = 'Completada' THEN 1 END) * 100.0) / 
        NULLIF(COUNT(v.id_visita), 0), 
        2
    ) AS tasa_completacion
FROM usuarios u
LEFT JOIN clientes c ON c.id_usuario = u.id_usuario
LEFT JOIN visitas v ON v.id_instalador = u.id_usuario
WHERE u.rol = 'instalador' AND u.estado = 1
GROUP BY u.id_usuario;

-- Vista: Métricas generales del dashboard
DROP VIEW IF EXISTS v_metricas_generales;

CREATE VIEW v_metricas_generales AS
SELECT 
    (SELECT COUNT(*) FROM clientes) AS total_clientes,
    (SELECT COUNT(*) FROM clientes WHERE DATE(fecha_registro) = CURDATE()) AS clientes_hoy,
    (SELECT COUNT(*) FROM clientes WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())) AS clientes_mes,
    (SELECT COUNT(*) FROM visitas WHERE estado IN ('Pendiente','Confirmada')) AS visitas_pendientes,
    (SELECT COUNT(*) FROM visitas WHERE DATE(fecha_visita) = CURDATE()) AS visitas_hoy,
    (SELECT COUNT(*) FROM visitas WHERE estado = 'Completada') AS visitas_completadas,
    (SELECT COUNT(*) FROM usuarios WHERE rol = 'instalador' AND estado = 1) AS instaladores_activos,
    (SELECT COUNT(*) FROM clientes WHERE JSON_CONTAINS(forecast_pipeline, '"Muy Interesado"')) AS clientes_muy_interesados;

-- ============================================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================================

-- Eliminar procedimientos si existen
DROP PROCEDURE IF EXISTS sp_visitas_semana;
DROP PROCEDURE IF EXISTS sp_clientes_por_forecast;

DELIMITER $$

-- Procedimiento: Obtener visitas de la semana actual
CREATE PROCEDURE sp_visitas_semana(IN p_id_instalador INT)
BEGIN
    SELECT 
        v.*,
        c.nombre_apellido AS cliente,
        c.telefono,
        c.ubicacion,
        DAYNAME(v.fecha_visita) AS dia_semana
    FROM visitas v
    INNER JOIN clientes c ON c.id_cliente = v.id_cliente
    WHERE v.id_instalador = p_id_instalador
        AND YEARWEEK(v.fecha_visita, 1) = YEARWEEK(CURDATE(), 1)
        AND v.estado IN ('Pendiente', 'Confirmada', 'En Proceso')
    ORDER BY v.fecha_visita, v.hora_visita;
END$$

-- Procedimiento: Obtener clientes por forecast
CREATE PROCEDURE sp_clientes_por_forecast(IN p_forecast VARCHAR(50))
BEGIN
    SELECT 
        c.*,
        u.nombre AS instalador_nombre,
        u.apellido AS instalador_apellido
    FROM clientes c
    INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
    WHERE JSON_CONTAINS(c.forecast_pipeline, JSON_QUOTE(p_forecast))
    ORDER BY c.fecha_registro DESC;
END$$

DELIMITER ;

-- ============================================================
-- TRIGGERS PARA AUDITORÍA
-- ============================================================

-- Eliminar triggers si existen
DROP TRIGGER IF EXISTS trg_cliente_creado;
DROP TRIGGER IF EXISTS trg_visita_completada;

DELIMITER $$

-- Trigger: Log cuando se crea un cliente
CREATE TRIGGER trg_cliente_creado
AFTER INSERT ON clientes
FOR EACH ROW
BEGIN
    -- Solo si existe la tabla logs_actividad
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'pdi' AND table_name = 'logs_actividad') THEN
        INSERT INTO logs_actividad (accion, detalles, tabla_afectada, id_registro, id_usuario, datos_nuevos)
        VALUES (
            'CLIENTE_CREADO',
            CONCAT('Cliente: ', NEW.nombre_apellido, ' - Tel: ', NEW.telefono),
            'clientes',
            NEW.id_cliente,
            NEW.id_usuario,
            JSON_OBJECT(
                'nombre_apellido', NEW.nombre_apellido,
                'telefono', NEW.telefono,
                'ubicacion', NEW.ubicacion
            )
        );
    END IF;
END$$

-- Trigger: Actualizar forecast cuando se completa visita
CREATE TRIGGER trg_visita_completada
AFTER UPDATE ON visitas
FOR EACH ROW
BEGIN
    -- Si la visita pasó a completada
    IF NEW.estado = 'Completada' AND OLD.estado != 'Completada' THEN
        -- Log en tabla de auditoría (si existe)
        IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'pdi' AND table_name = 'logs_actividad') THEN
            INSERT INTO logs_actividad (accion, detalles, tabla_afectada, id_registro, id_usuario)
            VALUES (
                'VISITA_COMPLETADA',
                CONCAT('Visita ID: ', NEW.id_visita, ' completada'),
                'visitas',
                NEW.id_visita,
                NEW.id_instalador
            );
        END IF;
        
        -- Actualizar forecast del cliente si está en estado inicial
        UPDATE clientes 
        SET forecast_pipeline = JSON_ARRAY('Interesado')
        WHERE id_cliente = NEW.id_cliente
            AND (
                JSON_CONTAINS(forecast_pipeline, '"Curiosidad"')
                OR JSON_CONTAINS(forecast_pipeline, '"Necesidad"')
            );
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================================

-- Índices compuestos para búsquedas comunes
ALTER TABLE clientes ADD INDEX idx_usuario_fecha (id_usuario, fecha_registro);
ALTER TABLE visitas ADD INDEX idx_instalador_fecha (id_instalador, fecha_visita);
ALTER TABLE visitas ADD INDEX idx_estado_fecha (estado, fecha_visita);

-- ============================================================
-- DATOS DE EJEMPLO (OPCIONAL - COMENTADOS)
-- ============================================================

-- Descomenta para insertar datos de prueba

/*
-- Insertar instaladores de ejemplo
INSERT INTO usuarios (cedula, nombre, apellido, usuario, password, rol, estado) VALUES
('12345678', 'José', 'Pérez', 'jperez', '$2y$10$ypvob8TryrmGin0hX4ERP.6wcOhtLc3duBCMLnl1su4rcMDmAxqJ2', 'instalador', 1),
('87654321', 'María', 'González', 'mgonzalez', '$2y$10$ypvob8TryrmGin0hX4ERP.6wcOhtLc3duBCMLnl1su4rcMDmAxqJ2', 'instalador', 1);

-- Insertar clientes de ejemplo
INSERT INTO clientes (nombre_apellido, telefono, ubicacion, target, productos, marcas, diagnostico, forecast_pipeline, id_usuario) VALUES
(
    'Juan Rodríguez',
    '04141234567',
    'Cumaná, Centro',
    '["Residencial"]',
    '["CCTV", "Alarmas"]',
    '["Hikvision"]',
    '["Instalación"]',
    '["Interesado"]',
    2
),
(
    'Comercial La Esquina C.A.',
    '04241234567',
    'Cumaná, Av. Universidad',
    '["Comercial"]',
    '["CCTV", "Control de Acceso"]',
    '["Hikvision", "Huawei"]',
    '["Ampliación"]',
    '["Muy Interesado"]',
    2
);

-- Insertar visitas de ejemplo
INSERT INTO visitas (id_cliente, id_instalador, fecha_visita, hora_visita, tipo_visita, prioridad, estado, notas) VALUES
(1, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'Evaluación', 'Normal', 'Pendiente', 'Primera visita de evaluación'),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '14:00:00', 'Instalación', 'Alta', 'Confirmada', 'Instalación de sistema completo');
*/

-- ============================================================
-- OPTIMIZACIÓN FINAL
-- ============================================================

-- Configurar charset por defecto
ALTER DATABASE pdi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Optimizar tablas
OPTIMIZE TABLE usuarios, clientes, visitas, logs_actividad;

-- Analizar tablas para mejorar rendimiento
ANALYZE TABLE usuarios, clientes, visitas, logs_actividad;

-- Reactivar foreign keys
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================

-- Mostrar resumen
SELECT 'Base de datos PDI creada exitosamente' AS mensaje;
SELECT COUNT(*) AS total_usuarios FROM usuarios;
SELECT COUNT(*) AS total_clientes FROM clientes;
SELECT COUNT(*) AS total_visitas FROM visitas;

-- Mostrar estructura de clientes
SHOW CREATE TABLE clientes;

-- Verificar vistas
SHOW FULL TABLES WHERE table_type = 'VIEW';

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================

-- Para verificar que todo está correcto, ejecuta:
-- SELECT * FROM v_metricas_generales;
-- CALL sp_visitas_semana(1);
-- CALL sp_clientes_por_forecast('Muy Interesado');