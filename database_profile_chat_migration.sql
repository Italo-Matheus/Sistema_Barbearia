-- Migração para bancos já existentes. Em instalações novas, database.sql
-- já contém estas estruturas; não execute este arquivo após criar esse schema.
USE barbearia;

ALTER TABLE usuarios
  ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER descricao;

CREATE TABLE IF NOT EXISTS mensagens (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  remetente_id    INT UNSIGNED NOT NULL,
  destinatario_id INT UNSIGNED NOT NULL,
  mensagem        TEXT NOT NULL,
  criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  visualizada     TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_mensagens_conversa (remetente_id, destinatario_id, id),
  KEY idx_mensagens_destinatario (destinatario_id, remetente_id, id),
  CONSTRAINT fk_mensagens_remetente
    FOREIGN KEY (remetente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_mensagens_destinatario
    FOREIGN KEY (destinatario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
