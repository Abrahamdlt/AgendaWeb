-- =========================================
-- AgendaWeb · Práctica 8 · Usuarios y eventos con dueño
-- =========================================
-- Se ejecuta en DOS partes. Entre una y otra te registras en registro.php
-- para conocer tu id.

USE agenda;

-- ---------- PARTE 1 · Respaldo y tabla de usuarios ----------
CREATE TABLE eventos_respaldo_p8 AS SELECT * FROM eventos;

CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,   -- UNIQUE: no puede haber dos cuentas con el mismo correo
  clave_hash VARCHAR(255) NOT NULL,          -- 255: los hashes son largos y pueden crecer en versiones futuras de PHP
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- (Ahora regístrate en registro.php y busca tu id con:  SELECT id, nombre, correo, clave_hash FROM usuarios;)

-- ---------- PARTE 2 · Cada evento con dueño (igual que en P5) ----------
-- Columna nueva → llenarla → volverla obligatoria con llave foránea. Cambia 1 por TU id.
ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id;

UPDATE eventos SET usuario_id = 1 WHERE id > 0;   -- tus eventos de antes ahora son tuyos

ALTER TABLE eventos
  MODIFY usuario_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_usuario
      FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
      ON DELETE CASCADE;   -- si se borra un usuario, sus eventos se van con él (en categoría es RESTRICT)

DESCRIBE eventos;
