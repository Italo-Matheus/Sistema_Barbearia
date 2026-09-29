<?php

class AgendamentoServicoDAO
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarPorAgendamento(int $agendamentoId): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT ags.id, ags.agendamento_id, ags.servico_id, ags.valor, s.nome, s.descricao, s.duracao
             FROM agendamento_servicos ags
             JOIN servicos s ON s.id = ags.servico_id
             WHERE ags.agendamento_id = ? ORDER BY s.id'
        );
        $consulta->execute([$agendamentoId]);

        return $consulta->fetchAll();
    }

    public function inserirLote(int $agendamentoId, array $servicos): void
    {
        $insercao = $this->pdo->prepare(
            'INSERT INTO agendamento_servicos (agendamento_id, servico_id, valor) VALUES (?, ?, ?)'
        );

        foreach ($servicos as $servico) {
            $insercao->execute([
                $agendamentoId,
                (int) $servico['id'],
                number_format((float) $servico['preco'], 2, '.', ''),
            ]);
        }
    }
}