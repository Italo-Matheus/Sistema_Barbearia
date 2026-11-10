-- Execute em bancos que já possuem a tabela mensagens criada.
USE barbearia;

ALTER TABLE mensagens
  ADD COLUMN visualizada TINYINT(1) NOT NULL DEFAULT 0 AFTER mensagem;
