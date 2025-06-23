Drop TABLE IF EXISTS llx_jornadas;

CREATE TABLE IF NOT EXISTS llx_jornadas (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    estado ENUM('activa', 'parada', 'finalizada') DEFAULT 'activa',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME DEFAULT NULL,
    tiempo_descanso INT DEFAULT 0 COMMENT 'Tiempo de descanso en segundos',
    tiempo_trabajo INT DEFAULT 0 COMMENT 'Tiempo efectivo de trabajo en segundos',
    comentarios TEXT DEFAULT NULL,
    INDEX (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
