<?php

class AgendamentoDAO
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function contarTodos(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM agendamentos')->fetchColumn();
    }

    public function listarRecentesAdmin(int $limite = 10): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT a.id, a.data, a.horario, a.status, a.valor_total,
                    c.nome AS cliente_nome, b.nome AS barbeiro_nome,
                    GROUP_CONCAT(s.nome ORDER BY s.id SEPARATOR ', ') AS servicos
             FROM agendamentos a
             JOIN usuarios c ON c.id = a.cliente_id
             JOIN usuarios b ON b.id = a.barbeiro_id
             LEFT JOIN agendamento_servicos ags ON ags.agendamento_id = a.id
             LEFT JOIN servicos s ON s.id = ags.servico_id
             GROUP BY a.id, a.data, a.horario, a.status, a.valor_total, c.nome, b.nome
             ORDER BY a.data DESC, a.horario DESC
             LIMIT :limite"
        );
        $consulta->bindValue(':limite', max(1, $limite), PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function listarHorariosOcupados(int $barbeiroId, string $data): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT horario FROM agendamentos
             WHERE barbeiro_id = ? AND data = ? AND status <> 'cancelado'"
        );
        $consulta->execute([$barbeiroId, $data]);

        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarProximosPorCliente(int $clienteId, string $hoje, string $agora): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT a.id, a.data, a.horario, a.observacao, a.valor_total,
                    b.nome AS barbeiro_nome,
                    GROUP_CONCAT(s.nome ORDER BY s.id SEPARATOR ', ') AS servicos
             FROM agendamentos a
             JOIN usuarios b ON b.id = a.barbeiro_id
             JOIN agendamento_servicos ags ON ags.agendamento_id = a.id
             JOIN servicos s ON s.id = ags.servico_id
             WHERE a.cliente_id = :cliente
               AND a.status = 'agendado'
               AND (a.data > :hoje OR (a.data = :hoje_igual AND a.horario >= :agora))
             GROUP BY a.id, a.data, a.horario, a.observacao, a.valor_total, b.nome
             ORDER BY a.data, a.horario"
        );
        $consulta->execute([
            'cliente' => $clienteId,
            'hoje' => $hoje,
            'hoje_igual' => $hoje,
            'agora' => $agora,
        ]);

        return $consulta->fetchAll();
    }

    public function listarProximosPorBarbeiro(int $barbeiroId, string $hoje, string $agora): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT a.id, a.data, a.horario, a.observacao, a.valor_total,
                    c.nome AS cliente_nome,
                    GROUP_CONCAT(s.nome ORDER BY s.id SEPARATOR ', ') AS servicos
             FROM agendamentos a
             JOIN usuarios c ON c.id = a.cliente_id
             JOIN agendamento_servicos ags ON ags.agendamento_id = a.id
             JOIN servicos s ON s.id = ags.servico_id
             WHERE a.barbeiro_id = :barbeiro
               AND a.status = 'agendado'
               AND (a.data > :hoje OR (a.data = :hoje_igual AND a.horario >= :agora))
             GROUP BY a.id, a.data, a.horario, a.observacao, a.valor_total, c.nome
             ORDER BY a.data, a.horario"
        );
        $consulta->execute([
            'barbeiro' => $barbeiroId,
            'hoje' => $hoje,
            'hoje_igual' => $hoje,
            'agora' => $agora,
        ]);

        return $consulta->fetchAll();
    }

    public function criar(
        int $clienteId,
        int $barbeiroId,
        string $data,
        string $horario,
        ?string $observacao,
        array $servicoIds
    ): int|false {
        $servicoIds = array_values(array_unique(array_filter(
            array_map('intval', $servicoIds),
            static fn(int $id): bool => $id > 0
        )));
        if (!$servicoIds) {
            throw new InvalidArgumentException('Informe ao menos um servico para o agendamento.');
        }

        $this->pdo->beginTransaction();
        try {
            $trava = $this->pdo->prepare(
                "SELECT id FROM usuarios WHERE id = ? AND tipo = 'barbeiro' AND ativo = 1 FOR UPDATE"
            );
            $trava->execute([$barbeiroId]);
            if ($trava->fetchColumn() === false) {
                throw new InvalidArgumentException('O barbeiro informado nao existe.');
            }

            $conflito = $this->pdo->prepare(
                "SELECT COUNT(*) FROM agendamentos
                 WHERE barbeiro_id = ? AND data = ? AND horario = ? AND status <> 'cancelado'"
            );
            $conflito->execute([$barbeiroId, $data, $horario]);
            if ((int) $conflito->fetchColumn() > 0) {
                $this->pdo->rollBack();
                return false;
            }

            $servicos = (new ServicoDAO($this->pdo))->buscarAtivosPorIds($servicoIds);
            if (count($servicos) !== count($servicoIds)) {
                throw new InvalidArgumentException('Um ou mais servicos nao estao disponiveis.');
            }

            $totalCentavos = 0;
            foreach ($servicos as $servico) {
                $totalCentavos += (int) round((float) $servico['preco'] * 100);
            }

            $insercao = $this->pdo->prepare(
                'INSERT INTO agendamentos (cliente_id, barbeiro_id, data, horario, observacao, valor_total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insercao->execute([
                $clienteId,
                $barbeiroId,
                $data,
                $horario,
                $observacao !== '' ? $observacao : null,
                number_format($totalCentavos / 100, 2, '.', ''),
            ]);
            $agendamentoId = (int) $this->pdo->lastInsertId();

            (new AgendamentoServicoDAO($this->pdo))->inserirLote($agendamentoId, $servicos);
            $this->pdo->commit();

            return $agendamentoId;
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $erro;
        }
    }

    public function atualizarStatus(int $id, string $status): bool
    {
        $statusPermitidos = ['agendado', 'concluido', 'cancelado'];
        if (!in_array($status, $statusPermitidos, true)) {
            throw new InvalidArgumentException('Status de agendamento invalido.');
        }

        $atualizacao = $this->pdo->prepare('UPDATE agendamentos SET status = ? WHERE id = ?');

        return $atualizacao->execute([$status, $id]);
    }
}