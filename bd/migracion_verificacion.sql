-- =========================================
-- AgendaWeb · Verificación del correo (extra de la P8)
-- =========================================
-- Una cuenta nueva no puede iniciar sesión hasta que abre el enlace que le llega por correo.
--
--   verificado     FALSE al crear la cuenta; TRUE cuando abre el enlace
--   token_hash     huella (SHA-256) del código secreto del enlace; el código real solo viaja en el correo
--   token_enviado  cuándo se envió el enlace: vence a las 24 horas y no se reenvía antes de 1 minuto
--
-- Cómo se ejecuta:  mysql -u root -p agenda < bd/migracion_verificacion.sql

USE agenda;

ALTER TABLE usuarios
  ADD COLUMN verificado    BOOLEAN  NOT NULL DEFAULT FALSE AFTER clave_hash,
  ADD COLUMN token_hash    CHAR(64) NULL AFTER verificado,
  ADD COLUMN token_enviado DATETIME NULL AFTER token_hash,
  ADD UNIQUE KEY uq_usuarios_token (token_hash);

-- Las cuentas que ya existían se crearon antes de este cambio: se dan por verificadas
UPDATE usuarios SET verificado = TRUE WHERE id > 0;

DESCRIBE usuarios;
