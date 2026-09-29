<?php

class ServicoDAO
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarAtivos(?int $limite = null): array
    {
        $sql = 'SELECT id, nome, descricao, preco, duracao, ativo, criado_em
                FROM servicos WHERE ativo = 1 ORDER BY id';
        if ($limite !== null) {
            $sql .= ' LIMIT ' . max(1, $limite);
        }

        return $this->pdo->query($sql)->fetchAll();
    }

    public function buscarAtivosPorIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));
        if (!$ids) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $consulta = $this->pdo->prepare(
            "SELECT id, nome, descricao, preco, duracao
             FROM servicos WHERE ativo = 1 AND id IN ($marcadores) ORDER BY id"
        );
        $consulta->execute($ids);

        return $consulta->fetchAll();
    }

    public function cadastrar(string $nome, ?string $descricao, float $preco, int $duracao): int
    {
        $insercao = $this->pdo->prepare(
            'INSERT INTO servicos (nome, descricao, preco, duracao) VALUES (?, ?, ?, ?)'
        );
        $insercao->execute([$nome, $descricao, number_format($preco, 2, '.', ''), $duracao]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, string $nome, ?string $descricao, float $preco, int $duracao): bool
    {
        $atualizacao = $this->pdo->prepare(
            'UPDATE servicos SET nome = ?, descricao = ?, preco = ?, duracao = ? WHERE id = ?'
        );

        return $atualizacao->execute([
            $nome,
            $descricao,
            number_format($preco, 2, '.', ''),
            $duracao,
            $id,
        ]);
    }

    public function alterarAtivo(int $id, bool $ativo): bool
    {
        $atualizacao = $this->pdo->prepare('UPDATE servicos SET ativo = ? WHERE id = ?');

        return $atualizacao->execute([$ativo ? 1 : 0, $id]);
    }
}