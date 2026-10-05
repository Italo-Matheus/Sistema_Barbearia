
CREATE DATABASE IF NOT EXISTS barbearia
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE barbearia;
CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome          VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL,
  telefone      VARCHAR(20)  NOT NULL,          
  cpf           CHAR(11)     NULL,              
  senha         VARCHAR(255) NOT NULL,          
  tipo          ENUM('cliente','barbeiro','admin') NOT NULL DEFAULT 'cliente',
  ativo         TINYINT(1)   NOT NULL DEFAULT 1,
  especialidade VARCHAR(100) NULL,
  descricao     VARCHAR(300) NULL,
  criado_em     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  UNIQUE KEY uq_usuarios_cpf (cpf)              
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicos (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome      VARCHAR(80)  NOT NULL,
  descricao VARCHAR(200) NULL,
  preco     DECIMAL(10,2) NOT NULL,
  duracao   SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  ativo     TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS agendamentos (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id  INT UNSIGNED NOT NULL,
  barbeiro_id INT UNSIGNED NOT NULL,
  data        DATE         NOT NULL,
  horario     TIME         NOT NULL,
  observacao  VARCHAR(255) NULL,
  valor_total DECIMAL(10,2) NOT NULL,
  status      ENUM('agendado','concluido','cancelado') NOT NULL DEFAULT 'agendado',
  criado_em   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_agendamentos_barbeiro (barbeiro_id, data, horario),
  KEY idx_agendamentos_cliente (cliente_id, data),
  CONSTRAINT fk_agendamentos_cliente
    FOREIGN KEY (cliente_id)  REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_agendamentos_barbeiro
    FOREIGN KEY (barbeiro_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agendamento_servicos (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  agendamento_id INT UNSIGNED NOT NULL,
  servico_id     INT UNSIGNED NOT NULL,
  valor          DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_agendamento_servico (agendamento_id, servico_id),
  CONSTRAINT fk_ags_agendamento
    FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE CASCADE,
  CONSTRAINT fk_ags_servico
    FOREIGN KEY (servico_id)     REFERENCES servicos (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO servicos (nome, descricao, preco, duracao) VALUES
  ('Corte tradicional', 'Na tesoura ou na máquina, com acabamento na navalha.', 35.00, 40),
  ('Corte + Barba',     'O combo completo: corte e barba no mesmo horário.',    55.00, 70),
  ('Barba',             'Modelagem e acabamento com navalha e toalha quente.',  25.00, 30),
  ('Corte infantil',    'Para os pequenos, com paciência e carinho.',           30.00, 30),
  ('Sobrancelha',       'Alinhamento na navalha, sem exagero.',                 15.00, 15),
  ('Platinado',         'Descoloração e tonalização, cuidando do fio.',         90.00, 90);
