<?php

class ChatDAO
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function inserirMensagem(int $remetenteId, int $destinatarioId, string $mensagem): array
    {
        $insercao = $this->pdo->prepare(
            'INSERT INTO mensagens (remetente_id, destinatario_id, mensagem) VALUES (?, ?, ?)'
        );
        $insercao->execute([$remetenteId, $destinatarioId, $mensagem]);

        $consulta = $this->pdo->prepare(
            'SELECT m.id, m.remetente_id, m.destinatario_id, m.mensagem, m.criado_em, m.visualizada,
                    u.nome AS remetente_nome
             FROM mensagens m
             JOIN usuarios u ON u.id = m.remetente_id
             WHERE m.id = ? LIMIT 1'
        );
        $consulta->execute([(int) $this->pdo->lastInsertId()]);

        return $consulta->fetch() ?: throw new RuntimeException('A mensagem não foi encontrada após a gravação.');
    }

    public function recuperarHistorico(int $usuarioA, int $usuarioB): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT m.id, m.remetente_id, m.destinatario_id, m.mensagem, m.criado_em, m.visualizada,
                    u.nome AS remetente_nome
             FROM mensagens m
             JOIN usuarios u ON u.id = m.remetente_id
             WHERE ((m.remetente_id = :remetente_a AND m.destinatario_id = :destinatario_a)
                 OR (m.remetente_id = :remetente_b AND m.destinatario_id = :destinatario_b))
             ORDER BY m.criado_em ASC, m.id ASC'
        );
        $consulta->execute([
            'remetente_a' => $usuarioA,
            'destinatario_a' => $usuarioB,
            'remetente_b' => $usuarioB,
            'destinatario_b' => $usuarioA,
        ]);

        return $consulta->fetchAll();
    }

    public function marcarComoVisualizadas(int $destinatarioId, int $remetenteId): void
    {
        $atualizacao = $this->pdo->prepare(
            'UPDATE mensagens
             SET visualizada = 1
             WHERE remetente_id = ? AND destinatario_id = ? AND visualizada = 0'
        );
        $atualizacao->execute([$remetenteId, $destinatarioId]);
    }

    public function podeConversar(int $usuarioId, int $contatoId): bool
    {
        if ($usuarioId < 1 || $contatoId < 1 || $usuarioId === $contatoId) {
            return false;
        }

        $consulta = $this->pdo->prepare(
            'SELECT id, tipo, ativo FROM usuarios WHERE id IN (?, ?)'
        );
        $consulta->execute([$usuarioId, $contatoId]);
        $usuarios = [];
        foreach ($consulta->fetchAll() as $usuario) {
            $usuarios[(int) $usuario['id']] = $usuario;
        }
        if (count($usuarios) !== 2 || !(bool) $usuarios[$usuarioId]['ativo'] || !(bool) $usuarios[$contatoId]['ativo']) {
            return false;
        }

        $tipoA = $usuarios[$usuarioId]['tipo'];
        $tipoB = $usuarios[$contatoId]['tipo'];
        if ($tipoA === 'admin' || $tipoB === 'admin') {
            return true;
        }
        if (!in_array($tipoA, ['cliente', 'barbeiro'], true) || !in_array($tipoB, ['cliente', 'barbeiro'], true) || $tipoA === $tipoB) {
            return false;
        }

        return true;
    }

    public function listarContatos(int $usuarioId): array
    {
        $consultaTipo = $this->pdo->prepare('SELECT tipo FROM usuarios WHERE id = ? AND ativo = 1 LIMIT 1');
        $consultaTipo->execute([$usuarioId]);
        $tipo = $consultaTipo->fetchColumn();
        if (!is_string($tipo)) {
            return [];
        }

        if ($tipo === 'admin') {
            $filtroTipo = '1 = 1';
        } elseif ($tipo === 'cliente') {
            $filtroTipo = "u.tipo IN ('admin', 'barbeiro')";
        } elseif ($tipo === 'barbeiro') {
            $filtroTipo = "u.tipo IN ('admin', 'cliente')";
        } else {
            return [];
        }

        $consulta = $this->pdo->prepare(
            "SELECT u.id, u.nome, u.tipo,
                    (SELECT MAX(m.id)
                     FROM mensagens m
                     WHERE (m.remetente_id = ? AND m.destinatario_id = u.id)
                        OR (m.remetente_id = u.id AND m.destinatario_id = ?)
                    ) AS ultima_mensagem_id
             FROM usuarios u
             WHERE $filtroTipo AND u.ativo = 1 AND u.id <> ?
             ORDER BY (ultima_mensagem_id IS NULL), ultima_mensagem_id DESC, u.tipo, u.nome"
        );
        $consulta->execute([$usuarioId, $usuarioId, $usuarioId]);

        return $consulta->fetchAll();
    }
}
