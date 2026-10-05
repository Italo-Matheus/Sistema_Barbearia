USE barbearia;

ALTER TABLE usuarios
  MODIFY tipo ENUM('cliente','barbeiro','admin') NOT NULL DEFAULT 'cliente';

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER tipo;