-- =========================================
-- AgendaWeb · Instalación de la base de datos
-- =========================================
-- Crea las tablas "categoria" y "eventos" con la estructura final y los datos iniciales.
-- Compatible con MySQL 8 y MariaDB 10.11 (la que usa DOM Cloud).
--
-- Se puede ejecutar más de una vez: no borra tablas ni datos existentes
-- (CREATE TABLE IF NOT EXISTS + INSERT IGNORE).
--
-- Cómo se ejecuta:  mysql -u USUARIO -p NOMBRE_DE_LA_BASE < bd/instalar_agenda.sql
--
-- (bd/respaldo_agenda_antes.sql + bd/actualizar_agenda.sql documentan cómo se llegó
--  a esta estructura desde la base original; para instalar solo hace falta este archivo.)

CREATE TABLE IF NOT EXISTS categoria (
  id     INT NOT NULL AUTO_INCREMENT,
  clave  VARCHAR(20) NOT NULL COMMENT 'Valor que envía el formulario',
  nombre VARCHAR(50) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categoria_clave (clave),
  UNIQUE KEY uq_categoria_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos (
  id             INT NOT NULL AUTO_INCREMENT,
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

INSERT IGNORE INTO eventos (id, tipo, titulo, fecha, todo_el_dia, categoria_id, recordatorio, prioridad, notas) VALUES
  (1, 'evento', 'Quiz de PHP',     '2026-09-26', TRUE, 1, 15, 'media', 'cuestionario de la U2'),
  (2, 'evento', 'Practica de PHP', '2026-09-28', TRUE, 1, 15, 'media', 'Practica de PHP'),
  (3, 'evento', 'Examen',          '2026-10-11', TRUE, 1, 15, 'media', 'Examen de programacion'),
  (4, 'evento', 'Fiesta',          '2026-12-02', TRUE, 2, 15, 'media', 'Cumpleanios'),
  (5, 'evento', 'Concierto',       '2027-01-15', TRUE, 2, 15, 'media', 'Concierto de Travis Scott'),
  (7, 'evento', 'Aniversario',     '2027-07-23', TRUE, 2, 15, 'media', 'Aniversario');
