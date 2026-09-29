-- =========================================
-- AgendaWeb · Actualización de la base "agenda"
-- =========================================
-- Deja las tablas listas para todo lo que envía el formulario "Nuevo registro".
-- Conserva los eventos y las categorías que ya existían.
--
-- Respaldo previo:  bd/respaldo_agenda_antes.sql
-- Cómo se ejecuta:  mysql -u root -p agenda < bd/actualizar_agenda.sql
--
-- Resultado:
--   categoria (id, clave, nombre)
--   eventos   (id, tipo, titulo, fecha, hora_inicio, hora_fin, todo_el_dia, lugar, persona,
--              categoria_id, recordatorio, prioridad, notas, creado_en, actualizado_en)


-- ---------- 1. Categorías ----------
-- "clave" es el valor que envía el formulario (escuela, trabajo, salud, personal).
-- "nombre" es el texto que ya tenías y se conserva tal cual.
ALTER TABLE categoria ADD COLUMN clave VARCHAR(20) NULL AFTER id;

UPDATE categoria SET clave = 'escuela'  WHERE nombre = 'Escolar';
UPDATE categoria SET clave = 'personal' WHERE nombre = 'Personal';
UPDATE categoria SET clave = 'trabajo'  WHERE nombre = 'Trabajo';
INSERT INTO categoria (clave, nombre) VALUES ('salud', 'Salud');

ALTER TABLE categoria
  MODIFY clave VARCHAR(20) NOT NULL,
  ADD UNIQUE KEY uq_categoria_clave (clave),
  ADD UNIQUE KEY uq_categoria_nombre (nombre);


-- ---------- 2. Eventos: columnas ----------
-- Los nombres quedan iguales a los "name" del formulario. Los datos se conservan.
ALTER TABLE eventos
  RENAME COLUMN hora TO hora_inicio,
  RENAME COLUMN descripcion TO notas;

ALTER TABLE eventos
  ADD COLUMN tipo ENUM('evento', 'cita') NOT NULL DEFAULT 'evento' AFTER id,
  ADD COLUMN hora_fin TIME NULL AFTER hora_inicio,
  ADD COLUMN todo_el_dia BOOLEAN NOT NULL DEFAULT FALSE AFTER hora_fin,
  ADD COLUMN lugar VARCHAR(120) NULL AFTER todo_el_dia,
  ADD COLUMN persona VARCHAR(80) NULL AFTER lugar,
  ADD COLUMN categoria_id INT NULL AFTER persona,
  ADD COLUMN recordatorio SMALLINT UNSIGNED NOT NULL DEFAULT 15
      COMMENT 'Minutos antes del inicio: 0 (sin recordatorio), 15, 60 o 1440' AFTER categoria_id,
  ADD COLUMN prioridad ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media' AFTER recordatorio,
  ADD COLUMN creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Las notas van al final, después de los datos cortos
ALTER TABLE eventos MODIFY notas TEXT NULL AFTER prioridad;


-- ---------- 3. Eventos: completar los registros que ya existían ----------
-- No tenían hora, así que se marcan como "todo el día".
UPDATE eventos SET todo_el_dia = TRUE WHERE hora_inicio IS NULL;

-- No tenían categoría. Se asigna según el título:
--   1 Quiz de PHP, 2 Practica de PHP, 3 Examen      -> escuela
--   4 Fiesta, 5 Concierto, 7 Aniversario            -> personal
UPDATE eventos SET categoria_id = (SELECT id FROM categoria WHERE clave = 'escuela')
 WHERE id IN (1, 2, 3);
UPDATE eventos SET categoria_id = (SELECT id FROM categoria WHERE clave = 'personal')
 WHERE categoria_id IS NULL;


-- ---------- 4. Eventos: reglas que la base hace cumplir ----------
ALTER TABLE eventos
  MODIFY fecha DATE NOT NULL,
  MODIFY categoria_id INT NOT NULL,
  ADD INDEX idx_eventos_fecha (fecha),
  ADD CONSTRAINT fk_eventos_categoria
      FOREIGN KEY (categoria_id) REFERENCES categoria (id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT chk_eventos_titulo CHECK (CHAR_LENGTH(TRIM(titulo)) > 0),
  ADD CONSTRAINT chk_eventos_recordatorio CHECK (recordatorio IN (0, 15, 60, 1440)),
  ADD CONSTRAINT chk_eventos_hora_fin CHECK (hora_fin IS NULL OR hora_fin > hora_inicio),
  -- Todo el día = sin horas; si no es todo el día, la hora de inicio es obligatoria
  ADD CONSTRAINT chk_eventos_todo_el_dia CHECK (
      (todo_el_dia = TRUE  AND hora_inicio IS NULL AND hora_fin IS NULL) OR
      (todo_el_dia = FALSE AND hora_inicio IS NOT NULL)
  );
