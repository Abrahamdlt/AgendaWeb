-- =========================================
-- AgendaWeb · Instalación de la base de datos
-- =========================================
-- Crea las tablas "categoria", "usuarios" y "eventos" con la estructura final (incluye la
-- Práctica 8: cada evento tiene dueño). Después, crea tu cuenta en registro.php.
-- Compatible con MySQL 8 y MariaDB 10.11 (la que usa DOM Cloud).
--
-- Se puede ejecutar más de una vez: no borra tablas ni datos existentes
-- (CREATE TABLE IF NOT EXISTS + INSERT IGNORE).
--
-- Cómo se ejecuta:  mysql -u USUARIO -p NOMBRE_DE_LA_BASE < bd/instalar_agenda.sql
--
-- (bd/migracion_p8.sql muestra cómo se agregaron los usuarios a una base que ya existía.)

CREATE TABLE IF NOT EXISTS categoria (
  id     INT NOT NULL AUTO_INCREMENT,
  clave  VARCHAR(20) NOT NULL COMMENT 'Valor que envía el formulario',
  nombre VARCHAR(50) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categoria_clave (clave),
  UNIQUE KEY uq_categoria_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos (
  id             INT NOT NULL AUTO_INCREMENT,
  usuario_id     INT NOT NULL,
  tipo           ENUM('evento', 'cita') NOT NULL DEFAULT 'evento',
  titulo         VARCHAR(120) NOT NULL,
  fecha          DATE NOT NULL,
  hora_inicio    TIME NULL,
  hora_fin       TIME NULL,
  todo_el_dia    BOOLEAN NOT NULL DEFAULT FALSE,
  lugar          VARCHAR(120) NULL,
  persona        VARCHAR(80) NULL,
  categoria_id   INT NOT NULL,
  recordatorio   SMALLINT UNSIGNED NOT NULL DEFAULT 15
                 COMMENT 'Minutos antes del inicio: 0 (sin recordatorio), 15, 60 o 1440',
  prioridad      ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media',
  notas          TEXT NULL,
  creado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_eventos_fecha (fecha),
  CONSTRAINT fk_eventos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_eventos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categoria (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_eventos_titulo CHECK (CHAR_LENGTH(TRIM(titulo)) > 0),
  CONSTRAINT chk_eventos_recordatorio CHECK (recordatorio IN (0, 15, 60, 1440)),
  CONSTRAINT chk_eventos_hora_fin CHECK (hora_fin IS NULL OR hora_fin > hora_inicio),
  -- Todo el día = sin horas; si no es todo el día, la hora de inicio es obligatoria
  CONSTRAINT chk_eventos_todo_el_dia CHECK (
    (todo_el_dia = TRUE  AND hora_inicio IS NULL AND hora_fin IS NULL) OR
    (todo_el_dia = FALSE AND hora_inicio IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Datos iniciales ----------
INSERT IGNORE INTO categoria (id, clave, nombre) VALUES
  (1, 'escuela',  'Escolar'),
  (2, 'personal', 'Personal'),
  (3, 'trabajo',  'Trabajo'),
  (4, 'salud',    'Salud');
