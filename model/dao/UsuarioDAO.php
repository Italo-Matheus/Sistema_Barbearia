<?php
require_once __DIR__ . '/../dto/UsuarioDTO.php';
require_once __DIR__ . '/../dto/AdminUsuarioDTO.php';

class UsuarioDAO
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarTodos(): array
    {
        return $this->pdo->query(
            'SELECT id, nome, email, telefone, cpf, tipo, especialidade, descricao, ativo, criado_em
             FROM usuarios ORDER BY criado_em DESC, id DESC'
        )->fetchAll();
    }

    public function listarGerenciavelPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            "SELECT id, nome, email, telefone, cpf, tipo, especialidade, descricao, ativo
             FROM usuarios WHERE id = ? AND tipo IN ('cliente', 'barbeiro') LIMIT 1"
        );
        $consulta->execute([$id]);
        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function emailUsadoPorOutro(int $id, string $email): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? AND id <> ? LIMIT 1');
        $consulta->execute([$email, $id]);

        return (bool) $consulta->fetchColumn();
    }

    public function cpfUsadoPorOutro(int $id, string $cpf): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE cpf = ? AND id <> ? LIMIT 1');
        $consulta->execute([$cpf, $id]);

        return (bool) $consulta->fetchColumn();
    }

    public function atualizarGerenciavel(AdminUsuarioDTO $usuario): bool
    {
        $atualizacao = $this->pdo->prepare(
            "UPDATE usuarios
             SET nome = ?, email = ?, telefone = ?, cpf = ?, tipo = ?, especialidade = ?, descricao = ?
             WHERE id = ? AND tipo IN ('cliente', 'barbeiro')"
        );

        return $atualizacao->execute([
            $usuario->nome,
            $usuario->email,
            $usuario->telefone,
            $usuario->cpf,
            $usuario->tipo,
            $usuario->especialidade,
            $usuario->descricao,
            $usuario->id,
        ]);
    }

    public function alterarAtivoGerenciavel(int $id, bool $ativo): bool
    {
        $atualizacao = $this->pdo->prepare(
            "UPDATE usuarios SET ativo = ? WHERE id = ? AND tipo IN ('cliente', 'barbeiro')"
        );

        return $atualizacao->execute([$ativo ? 1 : 0, $id]);
    }

    public function alterarTipoGerenciavel(int $id, string $tipo): bool
    {
        if (!in_array($tipo, ['cliente', 'barbeiro'], true)) {
            return false;
        }

        $atualizacao = $this->pdo->prepare(
            "UPDATE usuarios SET tipo = ? WHERE id = ? AND tipo IN ('cliente', 'barbeiro')"
        );

        return $atualizacao->execute([$tipo, $id]);
    }

    public function temAgendamentos(int $id): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT 1 FROM agendamentos WHERE cliente_id = ? OR barbeiro_id = ? LIMIT 1'
        );
        $consulta->execute([$id, $id]);

        return (bool) $consulta->fetchColumn();
    }

    public function excluirGerenciavelSemAgendamentos(int $id): bool
    {
        $exclusao = $this->pdo->prepare(
            "DELETE FROM usuarios
             WHERE id = ? AND tipo IN ('cliente', 'barbeiro')
               AND NOT EXISTS (
                   SELECT 1 FROM agendamentos a
                   WHERE a.cliente_id = usuarios.id OR a.barbeiro_id = usuarios.id
               )"
        );
        $exclusao->execute([$id]);

        return $exclusao->rowCount() === 1;
    }

    public function contarPorTipo(): array
    {
        $contagens = $this->pdo->query(
            "SELECT
                SUM(tipo = 'cliente') AS clientes,
                SUM(tipo = 'barbeiro') AS barbeiros,
                SUM(tipo = 'admin') AS administradores
             FROM usuarios"
        )->fetch();

        return array_map('intval', $contagens ?: ['clientes' => 0, 'barbeiros' => 0, 'administradores' => 0]);
    }

    public function buscarPorEmail(string $email): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nome, email, telefone, cpf, senha, tipo, especialidade, descricao, ativo, criado_em
             FROM usuarios WHERE email = ? LIMIT 1'
        );
        $consulta->execute([$email]);
        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nome, email, telefone, cpf, tipo, especialidade, descricao, ativo, criado_em
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $consulta->execute([$id]);
        $usuario = $consulta->fetch();

        return $usuario ?: null;
    }

    public function buscarHashSenha(int $id): ?string
    {
        $consulta = $this->pdo->prepare('SELECT senha FROM usuarios WHERE id = ? AND ativo = 1 LIMIT 1');
        $consulta->execute([$id]);
        $hash = $consulta->fetchColumn();

        return is_string($hash) ? $hash : null;
    }

    public function atualizarSenha(int $id, string $hash): bool
    {
        $atualizacao = $this->pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ? AND ativo = 1');

        return $atualizacao->execute([$hash, $id]) && $atualizacao->rowCount() === 1;
    }

    public function listarBarbeiros(): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT id, nome, email, telefone, especialidade, descricao
             FROM usuarios WHERE tipo = 'barbeiro' AND ativo = 1 ORDER BY nome"
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