<?php
require_once __DIR__ . '/../dto/UsuarioDTO.php';

class UsuarioDAO
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nome, email, telefone, cpf, senha, tipo, especialidade, descricao, criado_em
             FROM usuarios WHERE email = ? LIMIT 1'
        );
        $consulta->execute([$email]);
        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nome, email, telefone, cpf, tipo, especialidade, descricao, criado_em
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $consulta->execute([$id]);
        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function listarBarbeiros(): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT id, nome, email, telefone, especialidade, descricao
             FROM usuarios WHERE tipo = 'barbeiro' ORDER BY nome"
        );
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function emailExiste(string $email): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
        $consulta->execute([$email]);

        return (bool) $consulta->fetchColumn();
    }

    public function cpfExiste(string $cpf): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE cpf = ? LIMIT 1');
        $consulta->execute([$cpf]);

        return (bool) $consulta->fetchColumn();
    }

    public function cadastrar(UsuarioDTO $usuario): int
    {
        $insercao = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, telefone, cpf, senha, tipo, especialidade, descricao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insercao->execute([
            $usuario->nome,
            $usuario->email,
            $usuario->telefone,
            $usuario->cpf,
            $usuario->senhaHash,
            $usuario->tipo,
            $usuario->especialidade,
            $usuario->descricao,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(
        int $id,
        string $nome,
        string $email,
        string $telefone,
        ?string $cpf,
        string $tipo,
        ?string $especialidade,
        ?string $descricao
    ): bool {
        $atualizacao = $this->pdo->prepare(
            'UPDATE usuarios
             SET nome = ?, email = ?, telefone = ?, cpf = ?, tipo = ?, especialidade = ?, descricao = ?
             WHERE id = ?'
        );

        return $atualizacao->execute([
            $nome,
            $email,
            $telefone,
            $cpf,
            $tipo,
            $especialidade,
            $descricao,
            $id,
        ]);
    }
}