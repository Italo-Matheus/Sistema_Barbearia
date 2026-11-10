<?php

class HorarioDAO
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarTodos(): array
    {
        return $this->pdo->query(
            'SELECT id, dia_semana, horario
             FROM horarios_atendimento
             ORDER BY dia_semana, horario'
        )->fetchAll();
    }

    public function listarPorDia(int $diaSemana): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT horario FROM horarios_atendimento
             WHERE dia_semana = ? ORDER BY horario'
        );
        $consulta->execute([$diaSemana]);

        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }

    public function existe(int $diaSemana, string $horario): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT 1 FROM horarios_atendimento
             WHERE dia_semana = ? AND horario = ? LIMIT 1'
        );
        $consulta->execute([$diaSemana, $horario]);

        return (bool) $consulta->fetchColumn();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, dia_semana, horario
             FROM horarios_atendimento WHERE id = ? LIMIT 1'
        );
        $consulta->execute([$id]);
        $horario = $consulta->fetch();

        return $horario ?: null;
    }

    public function cadastrar(int $diaSemana, string $horario): bool
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO horarios_atendimento (dia_semana, horario) VALUES (?, ?)'
        );

        return $consulta->execute([$diaSemana, $horario]);
    }

    public function atualizar(int $id, int $diaSemana, string $horario): bool
    {
        $consulta = $this->pdo->prepare(
            'UPDATE horarios_atendimento
             SET dia_semana = ?, horario = ? WHERE id = ?'
        );

        return $consulta->execute([$diaSemana, $horario, $id]);
    }

    public function excluir(int $id): bool
    {
        $consulta = $this->pdo->prepare(
            'DELETE FROM horarios_atendimento WHERE id = ?'
        );

        return $consulta->execute([$id]);
    }
}
