-- =========================================
-- AgendaWeb · ¿Olvidaste tu contraseña?
-- =========================================
-- Mismo método que la verificación del correo, con su propio código y un vencimiento más corto:
--
--   reset_hash     huella (SHA-256) del código del enlace para elegir contraseña nueva
--   reset_enviado  cuándo se envió: vence en 1 hora y no se reenvía antes de 1 minuto
--
-- Cómo se ejecuta:  mysql -u root -p agenda < bd/migracion_restablecer.sql

USE agenda;

ALTER TABLE usuarios
  ADD COLUMN reset_hash    CHAR(64) NULL AFTER token_enviado,
  ADD COLUMN reset_enviado DATETIME NULL AFTER reset_hash,
  ADD UNIQUE KEY uq_usuarios_reset (reset_hash);

DESCRIBE usuarios;
